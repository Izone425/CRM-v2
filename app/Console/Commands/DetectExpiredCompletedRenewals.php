<?php

namespace App\Console\Commands;

use App\Models\Renewal;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DetectExpiredCompletedRenewals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'renewals:detect-expired-completed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset completed renewals to new status if their license has already started';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to reset completed renewals with active licenses...');

        $today = Carbon::now()->format('Y-m-d');

        try {
            // Get all renewals with completed_renewal status
            $completedRenewals = Renewal::where('renewal_progress', 'completed_renewal')->get();

            $resetCount = 0;
            $resetCompanies = [];

            foreach ($completedRenewals as $renewal) {
                // Get the latest license for this company (by start date)
                $latestLicense = DB::connection('frontenddb')
                    ->table('crm_expiring_license')
                    ->where('f_company_id', $renewal->f_company_id)
                    ->where('f_currency', 'MYR')
                    ->orderBy('f_start_date', 'desc')
                    ->first();

                if (!$latestLicense) {
                    continue; // No license found
                }

                // Check if the latest license has already started (start date has passed)
                $licenseAlreadyStarted = $latestLicense->f_start_date < $today;
                $isExpired = $latestLicense->f_expiry_date < $today;

                // Only detect if license started AND still active (not expired)
                if ($licenseAlreadyStarted && !$isExpired) {
                    $daysSinceStart = Carbon::parse($latestLicense->f_start_date)->diffInDays($today);

                    // Update the renewal status to new
                    $renewal->update([
                        'renewal_progress' => 'new',
                        'follow_up_date' => null,
                        'follow_up_counter' => false,
                    ]);

                    $resetCount++;

                    $companyInfo = [
                        'company_id' => $renewal->f_company_id,
                        'company_name' => $renewal->company_name,
                        'previous_status' => 'completed_renewal',
                        'new_status' => 'new',
                        'license_start_date' => $latestLicense->f_start_date,
                        'license_expiry_date' => $latestLicense->f_expiry_date,
                        'days_since_start' => $daysSinceStart,
                        'license_created_at' => $latestLicense->f_created_time ?? 'N/A',
                    ];

                    $resetCompanies[] = $companyInfo;

                    $this->info("Reset renewal for company: {$renewal->company_name} (ID: {$renewal->f_company_id})");
                    $this->info("  License: {$latestLicense->f_start_date} to {$latestLicense->f_expiry_date} (Active, started {$daysSinceStart} days ago)");

                    Log::info("Renewal reset to new (active license detected)", $companyInfo);
                }
            }

            $this->info("\n=== Summary ===");
            $this->info("Total completed renewals checked: {$completedRenewals->count()}");
            $this->info("Renewals reset to 'new': {$resetCount}");

            Log::info("Completed renewals with active licenses reset", [
                'total_completed_renewals' => $completedRenewals->count(),
                'reset_count' => $resetCount,
                'date' => $today,
                'reset_companies' => $resetCompanies,
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Error detecting renewals: {$e->getMessage()}");

            Log::error("Error in renewals:detect-expired-completed command", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return Command::FAILURE;
        }
    }
}
