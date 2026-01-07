<?php

namespace App\Http\Controllers;

use App\Classes\Encryptor;
use App\Models\Lead;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Log;

class EInvoiceExportController extends Controller
{
    public function exportEInvoiceDetails($leadId, $subsidiaryId = null)
    {
        try {
            Log::info('Starting E-Invoice export for lead ID: ' . $leadId . ', subsidiary ID: ' . $subsidiaryId);

            // Decrypt the lead ID
            $decryptedLeadId = Encryptor::decrypt($leadId);
            Log::info('Decrypted lead ID: ' . $decryptedLeadId);

            // Get the lead with e-invoice details, company details, and subsidiaries
            $lead = Lead::with(['eInvoiceDetail', 'companyDetail', 'subsidiaries'])->findOrFail($decryptedLeadId);
            Log::info('Lead found: ' . $lead->id);

            $eInvoiceDetail = $lead->eInvoiceDetail;
            $companyDetail = $lead->companyDetail;

            // Check if this is for a specific subsidiary based on subsidiary_id
            $subsidiary = null;
            $isSubsidiary = false;

            if ($subsidiaryId) {
                $subsidiary = $lead->subsidiaries->where('id', $subsidiaryId)->first();
                $isSubsidiary = $subsidiary !== null;
                Log::info('Using specific subsidiary ID: ' . $subsidiaryId . ', found: ' . ($subsidiary ? 'Yes' : 'No'));
            }

            // Create Excel spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Build the data for our spreadsheet
            // Row 1: Format descriptions
            $descriptionRow = [
                "(20 chars)",
                "Identity No/Business Reg.\nNo.\n(30 chars)",
                "(100 chars)",
                "0: Individual\n1: Business\n2: Government\n(Integer)",
                "(20 chars)",
                "MSIC CODE Sheet\n(5 chars)",
                "(100 chars)",
                "(12 chars)",
                "(200 chars)",
                "(10 chars)",
                "(25 chars)",
                "(200 chars)",
                "(50 chars)",
                "COUNTRY CODE Sheet\n(3 chars)",
                "STATE CODE Sheet\n(2 chars)"
            ];

            // Row 2: Actual headers
            $headerRow = [
                'TIN',
                'IdentityNo',
                'Name',
                'TaxClassification',
                'SSTRegisterNo',
                'MSICCode',
                'BusinessActivityDesc',
                'DebtorCode',
                'Address',
                'PostCode',
                'Phone',
                'EmailAddress',
                'City',
                'CountryCode',
                'StateCode'
            ];

            // Add data to the spreadsheet
            $sheet->fromArray([$descriptionRow], null, 'B1');
            $sheet->fromArray([$headerRow], null, 'B2');

            // Build data row - get data from models
            // TIN and Business Register Number: use subsidiary if exists, otherwise main company
            if ($isSubsidiary) {
                $tin = $subsidiary->tax_identification_number ?? '';
                $identityNo = $subsidiary->business_register_number ?? '';
            } else {
                $tin = $eInvoiceDetail->tax_identification_number ?? '';
                $identityNo = $eInvoiceDetail->business_register_number ?? $companyDetail->reg_no_new ?? '';
            }

            // Company name: use subsidiary if exists, otherwise main company
            if ($isSubsidiary) {
                $name = $subsidiary->company_name;
            } else {
                $name = $eInvoiceDetail->company_name ?? $companyDetail->company_name ?? '';
            }

            // Tax Classification: 1 for Business (default)
            $taxClassification = 1;
            if ($eInvoiceDetail && $eInvoiceDetail->business_category === 'government') {
                $taxClassification = 2;
            }

            // Generate SST Register No in format similar to sample: STN-YYYY-XXXXXXXX
            $sstRegisterNo = ''; // Made empty as requested

            // MSIC Code: use subsidiary if exists, otherwise main company
            if ($isSubsidiary) {
                $msicCode = $subsidiary->msic_code ?? '01111'; // Default sample code
            } else {
                $msicCode = $eInvoiceDetail->msic_code ?? '01111'; // Default sample code
            }
            $businessActivityDesc = ''; // Made empty as requested

            // Generate debtor code based on lead ID like the example: 300-0001
            $debtorCode = ''; // Made empty as requested

            // Build address - only combine address1 and address2
            if ($isSubsidiary) {
                $address1 = $subsidiary->company_address1 ?? '';
                $address2 = $subsidiary->company_address2 ?? '';
                $city = $subsidiary->city ?? '';
                $postcode = $subsidiary->postcode ?? '';
                $state = $subsidiary->state ?? '';
            } else {
                $address1 = $eInvoiceDetail->address_1 ?? $companyDetail->company_address1 ?? '';
                $address2 = $eInvoiceDetail->address_2 ?? $companyDetail->company_address2 ?? '';
                $city = $eInvoiceDetail->city ?? $companyDetail->city ?? '';
                $postcode = $eInvoiceDetail->postcode ?? $companyDetail->postcode ?? '';
                $state = $eInvoiceDetail->state ?? $companyDetail->state ?? '';
            }

            $addressParts = array_filter([$address1, $address2]);
            $fullAddress = implode(', ', $addressParts); // Only address1 and address2

            // Contact information: use subsidiary if exists, otherwise main company
            if ($isSubsidiary) {
                $phone = $this->cleanPhoneNumber($subsidiary->contact_number ?? '');
                $email = $subsidiary->finance_person_email ?? $subsidiary->email ?? '';
                $cityOnly = $subsidiary->city ?? '';
                $countryCode = $subsidiary->country ?? 'MYS';
                $stateCode = $this->getSimpleStateCode($subsidiary->state ?? '');
            } else {
                $phone = $this->cleanPhoneNumber($companyDetail->contact_no ?? $lead->phone ?? '');
                $email = $eInvoiceDetail->finance_person_email ?? $companyDetail->email ?? $lead->email ?? '';
                $cityOnly = $eInvoiceDetail->city ?? $companyDetail->city ?? '';
                $countryCode = $eInvoiceDetail->country ?? 'MYS';
                $stateCode = $this->getSimpleStateCode($eInvoiceDetail->state ?? $companyDetail->state ?? '');
            }

            // Create the data row
            $dataRow = [
                $tin,                    // TIN
                $identityNo,            // IdentityNo
                $name,                  // Name
                $taxClassification,     // TaxClassification
                $sstRegisterNo,         // SSTRegisterNo
                $msicCode,              // MSICCode
                $businessActivityDesc,  // BusinessActivityDesc
                $debtorCode,            // DebtorCode
                $fullAddress,           // Address
                $postcode,              // PostCode
                $phone,                 // Phone
                $email,                 // EmailAddress
                $cityOnly,              // City
                $countryCode,           // CountryCode
                $stateCode              // StateCode
            ];

            // Add data to row 3
            $sheet->fromArray([$dataRow], null, 'B3');

            // Apply text wrapping to the description row
            $lastCol = count($descriptionRow);
            $lastColLetter = $this->getColumnLetter($lastCol + 1); // +1 because we start from B

            $sheet->getStyle('B1:' . $lastColLetter . '1')->getAlignment()
                ->setWrapText(true)
                ->setVertical(Alignment::VERTICAL_CENTER)
                ->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Set row height to accommodate wrapped text
            $sheet->getRowDimension(1)->setRowHeight(60);

            // Apply header styling to row 2
            $sheet->getStyle('B2:' . $lastColLetter . '2')->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'd4d0c9'],
                ],
                'font' => [
                    'bold' => true,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            // Apply data row styling to row 3
            $sheet->getStyle('B3:' . $lastColLetter . '3')->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'ffffff'], // Light green background for data row
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => '000000'],
                    ],
                ],
            ]);

            // Apply special styling to SST Register No, Business Activity Desc, and Debtor Code columns
            $specialColumns = ['F3', 'H3', 'I3']; // SST Register No, Business Activity Desc, Debtor Code
            foreach ($specialColumns as $cell) {
                $sheet->getStyle($cell)->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'fffe3b'], // Light yellow background
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['rgb' => '000000'],
                        ],
                    ],
                ]);
            }

            // Auto-size columns (starting from B)
            for ($i = 2; $i <= $lastCol + 1; $i++) { // Start from 2 (column B) and go to lastCol + 1
                $colLetter = $this->getColumnLetter($i);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'einvoice_export_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            // Create filename
            if ($isSubsidiary) {
                $companyName = $subsidiary->company_name ?? 'Company';
            } else {
                $companyName = $eInvoiceDetail->company_name ?? $companyDetail->company_name ?? 'Company';
            }
            $filename = 'AutoCount_EInvoice_' . str_replace(' ', '_', $companyName) . '_' . date('Y-m-d') . '.xlsx';

            Log::info('About to send Excel file: ' . $filename);

            // Return file as download
            return response()->download($tempFile, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            Log::error('E-Invoice export error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return back()->with('error', 'Error exporting e-invoice details: ' . $e->getMessage());
        }
    }

    /**
     * Clean phone number format
     */
    private function cleanPhoneNumber($phone)
    {
        // Clean the phone number - only remove + and - characters
        $phone = str_replace(['+', '-'], '', $phone);

        return $phone;
    }

    /**
     * Get simple state code mapping
     */
    private function getSimpleStateCode($stateName)
    {
        $filePath = storage_path('app/public/json/StateCodes.json');

        if (file_exists($filePath)) {
            $statesContent = file_get_contents($filePath);
            $states = json_decode($statesContent, true);

            $normalizedInput = strtolower(trim($stateName));

            foreach ($states as $state) {
                $normalizedState = strtolower($state['State']);

                // Exact match
                if ($normalizedState === $normalizedInput) {
                    return $state['Code'];
                }

                // Handle common variations
                if ($normalizedInput === 'kuala lumpur' && str_contains($normalizedState, 'kuala lumpur')) {
                    return $state['Code'];
                }
                if ($normalizedInput === 'labuan' && str_contains($normalizedState, 'labuan')) {
                    return $state['Code'];
                }
                if ($normalizedInput === 'putrajaya' && str_contains($normalizedState, 'putrajaya')) {
                    return $state['Code'];
                }
                if ($normalizedInput === 'penang' && str_contains($normalizedState, 'pinang')) {
                    return $state['Code'];
                }
                if ($normalizedInput === 'pulau pinang' && str_contains($normalizedState, 'pinang')) {
                    return $state['Code'];
                }
            }
        }

        return '10'; // Default to Selangor code
    }

    /**
     * Get column letter from column number
     */
    private function getColumnLetter($column)
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
