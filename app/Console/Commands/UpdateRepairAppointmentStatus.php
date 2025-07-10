<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\RepairAppointment;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateRepairAppointmentStatus extends Command
{
    protected $signature = 'repair-appointments:update-status';
    protected $description = 'Update repair appointments to Completed status the day after the scheduled date';

    public function handle()
    {
        info('Running auto-update for overdue repair appointments — ' . now());

        $appointments = RepairAppointment::whereDate('date', '<=', Carbon::yesterday())
            ->where('status', 'New')
            ->get();

        foreach ($appointments as $appointment) {

            $appointment->updateQuietly(['status' => 'Done']);

        }

        info('Finished updating ' . $appointments->count() . ' repair appointments.');

        return 0;
    }
}
