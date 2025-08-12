<?php

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\CompanyDetail;
use App\Models\SoftwareHandover;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessOrphanSoftwareHandovers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'software:process-orphans';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process software handovers without lead IDs by creating leads and company details';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting to process orphan software handovers...');
        Log::info('Starting to process orphan software handovers');

        // Find all software handovers with null lead_id
        $orphanHandovers = SoftwareHandover::whereNull('lead_id')->get();
        $count = $orphanHandovers->count();

        $this->info("Found {$count} software handovers without lead IDs");
        Log::info("Found {$count} software handovers without lead IDs");

        if ($count === 0) {
            $this->info('No orphaned handovers to process.');
            return 0;
        }

        // Process each orphaned handover
        $processed = 0;
        $failed = 0;

        DB::beginTransaction();
        try {
            foreach ($orphanHandovers as $handover) {
                $this->info("Processing handover ID: {$handover->id}");

                // Extract company name from handover data if available, otherwise use a placeholder
                $companyName = $handover->company_name ?? "Company for Handover #{$handover->id}";

                // Convert numeric company_size to the appropriate string range
                $companySizeString = $this->convertCompanySize($handover->company_size);

                // Find the user ID from the salesperson name in the handover
                $salespersonId = null;
                if ($handover->salesperson) {
                    // Try to find a user with matching name
                    $salespersonUser = User::where('name', $handover->salesperson)->first();
                    if ($salespersonUser) {
                        $salespersonId = $salespersonUser->id;
                        $this->info("Found salesperson user ID: {$salespersonId} for name: {$handover->salesperson}");
                    } else {
                        $this->info("Could not find user with name: {$handover->salesperson}, setting salesperson to null");
                    }
                }

                // Create a new lead
                $lead = new Lead();
                $lead->lead_code = 'Existing Customer';
                $lead->categories = 'Inactive';
                $lead->lead_status = 'Closed'; // Since it already has a software handover
                $lead->salesperson = $salespersonId;
                $lead->lead_source_id = 1; // Default lead source
                $lead->industry_id = 1; // Default industry
                $lead->company_size = $companySizeString; // Set the converted company size
                $lead->follow_up_date = now();
                $lead->last_status_update = now();
                $lead->save();

                $this->info("Created lead ID: {$lead->id}");

                // Create company details
                $companyDetail = new CompanyDetail();
                $companyDetail->lead_id = $lead->id;
                $companyDetail->company_name = $companyName; // Store company name in company_details table
                $companyDetail->created_at = $handover->created_at ?? now();
                $companyDetail->updated_at = now();
                $companyDetail->save();

                $this->info("Created company detail ID: {$companyDetail->id}");

                // Update the lead with the company_detail_id
                $lead->company_name = $companyDetail->id;
                $lead->save();
                $this->info("Updated lead with company_detail_id: {$companyDetail->id}");

                // Update the software handover with the new lead ID
                $handover->lead_id = $lead->id;
                $handover->save();

                $processed++;

                $this->info("Successfully processed handover ID: {$handover->id}");
                Log::info("Created lead and company detail for orphaned software handover ID: {$handover->id}", [
                    'handover_id' => $handover->id,
                    'lead_id' => $lead->id,
                    'company_id' => $companyDetail->id,
                    'company_name' => $companyName,
                    'company_size' => $companySizeString
                ]);
            }

            DB::commit();
            $this->info("Successfully processed {$processed} orphaned software handovers");
            Log::info("Successfully processed {$processed} orphaned software handovers");

        } catch (\Exception $e) {
            DB::rollBack();
            $failed = $count - $processed;

            $this->error("Error processing orphaned software handovers: {$e->getMessage()}");
            Log::error("Error processing orphaned software handovers", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'processed' => $processed,
                'failed' => $failed
            ]);

            return 1;
        }

        return 0;
    }

    /**
     * Convert numeric company size to string range format
     *
     * @param int|null $size Numeric company size
     * @return string Company size range as string
     */
    private function convertCompanySize($size)
    {
        if ($size === null) {
            return null; // Return null if no company size provided
        }

        // Convert integer company size to string range
        if ($size >= 1 && $size <= 24) {
            return '1-24';
        } elseif ($size >= 25 && $size <= 99) {
            return '25-99';
        } elseif ($size >= 100 && $size <= 500) {
            return '100-500';
        } elseif ($size > 500) {
            return '501 and Above';
        } else {
            return null; // Handle zero or negative cases
        }
    }
}
