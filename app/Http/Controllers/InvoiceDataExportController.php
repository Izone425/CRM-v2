<?php
namespace App\Http\Controllers;

use App\Classes\Encryptor;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Log;

class InvoiceDataExportController extends Controller
{
    public function exportInvoiceData($softwareHandoverId)
    {
        try {
            Log::info('Starting Invoice Data export for Software Handover ID: ' . $softwareHandoverId);

            // Decrypt the software handover ID
            $decryptedHandoverId = Encryptor::decrypt($softwareHandoverId);
            Log::info('Decrypted software handover ID: ' . $decryptedHandoverId);

            // Get the software handover with related data
            $softwareHandover = SoftwareHandover::with([
                'lead.companyDetail',
                'lead.eInvoiceDetail'
            ])->findOrFail($decryptedHandoverId);

            Log::info('Software handover found: ' . $softwareHandover->id);
            Log::info('Training type: ' . ($softwareHandover->training_type ?? 'null'));

            $lead = $softwareHandover->lead;
            if (!$lead) {
                return back()->with('error', 'No lead found for this software handover.');
            }

            Log::info('Lead found: ' . $lead->id);

            $piIds = [];

            Log::info('Determining PI fields based on training type: ' . ($softwareHandover->training_type ?? 'null'));

            if ($softwareHandover->training_type === 'online_webinar_training') {
                // ✅ For webinar training, only use proforma_invoice_product
                Log::info('Training type is online_webinar_training - checking proforma_invoice_product only');

                if (!empty($softwareHandover->proforma_invoice_product)) {
                    // ✅ Fix: Convert to string for logging
                    $rawProductData = $softwareHandover->proforma_invoice_product;
                    Log::info('proforma_invoice_product raw data: ' . (is_array($rawProductData) ? json_encode($rawProductData) : $rawProductData));

                    $productPiIds = is_string($softwareHandover->proforma_invoice_product)
                        ? json_decode($softwareHandover->proforma_invoice_product, true)
                        : $softwareHandover->proforma_invoice_product;

                    Log::info('proforma_invoice_product decoded: ' . json_encode($productPiIds));

                    if (is_array($productPiIds)) {
                        $piIds = array_merge($piIds, $productPiIds);
                        Log::info('Added ' . count($productPiIds) . ' PI IDs from proforma_invoice_product');
                    }
                } else {
                    Log::info('proforma_invoice_product is empty');
                }
            } elseif ($softwareHandover->training_type === 'online_hrdf_training') {
                // ✅ For HRDF training, use non_hrdf_pi and proforma_invoice_hrdf
                Log::info('Training type is online_hrdf_training - checking non_hrdf_pi and proforma_invoice_hrdf');

                // Check non_hrdf_pi field
                if (!empty($softwareHandover->non_hrdf_pi)) {
                    // ✅ Fix: Convert to string for logging
                    $rawNonHrdfData = $softwareHandover->non_hrdf_pi;
                    Log::info('non_hrdf_pi raw data: ' . (is_array($rawNonHrdfData) ? json_encode($rawNonHrdfData) : $rawNonHrdfData));

                    $nonHrdfPiIds = is_string($softwareHandover->non_hrdf_pi)
                        ? json_decode($softwareHandover->non_hrdf_pi, true)
                        : $softwareHandover->non_hrdf_pi;

                    Log::info('non_hrdf_pi decoded: ' . json_encode($nonHrdfPiIds));

                    if (is_array($nonHrdfPiIds)) {
                        $piIds = array_merge($piIds, $nonHrdfPiIds);
                        Log::info('Added ' . count($nonHrdfPiIds) . ' PI IDs from non_hrdf_pi');
                    }
                } else {
                    Log::info('non_hrdf_pi is empty');
                }

                // Check proforma_invoice_hrdf field
                if (!empty($softwareHandover->proforma_invoice_hrdf)) {
                    // ✅ Fix: Convert to string for logging
                    $rawHrdfData = $softwareHandover->proforma_invoice_hrdf;
                    Log::info('proforma_invoice_hrdf raw data: ' . (is_array($rawHrdfData) ? json_encode($rawHrdfData) : $rawHrdfData));

                    $hrdfPiIds = is_string($softwareHandover->proforma_invoice_hrdf)
                        ? json_decode($softwareHandover->proforma_invoice_hrdf, true)
                        : $softwareHandover->proforma_invoice_hrdf;

                    Log::info('proforma_invoice_hrdf decoded: ' . json_encode($hrdfPiIds));

                    if (is_array($hrdfPiIds)) {
                        $piIds = array_merge($piIds, $hrdfPiIds);
                        Log::info('Added ' . count($hrdfPiIds) . ' PI IDs from proforma_invoice_hrdf');
                    }
                } else {
                    Log::info('proforma_invoice_hrdf is empty');
                }
            } else {
                // ✅ For other training types, check all PI fields as fallback
                Log::info('Training type is not recognized (' . ($softwareHandover->training_type ?? 'null') . ') - checking all PI fields');

                $piFields = [
                    'proforma_invoice_product',
                    'software_hardware_pi',
                    'non_hrdf_pi',
                    'proforma_invoice_hrdf'
                ];

                foreach ($piFields as $field) {
                    Log::info("Checking {$field} field...");
                    if (!empty($softwareHandover->$field)) {
                        // ✅ Fix: Convert to string for logging
                        $rawFieldData = $softwareHandover->$field;
                        Log::info("{$field} raw data: " . (is_array($rawFieldData) ? json_encode($rawFieldData) : $rawFieldData));

                        $fieldPiIds = is_string($softwareHandover->$field)
                            ? json_decode($softwareHandover->$field, true)
                            : $softwareHandover->$field;

                        Log::info("{$field} decoded: " . json_encode($fieldPiIds));

                        if (is_array($fieldPiIds)) {
                            $piIds = array_merge($piIds, $fieldPiIds);
                            Log::info('Added ' . count($fieldPiIds) . " PI IDs from {$field}");
                        }
                    } else {
                        Log::info("{$field} is empty");
                    }
                }
            }

            // Get quotations based on the PI IDs from software handover
            $quotations = \App\Models\Quotation::whereIn('id', $piIds)
                ->with(['items.product', 'sales_person', 'subsidiary']) // ✅ Added subsidiary relationship
                ->get();

            Log::info('Found ' . $quotations->count() . ' quotations');

            if ($quotations->isEmpty()) {
                Log::warning('No quotations found for PI IDs: ' . implode(', ', $piIds));
                return back()->with('error', 'No quotations found for the selected proforma invoices.');
            }

            // Collect all items from all quotations
            $allItems = collect();
            foreach ($quotations as $quotation) {
                foreach ($quotation->items as $item) {
                    $productCode = $item->product ? $item->product->code : 'No Product';
                    $pushToAutocountRaw = $item->product ? $item->product->push_to_autocount : null;

                    Log::info('Debug item - Product: ' . $productCode . ' - push_to_autocount raw: ' . var_export($pushToAutocountRaw, true) . ' - type: ' . gettype($pushToAutocountRaw));

                    // ✅ More flexible check for truthy values
                    $shouldInclude = $item->product && (
                        $pushToAutocountRaw === true ||
                        $pushToAutocountRaw === 1 ||
                        $pushToAutocountRaw === '1' ||
                        strtolower($pushToAutocountRaw) === 'true'
                    );

                    if ($shouldInclude) {
                        $allItems->push($item);
                        Log::info('Added item to export - Product: ' . $productCode . ' (push_to_autocount matches truthy value)');
                    } else {
                        Log::info('Skipped item - Product: ' . $productCode . ' (push_to_autocount does not match truthy value)');
                    }
                }
            }

            Log::info('Total items collected: ' . $allItems->count());

            // ✅ Check if we have any items after filtering
            if ($allItems->isEmpty()) {
                Log::warning('No items with push_to_autocount=true found in any quotations');
                return back()->with('error', 'No products eligible for AutoCount export found in the selected proforma invoices.');
            }
            // Create Excel file
            Log::info('Creating Excel spreadsheet...');
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Invoice Data');

            // ✅ Generate common invoice data once (based on first quotation for reference)
            $firstQuotation = $quotations->first();
            Log::info('Using first quotation for reference: ID ' . $firstQuotation->id);

            // ✅ DebtorCode - Make it empty as requested
            $debtorCode = '';

            // ✅ DocNo based on training type from software handover
            $docNo = match($softwareHandover->training_type) {
                'online_webinar_training' => 'EPIN',
                'online_hrdf_training' => 'EHIN',
                default => 'EGIN'
            };
            Log::info('DocNo determined: ' . $docNo . ' (training type: ' . ($softwareHandover->training_type ?? 'null') . ')');

            // ✅ DocDate is today's date in j/n/Y format
            $docDate = date('j/n/Y');
            Log::info('DocDate: ' . $docDate);

            // ✅ SalesAgent - Map to specific values
            $salesAgent = $this->mapSalesAgent($lead, $firstQuotation);
            Log::info('Sales Agent determined: ' . $salesAgent . ' (Lead salesperson: ' . $lead->salesperson . ')');

            // ✅ CurrencyCode from lead->eInvoiceDetail->currency with fallback
            $currencyCode = 'MYR'; // Default fallback
            if ($lead->eInvoiceDetail && !empty($lead->eInvoiceDetail->currency)) {
                $currencyCode = $lead->eInvoiceDetail->currency;
                Log::info('Currency from eInvoiceDetail: ' . $currencyCode);
            } elseif (!empty($firstQuotation->currency)) {
                $currencyCode = $firstQuotation->currency;
                Log::info('Currency from quotation: ' . $currencyCode);
            } else {
                Log::info('Using default currency: ' . $currencyCode);
            }

            // Currency rate based on currency
            $currencyRate = $currencyCode === 'USD' ? null : '1';

            // Determine UDF fields based on software handover training type
            $salesAdmin = $this->mapSalesAdmin($lead);
            Log::info('Sales Admin determined: ' . $salesAdmin);

            $billingType = 'New';
            $cancelled = '';

            if (auth()->id() === 5) {
                $udfSupport = 'FATIMAH';
            } elseif(auth()->id() === 52) {
                $udfSupport = 'IRDINA';
            } else {
                $udfSupport = 'YAT';
            }

            // ✅ Create header row
            $headers = [
                'DocNo',
                'DocDate',
                'CompanyName',
                'DebtorCode',
                'SalesAgent',
                'CurrencyCode',
                'CurrencyRate',
                'UDF_IV_LicenseNumber',
                'UDF_IV_SalesAdmin',
                'UDF_IV_Support',
                'UDF_IV_BillingType',
                'Cancelled',
                'ItemCode',
                'Qty',
                'UnitPrice',
                'TaxCode',
                'TariffCode'
            ];

            // Apply header row
            $sheet->fromArray([$headers], null, 'A1');
            Log::info('Headers applied to spreadsheet');

            // Style the header row
            $headerStyle = [
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ];
            $sheet->getStyle('A1:Q1')->applyFromArray($headerStyle);
            Log::info('Header styling applied');

            $row = 2; // Start from row 2 for data

            // ✅ Process each quotation (PI) separately
            Log::info('Processing ' . $quotations->count() . ' quotations (PIs)...');

            foreach ($quotations as $quotationIndex => $quotation) {
                Log::info('Processing PI/Quotation ' . ($quotationIndex + 1) . ' - ID: ' . $quotation->id);

                // ✅ Filter quotation items - flexible check for push_to_autocount
                $quotationItems = $quotation->items->filter(function ($item) {
                    if (!$item->product) {
                        Log::info('Excluding item - No product found');
                        return false;
                    }

                    $pushToAutocountRaw = $item->product->push_to_autocount;
                    $productCode = $item->product->code;

                    Log::info('Debug PI item - Product: ' . $productCode . ' - push_to_autocount raw: ' . var_export($pushToAutocountRaw, true) . ' - type: ' . gettype($pushToAutocountRaw));

                    // ✅ Flexible check for truthy values
                    $shouldInclude = $pushToAutocountRaw === true ||
                                    $pushToAutocountRaw === 1 ||
                                    $pushToAutocountRaw === '1' ||
                                    strtolower($pushToAutocountRaw) === 'true';

                    if ($shouldInclude) {
                        Log::info('Including item in PI export - Product: ' . $productCode);
                        return true;
                    } else {
                        Log::info('Excluding item from PI export - Product: ' . $productCode);
                        return false;
                    }
                });

                if ($quotationItems->isEmpty()) {
                    Log::warning('No items with push_to_autocount=true found for quotation ID: ' . $quotation->id . ' - skipping this PI');
                    continue;
                }

                // Rest of the processing logic remains the same...
                $firstItem = $quotationItems->first();
                $firstProduct = $firstItem ? $firstItem->product : null;

                Log::info('Adding full invoice row for first item of PI ' . $quotation->id . ' - Product: ' . ($firstProduct ? $firstProduct->code : 'NULL'));

                $firstRowData = [
                    $docNo,                                             // DocNo
                    $docDate,                                           // DocDate
                    $this->getCompanyName($quotation, $softwareHandover), // CompanyName (from subsidiary or software handover)
                    $debtorCode,                                        // DebtorCode (empty)
                    $salesAgent,                                        // SalesAgent
                    $currencyCode,                                      // CurrencyCode
                    $currencyRate,                                      // CurrencyRate
                    '',
                    $salesAdmin,                                        // UDF_IV_SalesAdmin
                    $udfSupport,                                        // UDF_IV_Support (FATIMAH if user ID = 5, else YAT)
                    $billingType,                                       // UDF_IV_BillingType
                    $cancelled,                                         // Cancelled
                    $firstProduct ? $firstProduct->code : '',           // ItemCode (first product)
                    $firstItem ? ($firstItem->quantity ?? 1) : 1,      // Qty (first product)
                    // ✅ Calculate unit price based on product solution
                    $firstItem ? $this->calculateUnitPrice($firstItem, $firstProduct) : 0, // UnitPrice (calculated)
                    $firstItem ? $this->getTaxCode($firstItem, $firstProduct, $quotation) : 'NTS', // ✅ TaxCode based on taxable & currency
                    $firstProduct ? ($firstProduct->tariff_code ?? '') : '', // TariffCode (first product)
                ];

                $sheet->fromArray([$firstRowData], null, 'A' . $row);
                Log::info('Added full invoice row for PI ' . $quotation->id . ' at row ' . $row);
                $row++;

                // ✅ Remaining items of this PI get only product data
                $remainingItems = $quotationItems->skip(1);
                Log::info('Processing ' . $remainingItems->count() . ' remaining items for PI ' . $quotation->id);

                foreach ($remainingItems as $item) {
                    $product = $item->product;
                    Log::info('Processing additional item - Product: ' . ($product ? $product->code : 'NULL') . ' for PI ' . $quotation->id);

                    // ✅ For additional products, only fill the product-related columns (L-P)
                    $additionalRowData = [
                        '', '', '', '', '', '', '', '', '', '', '', '', // Empty columns A-K (invoice info columns) - now 11 empty columns
                        $product ? $product->code : '',           // ItemCode (column L)
                        $item->quantity ?? 1,                     // Qty (column M)
                        // ✅ Calculate unit price based on product solution
                        $this->calculateUnitPrice($item, $product), // UnitPrice (calculated) (column N)
                        $this->getTaxCode($item, $product, $quotation), // ✅ TaxCode based on taxable & currency (column O)
                        $product ? $product->tariff_code : '',    // TariffCode (column P)
                    ];

                    $sheet->fromArray([$additionalRowData], null, 'A' . $row);
                    $row++;
                }

                Log::info('Completed processing PI ' . $quotation->id . ', current row: ' . $row);
            }

            Log::info('Processed all ' . $quotations->count() . ' PIs, final row: ' . ($row - 1));

            // Apply yellow highlighting to specific cells in the 2nd row (first data row)
            if ($row > 2) { // Only apply if we have data rows
                $yellowStyle = [
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'color' => ['rgb' => 'FFFF00'] // Yellow color
                    ]
                ];

                // Apply yellow color to DocNo (A2), DebtorCode (D2), and UDF_IV_LicenseNumber (H2)
                $sheet->getStyle('A2')->applyFromArray($yellowStyle);
                $sheet->getStyle('D2')->applyFromArray($yellowStyle);
                $sheet->getStyle('H2')->applyFromArray($yellowStyle);

                Log::info('Applied yellow highlighting to DocNo, DebtorCode, and UDF_IV_LicenseNumber in row 2');
            }

            // Auto-size columns
            foreach (range('A', 'P') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Save as Excel file
            Log::info('Saving Excel file...');
            $tempFile = tempnam(sys_get_temp_dir(), 'invoice_data_export_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);
            Log::info('Excel file saved to: ' . $tempFile);

            // Create filename with handover ID
            $companyName = $lead->companyDetail->company_name ?? 'Company';
            $handoverId = 'SW_' . str_pad($softwareHandover->id, 3, '0', STR_PAD_LEFT);
            $filename = 'Invoice_Data_' . $handoverId . '_' . str_replace([' ', '/', '\\', '&'], '_', $companyName) . '_' . date('Y-m-d') . '.xlsx';

            Log::info('About to send Invoice Data Excel file: ' . $filename);

            // Return file as download
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('Invoice Data export error: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('error', 'Error exporting invoice data: ' . $e->getMessage());
        }
    }

    private function mapSalesAdmin($lead)
    {
        // ✅ Allowed sales admin values (same as sales agent)
        $allowedSalesAdmins = [
            'JAJA',
            'AFIFAH',
            'SHAHILAH',
            'SHEENA'
        ];

        // Get lead owner name
        $salesAdminName = '';

        // ✅ Try lead owner first (get actual user name, not just ID)
        if ($lead->lead_owner) {
            $leadOwnerName = strtoupper(trim($lead->lead_owner));
            Log::info('Checking lead owner name for sales admin: ' . $leadOwnerName);

            // ✅ Map specific full names to their short versions
            if (strpos($leadOwnerName, 'NURUL NAJAA NADIAH') !== false || strpos($leadOwnerName, 'NAJAA') !== false) {
                $salesAdminName = 'JAJA';
                Log::info('Mapped "' . $leadOwnerName . '" to JAJA for sales admin');
            } elseif (strpos($leadOwnerName, 'AFIFAH SHAHILAH') !== false) {
                $salesAdminName = 'AFIFAH';
                Log::info('Mapped "' . $leadOwnerName . '" to AFIFAH for sales admin');
            } elseif (strpos($leadOwnerName, 'SHEENA') !== false) {
                $salesAdminName = 'SHEENA';
                Log::info('Mapped "' . $leadOwnerName . '" to SHEENA for sales admin');
            } else {
                $salesAdminName = $leadOwnerName;
            }
        }

        // Check if the sales admin name matches any allowed values (exact match)
        if (in_array($salesAdminName, $allowedSalesAdmins)) {
            Log::info('Found exact match for sales admin: ' . $salesAdminName);
            return $salesAdminName;
        }

        // Try partial matching (in case the full name contains the allowed name)
        foreach ($allowedSalesAdmins as $allowedAdmin) {
            if (strpos($salesAdminName, $allowedAdmin) !== false) {
                Log::info('Found partial match for sales admin: ' . $allowedAdmin . ' in ' . $salesAdminName);
                return $allowedAdmin;
            }
        }

        // If no match found, return empty string
        Log::warning('Sales admin name "' . $salesAdminName . '" not found in allowed list. Using empty string.');
        return '';
    }

    /**
     * Map sales agent to specific allowed values
     */
    private function mapSalesAgent($lead, $quotation)
    {
        // ✅ Updated allowed salesperson values to include lead owner names
        $allowedSalesAgents = [
            'JONATHAN',
            'WIRSON',
            'MUIM',
            'YASMIN',
            'FARHANAH',
            'JOSHUA',
            'AZIZ',
            'BARI',
            'VINCE',
            'JAJA',           // ✅ Added for lead owner
            'AFIFAH'.
            'SHAHILAH', // ✅ Added for lead owner
            'SHEENA'          // ✅ Added for lead owner
        ];

        // Get salesperson name from various sources
        $salespersonName = '';

        // If no lead owner or lead owner not in allowed list, try lead salesperson
        if (empty($salespersonName) || !in_array($salespersonName, $allowedSalesAgents)) {
            $salespersonUser = $lead->getSalespersonUser();
            if ($salespersonUser && $salespersonUser->name) {
                $salespersonName = strtoupper(trim($salespersonUser->name));
                Log::info('Checking lead salesperson name: ' . $salespersonName);
            }
        }

        // Fallback to quotation's sales_person
        if (empty($salespersonName) || !in_array($salespersonName, $allowedSalesAgents)) {
            if ($quotation->sales_person && $quotation->sales_person->name) {
                $salespersonName = strtoupper(trim($quotation->sales_person->name));
                Log::info('Checking quotation sales person name: ' . $salespersonName);
            }
        }

        // Final fallback: if salesperson is stored as string
        if (empty($salespersonName) || !in_array($salespersonName, $allowedSalesAgents)) {
            if ($lead->salesperson && is_string($lead->salesperson) && !is_numeric($lead->salesperson)) {
                $salespersonName = strtoupper(trim($lead->salesperson));
                Log::info('Checking lead salesperson string: ' . $salespersonName);
            }
        }

        // Check if the salesperson name matches any allowed values (exact match)
        if (in_array($salespersonName, $allowedSalesAgents)) {
            Log::info('Found exact match: ' . $salespersonName);
            return $salespersonName;
        }

        // Try partial matching (in case the full name contains the allowed name)
        foreach ($allowedSalesAgents as $allowedAgent) {
            if (strpos($salespersonName, $allowedAgent) !== false) {
                Log::info('Found partial match: ' . $allowedAgent . ' in ' . $salespersonName);
                return $allowedAgent;
            }
        }

        // If no match found, return empty string or default
        Log::warning('Salesperson name "' . $salespersonName . '" not found in allowed list. Using empty string.');
        return '';
    }

    private function calculateUnitPrice($item, $product)
    {
        $baseUnitPrice = $item->unit_price ?? 0;

        // If no product or no solution defined, return base unit price
        if (!$product || !$product->solution) {
            Log::info('No product or solution found, using base unit price: ' . $baseUnitPrice);
            return $baseUnitPrice;
        }

        // Check if product solution is "software"
        if (strtolower(trim($product->solution)) === 'software') {
            $subscriptionPeriod = $item->subscription_period ?? 1; // Default to 1 if not set
            $calculatedPrice = $baseUnitPrice * $subscriptionPeriod;

            Log::info('Product solution is software - Base price: ' . $baseUnitPrice .
                    ', Subscription period: ' . $subscriptionPeriod .
                    ', Calculated price: ' . $calculatedPrice);

            return $calculatedPrice;
        } else {
            // For non-software solutions, just return the base unit price
            Log::info('Product solution is not software (' . $product->solution . '), using base unit price: ' . $baseUnitPrice);
            return $baseUnitPrice;
        }
    }

    private function getCompanyName($quotation, $softwareHandover)
    {
        // Check if quotation has subsidiary_id and subsidiary relationship
        if ($quotation->subsidiary_id && $quotation->subsidiary && $quotation->subsidiary->company_name) {
            $companyName = $quotation->subsidiary->company_name;
            Log::info('Company name from subsidiary: ' . $companyName . ' (subsidiary_id: ' . $quotation->subsidiary_id . ')');
            return $companyName;
        }

        // Fallback to software handover company_name
        if ($softwareHandover->company_name) {
            $companyName = $softwareHandover->company_name;
            Log::info('Company name from software handover: ' . $companyName);
            return $companyName;
        }

        // Final fallback to empty string
        Log::warning('No company name found in subsidiary or software handover');
        return '';
    }

    private function getTaxCode($item, $product, $quotation)
    {
        // If currency is USD, always return NTS regardless of taxable status
        if ($quotation->currency === 'USD') {
            Log::info('Currency is USD - Tax code: NTS (regardless of taxable status)');
            return 'NTS';
        }

        // For MYR currency, check product taxable status
        if ($quotation->currency === 'MYR') {
            if ($product) {
                $taxableRaw = $product->taxable;
                Log::info('Product taxable raw value: ' . var_export($taxableRaw, true) . ' - type: ' . gettype($taxableRaw));

                // ✅ Flexible check for taxable values
                $isTaxable = $taxableRaw === true ||
                            $taxableRaw === 1 ||
                            $taxableRaw === '1' ||
                            (is_string($taxableRaw) && strtolower($taxableRaw) === 'true');

                if ($isTaxable) {
                    Log::info('Currency is MYR and product is taxable - Tax code: SV-8');
                    return 'SV-8';
                } else {
                    Log::info('Currency is MYR but product is not taxable - Tax code: NTS');
                    return 'NTS';
                }
            } else {
                Log::info('No product found - Tax code: NTS');
                return 'NTS';
            }
        }

        // Default fallback for other currencies or null currency
        Log::info('Currency is ' . ($quotation->currency ?? 'null') . ' - defaulting to NTS');
        return 'NTS';
    }
}
