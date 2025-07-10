<?php

namespace App\Console\Commands;

use App\Models\SoftwareHandover;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPendingConfirmationHandovers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'handovers:check-pending-confirmation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for software handovers in Pending Confirmation status for more than 30 weekdays and mark as Inactive';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking for stale pending confirmation handovers...');

        // Get all handovers with status "Pending Confirmation" that have a pending_confirmation_date
        $handovers = SoftwareHandover::where('status', 'Pending Confirmation')
            ->whereNotNull('pending_confirmation_date')
            ->get();

        $count = 0;

        foreach ($handovers as $handover) {
            $pendingDate = Carbon::parse($handover->pending_confirmation_date);
            $today = Carbon::now();

            // Count only weekdays (exclude weekends)
            $weekdaysDifference = $this->countWeekdaysBetween($pendingDate, $today);

            // If more than 30 weekdays, mark as Inactive
            if ($weekdaysDifference > 30) {
                $handover->status = 'Inactive';
                $handover->saveQuietly(); // Save without triggering events
                $count++;

                $this->info("Handover #{$handover->id} for {$handover->company_name} marked as Inactive ({$weekdaysDifference} weekdays since pending confirmation)");
                Log::info("Software handover #{$handover->id} automatically marked as Inactive after {$weekdaysDifference} weekdays in Pending Confirmation status");
            }
        }

        $this->info("Completed. {$count} handovers marked as Inactive.");
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
