<?php

namespace App\Imports;

use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\CompanyDetail;
use App\Models\ReferralDetail;
use App\Models\UtmDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class LeadImport implements ToCollection, WithStartRow, SkipsEmptyRows, WithHeadingRow, WithCustomCsvSettings
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
        $updatedCount = 0;

        $collection->chunk(10)->each(function ($chunk) use (&$importedCount, &$updatedCount) {
            foreach ($chunk as $row) {
                // Sanitize all row values to handle potential encoding issues
                $sanitizedRow = collect($row)->map(function ($value) {
                    if (is_string($value)) {
                        // Clean and encode as UTF-8 to prevent multibyte character issues
                        return mb_convert_encoding(trim($value), 'UTF-8', 'UTF-8');
                    }
                    return $value;
                })->toArray();

                // Create or update lead record first
                $createdTime = null;
                if (!empty($sanitizedRow['created_time'])) {
                    try {
                        // Try to parse the date from the Excel sheet
                        if (is_numeric($sanitizedRow['created_time'])) {
                            // Handle Excel numeric date format
                            $createdTime = Date::excelToDateTimeObject($sanitizedRow['created_time']);
                            $createdTime->setTimezone(new \DateTimeZone('Asia/Kuala_Lumpur'));
                            $createdTime = $createdTime->format('Y-m-d H:i:s');
                        } else {
                            // Handle string date format and ensure it's in Malaysia time (UTC+8)
                            $createdTime = Carbon::parse($sanitizedRow['created_time'])
                                ->setTimezone('Asia/Kuala_Lumpur')
                                ->format('Y-m-d H:i:s');
                        }
                    } catch (\Exception $e) {
                        $createdTime = now()->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s');
                        Log::warning("Error parsing date: " . $e->getMessage());
                    }
                }

                // Format company size correctly
                $companySize = null;
                if (!empty($sanitizedRow['company_size'])) {
                    // Replace underscores with proper formatting
                    $companySize = match ($sanitizedRow['company_size']) {
                        '1_-_24' => '1-24',
                        '25_-_99' => '25-99',
                        '100_-_500' => '100-500',
                        '501_and_above' => '501 and Above',
                        default => $sanitizedRow['company_size'] // Keep original if it doesn't match any pattern
                    };
                }

                // Check if lead exists by lead_id from the excel
                $existingLead = null;
                if (!empty($sanitizedRow['lead_id'])) {
                    // Clean lead_id by removing 'l:' prefix
                    $cleanLeadId = $sanitizedRow['lead_id'];
                    if (is_string($cleanLeadId) && strpos($cleanLeadId, 'l:') === 0) {
                        $cleanLeadId = substr($cleanLeadId, 2); // Remove 'l:' prefix
                    }
                    $existingLead = Lead::where('zoho_id', $cleanLeadId)->first();
                }

                // First, create or find the company details
                $companyDetailId = null;
                if (!empty($sanitizedRow['company_name'])) {
                    // Create company details first to get the ID
                    $companyDetail = CompanyDetail::create([
                        'company_name' => $sanitizedRow['company_name'] ?? '',
                        'created_at' => $createdTime ?? now()->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
                        'updated_at' => now()->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
                    ]);

                    // Store the company detail ID for the lead
                    $companyDetailId = $companyDetail->id;
                }

                // Create or update lead with company_name field storing the CompanyDetail ID
                $lead = Lead::updateOrCreate(
                    [
                        'name' => $sanitizedRow['name'] ?? '',
                        'email' => $sanitizedRow['email'] ?? '',
                        'phone' => $sanitizedRow['phone'] ?? '',
                    ],
                    [
                        'company_name' => $companyDetailId, // Store the CompanyDetail ID here
                        'company_size' => $companySize ?? '',
                        'lead_code' => $sanitizedRow['lead_source'] ?? '',
                        'created_at' => $createdTime ?? now()->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
                        'updated_at' => now()->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
                        'products' => '["hr"]',
                        'country' => 'Malaysia',
                        'categories' => 'New',
                        'stage' => 'New',
                        'lead_status' => 'None',
                    ]
                );

                // Update the company details with the lead ID (establishing the relationship in both directions)
                if ($companyDetailId) {
                    CompanyDetail::where('id', $companyDetailId)->update([
                        'lead_id' => $lead->id
                    ]);
                }

                if (!$existingLead) {
                    $importedCount++;
                } else {
                    $updatedCount++;
                }

                // Create or update UTM details
                if (!empty($sanitizedRow['campaign_id']) || !empty($sanitizedRow['adset_id']) || !empty($sanitizedRow['ad_id']) || !empty($sanitizedRow['platform'])) {
                    // Clean up UTM parameters by removing prefixes
                    $campaignId = $sanitizedRow['campaign_id'] ?? '';
                    $adsetId = $sanitizedRow['adset_id'] ?? '';
                    $adId = $sanitizedRow['ad_id'] ?? '';
                    $leadId = $sanitizedRow['lead_id'] ?? '';

                    // Remove 'c:' prefix from campaign_id
                    if (is_string($campaignId) && strpos($campaignId, 'c:') === 0) {
                        $campaignId = substr($campaignId, 2);
                    }

                    // Remove 'as:' prefix from adset_id
                    if (is_string($adsetId) && strpos($adsetId, 'as:') === 0) {
                        $adsetId = substr($adsetId, 3);
                    }

                    // Remove 'ag:' prefix from ad_id
                    if (is_string($adId) && strpos($adId, 'ag:') === 0) {
                        $adId = substr($adId, 3);
                    }

                    // Remove 'l:' prefix from lead_id
                    if (is_string($leadId) && strpos($leadId, 'l:') === 0) {
                        $leadId = substr($leadId, 2);
                    }

                    UtmDetail::updateOrCreate(
                        ['lead_id' => $lead->id],
                        [
                            'utm_campaign' => $campaignId,
                            'device' => $sanitizedRow['platform'] ?? '',
                            'utm_adgroup' => $adsetId,
                            'utm_creative' => $adId,
                            'social_lead_id' => $leadId,
                            'created_at' => $createdTime ?? now()->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
                            'updated_at' => now()->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s'),
                        ]
                    );
                }
            }
        });

        Log::info("Lead import completed in chunks of 10. Imported: $importedCount, Updated: $updatedCount");
    }

    public function startRow(): int
    {
        return 2; // Skip headers
    }
}
