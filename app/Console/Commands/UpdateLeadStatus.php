<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\Lead;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateLeadStatus extends Command
{
    protected $signature = 'leads:update-status';
    protected $description = 'Update leads from Demo-Assigned to RFQ-Follow Up the day after the demo appointment';

    public function handle()
    {
        info('Running auto-update for overdue demos — ' . now());

        $appointments = Appointment::whereDate('date', '<=', Carbon::yesterday())
            ->where('status', 'New')
            ->get();

        foreach ($appointments as $appointment) {
            $lead = $appointment->lead;

            if (!$lead) {
                continue;
            }

            // Update appointment to Done
            $appointment->updateQuietly(['status' => 'Done']);

            ActivityLog::create([
                'description' => 'Demo auto-updated to Done status after overdue',
                'subject_id' => $lead->id,
                'causer_id' => null,
            ]);

            // Update lead if in 'Demo' category
            if ($lead && $lead->stage === 'Demo') {
                $lead->updateQuietly([
                    'lead_status' => 'RFQ-Follow Up',
                    'stage' => 'Follow Up',
                ]);

                ActivityLog::create([
                    'description' => 'Lead auto-updated to RFQ-Follow Up after overdue demo',
                    'subject_id' => $lead->id,
                    'causer_id' => null,
                ]);
            }
        }

        info('Finished updating ' . $appointments->count() . ' appointments.');
    }
}
