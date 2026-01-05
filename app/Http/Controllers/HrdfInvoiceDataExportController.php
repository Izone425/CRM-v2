<?php

namespace App\Http\Controllers;

use App\Classes\Encryptor;
use App\Models\CrmHrdfInvoiceV2;
use App\Models\HrdfClaim;
use App\Models\Quotation;
use App\Models\User;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Log;

class HrdfInvoiceDataExportController extends Controller
{
    public function exportHrdfInvoiceData($hrdfInvoice)
    {
        try {
            Log::info('=== HRDF INVOICE EXPORT START ===');
            Log::info('Raw HRDF Invoice ID parameter received: ' . $hrdfInvoice);
            Log::info('Parameter type: ' . gettype($hrdfInvoice));
            Log::info('Parameter length: ' . strlen($hrdfInvoice));

            // Decrypt the HRDF invoice ID
            try {
                $decryptedInvoiceId = Encryptor::decrypt($hrdfInvoice);
                Log::info('Successfully decrypted HRDF invoice ID: ' . $decryptedInvoiceId);
            } catch (\Exception $e) {
                Log::error('Decryption failed: ' . $e->getMessage());
                return back()->with('error', 'Invalid invoice ID format.');
            }

            // Get the HRDF invoice record
            try {
                $hrdfInvoice = CrmHrdfInvoiceV2::with(['hrdfClaim'])->findOrFail($decryptedInvoiceId);
                Log::info('HRDF invoice found successfully:');
                Log::info('- ID: ' . $hrdfInvoice->id);
                Log::info('- Invoice No: ' . $hrdfInvoice->invoice_no);
                Log::info('- Company Name: ' . $hrdfInvoice->company_name);
                Log::info('- Handover Type: ' . $hrdfInvoice->handover_type);
                Log::info('- Proforma Invoice Data: ' . ($hrdfInvoice->proforma_invoice_data ?? 'NULL'));
            } catch (\Exception $e) {
                Log::error('HRDF Invoice not found: ' . $e->getMessage());
                return back()->with('error', 'HRDF Invoice not found.');
            }

            // Get the quotation data from proforma_invoice_data
            if (!$hrdfInvoice->proforma_invoice_data) {
                Log::warning('No proforma invoice data found for HRDF invoice ID: ' . $hrdfInvoice->id);
                Log::warning('proforma_invoice_data field value: ' . var_export($hrdfInvoice->proforma_invoice_data, true));
                return back()->with('error', 'No proforma invoice data found for this HRDF invoice.');
            }

            Log::info('Attempting to find quotation with ID: ' . $hrdfInvoice->proforma_invoice_data);

            try {
                $quotation = Quotation::with(['items.product', 'sales_person', 'subsidiary', 'lead'])
                    ->find($hrdfInvoice->proforma_invoice_data);

                if (!$quotation) {
                    Log::warning('No quotation found for ID: ' . $hrdfInvoice->proforma_invoice_data);

                    // Check if quotation exists without relationships
                    $basicQuotation = Quotation::find($hrdfInvoice->proforma_invoice_data);
                    if ($basicQuotation) {
                        Log::info('Basic quotation exists but has issue with relationships');
                    } else {
                        Log::error('Quotation does not exist in database at all');
                    }

                    return back()->with('error', 'No quotation found for the proforma invoice data.');
                }

                Log::info('Quotation found successfully:');
                Log::info('- Quotation ID: ' . $quotation->id);
                Log::info('- Currency: ' . ($quotation->currency ?? 'NULL'));
                Log::info('- Items count: ' . $quotation->items->count());
                Log::info('- Sales person: ' . ($quotation->sales_person->name ?? 'NULL'));
                Log::info('- Subsidiary: ' . ($quotation->subsidiary->company_name ?? 'NULL'));

            } catch (\Exception $e) {
                Log::error('Error loading quotation: ' . $e->getMessage());
                return back()->with('error', 'Error loading quotation data: ' . $e->getMessage());
            }

            // Get all items from quotation (no filtering needed)
            Log::info('Getting all items from quotation...');

            $eligibleItems = $quotation->items;

            Log::info('Item processing complete:');
            Log::info('- Total items in quotation: ' . $quotation->items->count());
            Log::info('- Items for export: ' . $eligibleItems->count());

            if ($eligibleItems->count() > 0) {
                Log::info('Item IDs: ' . $eligibleItems->pluck('id')->implode(', '));
            }

            if ($eligibleItems->isEmpty()) {
                Log::warning('No items found in quotation: ' . $quotation->id);
                return back()->with('error', 'No items found in this quotation.');
            }

            // Create Excel file
            Log::info('Creating Excel spreadsheet...');

            try {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('HRDF Invoice Data');
                Log::info('Spreadsheet created successfully');
            } catch (\Exception $e) {
                Log::error('Failed to create spreadsheet: ' . $e->getMessage());
                return back()->with('error', 'Failed to create Excel file: ' . $e->getMessage());
            }

            // Generate invoice data based on HRDF invoice and quotation
            Log::info('Generating invoice data...');
            Log::info('Using HRDF invoice and quotation for data generation');

            // DebtorCode - Make it empty as requested
            $debtorCode = '';
            Log::info('DebtorCode set to empty string');

            // DocNo is the HRDF invoice number
            $docNo = $hrdfInvoice->invoice_no;
            Log::info('DocNo set to HRDF invoice number: ' . $docNo);

            // DocDate is today's date in j/n/Y format
            $docDate = date('j/n/Y');
            Log::info('DocDate set to: ' . $docDate);

            // Company name - use subsidiary name if available, otherwise HRDF invoice company name
            $companyName = $hrdfInvoice->company_name;
            if ($quotation->subsidiary_id && $quotation->subsidiary && $quotation->subsidiary->company_name) {
                $companyName = $quotation->subsidiary->company_name;
                Log::info('Using subsidiary company name: ' . $companyName);
            } else {
                Log::info('Using HRDF invoice company name: ' . $companyName);
            }

            // SalesAgent from quotation sales person autocount_name (but blank for renewal)
            $salesAgent = '';
            if ($hrdfInvoice->handover_type !== 'RW') {
                $salesAgent = $quotation->sales_person->autocount_name ?? '';
            }
            Log::info('SalesAgent set to: ' . $salesAgent . ' (handover type: ' . $hrdfInvoice->handover_type . ')');

            // CurrencyCode from quotation with fallback
            $currencyCode = $quotation->currency ?? 'MYR';
            Log::info('Currency determined: ' . $currencyCode);

            // Currency rate based on currency
            $currencyRate = $currencyCode === 'USD' ? null : '1';
            Log::info('Currency rate set to: ' . ($currencyRate ?? 'NULL'));

            // UDF fields - Default values for HRDF invoices
            // Get salesAdmin from lead owner's autocount_name (but blank for renewal)
            $salesAdmin = '';
            if ($hrdfInvoice->handover_type !== 'RW') {
                if ($quotation->lead && $quotation->lead->lead_owner) {
                    $leadOwnerName = $quotation->lead->lead_owner;
                    Log::info('Lead owner name: ' . $leadOwnerName);

                    $leadOwnerUser = User::where('name', $leadOwnerName)->first();
                    if ($leadOwnerUser && !empty($leadOwnerUser->autocount_name)) {
                        $salesAdmin = $leadOwnerUser->autocount_name;
                        Log::info('Found lead owner user with autocount_name: ' . $salesAdmin);
                    } else {
                        Log::info('Lead owner user not found or autocount_name is empty');
                    }
                } else {
                    Log::info('No lead or lead_owner found');
                }
            } else {
                Log::info('Renewal handover - salesAdmin set to blank');
            }

            $udfSupport = match(auth()->id()) {
                5 => 'FATIMAH',
                52 => 'IRDINA',
                default => 'YAT'
            };
            $billingType = $hrdfInvoice->handover_type === 'RW' ? 'Renewal' : 'New';
            $cancelled = '';

            Log::info('UDF fields set:');
            Log::info('- UDF_IV_SalesAdmin: ' . $salesAdmin);
            Log::info('- UDF_IV_Support: ' . $udfSupport);
            Log::info('- UDF_IV_BillingType: ' . $billingType);
            Log::info('- Cancelled: ' . $cancelled);

            // Get HRDF Claim data for new UDF fields
            $hrdfClaim = $hrdfInvoice->hrdfClaim;
            $udfHrDeposit = $hrdfClaim ? $hrdfClaim->upfront_payment : 0;
            $udfHrBalance = $hrdfClaim ? $hrdfClaim->hrdf_balance : 0;

            // Format UDF_IV_HRDInfo string
            $udfHrdInfo = $this->formatHrdfInfo($hrdfClaim, $hrdfInvoice, $quotation);

            // Create header row
            $headers = [
                'DocNo',
                'DocDate',
                'CompanyName',
                'DebtorCode',
                'UDF_IV_CustomerName',
                'UDF_IV_LicenseNumber',
                'SalesAgent',
                'CurrencyCode',
                'CurrencyRate',
                'RefDocNo',
                'UDF_IV_SalesAdmin',
                'UDF_IV_Support',
                'UDF_IV_BillingType',
                'Cancelled',
                'UDF_IV_HRDeposit',
                'UDF_IV_HRDBalance',
                'UDF_IV_HRDInfo',
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
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'color' => ['rgb' => 'E3F2FD']
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
            ];
            $sheet->getStyle('A1:V1')->applyFromArray($headerStyle);

            // Set column width for UDF_IV_HRDInfo (column Q)
            $sheet->getColumnDimension('Q')->setWidth(60);
            Log::info('Header styling applied');

            $row = 2; // Start from row 2 for data

            // Process items
            Log::info('Processing ' . $eligibleItems->count() . ' eligible items...');

            $firstItem = $eligibleItems->first();
            $firstProduct = $firstItem ? $firstItem->product : null;

            // First row gets full invoice data
            Log::info('Adding full invoice row for first item - Product: ' . ($firstProduct ? $firstProduct->code : 'NULL'));

            $firstRowData = [
                $docNo,                                              // DocNo
                $docDate,                                           // DocDate
                'PEMBANGUNAN SUMBER MANUSIA BERHAD',               // CompanyName
                'ARM-P0062',                                        // DebtorCode
                $companyName,                                       // UDF_IV_CustomerName
                $hrdfInvoice->tt_invoice_number ?? '',             // UDF_IV_LicenseNumber
                $salesAgent,                                        // SalesAgent
                $currencyCode,                                      // CurrencyCode
                $currencyRate,                                      // CurrencyRate
                '',                                                 // RefDocNo
                $salesAdmin,                                        // UDF_IV_SalesAdmin
                $udfSupport,                                        // UDF_IV_Support
                $billingType,                                       // UDF_IV_BillingType
                $cancelled,                                         // Cancelled
                $udfHrDeposit,                                      // UDF_IV_HRDeposit
                $udfHrBalance,                                      // UDF_IV_HRDBalance
                $udfHrdInfo,                                        // UDF_IV_HRDInfo
                $firstProduct ? $firstProduct->code : '',          // ItemCode
                $firstItem->quantity ?? 1,                         // Qty
                $this->calculateUnitPrice($firstItem, $firstProduct), // UnitPrice
                $this->getTaxCode($firstItem, $firstProduct, $quotation), // TaxCode
                $firstProduct ? ($firstProduct->tariff_code ?? '') : '' // TariffCode
            ];

            $sheet->fromArray([$firstRowData], null, 'A' . $row);

            // Set row height for the first data row to accommodate multi-line content
            $sheet->getRowDimension($row)->setRowHeight(80);

            // Enable text wrapping for the UDF_IV_HRDInfo cell (column Q)
            $sheet->getStyle('Q' . $row)->getAlignment()->setWrapText(true);
            $sheet->getStyle('Q' . $row)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

            Log::info('Added full invoice row at row ' . $row);
            $row++;

            // Remaining items get only product data
            $remainingItems = $eligibleItems->skip(1);
            Log::info('Processing ' . $remainingItems->count() . ' remaining items');

            foreach ($remainingItems as $item) {
                $product = $item->product;

                $itemRowData = [
                    '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', // Empty first 17 columns
                    $product ? $product->code : '',                  // ItemCode
                    $item->quantity ?? 1,                           // Qty
                    $this->calculateUnitPrice($item, $product),     // UnitPrice
                    $this->getTaxCode($item, $product, $quotation), // TaxCode
                    $product ? ($product->tariff_code ?? '') : ''   // TariffCode
                ];

                $sheet->fromArray([$itemRowData], null, 'A' . $row);
                Log::info('Added item row for product: ' . ($product ? $product->code : 'NULL') . ' at row ' . $row);
                $row++;
            }

            Log::info('Completed processing all items, final row: ' . ($row - 1));

            // Auto-size columns (except UDF_IV_HRDInfo which has fixed width)
            foreach (range('A', 'V') as $col) {
                if ($col !== 'Q') { // Don't auto-size UDF_IV_HRDInfo column
                    $sheet->getColumnDimension($col)->setAutoSize(true);
                }
            }

            // Save as Excel file
            Log::info('Saving Excel file...');
            $tempFile = tempnam(sys_get_temp_dir(), 'hrdf_invoice_data_export_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);
            Log::info('Excel file saved to: ' . $tempFile);

            // Create filename
            $filename = 'HRDF_Invoice_Data_' . $hrdfInvoice->invoice_no . '_' . str_replace([' ', '/', '\\', '&'], '_', $companyName) . '_' . date('Y-m-d') . '.xlsx';

            Log::info('About to send HRDF Invoice Data Excel file: ' . $filename);

            // Return file as download
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('=== HRDF INVOICE EXPORT ERROR ===');
            Log::error('HRDF Invoice Data export error: ' . $e->getMessage());
            Log::error('File: ' . $e->getFile() . ' Line: ' . $e->getLine());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            Log::error('=== END ERROR LOG ===');

            return back()->with('error', 'Error exporting HRDF invoice data: ' . $e->getMessage());
        }
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

                // Flexible check for taxable values
                $isTaxable = $taxableRaw === true ||
                           $taxableRaw === 1 ||
                           (is_string($taxableRaw) && strtolower($taxableRaw) === 'true') ||
                           (is_string($taxableRaw) && $taxableRaw === '1');

                if ($isTaxable) {
                    Log::info('Product is taxable - Tax code: SV-8');
                    return 'SV-8';
                } else {
                    Log::info('Product is not taxable - Tax code: NTS');
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

    private function formatHrdfInfo($hrdfClaim, $hrdfInvoice, $quotation)
    {
        if (!$hrdfClaim) {
            Log::warning('No HRDF claim found for formatting HRD info');
            return 'No HRDF claim data available';
        }

        // Field 1: Programme Name
        $programmeName = $hrdfClaim->programme_name ?? 'N/A';

        // Field 2: Date of Programme
        $trainingDate = $hrdfClaim->hrdf_training_date ?? 'N/A';

        // Field 3: Client Name (from quotation or invoice)
        $clientName = $hrdfClaim->company_name ?? 'N/A';

        // Field 4: Grant ID
        $grantId = $hrdfClaim->hrdf_grant_id ?? 'N/A';

        // Field 5: Course Fees (PAX and amount)
        $pax = $hrdfClaim->pax ?? 0;
        $totalAmount = $hrdfClaim->invoice_amount ?? 0;
        $perPaxAmount = $pax > 0 ? ($totalAmount / $pax) : 0;

        // Format the complete string
        $hrdInfo = sprintf(
            "PROGRAMME NAME: %s\nDATE OF PROGRAMME: %s\nCLIENT NAME: %s\nGRANT ID: %s\nCOURSE FEES: %d PAX / RM %.2f / PAX",
            $programmeName,
            $trainingDate,
            $clientName,
            $grantId,
            $pax,
            $perPaxAmount
        );

        Log::info('Formatted HRD Info: ' . $hrdInfo);
        return $hrdInfo;
    }
}
