<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\ImplementerAppointment;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateImplementerAppointmentStatus extends Command
{
    protected $signature = 'implementer-appointments:update-status';
    protected $description = 'Update implementer appointments to Completed status the day after the scheduled date';

    public function handle()
    {
        info('Running auto-update for overdue implementer appointments — ' . now());

        $appointments = ImplementerAppointment::whereDate('date', '<=', Carbon::yesterday())
            ->where('status', 'New')
            ->get();

        $updatedCount = 0;
        $cancelledCount = 0;

        foreach ($appointments as $appointment) {
            // Check if this is a "skip_email_teams" type appointment
            if (!$appointment->required_attendees && !$appointment->event_id && !$appointment->meeting_link) {
                // Mark these special appointments as Cancelled instead of Done
                $appointment->updateQuietly(['status' => 'Cancelled']);
                $cancelledCount++;
            } else {
                // Regular appointments get marked as Done
                $appointment->updateQuietly(['status' => 'Done']);
                $updatedCount++;
            }
        }

        info('Finished updating implementer appointments: ' . $updatedCount . ' marked as Done, ' . $cancelledCount . ' marked as Cancelled.');

        return 0;
    }
}
