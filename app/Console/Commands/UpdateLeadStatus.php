<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use App\Models\Lead; // Adjust the model namespace if needed
use Carbon\Carbon;

class UpdateLeadStatus extends Command
{
    protected $signature = 'leads:update-status';
    protected $description = 'Update leads from demo-assigned to RFQ-Follow Up after midnight';

    public function handle()
    {
        info('Demo-Assigned status update to RFQ-Follow Up command in everyday 12am executed at ' . now());

        // Fetch leads with status 'demo-assigned' that need to be updated
        $leads = Lead::where('lead_status', 'Demo-Assigned')
                     ->where('stage', 'Demo')
                    //  ->where('updated_at', '<', Carbon::now()->startOfDay()) // Only process leads not updated today
                     ->get();

        foreach ($leads as $lead) {
            $lead->update([
                'lead_status' => 'RFQ-Follow Up',
                'stage' => 'Follow Up',
            ]);
            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                ->orderByDesc('created_at')
                ->first();

            if ($latestActivityLog && $latestActivityLog->description) {
                $latestActivityLog->update([
                    'description' => 'Demo-Assigned auto change to RFQ-Follow Up'
                ]);
                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($lead);
            }
        }
    }
}
