<?php

namespace App\Console\Commands;

use App\Models\ContractWithdrawal;
use App\Services\ContractWithdrawalNotificationService;
use Illuminate\Console\Command;

class RetryWithdrawalNotifications extends Command
{
    protected $signature = 'withdrawals:retry-notifications';
    protected $description = 'Retry pending withdrawal receipts and merchant notifications (five attempts per recipient).';

    public function handle(ContractWithdrawalNotificationService $notifications): int
    {
        ContractWithdrawal::query()->where(function ($query) {
            foreach (['consumer', 'admin'] as $recipient) {
                $query->orWhere(function ($pending) use ($recipient) {
                    $pending->whereNull($recipient.'_notified_at')
                        ->where($recipient.'_notification_attempts', '<', 5)
                        ->where(function ($due) use ($recipient) {
                            $due->whereNull($recipient.'_last_attempt_at')
                                ->orWhere($recipient.'_last_attempt_at', '<=', now()->subMinutes(5));
                        });
                });
            }
        })->chunkById(100, function ($withdrawals) use ($notifications) {
            foreach ($withdrawals as $withdrawal) {
                $notifications->send($withdrawal);
            }
        });

        return 0;
    }
}
