<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('withdrawals:retry-notifications')->everyFiveMinutes()->withoutOverlapping();
        $generationTime = app(\App\Services\PriceListSettingsService::class)->all()['generation_time'] ?? '07:00';
        $schedule->command('price-list:generate')
            ->dailyAt($generationTime)
            ->timezone('Europe/Zagreb')
            ->withoutOverlapping();
        $schedule->command('clean:authors')->dailyAt('00:03');
        $schedule->command('clean:publishers')->dailyAt('00:04');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
