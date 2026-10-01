<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Back\Orders\Order;
use App\Models\ContractWithdrawal;
use App\Services\ContractWithdrawalNotificationService;
use App\Services\ContractWithdrawalSettingsService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ContractWithdrawalController extends Controller
{
    private const DRAFT_SESSION_PREFIX = 'contract_withdrawal_drafts.';

    private $notifications;
    private $settings;

    public function __construct(
        ContractWithdrawalNotificationService $notifications,
        ContractWithdrawalSettingsService $settings
    ) {
        $this->notifications = $notifications;
        $this->settings = $settings;
    }

    public function create(Request $request)
    {
        $settings = $this->settings->get();
        $prefill = $this->prefill($request);
        $draftToken = trim((string) $request->query('draft'));

        if ($draftToken !== '') {
            $draft = $request->session()->get(self::DRAFT_SESSION_PREFIX.$draftToken);

            if (is_array($draft) && (int) ($draft['expires_at'] ?? 0) >= now()->timestamp) {
                $prefill = array_merge($prefill, (array) ($draft['data'] ?? []));
            }
        }

        return view('front.contract-withdrawals.create', [
            'prefill' => $prefill,
            'withdrawalSettings' => $settings,
            'returnCostText' => $this->settings->returnCostText($settings),
            'captchaEnabled' => $this->captchaEnabled(),
        ]);
    }

    public function review(Request $request)
    {
        $validated = $this->validateSubmission($request);
        $this->verifyCaptcha($request, $validated);

        $data = $this->normalize($validated);
        $token = (string) Str::uuid();

        $request->session()->put(self::DRAFT_SESSION_PREFIX.$token, [
            'data' => $data,
            'user_id' => optional($request->user())->id,
            'expires_at' => now()->addMinutes(30)->timestamp,
        ]);

        return view('front.contract-withdrawals.review', [
            'draftToken' => $token,
            'withdrawal' => $data,
            'declaration' => $this->declaration($data['order_number'], $data['withdrawal_scope']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'draft_token' => ['required', 'uuid'],
        ]);

        $token = (string) $validated['draft_token'];
        $draftKey = self::DRAFT_SESSION_PREFIX.$token;
        $draft = $request->session()->get($draftKey);

        if (is_array($draft)) {
            $existing = ContractWithdrawal::where('submission_key', hash('sha256', $token))->first();
            if ($existing) {
                $this->rememberSubmittedDraft($request, $draftKey, $existing);

                return $this->receiptRedirect($existing);
            }
        }

        if (! is_array($draft) || (int) ($draft['expires_at'] ?? 0) < now()->timestamp) {
            $request->session()->forget($draftKey);

            return redirect()
                ->route('contract-withdrawal.create')
                ->withErrors(['draft' => __('contract_withdrawal.expired')]);
        }

        $data = (array) ($draft['data'] ?? []);
        $declaration = $this->declaration((string) ($data['order_number'] ?? ''), $data['withdrawal_scope'] ?? null);
        $submittedAt = now();
        $snapshot = [
            'version' => '2026-09-29',
            'submitted_at' => $submittedAt->toIso8601String(),
            'confirmation_channel' => 'email',
            'data' => $data,
            'declaration' => $declaration,
        ];
        $submissionKey = hash('sha256', $token);

        try {
            $withdrawal = DB::transaction(function () use (
                $request,
                $draft,
                $data,
                $declaration,
                $submittedAt,
                $snapshot,
                $submissionKey
            ) {
                $existing = ContractWithdrawal::query()
                    ->where('submission_key', $submissionKey)
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $order = $this->resolveOrder(
                    (string) $data['order_number'],
                    (string) $data['email'],
                    isset($draft['user_id']) ? (int) $draft['user_id'] : null
                );

                return ContractWithdrawal::query()->create([
                    'reference' => $this->newReference(),
                    'submission_key' => $submissionKey,
                    'user_id' => isset($draft['user_id']) ? (int) $draft['user_id'] : null,
                    'order_id' => optional($order)->id,
                    'order_number' => $data['order_number'],
                    'full_name' => $data['full_name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?: null,
                    'address_line' => $data['address_line'] ?: null,
                    'postal_code' => $data['postal_code'] ?: null,
                    'city' => $data['city'] ?: null,
                    'country_code' => $data['country_code'] ?: null,
                    'contract_date' => $data['contract_date'] ?: null,
                    'received_date' => $data['received_date'] ?: null,
                    'withdrawal_scope' => $data['withdrawal_scope'] ?? null,
                    'items' => ($data['withdrawal_scope'] ?? null) === 'whole'
                        ? __('contract_withdrawal.scopes.whole')
                        : $data['items'],
                    'note' => $data['note'] ?: null,
                    'declaration' => $declaration,
                    'request_snapshot' => $snapshot,
                    'snapshot_hash' => ContractWithdrawal::snapshotHash($snapshot),
                    'status' => ContractWithdrawal::STATUS_RECEIVED,
                    'locale' => 'hr',
                    'submitted_at' => $submittedAt,
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 512, ''),
                ]);
            });
        } catch (QueryException $exception) {
            // The unique submission key resolves concurrent confirmations.
            $withdrawal = ContractWithdrawal::where('submission_key', $submissionKey)->first();
            if (! $withdrawal) {
                throw $exception;
            }

            $this->rememberSubmittedDraft($request, $draftKey, $withdrawal);

            return $this->receiptRedirect($withdrawal);
        }

        $this->rememberSubmittedDraft($request, $draftKey, $withdrawal);

        if ($withdrawal->wasRecentlyCreated) {
            $this->notifications->send($withdrawal);
        }

        $withdrawal->refresh();

        return $this->receiptRedirect($withdrawal);
    }

    private function rememberSubmittedDraft(
        Request $request,
        string $draftKey,
        ContractWithdrawal $withdrawal
    ): void {
        $request->session()->put($draftKey, [
            'withdrawal_id' => $withdrawal->id,
        ]);
    }

    private function receiptRedirect(ContractWithdrawal $withdrawal)
    {
        $redirect = redirect()
            ->route('contract-withdrawal.create')
            ->with('success', __('contract_withdrawal.success', [
                'reference' => $withdrawal->reference,
                'submitted_at' => $withdrawal->submitted_at->timezone('Europe/Zagreb')->format('d.m.Y. H:i:s T'),
            ]))
            ->with('withdrawal_reference', $withdrawal->reference);

        if ($withdrawal->consumer_notified_at) {
            $redirect->with('receipt_sent', __('contract_withdrawal.receipt_sent'));
        } else {
            $redirect->with('warning', __('contract_withdrawal.email_warning'));
        }

        return $redirect;
    }

    private function validateSubmission(Request $request): array
    {
        $captchaEnabled = $this->captchaEnabled();
        $receivedDateRules = ['nullable', 'date', 'before_or_equal:today'];

        if ($request->filled('contract_date')) {
            $receivedDateRules[] = 'after_or_equal:contract_date';
        }

        return $request->validate(
            [
                'full_name' => ['required', 'string', 'min:2', 'max:191'],
                'email' => ['required', 'email', 'max:191'],
                'phone' => ['nullable', 'string', 'max:80'],
                'address_line' => ['nullable', 'string', 'min:3', 'max:255'],
                'postal_code' => ['nullable', 'string', 'max:32'],
                'city' => ['nullable', 'string', 'max:120'],
                'country_code' => ['nullable', 'string', 'alpha', 'size:2'],
                'order_number' => ['required', 'string', 'max:80'],
                'contract_date' => ['nullable', 'date', 'before_or_equal:today'],
                'received_date' => $receivedDateRules,
                'withdrawal_scope' => ['required', 'in:whole,partial'],
                'website' => ['nullable', 'string', 'max:0'],
                'items' => [
                    'exclude_if:withdrawal_scope,whole',
                    'required_if:withdrawal_scope,partial',
                    'nullable',
                    'string',
                    'min:2',
                    'max:5000',
                ],
                'note' => ['nullable', 'string', 'max:5000'],
                'recaptcha' => [$captchaEnabled ? 'required' : 'nullable', 'string', 'max:4096'],
            ],
            [
                'required' => __('contract_withdrawal.validation.required'),
                'required_if' => __('contract_withdrawal.validation.required_if'),
                'in' => __('contract_withdrawal.validation.in'),
                'website.max' => __('contract_withdrawal.captcha_failed'),
                'email' => __('contract_withdrawal.validation.email'),
                'alpha' => __('contract_withdrawal.validation.alpha'),
                'min.string' => __('contract_withdrawal.validation.min_string'),
                'max.string' => __('contract_withdrawal.validation.max_string'),
                'size' => __('contract_withdrawal.validation.size'),
                'date' => __('contract_withdrawal.validation.date'),
                'before_or_equal' => __('contract_withdrawal.validation.before_or_equal'),
                'after_or_equal' => __('contract_withdrawal.validation.after_or_equal'),
            ],
            trans('contract_withdrawal.attributes')
        );
    }

    private function normalize(array $validated): array
    {
        return [
            'full_name' => trim((string) $validated['full_name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'phone' => trim((string) ($validated['phone'] ?? '')),
            'address_line' => trim((string) ($validated['address_line'] ?? '')),
            'postal_code' => trim((string) ($validated['postal_code'] ?? '')),
            'city' => trim((string) ($validated['city'] ?? '')),
            'country_code' => strtoupper(trim((string) ($validated['country_code'] ?? ''))),
            'order_number' => trim((string) $validated['order_number']),
            'contract_date' => trim((string) ($validated['contract_date'] ?? '')),
            'received_date' => trim((string) ($validated['received_date'] ?? '')),
            'withdrawal_scope' => $validated['withdrawal_scope'],
            'items' => $validated['withdrawal_scope'] === 'whole'
                ? ''
                : trim((string) ($validated['items'] ?? '')),
            'note' => trim((string) ($validated['note'] ?? '')),
        ];
    }

    private function verifyCaptcha(Request $request, array $validated): void
    {
        if (! $this->captchaEnabled()) {
            return;
        }

        try {
            $response = Http::asForm()
                ->timeout(8)
                ->post((string) config('services.recaptcha.verify_url'), [
                    'secret' => (string) config('services.recaptcha.secret'),
                    'response' => (string) ($validated['recaptcha'] ?? ''),
                    'remoteip' => (string) $request->ip(),
                ]);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages([
                'recaptcha' => __('contract_withdrawal.captcha_failed'),
            ]);
        }

        $result = $response->ok() ? (array) $response->json() : [];
        $action = (string) ($result['action'] ?? '');

        if (
            ! (bool) ($result['success'] ?? false)
            || (float) ($result['score'] ?? 0) < 0.3
            || $action !== 'contract_withdrawal'
            || strtolower((string) ($result['hostname'] ?? '')) !== $this->expectedCaptchaHostname()
        ) {
            throw ValidationException::withMessages([
                'recaptcha' => __('contract_withdrawal.captcha_failed'),
            ]);
        }
    }

    private function captchaEnabled(): bool
    {
        if (app()->environment('local') && config('services.recaptcha.bypass_local', true)) {
            return false;
        }

        return trim((string) config('services.recaptcha.sitekey')) !== ''
            && trim((string) config('services.recaptcha.secret')) !== '';
    }

    private function expectedCaptchaHostname(): string
    {
        return strtolower((string) (config('services.recaptcha.hostname')
            ?: parse_url(config('app.url'), PHP_URL_HOST)));
    }

    private function declaration(string $orderNumber, ?string $scope = null): string
    {
        return (string) trans($scope === 'whole' ? 'contract_withdrawal.declaration_whole' : 'contract_withdrawal.declaration', [
            'order_number' => $orderNumber,
        ]);
    }

    private function newReference(): string
    {
        do {
            $reference = 'JR-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (ContractWithdrawal::query()->where('reference', $reference)->exists());

        return $reference;
    }

    private function resolveOrder(string $orderNumber, string $email, ?int $userId): ?Order
    {
        $orderId = ltrim(trim($orderNumber), '#');

        if (! ctype_digit($orderId)) {
            return null;
        }

        return Order::query()
            ->whereKey((int) $orderId)
            ->where(function ($query) use ($email, $userId): void {
                $query
                    ->where('payment_email', $email)
                    ->orWhere('shipping_email', $email);

                if ($userId) {
                    $query->orWhere('user_id', $userId);
                }
            })
            ->first();
    }

    private function prefill(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [];
        }

        $user->loadMissing('details');
        $details = $user->details;

        return array_filter([
            'full_name' => trim(implode(' ', array_filter([
                optional($details)->fname,
                optional($details)->lname,
            ]))) ?: $user->name,
            'email' => $user->email,
            'phone' => optional($details)->phone,
            'address_line' => optional($details)->address,
            'postal_code' => optional($details)->zip,
            'city' => optional($details)->city,
            'country_code' => 'HR',
        ], static function ($value): bool {
            return $value !== null && $value !== '';
        });
    }
}
