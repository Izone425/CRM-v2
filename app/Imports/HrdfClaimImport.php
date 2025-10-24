<?php

namespace App\Imports;

use App\Models\HrdfClaim;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HrdfClaimImport implements ToCollection, WithStartRow, SkipsEmptyRows, WithHeadingRow, WithCustomCsvSettings
{
    /**
     * Configure CSV settings to handle encoding properly
     */
    public function getCsvSettings(): array
    {
        return [
            'input_encoding' => 'UTF-8',
            'delimiter' => ',',
            'enclosure' => '"',
        ];
    }

    public function collection(Collection $collection)
    {
        $importedCount = 0;
        $skippedCount = 0;
        $duplicateCount = 0;

        Log::info('Starting HRDF Claim import', ['total_rows' => $collection->count()]);

        $collection->chunk(10)->each(function ($chunk) use (&$importedCount, &$skippedCount, &$duplicateCount) {
            foreach ($chunk as $row) {
                try {
                    // Sanitize all row values to handle potential encoding issues
                    $sanitizedRow = collect($row)->map(function ($value) {
                        if (is_string($value)) {
                            // Clean and encode as UTF-8 to prevent multibyte character issues
                            return mb_convert_encoding(trim($value), 'UTF-8', 'UTF-8');
                        }
                        return $value;
                    })->toArray();

                    // Map the column headers to our expected keys
                    $mappedRow = $this->mapRowData($sanitizedRow);

                    // Skip if no company name or HRDF grant ID
                    if (empty($mappedRow['company_name']) || empty($mappedRow['hrdf_grant_id'])) {
                        Log::warning('Skipping row due to missing required fields', [
                            'company_name' => $mappedRow['company_name'] ?? 'missing',
                            'hrdf_grant_id' => $mappedRow['hrdf_grant_id'] ?? 'missing'
                        ]);
                        $skippedCount++;
                        continue;
                    }

                    // Check for duplicates based on HRDF Grant ID
                    $existingClaim = HrdfClaim::where('hrdf_grant_id', $mappedRow['hrdf_grant_id'])->first();
                    if ($existingClaim) {
                        Log::info('Duplicate HRDF claim found, skipping', [
                            'hrdf_grant_id' => $mappedRow['hrdf_grant_id'],
                            'company_name' => $mappedRow['company_name']
                        ]);
                        $duplicateCount++;
                        continue;
                    }

                    // Create new HRDF claim
                    $hrdfClaim = HrdfClaim::create([
                        'sales_person' => $mappedRow['sales_person'],
                        'company_name' => $mappedRow['company_name'],
                        'invoice_amount' => $mappedRow['invoice_amount'],
                        'invoice_number' => $mappedRow['invoice_number'],
                        'sales_remark' => $mappedRow['sales_remark'],
                        'claim_status' => $mappedRow['claim_status'],
                        'hrdf_grant_id' => $mappedRow['hrdf_grant_id'],
                        'hrdf_training_date' => $mappedRow['hrdf_training_date'],
                        'hrdf_claim_id' => $mappedRow['hrdf_claim_id'],
                        'programme_name' => $mappedRow['programme_name'] ?? 'Imported Programme',
                        'approved_date' => $mappedRow['approved_date'],
                        'email_processed_at' => now(),
                    ]);

                    $importedCount++;

                    Log::info("Created HRDF claim: {$mappedRow['company_name']}", [
                        'hrdf_grant_id' => $mappedRow['hrdf_grant_id'],
                        'claim_status' => $mappedRow['claim_status'],
                        'invoice_amount' => $mappedRow['invoice_amount']
                    ]);

                } catch (\Exception $e) {
                    Log::error("Error importing HRDF claim row", [
                        'error' => $e->getMessage(),
                        'row_data' => $sanitizedRow ?? []
                    ]);
                    $skippedCount++;
                }
            }
        });

        Log::info("HRDF Claim import completed", [
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'duplicates' => $duplicateCount,
            'total' => $importedCount + $skippedCount + $duplicateCount
        ]);
    }

    /**
     * Map CSV row data to our expected array keys
     */
    private function mapRowData(array $row): array
    {
        // Parse invoice amount - remove RM, spaces, and convert to decimal
        $invoiceAmount = 0;
        if (isset($row['invoice_amount'])) {
            $amount = str_replace(['RM', ',', ' '], '', $row['invoice_amount']);
            $invoiceAmount = is_numeric($amount) ? (float) $amount : 0;
        }

        // Parse training date - ensure proper format
        $trainingDate = '';
        if (isset($row['hrdf_training_date'])) {
            $trainingDate = trim($row['hrdf_training_date']);
        }

        // Determine approved date based on claim status
        $approvedDate = null;
        if (isset($row['claim_status']) && in_array(strtoupper($row['claim_status']), ['APPROVED', 'RECEIVED', 'SUBMITTED'])) {
            $approvedDate = now()->toDateString(); // Set to today if already approved/received
        }

        return [
            'sales_person' => $row['sales_person'] ?? 'IMPORTED',
            'company_name' => strtoupper($row['company_name'] ?? ''),
            'invoice_amount' => $invoiceAmount,
            'invoice_number' => $row['invoice_number'] ?? null,
            'sales_remark' => $row['sales_remark'] ?? 'Imported from CSV',
            'claim_status' => strtoupper($row['claim_status'] ?? 'PENDING'),
            'hrdf_grant_id' => $row['hrdf_grant_id'] ?? '',
            'hrdf_training_date' => $trainingDate,
            'hrdf_claim_id' => $row['hrdf_claim_id'] ?? null,
            'programme_name' => 'Imported Programme', // Default since not in CSV
            'approved_date' => $approvedDate,
        ];
    }

    public function startRow(): int
    {
        return 2; // Skip headers
    }
}
