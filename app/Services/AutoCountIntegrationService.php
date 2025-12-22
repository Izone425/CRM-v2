<?php

namespace App\Services;

use App\Models\SoftwareHandover;
use App\Models\User;
use App\Models\Quotation;
use App\Models\QuotationDetail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AutoCountIntegrationService
{
    protected AutoCountInvoiceService $autoCountService;

    public function __construct(AutoCountInvoiceService $autoCountService)
    {
        $this->autoCountService = $autoCountService;
    }

    /**
     * Main method to handle complete AutoCount integration for software handover
     */
    public function processHandoverInvoiceCreation(
        SoftwareHandover $handover,
        array $formData
    ): array {
        try {
            $result = [
                'success' => false,
                'debtor_code' => null,
                'invoice_numbers' => [],
                'error' => null,
                'steps' => []
            ];

            // Check if AutoCount integration is requested
            if (!($formData['create_autocount_invoice'] ?? false)) {
                return [
                    'success' => true,
                    'message' => 'AutoCount integration skipped',
                    'skipped' => true
                ];
            }

            // ✅ Get quotation groups and validate they haven't been processed
            $quotationGroups = $this->getQuotationGroups($handover);

            if (empty($quotationGroups)) {
                $result['error'] = 'No quotation details found for invoice creation';
                return $result;
            }

            // ✅ Check if any quotations already have AutoCount invoices generated
            $allQuotationIds = array_merge(...$quotationGroups);
            $alreadyProcessed = \App\Models\Quotation::whereIn('id', $allQuotationIds)
                ->where('autocount_generated_pi', true)
                ->pluck('pi_reference_no')
                ->toArray();

            if (!empty($alreadyProcessed)) {
                $result['error'] = 'The following quotations already have AutoCount invoices: ' . implode(', ', $alreadyProcessed);
                return $result;
            }

            // ✅ Use fixed debtor code
            $result['debtor_code'] = 'ARM-P0062';
            $result['steps'][] = "Using fixed debtor: ARM-P0062";

            $result['steps'][] = "Found " . count($quotationGroups) . " proforma invoice(s) to process";

            // ✅ Pre-generate ALL invoice numbers at once to avoid conflicts
            $preGeneratedInvoiceNumbers = $this->generateMultipleInvoiceNumbers($handover, count($quotationGroups));
            $result['steps'][] = "Pre-generated invoice numbers: " . implode(', ', $preGeneratedInvoiceNumbers);

            // ✅ Create separate invoice for each proforma invoice
            foreach ($quotationGroups as $index => $quotationIds) {
                $result['steps'][] = "Processing proforma invoice group " . ($index + 1) . " with quotations: " . implode(', ', $quotationIds);

                // Use pre-generated invoice number
                $invoiceNo = $preGeneratedInvoiceNumbers[$index];
                $result['invoice_numbers'][] = $invoiceNo;

                // Create invoice for this specific group
                $invoiceResult = $this->createInvoiceForQuotationGroup($handover, $result['debtor_code'], $quotationIds, $invoiceNo);

                if (!$invoiceResult['success']) {
                    $result['error'] = "Failed to create invoice " . ($index + 1) . ": " . $invoiceResult['error'];
                    $result['steps'][] = "Invoice " . ($index + 1) . " creation failed";

                    if (str_contains(strtolower($invoiceResult['error']), 'timeout') ||
                        str_contains(strtolower($invoiceResult['error']), 'connection')) {
                        $result['connectivity_issue'] = true;
                        $result['error'] = 'AutoCount API timeout. The handover was completed, but please create the invoices manually in AutoCount.';
                    }

                    return $result;
                }

                // ✅ Mark all quotations in this group as having AutoCount invoice generated
                foreach ($quotationIds as $quotationId) {
                    \App\Models\Quotation::where('id', $quotationId)->update([
                        'autocount_generated_pi' => true
                    ]);

                    Log::info('Marked quotation as having AutoCount invoice generated', [
                        'quotation_id' => $quotationId,
                        'invoice_no' => $invoiceNo,
                        'handover_id' => $handover->id
                    ]);
                }

                $result['steps'][] = "Invoice " . ($index + 1) . " created successfully: {$invoiceNo}";
                $result['steps'][] = "Marked quotations as processed: " . implode(', ', $quotationIds);
            }

            $result['success'] = true;
            $result['total_invoices'] = count($result['invoice_numbers']);
            $result['steps'][] = "Successfully created " . count($result['invoice_numbers']) . " invoices: " . implode(', ', $result['invoice_numbers']);

            // ✅ Update handover record with all invoice numbers
            $handover->update([
                'autocount_debtor_code' => $result['debtor_code'],
                'autocount_invoice_no' => json_encode($result['invoice_numbers'])
            ]);

            $result['steps'][] = 'Handover record updated with AutoCount details';

            Log::info('AutoCount integration completed successfully', [
                'handover_id' => $handover->id,
                'debtor_code' => $result['debtor_code'],
                'invoice_numbers' => $result['invoice_numbers'],
                'total_invoices' => count($result['invoice_numbers']),
                'processed_quotations' => $allQuotationIds,
                'steps' => $result['steps']
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('AutoCount integration failed', [
                'handover_id' => $handover->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'steps' => array_merge($result['steps'] ?? [], ['Exception occurred'])
            ];
        }
    }

    public function getQuotationGroups(SoftwareHandover $handover): array
    {
        $groups = [];

        // ✅ Each proforma invoice HRDF should be a separate invoice
        if ($handover->proforma_invoice_hrdf) {
            $hrdfPis = is_string($handover->proforma_invoice_hrdf)
                ? json_decode($handover->proforma_invoice_hrdf, true)
                : $handover->proforma_invoice_hrdf;

            if (is_array($hrdfPis)) {
                // ✅ Filter out quotations that already have AutoCount invoices
                $validPis = \App\Models\Quotation::whereIn('id', $hrdfPis)
                    ->where('autocount_generated_pi', false)
                    ->pluck('id')
                    ->toArray();

                // ✅ Each quotation ID becomes its own invoice - return simple arrays
                foreach ($validPis as $quotationId) {
                    $groups[] = [$quotationId]; // Simple array of quotation IDs
                }

                Log::info('Generated quotation groups for HRDF invoices', [
                    'handover_id' => $handover->id,
                    'total_quotations' => count($hrdfPis),
                    'valid_quotations' => count($validPis),
                    'groups_created' => count($groups),
                    'groups' => $groups
                ]);
            }
        }

        return $groups;
    }

    protected function createInvoiceForQuotationGroup(SoftwareHandover $handover, string $customerCode, array $quotationIds, string $invoiceNo): array
    {
        // Get company name from quotation subsidiary if available
        $customerName = $handover->company_name;
        if (!empty($quotationIds)) {
            $quotation = Quotation::with('subsidiary', 'lead.companyDetail')->find($quotationIds[0]);
            if ($quotation && $quotation->subsidiary && !empty($quotation->subsidiary->company_name)) {
                $customerName = $quotation->subsidiary->company_name;
            } elseif ($quotation && $quotation->lead && $quotation->lead->companyDetail && !empty($quotation->lead->companyDetail->company_name)) {
                $customerName = $quotation->lead->companyDetail->company_name;
            }
        }

        $invoiceData = [
            'company' => $this->determineCompanyByHandover($handover),
            'customer_code' => $customerCode,
            'document_no' => $invoiceNo,
            'document_date' => now()->format('Y-m-d'),
            'description' => 'Software Handover Invoice - ' . $customerName,
            'salesperson' => $this->getAutoCountSalesperson($handover),
            'round_method' => 0,
            'inclusive' => true,
            'details' => $this->getInvoiceDetailsFromQuotationIds($quotationIds),
            'uDFCustomerName' => $customerName,
            'uDFLicenseNumber' => $handover->tt_invoice_number ?? '',
        ];

        return $this->autoCountService->createInvoice($invoiceData);
    }

    protected function getInvoiceDetailsFromQuotationIds(array $quotationIds): array
    {
        if (empty($quotationIds)) {
            // Fallback details with default account
            return [[
                'account' => $this->getDefaultAccountCode(),
                'itemCode' => 'TCL_ACCESS-NEW',
                'location' => 'HQ',
                'quantity' => 1,
                'uom' => 'UNIT',
                'unitPrice' => 1275,
                'amount' => 1275,
                // ✅ Add tax fields for fallback item
                'taxCode' => 'SV-8',
                'taxRate' => 8,
            ]];
        }

        $quotationDetails = QuotationDetail::whereIn('quotation_id', $quotationIds)
            ->with('product')
            ->get();

        // ✅ Group items by product code, unit price, account, and tax info to combine duplicates WITHIN this quotation
        $groupedDetails = [];

        foreach ($quotationDetails as $detail) {
            $product = $detail->product;
            $productCode = $product->code ?? 'ITEM-' . $product->id;
            $baseUnitPrice = (float) $detail->unit_price;
            $account = $this->getAccountFromProduct($product);

            // ✅ Determine tax information based on product->taxable
            $taxCode = '';
            $taxRate = 0;

            if ($product && $product->taxable) {
                $taxCode = 'SV-8';
                $taxRate = 8;
            }

            // ✅ Calculate tax-inclusive unit price for AutoCount
            $taxInclusiveUnitPrice = $baseUnitPrice;
            if ($product && $product->taxable && $taxRate > 0) {
                // Calculate tax-inclusive price: base price * (1 + tax rate)
                $taxInclusiveUnitPrice = $baseUnitPrice * (1 + ($taxRate / 100));
            }

            // Create a unique key based on product code, tax-inclusive unit price, account, and tax info
            $key = $productCode . '|' . $taxInclusiveUnitPrice . '|' . $account . '|' . $taxCode . '|' . $taxRate;

            if (isset($groupedDetails[$key])) {
                // ✅ Combine with existing item (within the same quotation)
                $groupedDetails[$key]['quantity'] += (float) $detail->quantity;
                $groupedDetails[$key]['amount'] += (float) $detail->total_after_tax; // ✅ CHANGED from total_before_tax
            } else {
                // ✅ Add new item with tax information
                $groupedDetails[$key] = [
                    'account' => $account,
                    'itemCode' => $productCode,
                    'location' => 'HQ',
                    'quantity' => (float) $detail->quantity,
                    'uom' => 'UNIT',
                    'unitPrice' => $taxInclusiveUnitPrice, // ✅ Use tax-inclusive price for AutoCount
                    'amount' => (float) $detail->total_after_tax, // ✅ CHANGED from total_before_tax
                    'taxCode' => $taxCode,
                    'taxRate' => $taxRate,
                ];
            }

            // ✅ Log account assignment for debugging
            Log::info('Account assignment for product', [
                'quotation_ids' => $quotationIds,
                'product_id' => $product->id ?? 'unknown',
                'product_code' => $productCode,
                'gl_posting' => $product->gl_posting ?? 'null',
                'assigned_account' => $account,
                'base_unit_price' => $baseUnitPrice,
                'tax_inclusive_unit_price' => $taxInclusiveUnitPrice,
                'quantity' => $detail->quantity,
                'amount' => $detail->total_after_tax, // ✅ CHANGED from total_before_tax
                'taxable' => $product->taxable ?? false,
                'tax_code' => $taxCode,
                'tax_rate' => $taxRate,
                'combined_key' => $key
            ]);
        }

        // Convert grouped items back to indexed array
        return array_values($groupedDetails);
    }

    /**
     * Create new debtor from handover and form data
     */
    protected function createNewDebtor(SoftwareHandover $handover, array $formData): array
    {
        $debtorData = [
            'company' => $this->determineCompanyByHandover($handover),
            'control_account' => 'ARM-0112-01',
            'company_name' => $formData['debtor_company_name'],
            'addr1' => $formData['debtor_addr1'] ?? '',
            'addr2' => $formData['debtor_addr2'] ?? '',
            'addr3' => $formData['debtor_addr3'] ?? '',
            'post_code' => $formData['debtor_postcode'] ?? '',
            'contact_person' => $formData['debtor_contact_person'],
            'phone' => $formData['debtor_phone'] ?? '',
            'mobile' => $formData['debtor_mobile'] ?? '',
            'email' => $formData['debtor_email'] ?? '',
            'area_code' => $formData['debtor_area_code'] ?? 'MYS-SEL',
            'sales_agent' => $this->getAutoCountSalesperson($handover),
            'tax_entity_id' => 3,
        ];

        $result = $this->autoCountService->createDebtor($debtorData);

        // ✅ If debtor creation is successful, save to local database using your existing model structure
        if ($result['success']) {
            try {
                \App\Models\Debtor::create([
                    'debtor_code' => $result['debtor_code'],
                    'debtor_name' => $formData['debtor_company_name'], // ✅ Using debtor_name field
                    'tax_entity_id' => 3,
                ]);

                Log::info('Debtor saved to local database', [
                    'debtor_code' => $result['debtor_code'],
                    'debtor_name' => $formData['debtor_company_name']
                ]);

            } catch (\Exception $e) {
                Log::warning('Failed to save debtor to local database', [
                    'debtor_code' => $result['debtor_code'],
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $result;
    }

    /**
     * Create invoice for handover
     */
    protected function createInvoiceForHandover(SoftwareHandover $handover, string $customerCode): array
    {
        // Get company name from quotation subsidiary if available
        $quotationIds = $this->getQuotationIds($handover);
        $customerName = $handover->company_name;
        if (!empty($quotationIds)) {
            $quotation = Quotation::with('subsidiary', 'lead.companyDetail')->find($quotationIds[0]);
            if ($quotation) {
                if ($quotation->subsidiary_id && $quotation->subsidiary) {
                    $customerName = $quotation->subsidiary->company_name;
                } elseif ($quotation->lead && $quotation->lead->companyDetail) {
                    $customerName = $quotation->lead->companyDetail->company_name;
                }
            }
        }

        $invoiceData = [
            'company' => $this->determineCompanyByHandover($handover),
            'customer_code' => $customerCode,
            'document_no' => $this->generateInvoiceDocumentNumber($handover),
            'document_date' => now()->format('Y-m-d'),
            'description' => 'Software Handover Invoice - ' . $handover->company_name,
            'salesperson' => $this->getAutoCountSalesperson($handover),
            'round_method' => 0,
            'inclusive' => true,
            'details' => $this->getInvoiceDetailsFromHandover($handover),
            'uDFCustomerName' => $customerName,
            'uDFLicenseNumber' => $handover->tt_invoice_number ?? '',
        ];

        return $this->autoCountService->createInvoice($invoiceData);
    }

    /**
     * Get AutoCount salesperson name from handover
     */
    protected function getAutoCountSalesperson(SoftwareHandover $handover): string
    {
        // Try to get from handover salesperson
        if ($handover->salesperson) {
            $user = User::where('name', $handover->salesperson)->first();
            if ($user && $user->autocount_name) {
                return $user->autocount_name;
            }
        }

        // Try to get from lead salesperson
        if ($handover->lead_id) {
            $lead = \App\Models\Lead::find($handover->lead_id);
            if ($lead && $lead->salesperson) {
                $user = User::find($lead->salesperson);
                if ($user && $user->autocount_name) {
                    return $user->autocount_name;
                }
            }
        }

        return 'ADMIN'; // Default fallback
    }

    /**
     * Get invoice details from handover quotations
     */
    protected function getInvoiceDetailsFromHandover(SoftwareHandover $handover): array
    {
        $quotationIds = $this->getQuotationIds($handover);

        if (empty($quotationIds)) {
            // Fallback details with default account and tax
            return [[
                'account' => $this->getDefaultAccountCode(),
                'itemCode' => 'TCL_ACCESS-NEW',
                'location' => 'HQ',
                'quantity' => 1,
                'uom' => 'UNIT',
                'unitPrice' => 1275,
                'amount' => 1275,
                'taxCode' => 'SV-8',
                'taxRate' => 8,
            ]];
        }

        $quotationDetails = QuotationDetail::whereIn('quotation_id', $quotationIds)
            ->with('product')
            ->get();

        // ✅ Group items by product code, unit price, account, and tax info to combine duplicates
        $groupedDetails = [];

        foreach ($quotationDetails as $detail) {
            $product = $detail->product;
            $productCode = $product->code ?? 'ITEM-' . $product->id;
            $baseUnitPrice = (float) $detail->unit_price;
            $account = $this->getAccountFromProduct($product);

            // ✅ Determine tax information based on product->taxable
            $taxCode = '';
            $taxRate = 0;

            if ($product && $product->taxable) {
                $taxCode = 'SV-8';
                $taxRate = 8;
            }

            // ✅ Calculate tax-inclusive unit price for AutoCount
            $taxInclusiveUnitPrice = $baseUnitPrice;
            if ($product && $product->taxable && $taxRate > 0) {
                // Calculate tax-inclusive price: base price * (1 + tax rate)
                $taxInclusiveUnitPrice = $baseUnitPrice * (1 + ($taxRate / 100));
            }

            // Create a unique key based on product code, tax-inclusive unit price, account, and tax info
            $key = $productCode . '|' . $taxInclusiveUnitPrice . '|' . $account . '|' . $taxCode . '|' . $taxRate;

            if (isset($groupedDetails[$key])) {
                // ✅ Combine with existing item
                $groupedDetails[$key]['quantity'] += (float) $detail->quantity;
                $groupedDetails[$key]['amount'] += (float) $detail->total_after_tax; // ✅ CHANGED to total_after_tax
            } else {
                // ✅ Add new item with tax information
                $groupedDetails[$key] = [
                    'account' => $account,
                    'itemCode' => $productCode,
                    'location' => 'HQ',
                    'quantity' => (float) $detail->quantity,
                    'uom' => 'UNIT',
                    'unitPrice' => $taxInclusiveUnitPrice, // ✅ Use tax-inclusive price for AutoCount
                    'amount' => (float) $detail->total_after_tax, // ✅ CHANGED to total_after_tax
                    'taxCode' => $taxCode,
                    'taxRate' => $taxRate,
                ];
            }

            // ✅ Log account assignment for debugging
            Log::info('Account assignment for product', [
                'handover_id' => $handover->id,
                'product_id' => $product->id ?? 'unknown',
                'product_code' => $productCode,
                'gl_posting' => $product->gl_posting ?? 'null',
                'assigned_account' => $account,
                'base_unit_price' => $baseUnitPrice,
                'tax_inclusive_unit_price' => $taxInclusiveUnitPrice,
                'quantity' => $detail->quantity,
                'amount' => $detail->total_after_tax, // ✅ CHANGED to total_after_tax
                'taxable' => $product->taxable ?? false,
                'tax_code' => $taxCode,
                'tax_rate' => $taxRate,
                'combined_key' => $key
            ]);
        }

        // Convert grouped items back to indexed array
        return array_values($groupedDetails);
    }

    /**
     * Get account code from product GL posting
     */
    protected function getAccountFromProduct($product): string
    {
        // ✅ Get account from product->gl_posting field
        if ($product && $product->gl_posting) {
            // Validate that the GL posting looks like a valid account code
            $glPosting = trim($product->gl_posting);

            // Basic validation for account format (adjust regex as needed)
            if (preg_match('/^\d{5}-\d{3}$/', $glPosting)) {
                return $glPosting;
            }

            // If gl_posting doesn't match expected format, log a warning but continue
            Log::warning('Invalid GL posting format found', [
                'product_id' => $product->id,
                'product_code' => $product->code,
                'gl_posting' => $glPosting
            ]);
        }

        // Fallback to default account if no valid gl_posting
        return $this->getDefaultAccountCode();
    }

    /**
     * Get default account code when product GL posting is not available
     */
    protected function getDefaultAccountCode(): string
    {
        return '40000-000'; // Default sales revenue account
    }

    /**
     * Get valid account code for products (legacy method - kept for fallback)
     */
    protected function getAccountCodeForProduct($product = null): string
    {
        // ✅ First try to get from product GL posting
        if ($product && $product->gl_posting) {
            $account = $this->getAccountFromProduct($product);
            if ($account !== $this->getDefaultAccountCode()) {
                return $account;
            }
        }

        // Legacy mapping as fallback (you can remove this if not needed)
        $accountMapping = [
            // TimeTec Access
            'TCL_ACCESS-NEW' => '40001-000',
            'TCL_ACCESS-RENEWAL' => '40001-000',

            // TimeTec Attendance
            'TCL_TA' => '40002-000',

            // TimeTec Leave
            'TCL_LEAVE' => '40003-000',

            // TimeTec Claim
            'TCL_CLAIM' => '40004-000',

            // TimeTec Payroll
            'TCL_PAYROLL' => '40005-000',

            // TimeTec Hire
            'TCL_HIRE-NEW' => '40006-000',
            'TCL_HIRE-RENEWAL' => '40006-000',

            // TimeTec Appraisal
            'TCL_APPRAISAL' => '40007-000',

            // TimeTec Power BI
            'TCL_POWER' => '40008-000',

            // Training Services
            'TRAINING' => '40100-000',
            'HRDF_TRAINING' => '40100-000',

            // Default fallback
            'DEFAULT' => '40000-000',
        ];

        if ($product && $product->code) {
            $productCode = $product->code;

            // Direct match
            if (isset($accountMapping[$productCode])) {
                return $accountMapping[$productCode];
            }

            // Partial match for similar products
            foreach ($accountMapping as $code => $account) {
                if (str_contains($productCode, $code) || str_contains($code, $productCode)) {
                    return $account;
                }
            }
        }

        // Return default account
        return $this->getDefaultAccountCode();
    }

    /**
     * Get quotation IDs from handover
     */
    protected function getQuotationIds(SoftwareHandover $handover): array
    {
        $quotationIds = [];

        // Get from proforma_invoice_product
        // if ($handover->proforma_invoice_product) {
        //     $productPis = is_string($handover->proforma_invoice_product)
        //         ? json_decode($handover->proforma_invoice_product, true)
        //         : $handover->proforma_invoice_product;
        //     if (is_array($productPis)) {
        //         $quotationIds = array_merge($quotationIds, $productPis);
        //     }
        // }

        // Get from proforma_invoice_hrdf
        if ($handover->proforma_invoice_hrdf) {
            $hrdfPis = is_string($handover->proforma_invoice_hrdf)
                ? json_decode($handover->proforma_invoice_hrdf, true)
                : $handover->proforma_invoice_hrdf;
            if (is_array($hrdfPis)) {
                $quotationIds = array_merge($quotationIds, $hrdfPis);
            }
        }

        // Get from software_hardware_pi
        // if ($handover->software_hardware_pi) {
        //     $swPis = is_string($handover->software_hardware_pi)
        //         ? json_decode($handover->software_hardware_pi, true)
        //         : $handover->software_hardware_pi;
        //     if (is_array($swPis)) {
        //         $quotationIds = array_merge($quotationIds, $swPis);
        //     }
        // }

        return array_unique($quotationIds);
    }

    /**
     * Generate multiple invoice numbers at once to avoid conflicts
     */
    protected function generateMultipleInvoiceNumbers(SoftwareHandover $handover, int $count): array
    {
        $year = date('y');
        $month = date('m');
        $yearMonth = $year . $month;

        // Get latest sequence from CRM HRDF invoices table - ONE TIME ONLY
        $latestInvoice = \App\Models\CrmHrdfInvoice::where('invoice_no', 'LIKE', "EHIN{$yearMonth}-%")
            ->orderByRaw('CAST(SUBSTRING(invoice_no, -4) AS UNSIGNED) DESC')
            ->first();

        $startSequence = 1;
        if ($latestInvoice) {
            preg_match("/EHIN{$yearMonth}-(\d+)/", $latestInvoice->invoice_no, $matches);
            $startSequence = (isset($matches[1]) ? intval($matches[1]) : 0) + 1;
        }

        // Generate all invoice numbers sequentially
        $invoiceNumbers = [];
        for ($i = 0; $i < $count; $i++) {
            $sequence = str_pad($startSequence + $i, 4, '0', STR_PAD_LEFT);
            $invoiceNumbers[] = "EHIN{$yearMonth}-{$sequence}";
        }

        Log::info('Generated multiple invoice numbers', [
            'handover_id' => $handover->id,
            'count' => $count,
            'start_sequence' => $startSequence,
            'invoice_numbers' => $invoiceNumbers
        ]);

        return $invoiceNumbers;
    }

    /**
     * Generate invoice document number
     */
    protected function generateInvoiceDocumentNumber(SoftwareHandover $handover, int $invoiceIndex = 0): string
    {
        // Format: EHIN + YYMM + sequence
        $year = date('y');   // 25 for 2025
        $month = date('m');  // 12 for December
        $yearMonth = $year . $month; // 2512

        // ✅ Get the latest sequence from CRM HRDF invoices table (unified approach)
        $latestInvoice = \App\Models\CrmHrdfInvoice::where('invoice_no', 'LIKE', "EHIN{$yearMonth}-%")
            ->orderByRaw('CAST(SUBSTRING(invoice_no, -4) AS UNSIGNED) DESC')
            ->first();

        $nextSequence = 1 + $invoiceIndex;
        if ($latestInvoice) {
            preg_match("/EHIN{$yearMonth}-(\d+)/", $latestInvoice->invoice_no, $matches);
            $nextSequence = (isset($matches[1]) ? intval($matches[1]) : 0) + 1 + $invoiceIndex;
        }

        // Format sequence with leading zeros (4 digits)
        $sequence = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

        return "EHIN{$yearMonth}-{$sequence}";
    }

    /**
     * Determine company by handover
     */
    protected function determineCompanyByHandover(SoftwareHandover $handover): string
    {
        // For now, always use sandbox for testing
        return 'TIMETEC CLOUD Sandbox';

        // TODO: Add logic based on subsidiary when ready for production
    }

    /**
     * Get existing debtors for dropdown
     */
    public function getExistingDebtors(SoftwareHandover $handover): array
    {
        // ✅ Fetch from debtors table with both code and name for display
        try {
            $debtors = \App\Models\Debtor::select('debtor_code', 'debtor_name')
                ->orderBy('debtor_name')
                ->get()
                ->mapWithKeys(function ($debtor) {
                    // Format: "ARM-A0001 - Company Name ABC"
                    $displayText = $debtor->debtor_code . ' - ' . $debtor->debtor_name;
                    return [$debtor->debtor_code => $displayText];
                })
                ->toArray();

            return $debtors;
        } catch (\Exception $e) {
            Log::warning('Failed to fetch debtors from database', [
                'error' => $e->getMessage()
            ]);

            // Fallback to empty array if database query fails
            return [];
        }
    }

    /**
     * Generate invoice preview data
     */
    public function generateInvoicePreview(SoftwareHandover $handover): array
    {
        $quotationGroups = $this->getQuotationGroups($handover);

        if (empty($quotationGroups)) {
            return [
                'invoices' => [],
                'total_invoices' => 0,
                'grand_total' => 0,
                'salesperson' => $this->getAutoCountSalesperson($handover),
                'company' => $handover->company_name,
                'message' => 'No quotation details found'
            ];
        }

        $invoices = [];
        $grandTotal = 0;

        foreach ($quotationGroups as $index => $quotationIds) {
            $details = QuotationDetail::whereIn('quotation_id', $quotationIds)
                ->with('product')
                ->get();

            // ✅ Group items by product code and unit price to combine duplicates
            $groupedItems = [];
            $invoiceTotal = 0;

            foreach ($details as $detail) {
                $productCode = $detail->product->code ?? 'Item-' . $detail->product_id;
                $unitPrice = (float) $detail->unit_price;
                $amount = (float) $detail->total_after_tax; // ✅ CHANGED to total_after_tax
                $quantity = (float) $detail->quantity;

                // Create a unique key based on product code and unit price
                $key = $productCode . '|' . $unitPrice;

                if (isset($groupedItems[$key])) {
                    // ✅ Combine with existing item
                    $groupedItems[$key]['quantity'] += $quantity;
                    $groupedItems[$key]['amount'] += $amount;
                } else {
                    // ✅ Add new item
                    $groupedItems[$key] = [
                        'code' => $productCode,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'amount' => $amount
                    ];
                }

                $invoiceTotal += $amount;
            }

            // Convert grouped items back to indexed array
            $items = array_values($groupedItems);

            $invoices[] = [
                'invoice_no' => $this->generateInvoiceDocumentNumber($handover, $index),
                'items' => $items,
                'total' => $invoiceTotal,
                'quotation_ids' => $quotationIds
            ];

            $grandTotal += $invoiceTotal;
        }

        return [
            'invoices' => $invoices,
            'total_invoices' => count($invoices),
            'grand_total' => $grandTotal,
            'salesperson' => $this->getAutoCountSalesperson($handover),
            'company' => $handover->company_name
        ];
    }

    /**
     * Main method to handle product invoice creation for software handover
     */
    public function processProductInvoiceCreation(
        SoftwareHandover $handover,
        array $formData
    ): array {
        try {
            $result = [
                'success' => false,
                'debtor_code' => null,
                'invoice_numbers' => [],
                'error' => null,
                'steps' => []
            ];

            // Check if product invoice creation is requested
            if (!($formData['create_product_invoice'] ?? false)) {
                return [
                    'success' => true,
                    'message' => 'Product invoice creation skipped',
                    'skipped' => true
                ];
            }

            // ✅ Get SOFTWARE ONLY quotation groups from proforma_invoice_product
            $quotationGroups = $this->getProductQuotationGroups($handover);

            if (empty($quotationGroups)) {
                $result['error'] = 'No software products found in proforma_invoice_product for invoice creation';
                return $result;
            }

            // ✅ Use selected debtor code
            $result['debtor_code'] = $formData['product_debtor_selection'];
            $result['steps'][] = "Using selected debtor: {$result['debtor_code']}";

            $result['steps'][] = "Found " . count($quotationGroups) . " software product invoice(s) to process";

            // ✅ Create separate invoice for each product group
            foreach ($quotationGroups as $index => $quotationIds) {
                $result['steps'][] = "Processing product invoice group " . ($index + 1) . "...";

                // Generate unique invoice number for each invoice
                $invoiceNo = $this->generateProductInvoiceDocumentNumber($handover, $index);
                $result['invoice_numbers'][] = $invoiceNo;

                // Create invoice for this specific group
                $invoiceResult = $this->createProductInvoiceForQuotationGroup($handover, $result['debtor_code'], $quotationIds, $invoiceNo);

                if (!$invoiceResult['success']) {
                    $result['error'] = "Failed to create product invoice " . ($index + 1) . ": " . $invoiceResult['error'];
                    $result['steps'][] = "Product invoice " . ($index + 1) . " creation failed";
                    return $result;
                }

                // ✅ Mark quotations as processed
                foreach ($quotationIds as $quotationId) {
                    \App\Models\Quotation::where('id', $quotationId)->update([
                        'autocount_generated_pi' => true
                    ]);
                }

                $result['steps'][] = "Product invoice " . ($index + 1) . " created successfully: {$invoiceNo}";
            }

            $result['success'] = true;
            $result['steps'][] = "All " . count($quotationGroups) . " product invoices created successfully";

            return $result;

        } catch (\Exception $e) {
            Log::error('AutoCount product invoice integration failed', [
                'handover_id' => $handover->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get SOFTWARE ONLY quotation groups from proforma_invoice_product
     */
    protected function getProductQuotationGroups(SoftwareHandover $handover): array
    {
        $groups = [];

        if ($handover->proforma_invoice_product) {
            $productPis = is_string($handover->proforma_invoice_product)
                ? json_decode($handover->proforma_invoice_product, true)
                : $handover->proforma_invoice_product;

            if (is_array($productPis)) {
                // ✅ Filter quotations that contain ONLY SOFTWARE products
                foreach ($productPis as $quotationId) {
                    // Check if this quotation has any software products
                    $hasSoftwareProducts = \App\Models\QuotationDetail::where('quotation_id', $quotationId)
                        ->whereHas('product', function($query) {
                            $query->where('solution', 'software');
                        })
                        ->exists();

                    // ✅ Only include quotations with software products
                    if ($hasSoftwareProducts) {
                        $groups[] = [$quotationId];

                        Log::info('Including quotation for software product invoice', [
                            'quotation_id' => $quotationId,
                            'handover_id' => $handover->id,
                        ]);
                    } else {
                        Log::info('Skipping quotation - no software products found', [
                            'quotation_id' => $quotationId,
                            'handover_id' => $handover->id,
                        ]);
                    }
                }
            }
        }

        return $groups;
    }

    /**
     * Create product invoice for quotation group (SOFTWARE ONLY)
     */
    protected function createProductInvoiceForQuotationGroup(SoftwareHandover $handover, string $customerCode, array $quotationIds, string $invoiceNo): array
    {
        // Get company name from quotation subsidiary if available
        $customerName = $handover->company_name;
        if (!empty($quotationIds)) {
            $quotation = Quotation::with('subsidiary', 'lead.companyDetail')->find($quotationIds[0]);
            if ($quotation) {
                if ($quotation->subsidiary_id && $quotation->subsidiary) {
                    $customerName = $quotation->subsidiary->company_name;
                } elseif ($quotation->lead && $quotation->lead->companyDetail) {
                    $customerName = $quotation->lead->companyDetail->company_name;
                }
            }
        }

        $invoiceData = [
            'company' => $this->determineCompanyByHandover($handover),
            'customer_code' => $customerCode,
            'document_no' => $invoiceNo,
            'document_date' => now()->format('Y-m-d'),
            'description' => 'Software Product Invoice - ' . $customerName,
            'salesperson' => $this->getAutoCountSalesperson($handover),
            'round_method' => 0,
            'inclusive' => true,
            'details' => $this->getSoftwareProductInvoiceDetails($quotationIds),
            'uDFCustomerName' => $customerName,
            'uDFLicenseNumber' => $handover->tt_invoice_number ?? '',
        ];

        return $this->autoCountService->createInvoice($invoiceData);
    }

    /**
     * Get SOFTWARE ONLY invoice details from quotation IDs
     */
    protected function getSoftwareProductInvoiceDetails(array $quotationIds): array
    {
        if (empty($quotationIds)) {
            return [[
                'account' => $this->getDefaultAccountCode(),
                'itemCode' => 'TCL_ACCESS-NEW',
                'location' => 'HQ',
                'quantity' => 1,
                'uom' => 'USER',
                'unitPrice' => 1275,
                'amount' => 1275,
                'taxCode' => 'SV-8',
                'taxRate' => 8,
            ]];
        }

        // ✅ Only get quotation details for SOFTWARE products
        $quotationDetails = QuotationDetail::whereIn('quotation_id', $quotationIds)
            ->whereHas('product', function($query) {
                $query->where('solution', 'software');
            })
            ->with('product')
            ->get();

        $groupedDetails = [];

        foreach ($quotationDetails as $detail) {
            $product = $detail->product;

            // ✅ Double-check that this is a software product
            if ($product->solution !== 'software') {
                Log::warning('Non-software product found in software invoice processing', [
                    'product_id' => $product->id,
                    'product_code' => $product->code,
                    'solution' => $product->solution,
                ]);
                continue; // Skip non-software products
            }

            $productCode = $product->code ?? 'ITEM-' . $product->id;
            $baseUnitPrice = (float) $detail->unit_price;
            $account = $this->getAccountFromProduct($product);

            // ✅ Tax information
            $taxCode = '';
            $taxRate = 0;
            if ($product && $product->taxable) {
                $taxCode = 'SV-8';
                $taxRate = 8;
            }

            // ✅ Calculate tax-inclusive unit price for AutoCount
            $taxInclusiveUnitPrice = $baseUnitPrice;
            if ($product && $product->taxable && $taxRate > 0) {
                // Calculate tax-inclusive price: base price * (1 + tax rate)
                $taxInclusiveUnitPrice = $baseUnitPrice * (1 + ($taxRate / 100));
            }

            $key = $productCode . '|' . $taxInclusiveUnitPrice . '|' . $account . '|' . $taxCode . '|' . $taxRate;

            if (isset($groupedDetails[$key])) {
                $groupedDetails[$key]['quantity'] += (float) $detail->quantity;
                $groupedDetails[$key]['amount'] += (float) $detail->total_after_tax;
            } else {
                $groupedDetails[$key] = [
                    'account' => $account,
                    'itemCode' => $productCode,
                    'location' => 'HQ',
                    'quantity' => (float) $detail->quantity,
                    'uom' => 'USER',
                    'unitPrice' => $taxInclusiveUnitPrice, // ✅ Use tax-inclusive price for AutoCount
                    'amount' => (float) $detail->total_after_tax,
                    'taxCode' => $taxCode,
                    'taxRate' => $taxRate,
                ];
            }

            Log::info('Software product included in invoice', [
                'quotation_ids' => $quotationIds,
                'product_id' => $product->id,
                'product_code' => $productCode,
                'solution' => $product->solution,
                'amount' => $detail->total_after_tax,
                'uom' => 'USER',
            ]);
        }

        return array_values($groupedDetails);
    }

    /**
     * Generate product invoice preview (SOFTWARE ONLY)
     */
    public function generateProductInvoicePreview(SoftwareHandover $handover, string $selectedDebtor = null): array
    {
        $quotationGroups = $this->getProductQuotationGroups($handover);

        if (empty($quotationGroups)) {
            return [
                'invoices' => [],
                'total_invoices' => 0,
                'grand_total' => 0,
                'salesperson' => $this->getAutoCountSalesperson($handover),
                'company' => $handover->company_name,
                'message' => 'No software products found in proforma_invoice_product'
            ];
        }

        $invoices = [];
        $grandTotal = 0;

        foreach ($quotationGroups as $index => $quotationIds) {
            // ✅ Only get SOFTWARE products
            $details = QuotationDetail::whereIn('quotation_id', $quotationIds)
                ->whereHas('product', function($query) {
                    $query->where('solution', 'software');
                })
                ->with('product')
                ->get();

            $groupedItems = [];
            $invoiceTotal = 0;

            foreach ($details as $detail) {
                $product = $detail->product;

                // Skip if not software (additional safety check)
                if ($product->solution !== 'software') {
                    continue;
                }

                $productCode = $product->code ?? 'Item-' . $detail->product_id;
                $unitPrice = (float) $detail->unit_price;
                $amount = (float) $detail->total_after_tax;
                $quantity = (float) $detail->quantity;

                $key = $productCode . '|' . $unitPrice;

                if (isset($groupedItems[$key])) {
                    $groupedItems[$key]['quantity'] += $quantity;
                    $groupedItems[$key]['amount'] += $amount;
                } else {
                    $groupedItems[$key] = [
                        'code' => $productCode,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'amount' => $amount
                    ];
                }

                $invoiceTotal += $amount;
            }

            if (!empty($groupedItems)) {
                $items = array_values($groupedItems);

                $invoices[] = [
                    'invoice_no' => $this->generateProductInvoiceDocumentNumber($handover, $index),
                    'items' => $items,
                    'total' => $invoiceTotal,
                    'quotation_ids' => $quotationIds
                ];

                $grandTotal += $invoiceTotal;
            }
        }

        return [
            'invoices' => $invoices,
            'total_invoices' => count($invoices),
            'grand_total' => $grandTotal,
            'salesperson' => $this->getAutoCountSalesperson($handover),
            'company' => $handover->company_name
        ];
    }

    /**
     * Generate product invoice document number
     */
    protected function generateProductInvoiceDocumentNumber(SoftwareHandover $handover, int $invoiceIndex = 0): string
    {
        // Format: PROD + YYMM + sequence (different from HRDF invoices)
        $year = date('y');
        $month = date('m');
        $yearMonth = $year . $month;

        // Get the latest sequence from product invoices
        $latestInvoice = \App\Models\CrmHrdfInvoice::where('invoice_no', 'LIKE', "EPIN{$yearMonth}-%")
            ->orderByRaw('CAST(SUBSTRING(invoice_no, -4) AS UNSIGNED) DESC')
            ->first();

        $nextSequence = 1 + $invoiceIndex;
        if ($latestInvoice) {
            preg_match("/EPIN{$yearMonth}-(\d+)/", $latestInvoice->invoice_no, $matches);
            $nextSequence = (isset($matches[1]) ? intval($matches[1]) : 0) + 1 + $invoiceIndex;
        }

        $sequence = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);

        return "EPIN{$yearMonth}-{$sequence}";
    }

    public function getExistingDebtorsFromAutoCount(): array
    {
        try {
            Log::info('Fetching debtors from local database table');

            // ✅ Get debtors from local database table
            $debtors = \App\Models\Debtor::select('debtor_code', 'debtor_name')
                ->orderBy('debtor_name')
                ->get()
                ->mapWithKeys(function ($debtor) {
                    // Format: "ARM-C0001 - Company Name"
                    $displayText = $debtor->debtor_code . ' - ' . $debtor->debtor_name;
                    return [$debtor->debtor_code => $displayText];
                })
                ->toArray();

            Log::info('Retrieved debtors from database', [
                'count' => count($debtors),
                'debtors' => array_keys($debtors)
            ]);

            return $debtors;

        } catch (\Exception $e) {
            Log::error('Failed to fetch debtors from database table', [
                'error' => $e->getMessage()
            ]);

            // Return empty array if database query fails
            return [];
        }
    }
}
