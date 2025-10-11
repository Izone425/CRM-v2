<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Renewal;
use App\Models\Lead;
use App\Models\CompanyDetail;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoMappingRenewalCommand extends Command
{
    protected $signature = 'renewal:auto-mapping';
    protected $description = 'Automatically map companies that don\'t exist in renewal table using before_handover mapping';

    // Define excluded products (same as in AdminRenewalProcessDataMyr)
    protected static $excludedProducts = [
        'TimeTec VMS Corporate (1 Floor License)',
        'TimeTec VMS SME (1 Location License)',
        'TimeTec Patrol (1 Checkpoint License)',
        'TimeTec Patrol (10 Checkpoint License)',
        'Other',
        'TimeTec Profile (10 User License)',
    ];

    public function handle()
    {
        $this->info('Starting auto-mapping process...');

        try {
            // Get companies that need mapping
            $companiesNeedingMapping = $this->getCompaniesNeedingMapping();

            if ($companiesNeedingMapping->isEmpty()) {
                $this->info('No companies found that need auto-mapping.');
                return Command::SUCCESS;
            }

            $this->info("Found {$companiesNeedingMapping->count()} companies that need auto-mapping.");

            $successCount = 0;
            $errorCount = 0;

            foreach ($companiesNeedingMapping as $company) {
                try {
                    $this->info("Processing company: {$company->f_company_name} (ID: {$company->f_company_id})");

                    // Create lead using before_handover mapping method
                    $lead = $this->createLeadForCompany($company);

                    if ($lead) {
                        // Create renewal record
                        $this->createRenewalRecord($company, $lead->id);

                        $successCount++;
                        $this->info("✓ Successfully mapped company: {$company->f_company_name}");
                    } else {
                        $errorCount++;
                        $this->error("✗ Failed to create lead for company: {$company->f_company_name}");
                    }

                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("✗ Error processing company {$company->f_company_name}: {$e->getMessage()}");
                    Log::error("Auto-mapping error for company {$company->f_company_id}: {$e->getMessage()}");
                }
            }

            $this->info("Auto-mapping completed: {$successCount} successful, {$errorCount} errors");

            // Log the summary
            Log::info("Auto-mapping summary: {$successCount} successful, {$errorCount} errors");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("Auto-mapping process failed: {$e->getMessage()}");
            Log::error("Auto-mapping process error: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    protected function getCompaniesNeedingMapping()
    {
        $today = Carbon::now()->format('Y-m-d');
        $next90Days = Carbon::now()->addDays(90)->format('Y-m-d');

        // Get companies from crm_expiring_license that don't exist in renewal table
        $query = DB::connection('frontenddb')->table('crm_expiring_license')
            ->select([
                'f_company_id',
                DB::raw('ANY_VALUE(f_company_name) as f_company_name'),
                DB::raw('MIN(f_expiry_date) as earliest_expiry'),
                DB::raw('COUNT(*) as license_count')
            ])
            ->whereBetween('f_expiry_date', [$today, $next90Days])
            ->whereRaw('f_expiry_date >= ?', [$today]);

        // Apply product exclusions
        foreach (self::$excludedProducts as $excludedProduct) {
            $query->where('f_name', 'NOT LIKE', '%' . $excludedProduct . '%');
        }

        // Group by company and get companies not in renewal table
        $expiring_companies = $query->groupBy('f_company_id')
            ->having(DB::raw('COUNT(*)'), '>', 0)
            ->get();

        // Filter out companies that already exist in renewal table
        $existingCompanyIds = Renewal::pluck('f_company_id')->toArray();

        return $expiring_companies->filter(function ($company) use ($existingCompanyIds) {
            return !in_array($company->f_company_id, $existingCompanyIds);
        });
    }

    protected function createLeadForCompany($company)
    {
        try {
            // Get the latest lead ID to determine the next one
            $latestLeadId = Lead::max('id') ?? 0;
            $nextLeadId = $latestLeadId + 1;

            // Create CompanyDetail first
            $companyDetail = CompanyDetail::create([
                'company_name' => strtoupper(trim($company->f_company_name)),
                'lead_id' => $nextLeadId,
            ]);

            // Create Lead with default values for auto-mapping
            $lead = Lead::create([
                'company_name' => $companyDetail->id, // Store CompanyDetail ID
                'name' => '-', // Default name for auto-mapped leads
                'email' => 'fatimah.tarmizi@timeteccloud.com', // Default email
                'phone' => '0', // Default phone
                'company_size' => '1-24', // Default to SMALL
                'country' => 'Malaysia', // Default to Malaysia
                'lead_owner' => 'Fatimah Nurnabilah', // Mark as auto renewal
                'lead_code' => 'Existing Customer (Migration)',
                'products' => 'hr', // Default to HR products
                'status' => 'new',
                'f_company_id' => $company->f_company_id, // Link to renewal data
            ]);

            // Update activity log for renewal mapping
            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                ->orderByDesc('created_at')
                ->first();

            if ($latestActivityLog) {
                $latestActivityLog->update([
                    'description' => 'Auto-mapped lead created for renewal process',
                    'causer_id' => 1, // System user ID
                ]);
            }

            // Auto-assign and close the lead (for auto-mapping)
            sleep(1);
            $lead->update([
                'categories' => 'Inactive',
                'stage' => null,
                'lead_status' => 'Closed',
                'pickup_date' => now(),
                'closing_date' => now(),
            ]);

            // Update activity log for assignment and closure
            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                ->orderByDesc('id')
                ->first();

            if ($latestActivityLog) {
                $latestActivityLog->update([
                    'subject_id' => $lead->id,
                    'description' => 'Lead auto-assigned to Fatimah Nurnabilah and marked as Closed',
                ]);
            }

            return $lead;

        } catch (\Exception $e) {
            Log::error("Error creating lead for auto-mapping company {$company->f_company_id}: {$e->getMessage()}");
            return null;
        }
    }

    protected function createRenewalRecord($company, $leadId)
    {
        try {
            Renewal::create([
                'f_company_id' => $company->f_company_id,
                'lead_id' => $leadId,
                'company_name' => $company->f_company_name,
                'mapping_status' => 'completed_mapping',
                'renewal_progress' => 'new',
                'admin_renewal' => 'Fatimah Nurnabilah',
                'follow_up_date' => now(),
                'follow_up_counter' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Error creating renewal record for company {$company->f_company_id}: {$e->getMessage()}");
            return false;
        }
    }
}
