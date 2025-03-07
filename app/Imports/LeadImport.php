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

class LeadImport implements ToCollection, WithStartRow, SkipsEmptyRows, WithHeadingRow
{
    public function collection(Collection $collection)
    {
        foreach ($collection as $row) {
            if (!empty($row['created_time'])) {
                $createdTime = Carbon::parse($row['created_time'])->toDateTimeString();

                // ✅ Default values
                $categories = null;
                $stage = null;
                $lead_status = null;

                // ✅ Check if Status Division is exactly "(2) Active - 24 Below"
                if (isset($row['status_division'])) {
                    $status = trim($row['status_division']);

                    if ($status === '(1) Active - 25 Above' || $status === '(2) Active - 24 Below') {
                        $categories = 'Active';
                        $stage = 'Transfer';
                        $lead_status = 'Under Review';
                    } elseif ($status === '(3) Follow Up - 25 Above' || $status === '(3) Follow Up - 24 Below') {
                        $categories = 'Active';
                        $stage = 'Transfer';
                        $lead_status = 'Under Review';
                    } elseif ($status === '(5) No Response - 25 Above' || $status === '(6) No Response - 24 Below') {
                        $categories = 'Inactive';
                        $stage = null;
                        $lead_status = 'No Response';
                    } elseif ($status === '(7) Leads - Junk') {
                        $categories = 'Inactive';
                        $stage = null;
                        $lead_status = 'Junk';
                    } elseif ($status === '(8) Leads - On Hold') {
                        $categories = 'Inactive';
                        $stage = null;
                        $lead_status = 'On Hold';
                    } else {
                        // Default values if no conditions match
                        $categories = null;
                        $stage = null;
                        $lead_status = null;
                    }
                }

                // ✅ Check if company exists in CompanyDetail table, otherwise create it
                $company = null;
                $company = CompanyDetail::firstOrCreate(
                    ['company_name' => $row['company']], // Find by company name
                    ['company_name' => $row['company']]  // If not found, create it
                );

                $leadOwner = ($this->normalizeCompanySize($row['company_size'] ?? null) === '1-24') ? 'Siti Afifah' : 'Nurul Najaa Nadiah';

                // ✅ Insert or update lead, storing company_id instead of company_name
                $newLead = Lead::updateOrCreate(
                    ['zoho_id' => isset($row['record_id']) ? preg_replace('/[^0-9]/', '', $row['record_id']) : null],
                    [
                        'name'         => $row['last_name'] ?? null,
                        'email'        => $row['email'] ?? null,
                        'phone'        => $row['phone'] ?? null,
                        'company_name'   => $company->id ?? null, // ✅ Store the company ID in Lead table
                        'company_size' => $this->normalizeCompanySize($row['company_size'] ?? null), // ✅ Normalize company size
                        'country'      => $row['country'] ?? null,
                        'lead_code'    => $row['lead_source'] ?? null,
                        'categories'   => $categories,  // ✅ Set to 'Active' if condition met
                        'stage'        => $stage,       // ✅ Set to 'Transfer' if condition met
                        'lead_status'  => $lead_status, // ✅ Set to 'Under Review' if condition met
                        'lead_owner'   => $leadOwner,
                        'created_at'   => $createdTime,
                        'updated_at'   => now(),
                    ]
                );

                if ($company && empty($company->lead_id)) {
                    $company->update(['lead_id' => $newLead->id]);
                }

                ReferralDetail::create([
                    'lead_id'     => $newLead->id,
                    'company'     => $row['Referee_Company_Name'] ?? null,
                    'name'        => $row['referrername'] ?? null,
                    'email'       => $row['Referee_Email'] ?? null,
                    'contact_no'  => $row['Referee_Phone'] ?? null,
                    'created_at'  => $leadCreatedTime ?? now(),
                    'updated_at'  => now(),
                ]);

                UtmDetail::create([
                    'lead_id'       => $newLead->id,
                    'utm_campaign'  => $row['utm_campaign'] ?? null,
                    'utm_adgroup'   => $row['utm_adgroup'] ?? null,
                    'utm_creative'  => $row['utm_creative'] ?? null,
                    'utm_term'      => $row['utm_term'] ?? null,
                    'utm_matchtype' => $row['utm_matchtype'] ?? null,
                    'device'        => $row['device'] ?? null,
                    'social_lead_id'=> $row['social_lead_id'] ?? null,
                ]);

                $latestActivityLog = ActivityLog::where('subject_id', $newLead->id)
                    ->orderByDesc('created_at')
                    ->first();

                // ✅ Update the latest activity log description
                if ($latestActivityLog) {
                    $latestActivityLog->update([
                        'description' => 'Leads Migration',
                    ]);
                }
            }
        }

        Log::info("CSV Import Completed Successfully.");
    }

    public function startRow(): int
    {
        return 2; // ✅ Skip headers
    }

    private function normalizeCompanySize($size)
    {
        if (!$size) {
            return null;
        }

        // Remove extra spaces and normalize the value
        $normalizedSize = preg_replace('/\s+/', '', $size); // Removes all spaces

        $sizeMappings = [
            ['variants' => ['1-24', '1- 24', '1 -24', '1 - 24'], 'normalized' => '1-24'],
            ['variants' => ['25-99', '25- 99', '25 -99', '25 - 99'], 'normalized' => '25-99'],
            ['variants' => ['100-500', '100- 500', '100 -500', '100 - 500'], 'normalized' => '100-500'],
            ['variants' => ['501andAbove', '501-and-Above', '501 and Above'], 'normalized' => '501 and Above'],
        ];

        foreach ($sizeMappings as $mapping) {
            if (in_array($normalizedSize, $mapping['variants'])) {
                return $mapping['normalized'];
            }
        }

        return 'Unknown'; // ✅ Fallback if not recognized
    }
}
