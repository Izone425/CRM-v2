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

        foreach ($appointments as $appointment) {
            $appointment->updateQuietly(['status' => 'Done']);
            $updatedCount++;
        }

        info('Finished updating ' . $updatedCount . ' implementer appointments.');

        return 0;
    }
}
