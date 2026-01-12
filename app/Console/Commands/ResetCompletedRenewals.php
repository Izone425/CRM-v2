<?php

namespace App\Console\Commands;

use App\Models\Renewal;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResetCompletedRenewals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'renewals:reset-completed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset completed renewals to new status if they have new licenses starting today or later';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to check completed renewals...');

        $today = Carbon::now()->subDay()->format('Y-m-d');

        try {
            // Get all renewals with completed_renewal status
            $completedRenewals = Renewal::where('renewal_progress', 'completed_renewal')->get();

            $resetCount = 0;

            foreach ($completedRenewals as $renewal) {
                // Check if this company has licenses with f_start_date >= today
                $hasNewLicenses = DB::connection('frontenddb')
                    ->table('crm_expiring_license')
                    ->where('f_company_id', $renewal->f_company_id)
                    ->where('f_start_date', '=', $today)
                    ->where('f_currency', 'MYR')
                    ->exists();

                if ($hasNewLicenses) {
                    // Reset the renewal status to new
                    $renewal->update([
                        'renewal_progress' => 'new',
                        'follow_up_date' => null,
                        'follow_up_counter' => false,
                    ]);

                    $resetCount++;

                    $this->info("Reset renewal for company: {$renewal->company_name} (ID: {$renewal->f_company_id})");

                    Log::info("Renewal reset to new", [
                        'f_company_id' => $renewal->f_company_id,
                        'company_name' => $renewal->company_name,
                        'previous_status' => 'completed_renewal',
                        'new_status' => 'new',
                    ]);
                }
            }

            $this->info("Completed! Reset {$resetCount} renewal(s) to 'new' status.");

            Log::info("Daily renewal reset completed", [
                'total_completed_renewals' => $completedRenewals->count(),
                'reset_count' => $resetCount,
                'date' => $today,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error resetting renewals: {$e->getMessage()}");

            Log::error("Error in renewals:reset-completed command", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
