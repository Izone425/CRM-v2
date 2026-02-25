<?php

namespace Database\Seeders;

use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use App\Models\SoftwareHandover;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SoftwareHandoverDummySeeder extends Seeder
{
    public function run(): void
    {
        // Step 1: Ensure products exist
        $products = $this->ensureProducts();

        // Step 2: Get a salesperson user ID (role_id = 2)
        $salespersonIds = User::where('role_id', 2)->pluck('id')->toArray();
        if (empty($salespersonIds)) {
            $salespersonIds = [auth()->id() ?? 1];
        }
        $salespersonNames = User::whereIn('id', $salespersonIds)->pluck('name', 'id')->toArray();

        // Step 3: Define dummy companies
        $companies = [
            ['name' => 'MM SPICE KITCHEN SDN BHD', 'address' => '1832, JALAN PERUSAHAAN, PRAI', 'state' => 'PULAU PINANG', 'postcode' => '13600', 'headcount' => 10, 'license_type' => 'new sales', 'speaker' => 'English'],
            ['name' => 'GLOBAL MATRIX SOLUTIONS SDN BHD', 'address' => '12, JALAN SULTAN ISMAIL', 'state' => 'KUALA LUMPUR', 'postcode' => '50250', 'headcount' => 25, 'license_type' => 'new sales', 'speaker' => 'English'],
            ['name' => 'OCEANIC TRADING SDN BHD', 'address' => '88, LORONG SELAMAT', 'state' => 'PULAU PINANG', 'postcode' => '10400', 'headcount' => 15, 'license_type' => 'addon module', 'speaker' => 'Mandarin'],
            ['name' => 'BRIGHT FUTURE EDUCATION SDN BHD', 'address' => '5, JALAN AMPANG', 'state' => 'SELANGOR', 'postcode' => '68000', 'headcount' => 50, 'license_type' => 'new sales', 'speaker' => 'English'],
            ['name' => 'SUPREME CONSTRUCTION GROUP SDN BHD', 'address' => '22, JALAN INDUSTRI', 'state' => 'JOHOR', 'postcode' => '81750', 'headcount' => 100, 'license_type' => 'new sales', 'speaker' => 'English'],
            ['name' => 'PHOENIX LOGISTICS SDN BHD', 'address' => '9, PERSIARAN BESTARI', 'state' => 'SELANGOR', 'postcode' => '47100', 'headcount' => 30, 'license_type' => 'addon module', 'speaker' => 'Mandarin'],
            ['name' => 'HARMONI TECH VENTURES SDN BHD', 'address' => '3, JALAN MEGA', 'state' => 'KUALA LUMPUR', 'postcode' => '50470', 'headcount' => 20, 'license_type' => 'new sales', 'speaker' => 'English'],
            ['name' => 'STELLAR MANUFACTURING SDN BHD', 'address' => '77, KAWASAN PERINDUSTRIAN', 'state' => 'PERAK', 'postcode' => '31400', 'headcount' => 75, 'license_type' => 'new sales', 'speaker' => 'English'],
        ];

        $piCounter = 265990;

        foreach ($companies as $index => $company) {
            $salespersonId = $salespersonIds[array_rand($salespersonIds)];
            $salespersonName = $salespersonNames[$salespersonId] ?? 'Admin';

            // Create Lead
            $lead = Lead::create([
                'name' => substr($company['name'], 0, 20),
                'company_name' => substr($company['name'], 0, 30),
                'salesperson' => $salespersonId,
                'country' => 'Malaysia',
                'categories' => 'New',
                'stage' => 'New',
                'lead_status' => 'None',
                'customer_type' => 'END USER',
                'region' => 'LOCAL',
            ]);

            // Create CompanyDetail
            CompanyDetail::create([
                'lead_id' => $lead->id,
                'company_name' => $company['name'],
                'company_address1' => $company['address'],
                'state' => $company['state'],
                'postcode' => $company['postcode'],
                'name' => 'PIC ' . ($index + 1),
                'email' => 'pic' . ($index + 1) . '@example.com',
                'contact_no' => '01' . rand(10000000, 99999999),
            ]);

            // Create Quotation
            $piCounter++;
            $quotation = Quotation::create([
                'lead_id' => $lead->id,
                'pi_reference_no' => 'PI//' . $piCounter,
                'quotation_reference_no' => 'QT//' . $piCounter,
                'quotation_type' => 'product',
                'currency' => 'MYR',
                'headcount' => $company['headcount'],
                'tax_rate' => 8,
                'subscription_period' => 12,
                'status' => 'new',
                'sales_person_id' => $salespersonId,
                'quotation_date' => now(),
            ]);

            // Create QuotationDetails — 4 software products + 1 onboarding
            $sortOrder = 1;
            $softwareProducts = ['TIMETEC-TA', 'TIMETEC-TL', 'TIMETEC-TC', 'TIMETEC-TP'];

            foreach ($softwareProducts as $code) {
                $product = $products[$code];
                $qty = $company['headcount'];
                $unitPrice = $product->unit_price;
                $subPeriod = 12;
                $totalBeforeTax = $qty * $unitPrice * $subPeriod;
                $tax = round($totalBeforeTax * 0.08, 2);
                $totalAfterTax = $totalBeforeTax + $tax;

                QuotationDetail::create([
                    'quotation_id' => $quotation->id,
                    'product_id' => $product->id,
                    'description' => $product->description,
                    'quantity' => $qty,
                    'subscription_period' => $subPeriod,
                    'unit_price' => $unitPrice,
                    'discount' => 0,
                    'taxation' => $tax,
                    'total_before_tax' => $totalBeforeTax,
                    'total_after_tax' => $totalAfterTax,
                    'sort_order' => $sortOrder++,
                ]);
            }

            // Onboarding item
            $onboardProduct = $products['TIMETEC-ONBOARD'];
            $onboardTotal = $onboardProduct->unit_price;
            $onboardTax = round($onboardTotal * 0.08, 2);

            QuotationDetail::create([
                'quotation_id' => $quotation->id,
                'product_id' => $onboardProduct->id,
                'description' => $onboardProduct->description,
                'quantity' => 1,
                'subscription_period' => 1,
                'unit_price' => $onboardProduct->unit_price,
                'discount' => 0,
                'taxation' => $onboardTax,
                'total_before_tax' => $onboardTotal,
                'total_after_tax' => $onboardTotal + $onboardTax,
                'sort_order' => $sortOrder,
            ]);

            // Create SoftwareHandover
            SoftwareHandover::create([
                'lead_id' => $lead->id,
                'company_name' => $company['name'],
                'headcount' => (string) $company['headcount'],
                'salesperson' => $salespersonName,
                'status' => 'New',
                'hr_version' => 1,
                'training_type' => 'online_webinar_training',
                'license_type' => $company['license_type'],
                'speaker_category' => $company['speaker'],
                'ta' => 1,
                'tl' => 1,
                'tc' => 1,
                'tp' => 1,
                'tapp' => 0,
                'thire' => 0,
                'tacc' => 0,
                'tpbi' => 0,
                'proforma_invoice_product' => json_encode([$quotation->id]),
                'implementation_pics' => json_encode([
                    [
                        'name' => 'PIC ' . ($index + 1),
                        'email' => 'pic' . ($index + 1) . '@example.com',
                        'phone' => '01' . rand(10000000, 99999999),
                        'position' => 'HR Manager',
                    ]
                ]),
                'submitted_at' => now()->subDays(rand(1, 14)),
                'created_by' => $salespersonId,
            ]);

            $this->command->info("Created dummy SW handover for: {$company['name']}");
        }

        $this->command->info('SoftwareHandoverDummySeeder completed: 8 records created');
    }

    private function ensureProducts(): array
    {
        $definitions = [
            'TIMETEC-TA' => ['description' => 'TimeTec - Time Attendance', 'solution' => 'software', 'unit_price' => 2.00, 'taxable' => true, 'push_to_autocount' => true, 'is_active' => true],
            'TIMETEC-TL' => ['description' => 'TimeTec - Leave', 'solution' => 'software', 'unit_price' => 2.00, 'taxable' => true, 'push_to_autocount' => true, 'is_active' => true],
            'TIMETEC-TC' => ['description' => 'TimeTec - Claim', 'solution' => 'software', 'unit_price' => 2.00, 'taxable' => true, 'push_to_autocount' => true, 'is_active' => true],
            'TIMETEC-TP' => ['description' => 'TimeTec - Payroll', 'solution' => 'software', 'unit_price' => 2.00, 'taxable' => true, 'push_to_autocount' => true, 'is_active' => true],
            'TIMETEC-ONBOARD' => ['description' => 'One Time Fee Onboarding Process', 'solution' => 'other', 'unit_price' => 250.00, 'taxable' => true, 'push_to_autocount' => true, 'is_active' => true],
        ];

        $products = [];
        foreach ($definitions as $code => $attrs) {
            $products[$code] = Product::firstOrCreate(
                ['code' => $code],
                $attrs
            );
        }

        return $products;
    }
}
