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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContractWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    protected function refreshTestDatabase()
    {
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_09_16_080000_create_contract_withdrawals_table.php',
        ]);

        $this->app[Kernel::class]->setArtisan(null);
        $this->beginDatabaseTransaction();
    }

    protected function setUp(): void
    {
        parent::setUp();

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
            ->assertSee('Raskid ugovora')
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
            ->assertSessionHasErrors(['full_name', 'email', 'address_line', 'order_number', 'items']);

        $this->assertDatabaseCount('contract_withdrawals', 0);
    }

    public function testAdminCanProcessRequestAndUpdateSettings(): void
    {
        Mail::fake();
        $this->withoutMiddleware([
            Authenticate::class,
            EnsureEmailIsVerified::class,
            RedirectCustomer::class,
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
