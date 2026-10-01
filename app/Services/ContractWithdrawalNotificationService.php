<?php

namespace App\Services;

use App\Mail\ContractWithdrawalAdminMail;
use App\Mail\ContractWithdrawalReceiptMail;
use App\Models\ContractWithdrawal;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContractWithdrawalNotificationService
{
    private $settings;

    public function __construct(ContractWithdrawalSettingsService $settings)
    {
        $this->settings = $settings;
    }

    public function send(ContractWithdrawal $withdrawal, bool $manual = false): void
    {
        foreach (['consumer', 'admin'] as $recipient) {
            // A database row lock also serializes scheduler and manual retries.
            DB::transaction(function () use ($withdrawal, $recipient, $manual): void {
                $record = ContractWithdrawal::whereKey($withdrawal->id)->lockForUpdate()->firstOrFail();
                $attempts = $recipient.'_notification_attempts';
                $lastAttempt = $recipient.'_last_attempt_at';
                $error = $recipient.'_notification_error';

                if ($record->{$recipient.'_notified_at'}) {
                    return;
                }

                if (! $manual && ((int) $record->{$attempts} >= 5
                    || ($record->{$lastAttempt} && Carbon::parse($record->{$lastAttempt})->gt(now()->subMinutes(5))))) {
                    return;
                }

                $record->forceFill([$attempts => (int) $record->{$attempts} + 1, $lastAttempt => now()])->save();

                try {
                    $recipient === 'consumer'
                        ? $this->sendConsumerReceipt($record)
                        : $this->sendAdminNotification($record);
                    $record->forceFill([$error => null]);
                } catch (\Throwable $exception) {
                    $record->forceFill([$error => 'Slanje nije uspjelo. Provjerite postavke e-pošte i zapisnik.']);
                    Log::error('Contract withdrawal notification failed', [
                        'withdrawal_id' => $record->id,
                        'recipient_type' => $recipient,
                        'exception' => $exception,
                    ]);
                }
                $record->forceFill(['notification_error' => implode("\n", array_filter([
                    $record->consumer_notification_error ? 'Kupac: '.$record->consumer_notification_error : null,
                    $record->admin_notification_error ? 'PharmAD: '.$record->admin_notification_error : null,
                ])) ?: null])->save();
            });
        }

        $withdrawal->refresh();
    }

    public function sendConsumerReceipt(ContractWithdrawal $withdrawal): void
    {
        $settings = $this->settings->get();
        $mail = new ContractWithdrawalReceiptMail(
            $withdrawal,
            $settings,
            $this->settings->returnCostText($settings)
        );

        Mail::to($withdrawal->email)->send($mail);

        $withdrawal->forceFill(['consumer_notified_at' => now()])->save();
    }

    public function sendAdminNotification(ContractWithdrawal $withdrawal): void
    {
        $settings = $this->settings->get();
        $adminEmail = trim((string) ($settings['admin_email'] ?? ''));

        if (! filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Nije postavljena ispravna administratorska e-mail adresa.');
        }

        Mail::to($adminEmail)->send(new ContractWithdrawalAdminMail(
            $withdrawal,
            route('contract-withdrawals.show', $withdrawal)
        ));

        $withdrawal->forceFill(['admin_notified_at' => now()])->save();
    }
}
