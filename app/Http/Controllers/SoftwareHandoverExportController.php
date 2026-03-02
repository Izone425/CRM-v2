<?php
namespace App\Http\Controllers;

use App\Classes\Encryptor;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class SoftwareHandoverExportController extends Controller
{
    public function exportCustomerCSV($leadId)
    {
        try {
            // Add debug log to verify the function is called
            Log::info('Starting Customer CSV export for lead ID: ' . $leadId);

            // Decrypt the lead ID
            $decryptedLeadId = Encryptor::decrypt($leadId);
            Log::info('Decrypted lead ID: ' . $decryptedLeadId);

            // Get the lead with company details and subsidiaries
            $lead = Lead::with(['companyDetail', 'softwareHandover', 'subsidiaries'])->findOrFail($decryptedLeadId);
            Log::info('Lead found: ' . $lead->id);

            // Build the data for our spreadsheet
            // Row 1: Format descriptions
            $descriptionRow = [
                "",
                "Group A/C?\n(Y -Yes, N-No)",
                "(12 chars)",
                "(80 chars)",
                "(80 chars)",
                "(12 chars)",
                "(12 chars)",
                "(20 chars)",
                "(30 chars)",
                "(1 char, I for\nInvoice Date,\nD for Due Date)",
                "(1 char, O for Open Item,\nB for Balance B/F,\nN for No Statement)",
                "(5 chars)",
                "(12 chars)",
                "(40 chars)",
                "(40 chars)",
                "(40 chars)",
                "(40 chars)",
                "(10 chars)",
                "(40 chars)",
                "(40 chars)",
                "(40 chars)",
                "(40 chars)",
                "(10 chars)",
                "(40 chars)",
                "(25 chars)",
                "(25 chars)",
                "(25 chars)",
                "(25 chars)",
                "(25 chars)",
                "(Sales Tax Exemption No.:\n60 chars)",
                "(Sales Tax Exemption\nExpiry Date:\ndd/MM/yyyy)",
                "(80 chars)"
            ];

            // Row 2: Actual headers
            $headerRow = [
                "",
                "If Yes, under which co?",
                "DebtorCode",
                "CompanyName",
                "Desc2",
                "AreaCode",
                "SalesAgent",
                "DebtorType",
                "DisplayTerm",
                "AgingOn",
                "StatementType",
                "CurrencyCode",
                "RegisterNo",
                "Address1",
                "Address2",
                "Address3",
                "Address4",
                "PostCode",
                "DeliverAddr1",
                "DeliverAddr2",
                "DeliverAddr3",
                "DeliverAddr4",
                "DeliverPostCode",
                "Attention",
                "Phone1",
                "Phone2",
                "Mobile",
                "Fax1",
                "Fax2",
                "ExemptNo",
                "ExpiryDate",
                "EmailAddress"
            ];

            // Create an array to hold all data rows (parent company + subsidiaries)
            $allDataRows = [];

            // Build parent company data row
            $companyName = $lead->companyDetail->company_name ?? '';
            $contactPerson = $lead->companyDetail->name ?? $lead->name ?? '';
            $tempPhone = $lead->companyDetail->contact_no ?? $lead->phone ?? '';
            $phone = $this->cleanPhoneNumber($tempPhone);
            $email = $lead->companyDetail->email ?? $lead->email ?? '';
            $registrationNo = $lead->companyDetail->reg_no_new ?? $lead->eInvoiceDetail->business_register_number;

            // Address fields
            $address1 = $lead->companyDetail->company_address1 ?? $lead->eInvoiceDetail->address_1;
            $address2 = $lead->companyDetail->company_address2 ?? $lead->eInvoiceDetail->address_2;
            $city = $lead->companyDetail->city ?? $lead->eInvoiceDetail->city;
            $state = $lead->companyDetail->state ?? $lead->eInvoiceDetail->state;
            $postcode = $lead->companyDetail->postcode ?? $lead->eInvoiceDetail->postcode;

            // Format address lines
            $formattedAddress1 = $address1;
            $formattedAddress2 = $address2;
            $formattedAddress3 = $city;
            $formattedAddress4 = strtoupper($state);
            $salesAgent = strtoupper(User::find($lead->salesperson)?->autocount_name ?? '');

            // Create the parent company row
            $parentDataRow = [
                '',
                'N',                     // If Yes, under which co?
                '',             // DebtorCode
                $companyName,            // CompanyName
                '',                      // Desc2
                $this->getAreaCodeFromState($state), // AreaCode
                $salesAgent,             // SalesAgent
                '',                      // DebtorType
                'C.O.D.',                 // DisplayTerm
                'I',                     // AgingOn (Invoice Date)
                'O',                     // StatementType (Open Item)
                'MYR',                   // CurrencyCode
                $registrationNo,         // RegisterNo
                $formattedAddress1,      // Address1
                $formattedAddress2,      // Address2
                $formattedAddress3,      // Address3 (City)
                $formattedAddress4,      // Address4 (State)
                $postcode,               // PostCode
                $formattedAddress1,      // DeliverAddr1 (same as billing address)
                $formattedAddress2,      // DeliverAddr2
                $formattedAddress3,      // DeliverAddr3
                $formattedAddress4,      // DeliverAddr4
                $postcode,               // DeliverPostCode
                $contactPerson,          // Attention
                $phone,                  // Phone1
                '',                      // Phone2
                '',                      // Mobile
                '',                      // Fax1
                '',                      // Fax2
                '',                      // ExemptNo
                '',                      // ExpiryDate
                $email                   // EmailAddress
            ];

            // Add the parent company row
            $allDataRows[] = $parentDataRow;

            // Check if subsidiaries exist and add them
            if ($lead->subsidiaries && count($lead->subsidiaries) > 0) {
                foreach ($lead->subsidiaries as $index => $subsidiary) {
                    // Create data row for this subsidiary
                    $subsidiaryDataRow = [
                        '',
                        $companyName,   // If Yes, under which co?
                        '',             // Parent company debtor code
                        $subsidiary->company_name,       // Subsidiary name
                        $subsidiary->description ?? '', // Desc2
                        $this->getAreaCodeFromState($subsidiary->state ?? $state), // AreaCode
                        $salesAgent,             // SalesAgent
                        '',                      // DebtorType
                        'C.O.D.',                 // DisplayTerm
                        'I',                     // AgingOn (Invoice Date)
                        'O',                     // StatementType (Open Item)
                        'MYR',                   // CurrencyCode
                        $subsidiary->business_register_number ?? '',  // RegisterNo
                        $subsidiary->company_address1 ?? $formattedAddress1,  // Address1
                        $subsidiary->company_address2 ?? $formattedAddress2,  // Address2
                        $subsidiary->city ?? $formattedAddress3,      // Address3 (City)
                        $subsidiary->state ? strtoupper($subsidiary->state) : $formattedAddress4,     // Address4 (State)
                        $subsidiary->postcode ?? $postcode,           // PostCode
                        $subsidiary->company_address1 ?? $formattedAddress1,  // DeliverAddr1
                        $subsidiary->company_address2 ?? $formattedAddress2,  // DeliverAddr2
                        $subsidiary->city ?? $formattedAddress3,      // DeliverAddr3
                        $subsidiary->state ? strtoupper($subsidiary->state) : $formattedAddress4,     // DeliverAddr4
                        $subsidiary->postcode ?? $postcode,           // DeliverPostCode
                        $subsidiary->name ?? $contactPerson, // Attention
                        $this->cleanPhoneNumber($subsidiary->contact_number ?? $phone), // Phone1
                        '',                      // Phone2
                        '',                      // Mobile
                        '',                      // Fax1
                        '',                      // Fax2
                        '',                      // ExemptNo
                        '',                      // ExpiryDate
                        $subsidiary->email ?? $email  // EmailAddress
                    ];

                    // Add this subsidiary row
                    $allDataRows[] = $subsidiaryDataRow;
                }
            }

            // Create Excel file directly instead of CSV
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Add data to the spreadsheet
            $sheet->fromArray([$descriptionRow], null, 'A1');
            $sheet->fromArray([$headerRow], null, 'A2');

            // Add all data rows starting from row 3
            $rowIndex = 3;
            foreach ($allDataRows as $dataRow) {
                $sheet->fromArray([$dataRow], null, 'A' . $rowIndex);
                $rowIndex++;
            }

            // Apply text wrapping to the description row
            $lastCol = count($descriptionRow);
            $lastColLetter = self::getColumnLetter($lastCol);

            $sheet->getStyle('A1:' . $lastColLetter . '1')->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_BOTTOM)
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Set row height to accommodate wrapped text
            $sheet->getRowDimension(1)->setRowHeight(60);

            for ($i = 2; $i <= 32; $i++) { // B is column 2, AF is column 32
                $colLetter = self::getColumnLetter($i);
                $sheet->getStyle("{$colLetter}1")->applyFromArray([
                    'borders' => [
                        'right' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '993366'], // Purple color
                        ],
                    ],
                ]);
            }

            $sheet->getStyle('B2:' . $lastColLetter . '2')->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'fff2cc'],
                ],
                'font' => [
                    'bold' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D9D9D9'],
                    ],
                ],
            ]);

            // Make all header row text bold
            $sheet->getStyle('B2:' . $lastColLetter . '2')->getFont()->setBold(true);

            // Then remove bold formatting from specific columns
            $excludedColumns = ['C', 'E', 'F', 'H', 'J', 'K', 'Z', 'AB', 'AC', 'AD', 'AE'];
            foreach ($excludedColumns as $col) {
                if (ord($col[0]) - 64 <= $lastCol) { // Make sure column is within range
                    // For single-letter columns
                    if (strlen($col) === 1) {
                        $sheet->getStyle($col . '2')->getFont()->setBold(false);
                    }
                    // For double-letter columns like AB
                    else if (strlen($col) === 2) {
                        // Convert column letters to column index
                        $firstLetter = ord($col[0]) - 64;
                        $secondLetter = ord($col[1]) - 64;
                        $columnIndex = $firstLetter * 26 + $secondLetter;

                        // Only process if within our spreadsheet range
                        if ($columnIndex <= $lastCol) {
                            $sheet->getStyle($col . '2')->getFont()->setBold(false);
                        }
                    }
                }
            }

            // Set all header text to red EXCEPT the specified columns (maintain this after bold changes)
            // First set all to red
            $sheet->getStyle('B2:' . $lastColLetter . '2')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_RED));

            // Then reset the specified columns to black
            foreach ($excludedColumns as $col) {
                if (ord($col[0]) - 64 <= $lastCol) { // Make sure column is within range
                    $sheet->getStyle($col . '2')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color(\PhpOffice\PhpSpreadsheet\Style\Color::COLOR_BLACK));
                }
            }

            // Apply style to the first 10 rows
            $maxStyleRow = min(11, $rowIndex + 7); // Apply to first 10 rows or all rows + 7 (whichever is less)

            for ($i = 2; $i <= $maxStyleRow; $i++) {
                $sheet->getStyle("C{$i}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'fff2cc'], // Light background
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D9D9D9'],
                        ],
                    ],
                ]);
            }

            // Format all registration numbers as strings
            for ($i = 3; $i < $rowIndex; $i++) {
                $registrationNo = $sheet->getCell("M{$i}")->getValue();
                if (!empty($registrationNo)) {
                    $sheet->setCellValueExplicit("M{$i}", $registrationNo, DataType::TYPE_STRING);
                }
            }

            // Color F2:F10 cells
            for ($i = 2; $i <= $maxStyleRow; $i++) {
                $sheet->getStyle("F{$i}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'fff2cc'], // Light background
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => 'D9D9D9'],
                        ],
                    ],
                ]);
            }

            $sheet->getStyle('B2:AF' . $maxStyleRow)->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '993366'],
                    ],
                ],
            ]);

            // Auto-size columns for better readability
            for ($i = 1; $i <= $lastCol; $i++) {
                $colLetter = self::getColumnLetter($i);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }
            // Save as Excel file
            $tempFile = tempnam(sys_get_temp_dir(), 'customer_export_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            // Create filename
            $filename = 'Customer_' . str_replace(' ', '_', $companyName) . '_' . date('Y-m-d') . '.xlsx';
            Log::info('About to send Excel file: ' . $filename);

            // Return file as download
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('CSV/Excel export error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('error', 'Error exporting customer information: ' . $e->getMessage());
        }
    }

    public function exportInvoice($leadId)
    {
        try {
            // Add debug log to verify the function is called
            Log::info('Starting Excel export for lead ID: ' . $leadId);

            // Decrypt the lead ID
            $decryptedLeadId = Encryptor::decrypt($leadId);
            Log::info('Decrypted lead ID: ' . $decryptedLeadId);

            // Get the lead with company details
            $lead = Lead::with('companyDetail', 'softwareHandover')->findOrFail($decryptedLeadId);
            Log::info('Lead found: ' . $lead->id);

            // Create a new spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set the spreadsheet title
            $sheet->setTitle('Invoice Information');

            // Add header styles
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ];

            // Set column headers
            $sheet->setCellValue('A1', 'Invoice Information');
            $sheet->mergeCells('A1:B1');
            $sheet->getStyle('A1:B1')->applyFromArray($headerStyle);

            // Data rows
            $sheet->setCellValue('A2', 'Company Name');
            $sheet->setCellValue('B2', $lead->companyDetail->company_name ?? 'N/A');

            $sheet->setCellValue('A3', 'Account Name');
            $accountName = $lead->softwareHandover ?
                $lead->softwareHandover->account_name ?? 'TTC' . ($lead->id ?? '') :
                'TTC' . ($lead->id ?? '');
            $sheet->setCellValue('B3', $accountName);

            // Add more data rows...
            // ...

            // Auto-size columns
            foreach (range('A', 'D') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'invoice_export_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            // Create filename
            $filename = 'Invoice_Info_' . str_replace(' ', '_', $lead->companyDetail->company_name ?? 'Company') . '_' . date('Y-m-d') . '.xlsx';
            Log::info('About to send Excel file: ' . $filename);

            // Return file as download
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Excel export error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('error', 'Error exporting invoice information: ' . $e->getMessage());
        }
    }

    private static function getColumnLetter($column)
    {
        $column = intval($column);
        if ($column <= 0) return '';

        if ($column <= 26) {
            return chr(64 + $column);
        } else {
            $dividend = $column;
            $columnName = '';

            while ($dividend > 0) {
                $modulo = ($dividend - 1) % 26;
                $columnName = chr(65 + $modulo) . $columnName;
                $dividend = floor(($dividend - $modulo) / 26);
            }

            return $columnName;
        }
    }

    private function cleanPhoneNumber($phone)
    {
        // Remove +6 first, then 6, but only from the beginning
        if (str_starts_with($phone, '+6')) {
            return substr($phone, 2); // Remove +6
        } elseif (str_starts_with($phone, '6') && !str_starts_with($phone, '0')) {
            return substr($phone, 1); // Remove 6, but not if it starts with 0
        }
        return $phone; // Return original if no match
    }

    private function getAreaCodeFromState($state)
    {
        $stateMapping = [
            'JOHOR' => 'MYS-JHR',
            'KEDAH' => 'MYS-KDH',
            'KELANTAN' => 'MYS-KTN',
            'WILAYAH PERSEKUTUAN KUALA LUMPUR' => 'MYS-KUL',
            'MELAKA' => 'MYS-MLK',
            'PAHANG' => 'MYS-PHG',
            'PENANG' => 'MYS-PNG',
            'PERLIS' => 'MYS-PLS',
            'PERAK' => 'MYS-PRK',
            'SABAH' => 'MYS-SBH',
            'SELANGOR' => 'MYS-SEL',
            'NEGERI SEMBILAN' => 'MYS-SEM',
            'SARAWAK' => 'MYS-SWK',
            'TERENGGANU' => 'MYS-TRG',
            'WILAYAH PERSEKUTUAN PUTRAJAYA' => 'MYS-PJY',
            'WILAYAH PERSEKUTUAN LABUAN' => 'MYS-LBN'
        ];

        $normalizedState = strtoupper(trim($state));
        return $stateMapping[$normalizedState] ?? '';
    }

    public function confirmCreateDb(\Illuminate\Http\Request $request, $softwareHandoverId)
    {
        $decryptedId = Encryptor::decrypt($softwareHandoverId);
        $handover = SoftwareHandover::findOrFail($decryptedId);

        $dbDate = $request->input('db_date', now()->format('Y-m-d'));
        $items = $request->input('items', []);
        $bufferMonth = $request->input('buffer_month', 1);

        // Save form data locally
        $handover->update([
            'db_creation' => $dbDate,
            'type_1_pi_invoice_data' => [
                'items' => $items,
                'buffer_month' => $bufferMonth,
            ],
        ]);

        // Create or update Customer record with master credentials
        try {
            $masterEmail = $request->input('master_email');
            $masterPassword = $request->input('master_password');

            if ($masterEmail) {
                Customer::updateOrCreate(
                    ['sw_id' => $handover->id],
                    [
                        'email' => $masterEmail,
                        'password' => $masterPassword,
                        'plain_password' => $masterPassword,
                        'name' => $handover->company_name ?? 'Master',
                        'company_name' => $handover->company_name,
                        'lead_id' => $handover->lead_id,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error("Failed to create/update customer for handover {$handover->id}: " . $e->getMessage());
        }

        // Auto-create CRM account if not yet created
        if (!$handover->hr_account_id || !$handover->hr_company_id) {
            try {
                $this->createCRMAccountForHandover($handover);
                $handover->refresh();
            } catch (\Exception $e) {
                Log::error('Failed to auto-create CRM account', [
                    'handover_id' => $handover->id,
                    'error' => $e->getMessage(),
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to create CRM account: ' . $e->getMessage(),
                ], 500);
            }

            if (!$handover->hr_account_id || !$handover->hr_company_id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to create CRM account. Please try again.',
                ], 500);
            }
        }

        // Map product code prefixes to API application names
        $productCodeToApp = [
            'TCL_TA' => 'Attendance',
            'TCL_LEAVE' => 'Leave',
            'TCL_CLAIM' => 'Claim',
            'TCL_PAYROLL' => 'Payroll',
        ];

        // Determine unique applications and seat limits from the items
        $applications = [];
        $seatLimits = [];
        foreach ($items as $item) {
            $code = $item['product_code'] ?? '';
            foreach ($productCodeToApp as $prefix => $appName) {
                if (str_starts_with($code, $prefix)) {
                    $applications[$appName] = true;
                    $seatLimits[$appName] = (int) ($item['qty'] ?? 0);
                    break;
                }
            }
        }

        if (empty($applications)) {
            return response()->json([
                'success' => false,
                'error' => 'No valid software modules found in the items.',
            ], 422);
        }

        // Calculate trial license dates
        $startDate = \Carbon\Carbon::parse($dbDate)->format('Y-m-d');
        $endDate = \Carbon\Carbon::parse($dbDate)->addMonths((int) $bufferMonth)->subDay()->format('Y-m-d');

        // Create buffer/trial licenses via CRM API (single call with all applications)
        $crmService = app(\App\Services\CRMApiService::class);
        $appList = array_keys($applications);

        try {
            $result = $crmService->addBufferLicense(
                $handover->hr_account_id,
                $handover->hr_company_id,
                [
                    'applications' => $appList,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'seatLimits' => $seatLimits,
                    'notes' => 'Trial license created from CRM Software Handover',
                ]
            );

            Log::info("Buffer license API result", [
                'handover_id' => $handover->id,
                'account_id' => $handover->hr_account_id,
                'applications' => $appList,
                'result' => $result,
            ]);

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'] ?? 'Failed to create trial licenses. Please try again.',
                ], 500);
            }

            // Store buffer license set ID
            $handover->update([
                'crm_buffer_license_id' => $result['data']['licenseSetId'] ?? null,
            ]);

            // Create local hr_license record so it appears in "All Licenses" page
            $handoverId = 'SW_' . date('y', strtotime($handover->created_at)) . str_pad($handover->id, 4, '0', STR_PAD_LEFT);
            $appNames = implode(', ', $appList);
            $totalQty = array_sum(array_column($items, 'qty'));

            \App\Models\HrLicense::updateOrCreate(
                ['handover_id' => $handoverId],
                [
                    'software_handover_id' => $handover->id,
                    'type' => 'TRIAL',
                    'company_name' => $handover->company_name,
                    'license_category' => 'Subscriber',
                    'license_type' => "TimeTec {$appNames} (Trial)",
                    'unit' => (int) $totalQty,
                    'month' => (int) $bufferMonth,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'Enabled',
                    'auto_renewal' => 'Disabled',
                ]
            );

            return response()->json([
                'success' => true,
                'date' => $dbDate,
                'licenses' => $result['data'] ?? [],
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to create buffer licenses", [
                'handover_id' => $handover->id,
                'account_id' => $handover->hr_account_id,
                'applications' => $appList,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to create trial licenses: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function confirmCreatePendingLicense(\Illuminate\Http\Request $request, $softwareHandoverId)
    {
        $decryptedId = Encryptor::decrypt($softwareHandoverId);
        $handover = SoftwareHandover::findOrFail($decryptedId);

        $pendingDate = $request->input('pending_date', now()->format('Y-m-d'));
        $items = $request->input('items', []);
        $bufferMonth = $request->input('buffer_month', 1);
        $billingCycle = $request->input('billing_cycle', 12);

        // 1. Save JSON locally (existing behavior)
        $handover->update([
            'type_2_pi_invoice_data' => [
                'items' => $items,
                'buffer_month' => $bufferMonth,
                'billing_cycle' => $billingCycle,
                'pending_date' => $pendingDate,
            ],
        ]);

        // 2. Build handover_id base and invoice number
        $handoverId = 'SW_' . date('y', strtotime($handover->created_at))
            . str_pad($handover->id, 4, '0', STR_PAD_LEFT);

        $invoiceNo = 'TTC' . \Carbon\Carbon::parse($pendingDate)->format('ymd') . $handover->id;

        // 3. Compute year-based date ranges
        $pendingStart = \Carbon\Carbon::parse($pendingDate);
        $numberOfYears = max((int) ($billingCycle / 12), 1);

        $yearDateRanges = [];
        for ($y = 1; $y <= $numberOfYears; $y++) {
            $yearLabel = "Year {$y}";
            $yearStart = $pendingStart->copy()->addMonths(($y - 1) * 12);
            $yearEnd = $pendingStart->copy()->addMonths($y * 12)->subDay();
            $yearDateRanges[$yearLabel] = [
                'start_date' => $yearStart->format('Y-m-d'),
                'end_date'   => $yearEnd->format('Y-m-d'),
            ];
        }

        // 4. Product code to license name mapping
        $codeToName = [
            'TCL_TA'      => 'TimeTec TA',
            'TCL_LEAVE'   => 'TimeTec Leave',
            'TCL_CLAIM'   => 'TimeTec Claim',
            'TCL_PAYROLL' => 'TimeTec Payroll',
        ];

        // 5. Idempotency: delete existing invoice for this handover before re-creating
        $existingInvoice = \App\Models\HrSalesInvoice::where('software_handover_id', $handover->id)
            ->where('invoice_no', $invoiceNo)
            ->first();
        if ($existingInvoice) {
            $existingInvoice->delete(); // FK cascade deletes HrSalesInvoiceItem
        }

        // 6. Build line items for invoice (no HrLicense records — Products tab reads from type_2_pi_invoice_data)
        $lineItems = [];
        $totalSalesAmount = 0;

        foreach ($items as $item) {
            $code = $item['product_code'] ?? '';
            $yearLabel = $item['year'] ?? 'Year 1';
            $qty = (int) ($item['qty'] ?? 0);

            // Resolve license name
            $licenseName = $item['description'] ?? $code;
            foreach ($codeToName as $prefix => $name) {
                if (str_starts_with($code, $prefix)) {
                    $licenseName = $name;
                    break;
                }
            }

            // Get date range for this year
            $dateRange = $yearDateRanges[$yearLabel] ?? $yearDateRanges['Year 1'];
            $startDate = $dateRange['start_date'];
            $endDate = $dateRange['end_date'];

            // Use unit price from PI item (QuotationDetail)
            $unitPrice = (float) ($item['unit_price'] ?? 1.00);

            $itemAmount = $qty * $unitPrice * 12;
            $totalSalesAmount += $itemAmount;

            $lineItems[] = [
                'license_type' => $licenseName,
                'user_limit'   => $qty,
                'total_user'   => $qty,
                'month'        => 12,
                'unit_price'   => $unitPrice,
                'start_date'   => $startDate,
                'end_date'     => $endDate,
            ];
        }

        // 7. Create HrSalesInvoice record
        $piNo = 'AP' . \Carbon\Carbon::parse($pendingDate)->format('ym')
            . str_pad($handover->id, 6, '0', STR_PAD_LEFT);

        $salesInvoice = \App\Models\HrSalesInvoice::create([
            'software_handover_id' => $handover->id,
            'handover_id'          => $handoverId,
            'invoice_no'           => $invoiceNo,
            'invoice_date'         => $pendingDate,
            'company_name'         => $handover->company_name,
            'country'              => 'Malaysia',
            'sales_amount'         => round($totalSalesAmount, 2),
            'currency'             => 'MYR',
            'pi_no'                => $piNo,
            'invoice_amount'       => round($totalSalesAmount, 2),
            'line_items'           => $lineItems,
            'auto_renewal'         => 'No',
            'created_by_name'      => auth()->user()?->name ?? 'System',
            'status'               => 'PENDING',
        ]);

        // 8. Create HrSalesInvoiceItem records (denormalized)
        foreach ($lineItems as $lineItem) {
            \App\Models\HrSalesInvoiceItem::create([
                'hr_sales_invoice_id'  => $salesInvoice->id,
                'invoice_no'           => $invoiceNo,
                'invoice_date'         => $pendingDate,
                'company_name'         => $handover->company_name,
                'invoice_amount'       => round($totalSalesAmount, 2),
                'currency'             => 'MYR',
                'software_handover_id' => $handover->id,
                'handover_id'          => $handoverId,
                'created_by_name'      => auth()->user()?->name ?? 'System',
                'status'               => 'PENDING',
                'license_type'         => $lineItem['license_type'],
                'user_limit'           => $lineItem['user_limit'],
                'total_user'           => $lineItem['total_user'],
                'unit_price'           => $lineItem['unit_price'],
                'month'                => $lineItem['month'],
                'start_date'           => $lineItem['start_date'],
                'end_date'             => $lineItem['end_date'],
            ]);
        }

        Log::info('Pending license confirmed', [
            'handover_id' => $handover->id,
            'invoice_no'  => $invoiceNo,
            'sales_invoice_id' => $salesInvoice->id,
        ]);

        return response()->json([
            'success'    => true,
            'date'       => $pendingDate,
            'invoice_no' => $invoiceNo,
        ]);
    }

    protected function createCRMAccountForHandover(SoftwareHandover $handover): void
    {
        $handover->load('lead');
        $lead = $handover->lead;

        // Get or create customer credentials
        $customer = \App\Models\Customer::where('lead_id', $handover->lead_id)->first();

        if ($customer && $customer->email && $customer->plain_password) {
            $name = $customer->name;
            $email = $customer->email;
            $password = $customer->plain_password;
        } else {
            // Generate credentials via CustomerActivationController
            $activationController = app(\App\Http\Controllers\CustomerActivationController::class);
            $credentials = $activationController->generateCRMAccountCredentials($handover->lead_id, (string) $handover->id);
            $name = $credentials['name'];
            $email = $credentials['email'];
            $password = $credentials['password'];
            $customer = \App\Models\Customer::where('lead_id', $handover->lead_id)->first();
        }

        // Get country data (with Malaysia fallback)
        $countryId = 132; // Malaysia default
        $phoneCode = '+60';
        $timezone = 'Asia/Kuala_Lumpur';

        try {
            $countryService = app(\App\Services\CountryService::class);
            $countries = $countryService->getCountries();
            $leadCountry = $lead->country ?? 'Malaysia';
            $countryData = collect($countries)->firstWhere('name', $leadCountry)
                ?? collect($countries)->firstWhere('iso3', $leadCountry)
                ?? collect($countries)->firstWhere('id', 132);

            if ($countryData) {
                $countryId = (int) $countryData['id'];
                $phoneCode = $countryData['phone_code'] ?? '+60';
                $timezone = $countryData['timezone'] ?? 'Asia/Kuala_Lumpur';
            }
        } catch (\Exception $e) {
            Log::warning('CountryService failed, using Malaysia defaults', ['error' => $e->getMessage()]);
        }

        // Process phone number from implementation PICs
        $implementationPics = is_string($handover->implementation_pics)
            ? json_decode($handover->implementation_pics, true)
            : $handover->implementation_pics;

        $rawPhone = $implementationPics[0]['pic_phone_impl'] ?? $handover->pic_phone ?? '';
        $cleanPhone = preg_replace('/[^0-9]/', '', $rawPhone);
        $phoneCodeDigits = preg_replace('/[^0-9]/', '', $phoneCode);
        if (str_starts_with($cleanPhone, $phoneCodeDigits)) {
            $cleanPhone = substr($cleanPhone, strlen($phoneCodeDigits));
        }
        $cleanPhone = ltrim($cleanPhone, '0');

        // Call CRM API to create account
        $crmService = app(\App\Services\CRMApiService::class);
        $result = $crmService->createAccount([
            'company_name' => $handover->company_name,
            'country_id' => $countryId,
            'name' => $name ?: ($implementationPics[0]['pic_name_impl'] ?? $handover->pic_name ?? 'Admin'),
            'email' => $email,
            'password' => $password,
            'phone_code' => $phoneCode,
            'phone' => $cleanPhone,
            'timezone' => $timezone,
        ]);

        if (empty($result['success']) || empty($result['data'])) {
            throw new \Exception($result['error'] ?? 'CRM API returned no data');
        }

        // Save CRM account IDs to SoftwareHandover
        $handover->update([
            'hr_account_id' => $result['data']['accountId'],
            'hr_company_id' => $result['data']['companyId'],
            'hr_user_id' => $result['data']['userId'] ?? null,
        ]);

        // Update Customer record if exists
        if ($customer) {
            $customer->update([
                'hr_account_id' => $result['data']['accountId'],
                'hr_company_id' => $result['data']['companyId'],
                'hr_user_id' => $result['data']['userId'] ?? null,
                'status' => 'active',
                'email_verified_at' => $customer->email_verified_at ?? now(),
            ]);
        }

        Log::info('CRM account auto-created', [
            'handover_id' => $handover->id,
            'account_id' => $result['data']['accountId'],
            'company_id' => $result['data']['companyId'],
        ]);
    }
}
