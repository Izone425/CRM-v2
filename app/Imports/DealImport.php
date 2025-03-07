<?php

namespace App\Imports;

use App\Models\ActivityLog;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\ReferralDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DealImport implements ToCollection, WithStartRow, SkipsEmptyRows, WithHeadingRow
{
    public function collection(Collection $collection)
    {
        Log::info("Importing " . count($collection) . " leads.");
        foreach ($collection as $row) {
            $createdTime = Carbon::parse($row['lead_created'] ?? now())->toDateTimeString();

            Log::info("Processing lead: " . json_encode($row));

            // ✅ Default values
            $categories = null;
            $stage = null;
            $lead_status = null;

            // ✅ Check if Status Division is exactly "(2) Active - 24 Below"
            if (isset($row['stage'])) {
                $status = trim($row['stage']);

                if ($status === '[01] DEMO - CANCEL') {
                    $categories = 'Active';
                    $stage = 'Transfer';
                    $lead_status = 'RFQ-Transfer';
                } elseif ($status === '[02] QUOTATION B4 DEMO') {
                    $categories = 'Active';
                    $stage = 'Transfer';
                    $lead_status = 'RFQ-Transfer';
                } elseif ($status === '[03] DEMO - PENDING') {
                    $categories = 'Active';
                    $stage = 'Follow Up';
                    $lead_status = 'Hot';
                } elseif ($status === '[04] PENDING QUOTATION') {
                    $categories = 'Active';
                    $stage = 'Follow Up';
                    $lead_status = 'Hot';
                } elseif ($status === '[05] LEADS - HANDOVER') {
                    $categories = 'Active';
                    $stage = 'Follow Up';
                    $lead_status = 'Hot';
                } elseif ($status === '[06] LEADS - HOT') {
                    $categories = 'Active';
                    $stage = 'Follow Up';
                    $lead_status = 'Hot';
                } elseif ($status === '[07] LEADS - WARM') {
                    $categories = 'Active';
                    $stage = 'Follow Up';
                    $lead_status = 'Warm';
                } elseif ($status === '[08] LEADS - COLD') {
                    $categories = 'Active';
                    $stage = 'Follow Up';
                    $lead_status = 'Hot';
                } elseif ($status === '[09] CLOSED') {
                    $categories = 'Active';
                    $stage = 'Follow Up';
                    $lead_status = 'Hot';
                } elseif ($status === '[10] LOST') {
                    $categories = 'Active';
                    $stage = 'Follow Up';
                    $lead_status = 'Hot';
                } elseif ($status === '[11] ON-HOLD') {
                    $categories = 'Active';
                    $stage = 'Follow Up';
                    $lead_status = 'Hot';
                } elseif ($status === '[12] NO RESPOND') {
                    $categories = 'Active';
                    $stage = 'Follow Up';
                    $lead_status = 'Hot';
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
                ['company_name' => $row['deal_name']], // Find by company name
                ['company_name' => $row['deal_name']]  // If not found, create it
            );

            // ✅ Insert or update lead, storing company_id instead of company_name
            $newLead = Lead::updateOrCreate(
                ['zoho_id' => isset($row['record_id']) ? preg_replace('/[^0-9]/', '', $row['record_id']) : null],
                [
                    'name'         => $row['contact_name'] ?? null,
                    'lead_owner'   => $row['created_by'] ?? null,
                    'salesperson'  => $row['deal_owner'] ?? null,
                    'company_name' => $company->id ?? null, // ✅ Store the company ID in Lead table
                    'company_size' => $this->normalizeCompanySize($row['company_size'] ?? null), // ✅ Normalize company size
                    'country'      => $row['country'] ?? null,
                    'lead_code'    => $row['lead_source'] ?? null,
                    'contact_id'   => $row['contact_name_id'] ?? null,
                    'categories'   => $categories,  // ✅ Set to 'Active' if condition met
                    'stage'        => $stage,       // ✅ Set to 'Transfer' if condition met
                    'lead_status'  => $lead_status, // ✅ Set to 'Under Review' if condition met
                    'deal_amount'  => $row['amount'] ?? null,
                    'created_at'   => $createdTime,
                    'updated_at'   => now(),
                ]
            );

            if ($company && empty($company->lead_id)) {
                $company->update(['lead_id' => $newLead->id]);
            }

            ReferralDetail::create([
                'lead_id'     => $newLead->id,
                'company'     => $row['referee_company_name'] ?? null,
                'name'        => $row['referee_name'] ?? null,
                'email'       => $row['Referee_Email'] ?? null,
                'contact_no'  => $row['referee_phone'] ?? null,
                'created_at'  => $createdTime ?? now(),
                'updated_at'  => now(),
            ]);

            $latestActivityLog = ActivityLog::where('subject_id', $newLead->id)
                ->orderByDesc('created_at')
                ->first();

            // ✅ Update the latest activity log description
            if ($latestActivityLog) {
                $latestActivityLog->update([
                    'description' => 'Deals Migration',
                ]);
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
