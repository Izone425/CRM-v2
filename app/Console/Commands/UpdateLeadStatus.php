<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\Lead;
use Illuminate\Console\Command;
use Carbon\Carbon;

class UpdateLeadStatus extends Command
{
    protected $signature = 'leads:update-status';
    protected $description = 'Update leads from Demo-Assigned to RFQ-Follow Up the day after the demo appointment';

    public function handle()
    {
        info('Demo-Assigned status update to RFQ-Follow Up command executed at ' . now());

        $leads = Lead::where('lead_status', 'Demo-Assigned')
            ->where('stage', 'Demo')
            ->whereHas('demoAppointment', function ($query) {
                $query->whereDate('date', Carbon::yesterday());
            })
            ->get();

        foreach ($leads as $lead) {
            $yesterdayDemos = $lead->demoAppointment()
                ->whereDate('date', Carbon::yesterday())
                ->where('status', 'New')
                ->get();

            if ($lead->categories !== 'Inactive' && $yesterdayDemos->isNotEmpty()) {
                // Update lead
                $lead->update([
                    'lead_status' => 'RFQ-Follow Up',
                    'stage' => 'Follow Up',
                ]);

                // Mark demos as done
                $lead->demoAppointment()
                    ->whereDate('date', Carbon::yesterday())
                    ->where('status', 'New')
                    ->update(['status' => 'Done']);

                // Activity log
                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                    ->orderByDesc('created_at')
                    ->first();

                if ($latestActivityLog) {
                    $latestActivityLog->update([
                        'description' => 'Demo-Assigned auto changed to RFQ-Follow Up after appointment date',
                    ]);
                } else {
                    ActivityLog::create([
                        'description' => 'Demo-Assigned auto changed to RFQ-Follow Up after appointment date',
                        'subject_id' => $lead->id,
                        'causer_id' => null,
                    ]);
                }
            }
        }

        info('Status update completed for ' . $leads->count() . ' leads.');
    }
}
