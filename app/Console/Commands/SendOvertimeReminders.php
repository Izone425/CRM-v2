<?php
namespace App\Console\Commands;

use App\Mail\OvertimeReminder;
use App\Models\OvertimeSchedule;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOvertimeReminders extends Command
{
    protected $signature = 'overtime:send-reminders';
    protected $description = 'Send email reminders for weekend overtime schedules';

    public function handle()
    {
        $today = Carbon::now();
        $dayOfWeek = $today->dayOfWeek;

        // Reminder 1: Send on Thursday at 4 PM
        if ($dayOfWeek === Carbon::THURSDAY) {
            $this->sendReminders(1);
            $this->info('Sent first reminder emails for upcoming weekend overtime.');
        }

        // Reminder 2: Send on Friday at 4 PM
        if ($dayOfWeek === Carbon::FRIDAY) {
            $this->sendReminders(2);
            $this->info('Sent second reminder emails for upcoming weekend overtime.');
        }

        return 0;
    }

    private function sendReminders($reminderNumber)
    {
        // Get the upcoming Saturday
        $upcomingSaturday = Carbon::now()->next(Carbon::SATURDAY);

        // Find overtime schedules for this weekend
        $schedules = OvertimeSchedule::where('weekend_date', $upcomingSaturday->format('Y-m-d'))
            ->where('status', 'scheduled')
            ->get();

        foreach ($schedules as $schedule) {
            if (!$schedule->user) {
                Log::warning("Overtime schedule #{$schedule->id} has no assigned user.");
                continue;
            }

            $data = [
                'reminderType' => $reminderNumber,
                'staffName' => $schedule->user->name,
                'saturdayDate' => $schedule->weekend_date->format('d F Y'),
                'sundayDate' => $schedule->sunday_date->format('d F Y'),
            ];

            try {
                // Send email to assigned staff and support email
                Mail::to($schedule->user->email)
                    ->cc('support@timeteccloud.com')
                    ->send(new OvertimeReminder($data));

                Log::info("Sent reminder #{$reminderNumber} to {$schedule->user->name} for weekend overtime.");
            } catch (\Exception $e) {
                Log::error("Failed to send overtime reminder: " . $e->getMessage());
            }
        }
    }
}
