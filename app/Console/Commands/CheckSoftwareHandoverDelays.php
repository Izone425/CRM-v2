<?php

namespace App\Console\Commands;

use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckSoftwareHandoverDelays extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'handovers:check-delays';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for software handovers that are delayed (more than 60 days since completion)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking for delayed software handovers...');

        // Get all open handovers that have been completed
        $handovers = SoftwareHandover::where('status_handover', 'Open')
            ->whereNotNull('completed_at')
            ->get();

        $count = 0;

        foreach ($handovers as $handover) {
            $completedDate = Carbon::parse($handover->completed_at);
            $today = Carbon::now();
            $daysDifference = $completedDate->diffInDays($today);

            // If more than 60 days, mark as Delay
            if ($daysDifference > 60) {
                $handover->status_handover = 'DELAY';
                $handover->saveQuietly(); // Save without triggering events
                $count++;

                $this->info("Handover #{$handover->id} for {$handover->company_name} marked as Delay ({$daysDifference} days since completion)");
                Log::info("Software handover #{$handover->id} automatically marked as Delay after {$daysDifference} days");
            }
        }

        $this->info("Completed. {$count} handovers marked as Delay.");
        return 0;
    }
}
