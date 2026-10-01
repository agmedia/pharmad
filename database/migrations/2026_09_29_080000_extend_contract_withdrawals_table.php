<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendContractWithdrawalsTable extends Migration
{
    public function up()
    {
        Schema::table('contract_withdrawals', function (Blueprint $table) {
            $table->string('withdrawal_scope', 16)->nullable();
            foreach (['consumer', 'admin'] as $recipient) {
                $table->unsignedInteger($recipient.'_notification_attempts')->default(0);
                $table->timestamp($recipient.'_last_attempt_at')->nullable();
                $table->text($recipient.'_notification_error')->nullable();
            }

            $table->string('address_line', 255)->nullable()->change();
            $table->string('postal_code', 32)->nullable()->change();
            $table->string('city', 120)->nullable()->change();
            $table->string('country_code', 2)->nullable()->default(null)->change();
        });
    }

    public function down()
    {
        // Optional addresses remain nullable to preserve submissions without an address.
        Schema::table('contract_withdrawals', function (Blueprint $table) {
            $table->dropColumn([
                'withdrawal_scope',
                'consumer_notification_attempts',
                'admin_notification_attempts',
                'consumer_last_attempt_at',
                'admin_last_attempt_at',
                'consumer_notification_error',
                'admin_notification_error',
            ]);
        });
    }
}
