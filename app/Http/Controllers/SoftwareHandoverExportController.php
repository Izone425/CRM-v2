<?php
namespace App\Http\Controllers;

use App\Classes\Encryptor;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use App\Models\User;
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
                        $subsidiary->register_number ?? '',  // RegisterNo
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
            'KUALA LUMPUR' => 'MYS-KUL',
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
            'PUTRAJAYA' => 'MYS-PJY',
            'LABUAN' => 'MYS-LBN'
        ];

        $normalizedState = strtoupper(trim($state));
        return $stateMapping[$normalizedState] ?? '';
    }
}
