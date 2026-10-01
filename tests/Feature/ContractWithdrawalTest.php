<?php

namespace Tests\Feature;

use App\Mail\ContractWithdrawalAdminMail;
use App\Mail\ContractWithdrawalReceiptMail;
use App\Models\ContractWithdrawal;
use App\Services\ContractWithdrawalSettingsService;
use App\Http\Middleware\Authenticate;
use App\Http\Middleware\RedirectCustomer;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContractWithdrawalTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $config = $app['config'];
            $config->set('app.providers', array_map(function ($provider) {
                return $provider === \App\Providers\AppServiceProvider::class
                    ? \Tests\Support\WithdrawalTestServiceProvider::class : $provider;
            }, $config->get('app.providers')));
            $config->set('database.default', 'sqlite');
            $config->set('database.connections', ['sqlite' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true,
            ]]);
            $config->set('cache.default', 'array');
            $config->set('session.driver', 'array');
            $config->set('mail.default', 'array');
            $config->set('app.url', 'http://pharmad.test');
            $config->set('app.key', 'base64:'.base64_encode(str_repeat('x', 32)));
        });
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function refreshTestDatabase()
    {
        foreach ([
            '2014_10_12_000000_create_users_table.php',
            '2020_01_02_223800_create_bouncer_tables.php',
            '2020_11_07_114044_create_orders_table.php',
            '2021_06_11_211244_create_settings_table.php',
            '2026_09_16_080000_create_contract_withdrawals_table.php',
            '2026_09_29_080000_extend_contract_withdrawals_table.php',
        ] as $migration) {
            $this->artisan('migrate', ['--path' => 'database/migrations/'.$migration])->assertExitCode(0);
        }
        $this->app[Kernel::class]->setArtisan(null);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->refreshTestDatabase();

        config([
            'cache.default' => 'array',
            'services.recaptcha.sitekey' => '',
            'services.recaptcha.secret' => '',
        ]);

        $this->withoutMiddleware(ThrottleRequests::class);
    }

    public function testPublicFormContainsWithdrawalActionAndLegalInformation(): void
    {
        $response = $this->get(route('contract-withdrawal.create'));

        $response->assertOk()
            ->assertSee('Obrazac za jednostrani raskid ugovora')
            ->assertSee('Pregledaj podatke')
            ->assertSee('14 dana')
            ->assertSee('bez navođenja razloga')
            ->assertSee('Izravne troškove povrata proizvoda snosite sami.')
            ->assertSee('Zagrebačka 99')
            ->assertSee('Potvrda sa sadržajem');
    }

    public function testWithdrawalIsReviewedStoredAndEmailedToBothParties(): void
    {
        Mail::fake();

        app(ContractWithdrawalSettingsService::class)->save([
            'admin_email' => 'raskidi@example.test',
            'return_address' => 'PharmAD test, Povratna 1, Zagreb',
            'return_cost_policy' => 'consumer',
            'instructions' => 'U paket stavite referencu.',
        ]);

        $review = $this->post(route('contract-withdrawal.review'), $this->validPayload());

        $review->assertOk()
            ->assertSee('Pregledajte izjavu o raskidu')
            ->assertSee('Potvrditi raskid ugovora')
            ->assertSee('Ovime nedvosmisleno izjavljujem');

        $this->assertDatabaseCount('contract_withdrawals', 0);

        preg_match('/name="draft_token" value="([^"]+)"/', $review->getContent(), $matches);
        $this->assertNotEmpty($matches[1] ?? null);

        $edit = $this->get(route('contract-withdrawal.create', ['draft' => $matches[1]]));
        $edit->assertOk()
            ->assertSee('Ana Kupac')
            ->assertSee('PHARMAD-1001');

        $store = $this->post(route('contract-withdrawal.store'), [
            'draft_token' => $matches[1],
        ]);

        $store->assertRedirect(route('contract-withdrawal.create'))
            ->assertSessionHas('success')
            ->assertSessionHas('withdrawal_reference');

        $withdrawal = ContractWithdrawal::query()->firstOrFail();

        $this->assertSame('PHARMAD-1001', $withdrawal->order_number);
        $this->assertSame('kupac@example.test', $withdrawal->email);
        $this->assertSame(ContractWithdrawal::STATUS_RECEIVED, $withdrawal->status);
        $this->assertNotNull($withdrawal->submitted_at);
        $this->assertNotNull($withdrawal->consumer_notified_at);
        $this->assertNotNull($withdrawal->admin_notified_at);
        $this->assertNull($withdrawal->notification_error);
        $this->assertSame(
            ContractWithdrawal::snapshotHash($withdrawal->request_snapshot),
            $withdrawal->snapshot_hash
        );

        Mail::assertSent(ContractWithdrawalReceiptMail::class, function ($mail) use ($withdrawal) {
            return $mail->hasTo('kupac@example.test')
                && $mail->withdrawal->is($withdrawal)
                && strpos($mail->build()->subject, $withdrawal->reference) !== false;
        });

        Mail::assertSent(ContractWithdrawalAdminMail::class, function ($mail) use ($withdrawal) {
            return $mail->hasTo('raskidi@example.test')
                && $mail->withdrawal->is($withdrawal)
                && strpos($mail->build()->subject, 'PHARMAD-1001') !== false;
        });
    }

    public function testInvalidSubmissionDoesNotCreateWithdrawal(): void
    {
        $response = $this->from(route('contract-withdrawal.create'))
            ->post(route('contract-withdrawal.review'), [
                'full_name' => '',
                'email' => 'nije-email',
            ]);

        $response->assertRedirect(route('contract-withdrawal.create'))
            ->assertSessionHasErrors(['full_name', 'email', 'order_number', 'withdrawal_scope']);

        $this->assertDatabaseCount('contract_withdrawals', 0);
    }

    public function testAdminCanProcessRequestAndUpdateSettings(): void
    {
        Mail::fake();
        $this->withoutMiddleware([
            Authenticate::class,
            EnsureEmailIsVerified::class,
            RedirectCustomer::class,
            \App\Http\Middleware\AuthorizeContractWithdrawals::class,
        ]);

        $settingsResponse = $this->patch(route('contract-withdrawal-settings.update'), [
            'admin_email' => 'admin-raskidi@example.test',
            'return_address' => 'PharmAD, Nova adresa 2, Zagreb',
            'return_cost_policy' => 'merchant',
            'instructions' => 'Proizvode pošaljite preporučeno.',
        ]);

        $settingsResponse
            ->assertRedirect(route('contract-withdrawal-settings.edit'))
            ->assertSessionHas('success');

        $settings = app(ContractWithdrawalSettingsService::class)->get();
        $this->assertSame('admin-raskidi@example.test', $settings['admin_email']);
        $this->assertSame('merchant', $settings['return_cost_policy']);

        $withdrawal = $this->makeWithdrawal();

        $this->get(route('contract-withdrawals.index'))
            ->assertOk()
            ->assertSee($withdrawal->reference)
            ->assertSee($withdrawal->full_name);

        $this->patch(route('contract-withdrawals.update', $withdrawal), [
            'status' => ContractWithdrawal::STATUS_COMPLETED,
            'internal_note' => 'Povrat je obrađen.',
        ])->assertRedirect(route('contract-withdrawals.show', $withdrawal));

        $withdrawal->refresh();
        $this->assertSame(ContractWithdrawal::STATUS_COMPLETED, $withdrawal->status);
        $this->assertSame('Povrat je obrađen.', $withdrawal->internal_note);
        $this->assertNotNull($withdrawal->completed_at);
    }

    public function testWholeOrderNeedsOnlyNameEmailAndOrderNumber(): void
    {
        Mail::fake();
        $token = $this->reviewToken([
            'full_name' => 'Ana Kupac', 'email' => 'ana@example.test',
            'order_number' => 'TEST-42', 'withdrawal_scope' => 'whole',
        ]);
        $this->post(route('contract-withdrawal.store'), ['draft_token' => $token])
            ->assertSessionHas('receipt_sent')->assertSessionHas('success');
        $record = ContractWithdrawal::firstOrFail();
        $this->assertSame('whole', $record->withdrawal_scope);
        $this->assertSame('Cijela narudžba', $record->items);
        $this->assertNull($record->address_line);
        $this->assertNull($record->country_code);
        $this->assertSame('whole', $record->request_snapshot['data']['withdrawal_scope']);
        $this->assertStringContainsString('cijelu narudžbu', $record->declaration);
        $this->assertSame([
            'withdrawal_id' => $record->id,
        ], session('contract_withdrawal_drafts.'.$token));
    }

    public function testPartialOrderRequiresItemsAndScopeHasNoDefault(): void
    {
        $payload = $this->validPayload();
        unset($payload['withdrawal_scope']);
        $this->post(route('contract-withdrawal.review'), $payload)->assertSessionHasErrors('withdrawal_scope');
        $payload['withdrawal_scope'] = 'partial';
        $payload['items'] = '';
        $this->post(route('contract-withdrawal.review'), $payload)->assertSessionHasErrors('items');
        $payload['withdrawal_scope'] = 'invalid';
        $this->post(route('contract-withdrawal.review'), $payload)->assertSessionHasErrors('withdrawal_scope');
        $this->assertDatabaseCount('contract_withdrawals', 0);
    }

    public function testRepeatedConfirmationKeepsReferenceSnapshotAndSendsOnce(): void
    {
        Mail::fake();
        $token = $this->reviewToken($this->validPayload());
        $this->post(route('contract-withdrawal.store'), ['draft_token' => $token])->assertSessionHas('success');
        $original = ContractWithdrawal::firstOrFail();
        $this->travel(31)->minutes();
        $this->post(route('contract-withdrawal.store'), ['draft_token' => $token])
            ->assertSessionHas('withdrawal_reference', $original->reference);
        $this->assertDatabaseCount('contract_withdrawals', 1);
        $this->assertSame($original->snapshot_hash, $original->fresh()->snapshot_hash);
        Mail::assertSent(ContractWithdrawalReceiptMail::class, 1);
        Mail::assertSent(ContractWithdrawalAdminMail::class, 1);
    }

    public function testUnknownAndExpiredDraftsCannotSubmit(): void
    {
        Mail::fake();
        $this->post(route('contract-withdrawal.store'), ['draft_token' => (string) \Illuminate\Support\Str::uuid()])
            ->assertSessionHasErrors('draft');
        $token = $this->reviewToken($this->validPayload());
        $this->travel(31)->minutes();
        $this->post(route('contract-withdrawal.store'), ['draft_token' => $token])->assertSessionHasErrors('draft');
        $this->assertDatabaseCount('contract_withdrawals', 0);
        Mail::assertNothingSent();
    }

    public function testSubmittedValuesComeFromReviewNotConfirmationPayload(): void
    {
        Mail::fake();
        $token = $this->reviewToken($this->validPayload());
        $this->post(route('contract-withdrawal.store'), [
            'draft_token' => $token, 'email' => 'attacker@example.test', 'withdrawal_scope' => 'whole',
        ]);
        $record = ContractWithdrawal::firstOrFail();
        $this->assertSame('kupac@example.test', $record->email);
        $this->assertSame('partial', $record->withdrawal_scope);
    }

    public function testHoneypotAndCaptchaRejectInvalidSubmissions(): void
    {
        $payload = $this->validPayload();
        $payload['website'] = 'https://spam.example';
        $this->post(route('contract-withdrawal.review'), $payload)->assertSessionHasErrors('website');
        unset($payload['website']);
        config(['services.recaptcha.sitekey' => 'test', 'services.recaptcha.secret' => 'test']);
        $this->post(route('contract-withdrawal.review'), $payload)->assertSessionHasErrors('recaptcha');
        $payload['recaptcha'] = 'test-token';
        foreach ([
            ['success' => false],
            ['success' => true, 'score' => 0.1, 'action' => 'contract_withdrawal', 'hostname' => 'pharmad.test'],
            ['success' => true, 'score' => 0.9, 'action' => 'other', 'hostname' => 'pharmad.test'],
            ['success' => true, 'score' => 0.9, 'action' => 'contract_withdrawal', 'hostname' => 'other.test'],
            ['success' => true, 'score' => 0.9, 'hostname' => 'pharmad.test'],
        ] as $result) {
            \Illuminate\Support\Facades\Http::swap(new \Illuminate\Http\Client\Factory());
            \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response($result)]);
            $this->post(route('contract-withdrawal.review'), $payload)->assertSessionHasErrors('recaptcha');
        }
        $this->assertDatabaseCount('contract_withdrawals', 0);
    }

    public function testValidCaptchaAllowsReviewButDoesNotSave(): void
    {
        config(['services.recaptcha.sitekey' => 'test', 'services.recaptcha.secret' => 'test']);
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response([
            'success' => true, 'score' => 0.9, 'action' => 'contract_withdrawal', 'hostname' => 'pharmad.test',
        ])]);
        $payload = $this->validPayload();
        $payload['recaptcha'] = 'test-token';
        $this->post(route('contract-withdrawal.review'), $payload)->assertOk()->assertSee('Potvrditi raskid ugovora');
        $this->assertDatabaseCount('contract_withdrawals', 0);
    }

    public function testNotificationFailurePreservesRequestAndDoesNotClaimEmailWasSent(): void
    {
        $notifications = \Mockery::mock(\App\Services\ContractWithdrawalNotificationService::class,
            [app(ContractWithdrawalSettingsService::class)])->makePartial();
        $notifications->shouldReceive('sendConsumerReceipt')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $notifications->shouldReceive('sendAdminNotification')->once()->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->app->instance(\App\Services\ContractWithdrawalNotificationService::class, $notifications);
        $token = $this->reviewToken($this->validPayload());
        $this->post(route('contract-withdrawal.store'), ['draft_token' => $token])
            ->assertSessionHas('success')->assertSessionHas('warning')->assertSessionMissing('receipt_sent');
        $this->assertDatabaseCount('contract_withdrawals', 1);
        $record = ContractWithdrawal::firstOrFail();
        $this->assertSame(1, (int) $record->consumer_notification_attempts);
        $this->assertSame(1, (int) $record->admin_notification_attempts);
        $this->assertNotNull($record->notification_error);
    }

    public function testSchedulerRetriesOnlyFailedRecipientAndStopsAfterFiveAttempts(): void
    {
        Mail::fake();
        $record = $this->makeWithdrawal();
        $notifications = \Mockery::mock(\App\Services\ContractWithdrawalNotificationService::class,
            [app(ContractWithdrawalSettingsService::class)])->makePartial();
        $notifications->shouldReceive('sendConsumerReceipt')->times(5)->andThrow(new \RuntimeException('SMTP unavailable'));
        $this->app->instance(\App\Services\ContractWithdrawalNotificationService::class, $notifications);
        $notifications->send($record);
        // Not due yet.
        $this->artisan('withdrawals:retry-notifications')->assertExitCode(0);
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $this->travel(5)->minutes();
            $this->artisan('withdrawals:retry-notifications')->assertExitCode(0);
        }
        $record->refresh();
        $this->assertSame(5, (int) $record->consumer_notification_attempts);
        $this->assertSame(1, (int) $record->admin_notification_attempts);
        $this->assertNotNull($record->notification_error);
        Mail::assertSent(ContractWithdrawalAdminMail::class, 1);
        Mail::assertNotSent(ContractWithdrawalReceiptMail::class);
        // Manual recovery after the automatic limit does not resend the admin mail.
        $real = new \App\Services\ContractWithdrawalNotificationService(app(ContractWithdrawalSettingsService::class));
        $real->send($record, true);
        $this->assertNotNull($record->consumer_notified_at);
        $this->assertNull($record->notification_error);
        Mail::assertSent(ContractWithdrawalReceiptMail::class, 1);
        Mail::assertSent(ContractWithdrawalAdminMail::class, 1);
    }

    public function testFailedAdminNotificationRecoversWithoutResendingReceipt(): void
    {
        Mail::fake();
        $record = $this->makeWithdrawal();
        $settings = app(ContractWithdrawalSettingsService::class);
        $settings->save(['admin_email' => '']);
        $service = app(\App\Services\ContractWithdrawalNotificationService::class);
        $service->send($record);
        $this->assertNotNull($record->consumer_notified_at);
        $this->assertNull($record->admin_notified_at);
        $settings->save(['admin_email' => 'admin@example.test']);
        $this->travel(5)->minutes();
        $this->artisan('withdrawals:retry-notifications')->assertExitCode(0);
        $record->refresh();
        $this->assertNotNull($record->admin_notified_at);
        $this->assertNull($record->notification_error);
        Mail::assertSent(ContractWithdrawalReceiptMail::class, 1);
        Mail::assertSent(ContractWithdrawalAdminMail::class, 1);
    }

    public function testRenderedEmailsContainSubmittedContentAndLocalTime(): void
    {
        Mail::fake();
        $token = $this->reviewToken($this->validPayload());
        $this->post(route('contract-withdrawal.store'), ['draft_token' => $token]);
        $record = ContractWithdrawal::firstOrFail();
        $settings = app(ContractWithdrawalSettingsService::class);
        foreach ([new ContractWithdrawalReceiptMail($record, $settings->get(), $settings->returnCostText($settings->get())),
            new ContractWithdrawalAdminMail($record, route('contract-withdrawals.show', $record))] as $mail) {
            $html = view($mail->build()->view, $mail->buildViewData())->render();
            foreach ([$record->reference, $record->declaration, 'Odabrani proizvodi', 'Proizvod A',
                $record->submitted_at->timezone('Europe/Zagreb')->format('d.m.Y. H:i:s T')] as $text) {
                $this->assertStringContainsString($text, $html);
            }
        }
    }

    public function testLegacyRecordSurvivesMigrationWithoutGuessedScope(): void
    {
        $migration = new \ExtendContractWithdrawalsTable();
        $migration->down();
        $record = $this->makeWithdrawal();
        $snapshot = $record->request_snapshot;
        $migration->up();
        $record->refresh();
        $this->assertNull($record->withdrawal_scope);
        $this->assertSame('Prema izvornoj izjavi', $record->scope_label);
        $this->assertSame('Proizvod A, 1 kom', $record->items);
        $this->assertSame($snapshot, $record->request_snapshot);
    }

    public function testGuestsCustomersAndUnassignedUsersCannotAccessAdminRecords(): void
    {
        $record = $this->makeWithdrawal();
        $this->get(route('contract-withdrawals.index'))->assertRedirect();
        $user = \App\Models\User::create(['name' => 'Kupac', 'email' => 'customer@example.test', 'password' => 'test']);
        $this->actingAs($user)->get(route('contract-withdrawals.index'))->assertForbidden();
        \Bouncer::assign('customer')->to($user);
        \Bouncer::refresh();
        $this->get(route('contract-withdrawals.index'))->assertRedirect(route('moj-racun'));
        $this->patch(route('contract-withdrawals.update', $record), ['status' => 'completed'])->assertRedirect();
        $this->post(route('contract-withdrawals.resend', $record))->assertRedirect();
        $this->get(route('contract-withdrawal-settings.edit'))->assertRedirect();
        $this->assertSame('received', $record->fresh()->status);
    }

    public function testAdministratorCanAccessRecordsWithMiddlewareEnabled(): void
    {
        $record = $this->makeWithdrawal();
        $user = \App\Models\User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => 'test']);
        \Bouncer::assign('admin')->to($user);
        \Bouncer::refresh();
        $this->actingAs($user)->get(route('contract-withdrawals.index'))->assertOk();
        $this->get(route('contract-withdrawals.show', $record))->assertOk()->assertSee($record->reference);
        $this->get(route('contract-withdrawal-settings.edit'))->assertOk();
    }

    public function testRateLimitIsEnforced(): void
    {
        $this->withMiddleware(ThrottleRequests::class);
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('contract-withdrawal.review'), $this->validPayload())->assertOk();
        }
        $this->post(route('contract-withdrawal.review'), $this->validPayload())->assertStatus(429);
    }

    public function testOldGetRedirectsAndOldPostRoutesStillWork(): void
    {
        Mail::fake();
        $this->get('/forma-za-povrat-i-reklamacije')->assertRedirect('/raskid-ugovora')->assertStatus(301);
        $review = $this->post('/forma-za-povrat-i-reklamacije', $this->validPayload())->assertOk();
        preg_match('/name="draft_token" value="([^"]+)"/', $review->getContent(), $matches);
        $this->post('/forma-za-povrat-i-reklamacije/potvrdi', ['draft_token' => $matches[1]])->assertSessionHas('success');
    }

    private function reviewToken(array $payload): string
    {
        $review = $this->post(route('contract-withdrawal.review'), $payload)->assertOk();
        preg_match('/name="draft_token" value="([^"]+)"/', $review->getContent(), $matches);
        $this->assertNotEmpty($matches[1] ?? null);
        return $matches[1];
    }

    private function validPayload(): array
    {
        return [
            'full_name' => 'Ana Kupac',
            'email' => 'kupac@example.test',
            'phone' => '+385 91 123 4567',
            'address_line' => 'Ljekarnička 12',
            'postal_code' => '10000',
            'city' => 'Zagreb',
            'country_code' => 'HR',
            'order_number' => 'PHARMAD-1001',
            'contract_date' => '2026-09-10',
            'received_date' => '2026-09-12',
            'withdrawal_scope' => 'partial',
            'items' => "Proizvod A, 1 kom\nProizvod B, 1 kom",
            'note' => '',
            'recaptcha' => '',
        ];
    }

    private function makeWithdrawal(): ContractWithdrawal
    {
        $snapshot = [
            'version' => '2026-09-16',
            'submitted_at' => now()->toIso8601String(),
            'confirmation_channel' => 'email',
            'data' => $this->validPayload(),
            'declaration' => 'Ovime raskidam ugovor PHARMAD-1001.',
        ];

        return ContractWithdrawal::query()->create([
            'reference' => 'JR-20260916-ABC123',
            'submission_key' => hash('sha256', 'test-submission'),
            'order_number' => 'PHARMAD-1001',
            'full_name' => 'Ana Kupac',
            'email' => 'kupac@example.test',
            'phone' => '+385 91 123 4567',
            'address_line' => 'Ljekarnička 12',
            'postal_code' => '10000',
            'city' => 'Zagreb',
            'country_code' => 'HR',
            'contract_date' => '2026-09-10',
            'received_date' => '2026-09-12',
            'items' => 'Proizvod A, 1 kom',
            'declaration' => 'Ovime raskidam ugovor PHARMAD-1001.',
            'request_snapshot' => $snapshot,
            'snapshot_hash' => ContractWithdrawal::snapshotHash($snapshot),
            'status' => ContractWithdrawal::STATUS_RECEIVED,
            'locale' => 'hr',
            'submitted_at' => now(),
        ]);
    }
}
