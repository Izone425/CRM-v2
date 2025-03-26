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
        $collection->chunk(10)->each(function ($chunk) {
            foreach ($chunk as $row) {
                $zohoId = preg_replace('/[^0-9]/', '', $row['record_id']);
                $lead = Lead::where('zoho_id', $zohoId)->first();

                if ($lead) {
                    ReferralDetail::updateOrCreate(
                        ['lead_id' => $lead->id],
                        [
                            'company'     => $row['referrer_company_name'] ?? null,
                            'name'        => $row['referrer_name'] ?? null,
                            'email'       => $row['referrer_email'] ?? null,
                            'contact_no'  => $row['referrer_phone'] ?? null,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ]
                    );
                }
            }
        });

        Log::info("ReferralDetail import completed in chunks of 10.");
    }

    public function startRow(): int
    {
        return 2; // ✅ Skip headers
    }
}
