<?php

namespace Database\Seeders;

use App\Models\HrLicense;
use App\Models\HrSalesInvoice;
use App\Models\HrSalesInvoiceItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class HrSalesInvoiceSeeder extends Seeder
{
    // Standard product pricing
    private array $products = [
        ['name' => 'TimeTec TA', 'price' => 20.00],
        ['name' => 'TimeTec Leave', 'price' => 20.00],
        ['name' => 'TimeTec Claim', 'price' => 20.00],
        ['name' => 'TimeTec Payroll', 'price' => 50.00],
    ];

    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        HrSalesInvoiceItem::truncate();
        HrSalesInvoice::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

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
                $status = $statuses[array_rand($statuses)];
                $hasCommission = rand(0, 1);
                $hasPi = rand(0, 1);

                // Generate product line items and calculate total from them
                $lineItems = $this->generateLineItems($invoiceDate->format('Y-m-d'));
                $salesAmount = $this->calculateTotal($lineItems);

                $commission = $hasCommission ? round($salesAmount * (rand(3, 10) / 100), 2) : null;

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

                $invoice = HrSalesInvoice::create([
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
                    'line_items' => $lineItems,
                    'payment_method' => $status === 'PAID' ? $paymentMethods[array_rand($paymentMethods)] : null,
                    'auto_renewal' => rand(0, 1) ? 'Yes' : 'No',
                    'created_by_name' => $salespersons[array_rand($salespersons)],
                    'status' => $status,
                    'created_at' => $invoiceDate,
                    'updated_at' => $now,
                ]);

                $this->createLineItemRecords($invoice, $lineItems);

                $counter++;
            }
        }

        // Add the 13 TTC* invoices (previously hardcoded in CompanyInvoiceTab::appendDummyRecords)
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

            $lineItems = $this->generateLineItemsForTotal($ttc['total'], $ttc['invoice_date']);

            $invoice = HrSalesInvoice::create([
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
                'line_items' => $lineItems,
                'payment_method' => $ttc['status'] === 'PAID' ? $paymentMethods[array_rand($paymentMethods)] : null,
                'auto_renewal' => 'No',
                'created_by_name' => $salespersons[array_rand($salespersons)],
                'status' => $ttc['status'],
                'created_at' => $ttc['invoice_date'],
                'updated_at' => $now,
            ]);

            $this->createLineItemRecords($invoice, $lineItems);

            $counter++;
        }

        // Add a PENDING invoice specifically for ABC TECHNOLOGY SDN BHD
        $abcTech = HrLicense::where('company_name', 'like', '%ABC TECHNOLOGY%')->first();
        if ($abcTech) {
            $abcLineItems = [
                ['license_type' => 'TimeTec TA', 'user_limit' => 10, 'total_user' => 9, 'month' => 12, 'unit_price' => 2.00, 'start_date' => '2026-02-15', 'end_date' => '2027-02-14'],
                ['license_type' => 'TimeTec Leave', 'user_limit' => 10, 'total_user' => 9, 'month' => 12, 'unit_price' => 1.00, 'start_date' => '2026-02-15', 'end_date' => '2027-02-14'],
                ['license_type' => 'TimeTec Claim', 'user_limit' => 10, 'total_user' => 9, 'month' => 12, 'unit_price' => 1.00, 'start_date' => '2026-02-15', 'end_date' => '2027-02-14'],
                ['license_type' => 'TimeTec Payroll', 'user_limit' => 10, 'total_user' => 9, 'month' => 12, 'unit_price' => 3.00, 'start_date' => '2026-02-15', 'end_date' => '2027-02-14'],
            ];
            // Total: 9 * (2+1+1+3) * 12 = 756.00

            $invoice = HrSalesInvoice::create([
                'software_handover_id' => $abcTech->software_handover_id,
                'handover_id'          => $abcTech->handover_id,
                'invoice_no'           => 'TTC2602000045',
                'invoice_date'         => '2026-02-15',
                'company_name'         => $abcTech->company_name,
                'country'              => 'Malaysia',
                'reseller'             => null,
                'sales_amount'         => 756.00,
                'currency'             => 'MYR',
                'commission'           => null,
                'pi_no'                => 'AP2602' . str_pad($counter, 6, '0', STR_PAD_LEFT),
                'invoice_amount'       => 756.00,
                'line_items'           => $abcLineItems,
                'payment_method'       => null,
                'auto_renewal'         => 'No',
                'created_by_name'      => 'Ahmad Razali',
                'status'               => 'PENDING',
                'created_at'           => '2026-02-15',
                'updated_at'           => $now,
            ]);

            $this->createLineItemRecords($invoice, $abcLineItems);
            $counter++;
        }
    }

    /**
     * Create denormalized line item records in hr_sales_invoice_items.
     */
    private function createLineItemRecords(HrSalesInvoice $invoice, array $lineItems): void
    {
        foreach ($lineItems as $item) {
            HrSalesInvoiceItem::create([
                'hr_sales_invoice_id' => $invoice->id,
                'invoice_no'          => $invoice->invoice_no,
                'invoice_date'        => $invoice->invoice_date,
                'company_name'        => $invoice->company_name,
                'invoice_amount'      => $invoice->invoice_amount,
                'currency'            => $invoice->currency,
                'software_handover_id' => $invoice->software_handover_id,
                'handover_id'         => $invoice->handover_id,
                'created_by_name'     => $invoice->created_by_name,
                'status'              => $invoice->status,
                'license_type'        => $item['license_type'],
                'user_limit'          => $item['user_limit'] ?? 1,
                'total_user'          => $item['total_user'] ?? 0,
                'unit_price'          => $item['unit_price'] ?? 0,
                'month'               => $item['month'] ?? 1,
                'start_date'          => $item['start_date'],
                'end_date'            => $item['end_date'],
            ]);
        }
    }

    /**
     * Generate random product line items for an invoice.
     */
    private function generateLineItems(string $invoiceDate): array
    {
        $startDate = $invoiceDate;
        $numProducts = rand(2, 4);
        $selectedProducts = array_slice($this->products, 0, $numProducts);
        $totalUser = rand(5, 50);
        $userLimit = max(10, ceil($totalUser / 10) * 10);
        $month = [1, 3, 6, 12][array_rand([1, 3, 6, 12])];
        $endDate = date('Y-m-d', strtotime($startDate . " +{$month} months"));

        $items = [];
        foreach ($selectedProducts as $product) {
            $items[] = [
                'license_type' => $product['name'],
                'user_limit' => $userLimit,
                'total_user' => $totalUser,
                'month' => $month,
                'unit_price' => $product['price'],
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        }

        return $items;
    }

    /**
     * Generate line items that sum to a specific total.
     */
    private function generateLineItemsForTotal(float $total, string $invoiceDate): array
    {
        if ($total < 1) {
            // Very small amount — single item
            return [
                ['license_type' => 'TimeTec TA', 'user_limit' => 1, 'total_user' => 1, 'month' => 1, 'unit_price' => round($total, 2), 'start_date' => $invoiceDate, 'end_date' => date('Y-m-d', strtotime($invoiceDate . ' +1 month'))],
            ];
        }

        $startDate = $invoiceDate;
        $endDate = date('Y-m-d', strtotime($startDate . ' +12 months'));

        // Try to distribute across 2-4 products
        $numProducts = min(4, max(2, (int) floor($total / 20)));
        $numProducts = min($numProducts, 4);
        $selectedProducts = array_slice($this->products, 0, $numProducts);

        // Calculate per-product share
        $totalPricePerUser = array_sum(array_column($selectedProducts, 'price'));

        // Find qty and month that sum close to total
        // total = qty * totalPricePerUser * month
        // Try month=12 first, then month=1
        foreach ([12, 6, 3, 1] as $month) {
            $qty = round($total / ($totalPricePerUser * $month));
            if ($qty >= 1 && abs(($qty * $totalPricePerUser * $month) - $total) < 1) {
                $userLimit = max(10, ceil($qty / 10) * 10);
                $calcEndDate = date('Y-m-d', strtotime($startDate . " +{$month} months"));
                $items = [];
                foreach ($selectedProducts as $product) {
                    $items[] = [
                        'license_type' => $product['name'],
                        'user_limit' => (int) $userLimit,
                        'total_user' => (int) $qty,
                        'month' => $month,
                        'unit_price' => $product['price'],
                        'start_date' => $startDate,
                        'end_date' => $calcEndDate,
                    ];
                }
                return $items;
            }
        }

        // Fallback: distribute total across products proportionally with month=12
        $items = [];
        $remaining = $total;
        $month = 12;
        foreach ($selectedProducts as $idx => $product) {
            $isLast = ($idx === count($selectedProducts) - 1);
            if ($isLast) {
                $amount = $remaining;
            } else {
                $amount = round($total / count($selectedProducts), 2);
                $remaining -= $amount;
            }
            $qty = max(1, (int) round($amount / ($product['price'] * $month)));
            $items[] = [
                'license_type' => $product['name'],
                'user_limit' => max(10, ceil($qty / 10) * 10),
                'total_user' => $qty,
                'month' => $month,
                'unit_price' => $product['price'],
                'start_date' => $startDate,
                'end_date' => $endDate,
            ];
        }

        return $items;
    }

    /**
     * Calculate total from line items.
     */
    private function calculateTotal(array $lineItems): float
    {
        $total = 0;
        foreach ($lineItems as $item) {
            $total += ($item['total_user'] ?? 1) * ($item['unit_price'] ?? 0) * ($item['month'] ?? 1);
        }
        return round($total, 2);
    }
}
