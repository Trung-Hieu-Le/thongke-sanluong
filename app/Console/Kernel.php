<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('app:loc-san-luong')->withoutOverlapping()->hourly();
        // $schedule->command('app:tim-hinh-anh-trung')->withoutOverlapping()->hourly();
        $schedule->command('app:update-san-luong-hourly')->withoutOverlapping()->hourly();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
