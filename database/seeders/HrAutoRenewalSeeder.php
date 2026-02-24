<?php

namespace Database\Seeders;

use App\Models\HrAutoRenewal;
use App\Models\HrLicense;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HrAutoRenewalSeeder extends Seeder
{
    public function run(): void
    {
        HrAutoRenewal::truncate();

        $companies = [
            ['name' => 'CHS TECHNINICAL & CONTRACTING N.V.', 'country' => 'Aruba'],
            ['name' => 'Tomcare Resources Sdn. Bhd.', 'country' => 'Malaysia'],
            ['name' => 'Jiun Hardware Enterprise', 'country' => 'Malaysia'],
            ['name' => 'STUDIO RAWR PICTURES SDN BHD', 'country' => 'Malaysia'],
            ['name' => 'MAGNUS MANAGEMENT SERVICES', 'country' => 'Malaysia'],
            ['name' => 'Point S Mbombela', 'country' => 'South Africa'],
            ['name' => 'Tikka Kabab Ameen', 'country' => 'Qatar'],
            ['name' => 'Al Asmakh Real Estate Development Company', 'country' => 'Qatar'],
            ['name' => 'Bakemart', 'country' => 'Qatar'],
            ['name' => 'HMC-Security Department', 'country' => 'Qatar'],
            ['name' => 'HWT BOUKONTRAKTEURS', 'country' => 'South Africa'],
            ['name' => 'Jovoy Rare Perfumes', 'country' => 'Qatar'],
            ['name' => 'Georgetown Public Hospital Corporation.', 'country' => 'Guyana'],
            ['name' => 'Sheikh Faisal Bin Qassim Al Thani Museum', 'country' => 'Qatar'],
            ['name' => 'FRESH VIBES SDN BHD', 'country' => 'Malaysia'],
            ['name' => 'BLOOMTHIS FLORA SDN BHD', 'country' => 'Malaysia'],
            ['name' => 'NFS RACING SDN BHD', 'country' => 'Malaysia'],
            ['name' => 'Fingertec Australia Pty Ltd', 'country' => 'Australia'],
            ['name' => 'Ingress Synergy Sdn Bhd', 'country' => 'Malaysia'],
            ['name' => 'VECTOR DYNAMIC SDN. BHD.', 'country' => 'Malaysia'],
            ['name' => 'TONG SEH INDUSTRIES SUPPLY SDN BHD', 'country' => 'Malaysia'],
            ['name' => 'Genx Technology M Sdn Bhd', 'country' => 'Malaysia'],
            ['name' => 'MITRASHAZ SDN BHD', 'country' => 'Malaysia'],
            ['name' => 'ARA HOSPITALITY SDN BHD', 'country' => 'Malaysia'],
            ['name' => 'KCJ ENGINEERING SDN BHD', 'country' => 'Malaysia'],
            ['name' => 'Telkare cc', 'country' => 'South Africa'],
            ['name' => 'ldc.attend', 'country' => 'Malaysia'],
            ['name' => 'ASPIRAS PROPERTY MANAGEMENT SDN BHD', 'country' => 'Malaysia'],
            ['name' => 'labibah trading est', 'country' => 'Saudi Arabia'],
            ['name' => 'PREMIER CHEMICALS SDN BHD', 'country' => 'Malaysia'],
            ['name' => 'SYARIKAT BEKALAN AIR SELANGOR SDN BHD', 'country' => 'Malaysia'],
            ['name' => 'TENAGA NASIONAL BERHAD', 'country' => 'Malaysia'],
            ['name' => 'PETRONAS CHEMICALS GROUP BERHAD', 'country' => 'Malaysia'],
            ['name' => 'Firoz Group', 'country' => 'Qatar'],
            ['name' => 'Al Jazeera Services Qatar', 'country' => 'Qatar'],
            ['name' => 'Qatar Steel', 'country' => 'Qatar'],
            ['name' => 'Doha Bank', 'country' => 'Qatar'],
            ['name' => 'Mannai Corporation', 'country' => 'Qatar'],
            ['name' => 'Gulf Warehousing Company', 'country' => 'Qatar'],
            ['name' => 'Barwa Real Estate Company', 'country' => 'Qatar'],
        ];

        // Use existing licenses for linking
        $licenses = HrLicense::all();

        $counter = 1;
        $now = Carbon::now();
        $records = [];

        // Generate 350 records
        for ($i = 0; $i < 350; $i++) {
            $company = $companies[array_rand($companies)];
            $license = $licenses->isNotEmpty() ? $licenses->random() : null;

            // Spread next_billing_date across next 6 months
            $nextBilling = $now->copy()->addDays(rand(1, 180));

            // Spread created_at across recent 30 days
            $createdAt = $now->copy()->subDays(rand(0, 30));

            $invoiceNo = 'TT' . $createdAt->format('ym') . str_pad($counter, 6, '0', STR_PAD_LEFT);

            $records[] = [
                'invoice_no'            => $invoiceNo,
                'company_name'          => $company['name'],
                'country'               => $company['country'],
                'next_billing_date'     => $nextBilling->format('Y-m-d'),
                'status'                => 'PENDING',
                'is_enabled'            => true,
                'software_handover_id'  => $license?->software_handover_id,
                'handover_id'           => $license?->handover_id,
                'created_at'            => $createdAt,
                'updated_at'            => $now,
            ];

            $counter++;
        }

        // Bulk insert in chunks
        foreach (array_chunk($records, 100) as $chunk) {
            HrAutoRenewal::insert($chunk);
        }
    }
}
