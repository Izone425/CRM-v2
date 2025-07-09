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
    protected $description = 'Check for software handovers that are delayed (more than 60 weekdays since completion)';

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

            // Count only weekdays (exclude weekends)
            $weekdaysDifference = $this->countWeekdaysBetween($completedDate, $today);

            // If more than 60 weekdays, mark as Delay
            if ($weekdaysDifference > 60) {
                $handover->status_handover = 'DELAY';
                $handover->saveQuietly(); // Save without triggering events
                $count++;

                $this->info("Handover #{$handover->id} for {$handover->company_name} marked as Delay ({$weekdaysDifference} weekdays since completion)");
                Log::info("Software handover #{$handover->id} automatically marked as Delay after {$weekdaysDifference} weekdays");
            }
        }

        $this->info("Completed. {$count} handovers marked as Delay.");
        return 0;
    }

    /**
     * Count only weekdays between two dates
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return int
     */
    private function countWeekdaysBetween(Carbon $startDate, Carbon $endDate): int
    {
        $weekdayCount = 0;
        $currentDate = $startDate->copy();

        // Iterate through each day and count only weekdays
        while ($currentDate->lte($endDate)) {
            // Check if the current day is not Saturday (6) and not Sunday (0)
            if ($currentDate->dayOfWeek !== Carbon::SATURDAY && $currentDate->dayOfWeek !== Carbon::SUNDAY) {
                $weekdayCount++;
            }
            $currentDate->addDay();
        }

        return $weekdayCount;
    }
}
