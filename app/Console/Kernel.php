<?php

namespace App\Console;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected $commands = [
        \App\Console\Commands\AutoFollowUp::class,
        \App\Console\Commands\UpdateLeadStatus::class,
    ];

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('leads:update-status')->dailyAt('00:01'); // Runs daily at 12:01 AM

        $schedule->command('follow-up:auto')->everyMinute(); //Runs weekly at Tuesday 10am

        $schedule->command('facebook:fetch-leads')->everyMinute();  //Runs every minutes

        $schedule->command('userleave:update')->everyThreeHours(); // Runs every 3 hours

        $schedule->command('zoho:fetch-leads')->cron('*/15 6-21 * * *'); // Runs every 15 minutes between 6 AM and 9 PM
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
