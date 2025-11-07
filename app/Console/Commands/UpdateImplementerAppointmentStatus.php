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
            if ($appointment->type === 'BACKUP SUPPORT') {
                $appointment->updateQuietly(['status' => 'Done']);
                $updatedCount++;
            }
            elseif (!$appointment->required_attendees && !$appointment->event_id && !$appointment->meeting_link) {
                $appointment->updateQuietly(['status' => 'Cancelled']);
                $cancelledCount++;
            } else {
                $appointment->updateQuietly(['status' => 'Done']);
                $updatedCount++;
            }
        }

        info('Finished updating implementer appointments: ' . $updatedCount . ' marked as Done, ' . $cancelledCount . ' marked as Cancelled.');

        return 0;
    }
}
