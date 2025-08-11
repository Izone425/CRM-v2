<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Models\SoftwareHandover;
use App\Models\ImplementerLog;
use App\Models\User;

class CreateMissingImplementerLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'implementer:create-missing-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create missing implementer logs for open and delayed software handovers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to check for missing implementer logs...');

        // Find all software handovers with status OPEN or DELAY that don't have implementer logs
        $missingLogs = DB::table('software_handovers as sh')
            ->leftJoin(
                DB::raw('(SELECT DISTINCT subject_id FROM implementer_logs) as il'),
                'sh.id', '=', 'il.subject_id'
            )
            ->whereIn('sh.status_handover', ['OPEN', 'DELAY'])
            ->whereNull('il.subject_id')
            ->select('sh.*')
            ->get();

        $this->info("Found {$missingLogs->count()} software handovers without implementer logs.");

        $createdCount = 0;

        foreach ($missingLogs as $handover) {
            try {
                // Get lead information for the description
                $lead = DB::table('leads')->where('id', $handover->lead_id)->first();

                // Get company name from company_details table
                $company = DB::table('company_details')
                    ->where('lead_id', $handover->lead_id)
                    ->first();

                $companyName = $handover->company_name ? $handover->company_name : 'Unknown Company';

                // Create description
                $description = 'NEW PROJECT ASSIGNMENT';
                $remark = "Project assigned to {$handover->implementer} for {$companyName}";

                // Insert the log
                DB::table('implementer_logs')->insert([
                    'lead_id' => $handover->lead_id,
                    'description' => $description,
                    'remark' => $remark,
                    'subject_id' => $handover->id,
                    'causer_id' => 4,
                    'created_at' => $handover->created_at ?? now(),
                    'updated_at' => now(),
                ]);

                $createdCount++;
                $this->info("Created log for handover ID {$handover->id} - {$companyName}");
            } catch (\Exception $e) {
                $this->error("Error creating log for handover ID {$handover->id}: {$e->getMessage()}");
            }
        }

        $this->info("Process completed. Created {$createdCount} new implementer logs.");
    }
}
