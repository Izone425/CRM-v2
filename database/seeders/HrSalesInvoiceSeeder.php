<?php

namespace Database\Seeders;

use App\Models\HrLicense;
use App\Models\HrSalesInvoice;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class HrSalesInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        HrSalesInvoice::truncate();

        $licenses = HrLicense::all();
        $now = Carbon::now();
        $counter = 1;

        // Get actual reseller/distributor licenses for clickable reseller links
        $resellerLicenses = HrLicense::whereIn('license_category', ['Reseller', 'Distributor'])->get();

        $paymentMethods = ['Online Transfer', 'Credit Card', 'Cheque'];
        $salespersons = ['Ahmad Razali', 'Siti Nurhaliza', 'John Tan', 'Lee Wei Ming', 'Priya Sharma', 'Muhammad Faiz'];
        $countries = ['Malaysia', 'Singapore', 'Indonesia', 'Thailand', 'Philippines', 'Vietnam'];
        $statuses = ['PAID', 'PAID', 'PAID', 'PENDING', 'PENDING', 'CANCEL'];

        foreach ($licenses as $license) {
            $invoiceCount = rand(1, 3);

            for ($i = 0; $i < $invoiceCount; $i++) {
                $invoiceDate = $now->copy()->subDays(rand(1, 365));
                $invoiceNo = 'TT' . $invoiceDate->format('ym') . str_pad($counter, 6, '0', STR_PAD_LEFT);
                $currency = $license->country === 'Singapore' ? 'SGD' : 'MYR';
                $salesAmount = round(rand(10000, 500000) / 100, 2);
                $status = $statuses[array_rand($statuses)];
                $hasCommission = rand(0, 1);
                $commission = $hasCommission ? round($salesAmount * (rand(3, 10) / 100), 2) : null;
                $hasPi = rand(0, 1);

                // Randomly assign a reseller from actual license records (or null)
                $resellerName = null;
                $resellerSwId = null;
                $resellerHandoverId = null;
                if ($resellerLicenses->isNotEmpty() && rand(0, 1)) {
                    $resellerLicense = $resellerLicenses->random();
                    $resellerName = $resellerLicense->company_name;
                    $resellerSwId = $resellerLicense->software_handover_id;
                    $resellerHandoverId = $resellerLicense->handover_id;
                }

                HrSalesInvoice::create([
                    'software_handover_id' => $license->software_handover_id,
                    'handover_id' => $license->handover_id,
                    'invoice_no' => $invoiceNo,
                    'invoice_date' => $invoiceDate->format('Y-m-d'),
                    'company_name' => $license->company_name,
                    'country' => $license->country ?? $countries[array_rand($countries)],
                    'reseller' => $resellerName,
                    'reseller_software_handover_id' => $resellerSwId,
                    'reseller_handover_id' => $resellerHandoverId,
                    'sales_amount' => $salesAmount,
                    'currency' => $currency,
                    'commission' => $commission,
                    'pi_no' => $hasPi ? 'AP' . $invoiceDate->format('ym') . str_pad($counter, 6, '0', STR_PAD_LEFT) : null,
                    'invoice_amount' => $salesAmount,
                    'payment_method' => $status === 'PAID' ? $paymentMethods[array_rand($paymentMethods)] : null,
                    'auto_renewal' => rand(0, 1) ? 'Yes' : 'No',
                    'created_by_name' => $salespersons[array_rand($salespersons)],
                    'status' => $status,
                    'created_at' => $invoiceDate,
                    'updated_at' => $now,
                ]);

                $counter++;
            }
        }

        // Add the 13 TTC* invoices (previously hardcoded in CompanyInvoiceTab::appendDummyRecords)
        // Distribute across existing license companies
        $licensePool = $licenses->values();
        $ttcRecords = [
            ['invoice_no' => 'TTC2408000355', 'invoice_date' => '2024-08-29', 'total' => 110.00, 'currency' => 'USD', 'status' => 'PAID'],
            ['invoice_no' => 'TTC2409000134', 'invoice_date' => '2024-09-13', 'total' => 50.00, 'currency' => 'USD', 'status' => 'CANCEL'],
            ['invoice_no' => 'TTC2409000198', 'invoice_date' => '2024-09-19', 'total' => 60.00, 'currency' => 'USD', 'status' => 'CANCEL'],
            ['invoice_no' => 'TTC2410000012', 'invoice_date' => '2024-10-01', 'total' => 0.01, 'currency' => 'MYR', 'status' => 'PAID'],
            ['invoice_no' => 'TTC2410000078', 'invoice_date' => '2024-10-08', 'total' => 240.00, 'currency' => 'USD', 'status' => 'PAID'],
            ['invoice_no' => 'TTC2410000096', 'invoice_date' => '2024-10-09', 'total' => 120.00, 'currency' => 'USD', 'status' => 'CANCEL'],
            ['invoice_no' => 'TTC2410000153', 'invoice_date' => '2024-10-16', 'total' => 0.04, 'currency' => 'MYR', 'status' => 'CANCEL'],
            ['invoice_no' => 'TTC2502000209', 'invoice_date' => '2025-02-17', 'total' => 129.60, 'currency' => 'MYR', 'status' => 'CANCEL'],
            ['invoice_no' => 'TTC2502000211', 'invoice_date' => '2025-02-17', 'total' => 24.00, 'currency' => 'USD', 'status' => 'PAID'],
            ['invoice_no' => 'TTC2509000087', 'invoice_date' => '2025-09-08', 'total' => 500.00, 'currency' => 'USD', 'status' => 'CANCEL'],
            ['invoice_no' => 'TTC2509000168', 'invoice_date' => '2025-09-14', 'total' => 100.00, 'currency' => 'USD', 'status' => 'CANCEL'],
            ['invoice_no' => 'TTC2510000141', 'invoice_date' => '2025-10-11', 'total' => 0.04, 'currency' => 'MYR', 'status' => 'CANCEL'],
            ['invoice_no' => 'TTC2602000032', 'invoice_date' => '2026-02-03', 'total' => 1.08, 'currency' => 'MYR', 'status' => 'PENDING'],
        ];

        foreach ($ttcRecords as $idx => $ttc) {
            $license = $licensePool->isNotEmpty()
                ? $licensePool[$idx % $licensePool->count()]
                : null;

            $resellerName = null;
            $resellerSwId = null;
            $resellerHandoverId = null;
            if ($resellerLicenses->isNotEmpty() && $idx % 3 === 0) {
                $resellerLicense = $resellerLicenses->values()[$idx % $resellerLicenses->count()];
                $resellerName = $resellerLicense->company_name;
                $resellerSwId = $resellerLicense->software_handover_id;
                $resellerHandoverId = $resellerLicense->handover_id;
            }

            HrSalesInvoice::create([
                'software_handover_id' => $license?->software_handover_id,
                'handover_id' => $license?->handover_id,
                'invoice_no' => $ttc['invoice_no'],
                'invoice_date' => $ttc['invoice_date'],
                'company_name' => $license?->company_name ?? 'Demo Company',
                'country' => $license?->country ?? 'Malaysia',
                'reseller' => $resellerName,
                'reseller_software_handover_id' => $resellerSwId,
                'reseller_handover_id' => $resellerHandoverId,
                'sales_amount' => $ttc['total'],
                'currency' => $ttc['currency'],
                'commission' => $ttc['status'] === 'PAID' ? round($ttc['total'] * 0.05, 2) : null,
                'pi_no' => $ttc['status'] !== 'CANCEL' ? 'AP' . substr($ttc['invoice_no'], 3, 4) . str_pad($counter, 6, '0', STR_PAD_LEFT) : null,
                'invoice_amount' => $ttc['total'],
                'payment_method' => $ttc['status'] === 'PAID' ? $paymentMethods[array_rand($paymentMethods)] : null,
                'auto_renewal' => 'No',
                'created_by_name' => $salespersons[array_rand($salespersons)],
                'status' => $ttc['status'],
                'created_at' => $ttc['invoice_date'],
                'updated_at' => $now,
            ]);

            $counter++;
        }
    }
}
