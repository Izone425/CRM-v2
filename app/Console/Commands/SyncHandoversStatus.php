<?php

namespace App\Console\Commands;

use App\Models\HardwareHandover;
use App\Models\SoftwareHandover;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncHandoversStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'handovers:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync hardware handovers status based on software handover migration status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Starting handover synchronization...');
        $count = 0;

        // Get all completed software handovers with data migrated = true
        $migratedSoftwareHandovers = SoftwareHandover::where('data_migrated', true)
            ->whereNotNull('completed_at')
            ->whereNotNull('lead_id')
            ->get();

        Log::info('Found ' . $migratedSoftwareHandovers->count() . ' migrated software handovers');

        // For each migrated software handover, check related hardware handovers
        foreach ($migratedSoftwareHandovers as $softwareHandover) {
            // First, get the latest hardware handover for this lead
            $latestHandover = HardwareHandover::where('lead_id', $softwareHandover->lead_id)
                ->orderBy('created_at', 'desc')
                ->first();

            // Skip if no hardware handover exists
            if (!$latestHandover) {
                continue;
            }

            // Only proceed if the latest handover has 'Pending Migration' status
            if ($latestHandover->status === 'Pending Migration') {
                $latestHandover->update([
                    'status' => 'Completed Migration',
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

                $count++;
                $this->info("Updated latest hardware handover #{$latestHandover->id} for lead #{$softwareHandover->lead_id}");
            }
        }

        Log::info("Sync completed. Updated $count hardware handovers.");
    }
}
