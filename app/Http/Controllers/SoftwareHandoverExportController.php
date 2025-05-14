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

        // Get the lead with company details
        $lead = Lead::with('companyDetail', 'softwareHandover')->findOrFail($decryptedLeadId);
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

        // Build customer data row
        $companyName = $lead->companyDetail->company_name ?? '';
        $contactPerson = $lead->companyDetail->name ?? $lead->name ?? '';
        $phone = $lead->companyDetail->contact_no ?? $lead->phone ?? '';
        $email = $lead->companyDetail->email ?? $lead->email ?? '';
        $registrationNo = $lead->companyDetail->reg_no_new .' ('. $lead->companyDetail->reg_no_old . ')'  ?? '';

        // Address fields
        $address1 = $lead->companyDetail->company_address1 ?? '';
        $address2 = $lead->companyDetail->company_address2 ?? '';
        $city = $lead->companyDetail->city ?? '';
        $state = $lead->companyDetail->state ?? '';
        $postcode = $lead->companyDetail->postcode ?? '';

        // Format address lines
        $formattedAddress1 = $address1;
        $formattedAddress2 = $address2;
        $formattedAddress3 = $city;
        $formattedAddress4 = $state;
        $salesAgent = User::find($lead->salesperson)?->name ?? '';

        // Generate a unique debtor code based on company name
        $debtorCode = substr(strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $companyName)), 0, 8);
        if (empty($debtorCode)) {
            $debtorCode = 'CUS' . str_pad($lead->id, 5, '0', STR_PAD_LEFT);
        }

        // Create the data row with the specific format required
        $dataRow = [
            '',
            'N',                     // If Yes, under which co?
            '',                      // DebtorCode
            $companyName,            // CompanyName
            '',                      // Desc2
            '',                      // AreaCode
            $salesAgent,             // SalesAgent
            '',                      // DebtorType
            'C.O.D',                 // DisplayTerm
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

        // Create Excel file directly instead of CSV
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Add data to the spreadsheet
        $sheet->fromArray([$descriptionRow], null, 'A1');
        $sheet->fromArray([$headerRow], null, 'A2');
        $sheet->fromArray([$dataRow], null, 'A3');

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

        for ($i = 2; $i <= 11; $i++) {
            $sheet->getStyle("C{$i}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'fff2cc'], // Light blue background
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D9D9D9'],
                    ],
                ],
            ]);
        }

        // Color F2:F10 cells
        for ($i = 2; $i <= 11; $i++) {
            $sheet->getStyle("F{$i}")->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'fff2cc'], // Light green background
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D9D9D9'],
                    ],
                ],
            ]);
        }

        $sheet->getStyle('B2:AF11')->applyFromArray([
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
}
