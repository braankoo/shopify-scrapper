<?php

namespace App\Console;

use App\Console\Commands\GetCatalogs;
use App\Console\Commands\GetProducts;
use App\Console\Commands\PrepareData;
use App\Console\Commands\PrepareHistoricalData;
use App\Console\Commands\TestProxies;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        GetCatalogs::class,
        GetProducts::class,
        PrepareHistoricalData::class,
        TestProxies::class,
        PrepareData::class
    ];

    /**
     * Define the application's command schedule.
     *
     * @param \Illuminate\Console\Scheduling\Schedule $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
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
