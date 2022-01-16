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
        Commands\Subscriptions\EndSubscriptions::class,
        Commands\Payments\CheckPayoutTransferStatus::class,
        Commands\Payments\GeneratePayout::class,
        Commands\Payments\CashoutPayout::class,
        Commands\Trending\ComputeCollectionTrending::class,
        Commands\Trending\ComputeContentTrending::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('flok:end-subscriptions')->hourly();
        $schedule->command('flok:generate-payouts')->monthly(7, '8:00');
        $schedule->command('flok:cashout-payouts')->everyTwoMinutes();
        $schedule->command('flok:compute-content-trending')->daily();
        $schedule->command('flok:compute-collection-trending')->daily();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
