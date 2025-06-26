<?php

namespace App\Console\Commands;

use App\Models\AdminRepair;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPendingRepairStatus extends Command
{
    protected $signature = 'repair:check-pending-status';
    protected $description = 'Check repair records in Pending Confirmation status for more than 30 days and mark them as Inactive';

    public function handle()
    {
        $cutoffDate = Carbon::now()->subDays(30);

        // Query repairs with pending_confirmation_date older than 30 days
        $pendingRepairs = AdminRepair::where('status', 'Pending Confirmation')
            ->whereNotNull('pending_confirmation_date')
            ->where('pending_confirmation_date', '<', $cutoffDate)
            ->get();

        $count = 0;

        foreach ($pendingRepairs as $repair) {
            $repair->update([
                'status' => 'Inactive',
            ]);

            $count++;
        }

        $this->info("Updated $count repair records from 'Pending Confirmation' to 'Inactive'");
        Log::info("Scheduled task: Updated $count repair records from 'Pending Confirmation' to 'Inactive'");

        return Command::SUCCESS;
    }
}
