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
        $schedule->command('reports:update-manufacturer')
            ->weeklyOn(6, '1:00')
            ->name('weekly-manufacturer-report')
            ->withoutOverlapping();

        //* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1

       /* $schedule->command('reports:update-manufacturer')
            ->monthlyOn(1, '2:00')
            ->name('monthly-manufacturer-report')
            ->withoutOverlapping();*/

        // $schedule->command('inspire')->hourly();
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
