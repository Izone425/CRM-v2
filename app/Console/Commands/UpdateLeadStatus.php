<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use App\Models\Lead; // Adjust the model namespace if needed
use Carbon\Carbon;

class UpdateLeadStatus extends Command
{
    protected $signature = 'leads:update-status';
    protected $description = 'Update leads from demo-assigned to RFQ-Follow Up the day after the appointment date at midnight';

    public function handle()
    {
        info('Demo-Assigned status update to RFQ-Follow Up command executed at ' . now());

        // Fetch leads with status 'Demo-Assigned' and stage 'Demo' where today is the day after the appointment date
        $leads = Lead::whereHas('demoAppointment', function ($query) {
                $query->whereDate('date', Carbon::yesterday());
            })
            ->get();

        foreach ($leads as $lead) {
            // Update the lead's status and stage
            $lead->update([
                'lead_status' => 'RFQ-Follow Up',
                'stage' => 'Follow Up',
            ]);

            $latestAppointment = $lead->demoAppointment()
                ->orderByDesc('date') // Get the latest appointment based on the date
                ->first();

            $lead->demoAppointment()
                ->whereDate('date', Carbon::yesterday())
                ->where('status', 'New')
                ->update(['status' => 'Done']);

            // Fetch the latest activity log for the lead
            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                ->orderByDesc('created_at')
                ->first();

            // Update or create a new activity log
            if ($latestActivityLog) {
                $latestActivityLog->update([
                    'description' => 'Demo-Assigned auto changed to RFQ-Follow Up after appointment date',
                ]);
            } else {
                ActivityLog::create([
                    'description' => 'Demo-Assigned auto changed to RFQ-Follow Up after appointment date',
                    'subject_id' => $lead->id,
                    'causer_id' => null, // No specific user
                ]);
            }
        }

        info('Status update completed for ' . $leads->count() . ' appointments.');
    }
}
