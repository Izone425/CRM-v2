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

        $schedule->command('repair-appointments:update-status')->dailyAt('00:03');

        $schedule->command('implementer-appointments:update-status')->dailyAt('00:05');

        $schedule->command('follow-up:auto')->weeklyOn(2, '10:00'); // Runs at Tuesday 10 AM

        $schedule->command('userleave:update')->everyThirtyMinutes(); // Runs every 30 Minutes

        $schedule->command('zoho:fetch-leads')->cron('*/4 * * * *'); // Runs every 4 minutes

        $schedule->command('repair:check-pending-status')->dailyAt('00:01');

        $schedule->command('handovers:check-delays')->dailyAt('00:01');

        // $schedule->command('handovers:check-pending-confirmation')->dailyAt('00:01');

        $schedule->command('handovers:sync')->everyThirtyMinutes();

        $schedule->command('overtime:send-reminders')->weeklyOn(4, '16:00'); // Runs Thursday at 4:00 PM

        $schedule->command('overtime:send-reminders')->weeklyOn(5, '16:00'); // Runs Friday at 4:00 PM

        $schedule->command('emails:send-scheduled')
            ->dailyAt('08:00')
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/scheduled-emails.log'));

        $schedule->command('calls:map-to-leads')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->appendOutputTo(storage_path('logs/call-mapping.log'));

        $schedule->command('renewal:auto-mapping')
            ->everyThirtyMinutes()
            ->withoutOverlapping() // Prevent multiple instances running simultaneously
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/auto-mapping.log'));

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
