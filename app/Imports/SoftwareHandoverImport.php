<?php

namespace App\Imports;

use App\Models\Lead;
use App\Models\User;
use App\Models\SoftwareHandover;
use App\Models\CompanyDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SoftwareHandoverImport implements ToCollection, WithHeadingRow
{
    protected $rowCount = 0;
    protected $successCount = 0;
    protected $skipCount = 0;
    protected $errorCount = 0;

    public function collection(Collection $rows)
    {
        $skippedCompanies = [];

        foreach ($rows as $row) {
            $this->rowCount++;
            try {
                // Skip empty rows
                // if (empty($row['company_name'])) {
                //     $this->skipCount++;
                //     // Log skipped row index
                //     Log::info("Skipped empty row #{$this->rowCount}");
                //     continue;
                // }

                $companyName = trim($row['company_name']);

                // // For existing records with specific statuses that should be skipped
                // $status = $row['status'] ?? '';
                // if (in_array($status, ['DELAY', 'CLOSED', 'INACTIVE'])) {
                //     $this->skipCount++;
                //     $skippedCompanies[] = $companyName;

                //     // Log each skipped company individually
                //     Log::info("Skipped company: {$companyName} with status: {$status}");
                //     continue;
                // }

                // Find salesperson by name
                $salespersonName = trim($row['sales_pic'] ?? '');
                $salesperson = User::where('name', 'like', "%{$salespersonName}%")
                    ->where('role_id', 2) // Assuming role_id 2 is for salespersons
                    ->first();

                // Find implementer by name
                $implementerName = trim($row['implementer'] ?? '');
                $implementer = User::where('name', 'like', "%{$implementerName}%")
                    ->where('role_id', 4) // Assuming role_id 4 is for implementers
                    ->first();

                // // Create or find lead
                // $lead = Lead::firstOrCreate(
                //     ['company_id' => $companyDetail->id],
                //     [
                //         'lead_status' => $row['status'] ?? 'Closed',
                //         'salesperson' => $salesperson ? $salesperson->id : null,
                //     ]
                // );

                // Parse dates
                $dbCreationDate = $this->parseDate($row['db_creation'] ?? null);
                $goLiveDate = $this->parseDate($row['go_live_date'] ?? null);

                // Parse training dates
                $kickoffDate = $this->parseDate($row['on9_kick_off_meeting'] ?? null);
                $webinarDate = $this->parseDate($row['on9_webinar_training'] ?? null);

                // Calculate module selection based on X markers
                $ta = $this->convertModuleStatus($row['ta'] ?? null);
                $tl = $this->convertModuleStatus($row['tl'] ?? null);
                $tc = $this->convertModuleStatus($row['tc'] ?? null);
                $tp = $this->convertModuleStatus($row['tp'] ?? null);

                // Create the software handover record
                $handover = SoftwareHandover::create(
                    [
                        'company_name' => $companyName,
                        'lead_id' => null,
                        'headcount' => $row['hc'] ?? 0,
                        'status' => 'Completed', // Since these are all completed handovers
                        'status_handover' => $row['status'] ?? 'Completed',
                        'completed_at' => $dbCreationDate ?? now(),
                        'go_live_date' => $goLiveDate ?? now(),
                        'salesperson' => $salesperson ? $salesperson->name : ($row['sales_pic'] ?? null),
                        'implementer' => $implementer ? $implementer->name : ($row['implementer'] ?? null),
                        'payroll_code' => $row['payroll_code'] ?? null,
                        'webinar_training' => $webinarDate,
                        'kick_off_meeting' => $kickoffDate,

                        // Module selection
                        'ta' => $ta,
                        'tl' => $tl,
                        'tc' => $tc,
                        'tp' => $tp,
                    ]
                );
                $this->successCount++;

                Log::info($handover);
            } catch (\Exception $e) {
                $this->errorCount++;
                Log::error('Error importing software handover: ' . $e->getMessage(), [
                    'row' => $row,
                    'exception' => $e
                ]);
            }
        }

        // Log the summary of skipped companies
        if (count($skippedCompanies) > 0) {
            Log::info("Skipped companies summary:", [
                'count' => count($skippedCompanies),
                'companies' => $skippedCompanies
            ]);
        }

        Log::info("Software Handover Import completed. Total: {$this->rowCount}, Success: {$this->successCount}, Skipped: {$this->skipCount}, Errors: {$this->errorCount}");
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($dateString)
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            Log::warning("Failed to parse date: {$dateString}", ['exception' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Convert X or / to boolean value
     */
    private function convertModuleStatus($value)
    {
        if (empty($value)) {
            return false;
        }

        $value = strtoupper(trim($value));
        return ($value === '/' || $value === 'YES' || $value === 'TRUE' || $value === '1');
    }
}
