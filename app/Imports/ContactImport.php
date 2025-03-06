<?php

namespace App\Imports;

use App\Models\ActivityLog;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\UtmDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ContactImport implements ToCollection, WithStartRow, SkipsEmptyRows, WithHeadingRow
{
    public function collection(Collection $collection)
    {
        Log::info("Importing " . count($collection) . " leads.");

        foreach ($collection as $row) {
            Log::info("Processing lead: " . json_encode($row));

            // ✅ Remove "zcrm_" prefix from `record_id`
            $recordId = $row['record_id'];

            // ✅ Find a lead in the database where `contact_id` matches `record_id`
            $existingLead = Lead::where('contact_id', $recordId)->first();

            if ($existingLead) {
                // ✅ Update the existing lead
                $existingLead->update([
                    'email'        => $row['email'] ?? null,
                    'phone'        => $row['phone'] ?? null,
                    'products'     => $row['timeTec_products'] ?? null,
                ]);

                UtmDetail::create([
                    'lead_id'       => $existingLead->id,
                    'utm_campaign'  => $row['utm_campaign'] ?? null,
                    'utm_adgroup'   => $row['utm_adgroup'] ?? null,
                    'utm_creative'  => $row['utm_creative'] ?? null,
                    'utm_term'      => $row['utm_term'] ?? null,
                    'utm_matchtype' => $row['utm_matchtype'] ?? null,
                ]);
                Log::info("Updated lead with contact_id: " . $recordId);
            } else {
                Log::info("No matching lead found for contact_id: " . $recordId . ". Skipping update.");
            }
            $latestActivityLog = ActivityLog::where('subject_id', $existingLead->id)
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
}
