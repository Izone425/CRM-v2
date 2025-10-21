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

class ApolloImport implements ToCollection, WithStartRow, SkipsEmptyRows, WithHeadingRow, WithCustomCsvSettings
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

        $collection->chunk(10)->each(function ($chunk) use (&$importedCount) {
            foreach ($chunk as $row) {
                // Sanitize all row values to handle potential encoding issues
                $sanitizedRow = collect($row)->map(function ($value) {
                    if (is_string($value)) {
                        // Clean and encode as UTF-8 to prevent multibyte character issues
                        return mb_convert_encoding(trim($value), 'UTF-8', 'UTF-8');
                    }
                    return $value;
                })->toArray();

                // Build full name from first name and last name
                $firstName = $sanitizedRow['first_name'] ?? '';
                $lastName = $sanitizedRow['last_name'] ?? '';
                $fullName = trim($firstName . ' ' . $lastName);

                // Skip if no name - email can be null now
                if (empty($fullName)) {
                    Log::warning('Skipping row due to missing name', $sanitizedRow);
                    continue;
                }

                // Clean phone number (remove quotes and extra formatting)
                $phone = $sanitizedRow['company_phone'] ?? '';
                $phone = preg_replace('/[^0-9]/', '', $phone);

                // Set default created time to now in Malaysia timezone
                $createdTime = now()->setTimezone('Asia/Kuala_Lumpur')->format('Y-m-d H:i:s');

                // Get email (can be null)
                $email = !empty($sanitizedRow['email']) ? $sanitizedRow['email'] : null;

                // Create company details
                $companyDetailId = null;
                if (!empty($sanitizedRow['company_name'])) {
                    $companyDetail = CompanyDetail::create([
                        'name' => $fullName,
                        'email' => $email,
                        'contact_no' => $phone,
                        'position' => $sanitizedRow['title'] ?? '',
                        'company_name' => $sanitizedRow['company_name'],
                        'website_url' => $sanitizedRow['website'] ?? '',
                        'linkedin_url' => $sanitizedRow['person_linkedin_url'] ?? '',
                        'state' => $sanitizedRow['state'] ?? '',
                        'created_at' => $createdTime,
                        'updated_at' => $createdTime,
                    ]);
                    $companyDetailId = $companyDetail->id;
                }

                // Create new lead
                $lead = Lead::create([
                    'name' => $fullName,
                    'email' => $email, // Can be null
                    'phone' => $phone,
                    'company_name' => $companyDetailId, // Store the CompanyDetail ID
                    'created_at' => $createdTime,
                    'updated_at' => $createdTime,
                    'products' => '["hr"]',
                    'company_size' => '25-99',
                    'country' => 'Malaysia',
                    'categories' => 'New',
                    'stage' => 'New',
                    'lead_status' => 'None',
                    'lead_code' => 'LinkedIn', // Set lead source as LinkedIn
                ]);

                $importedCount++;

                // Update the company details with the lead ID (establishing the relationship)
                if ($companyDetailId && $lead) {
                    CompanyDetail::where('id', $companyDetailId)->update([
                        'lead_id' => $lead->id
                    ]);
                }

                // Create activity log for tracking
                ActivityLog::create([
                    'lead_id' => $lead->id,
                    'activity' => 'Lead imported from LinkedIn',
                    'description' => "Lead imported from LinkedIn CSV file - Company: {$sanitizedRow['company_name']}, Title: {$sanitizedRow['title']}" . ($email ? ", Email: {$email}" : " (No email provided)"),
                    'created_at' => $createdTime,
                    'updated_at' => $createdTime,
                ]);

                Log::info("Created lead: {$fullName} from {$sanitizedRow['company_name']}" . ($email ? " (Email: {$email})" : " (No email)"));
            }
        });

        Log::info("LinkedIn import completed. Total imported: $importedCount");
    }

    public function startRow(): int
    {
        return 2; // Skip headers
    }
}
