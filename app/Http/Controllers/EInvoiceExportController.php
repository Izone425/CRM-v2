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
    public function exportEInvoiceDetails($leadId)
    {
        try {
            Log::info('Starting E-Invoice export for lead ID: ' . $leadId);

            // Decrypt the lead ID
            $decryptedLeadId = Encryptor::decrypt($leadId);
            Log::info('Decrypted lead ID: ' . $decryptedLeadId);

            // Get the lead with e-invoice details
            $lead = Lead::with(['eInvoiceDetail', 'companyDetail'])->findOrFail($decryptedLeadId);
            Log::info('Lead found: ' . $lead->id);

            $eInvoiceDetail = $lead->eInvoiceDetail;
            $companyDetail = $lead->companyDetail;

            // Create Excel spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Set headers row
            $headers = [
                'TIN',                      // 20 chars
                'IdentityNo',               // 30 chars
                'Name',                     // 100 chars
                'IdentityType',             // Refer to Identity Type & State Code Sheet
                'TaxClassification',        // 0: Individual, 1: Business, 2: Government (Integer) - 10 chars
                'GSTRegisterNo',            // 20 chars
                'SSTRegisterNo',            // 20 chars
                'TourismTaxRegisterNo',     // 20 chars
                'MSICCode',                 // Refer to MSIC CODE Sheet - 5 chars
                'BusinessActivityDesc',     // 100 chars
                'DebtorCode',               // 12 chars
                'CreditorCode',             // 12 chars
                'TradeName',                // 100 chars
                'Address',                  // 200 chars
                'PostCode',                 // 10 chars
                'Phone',                    // 25 chars
                'EmailAddress',             // 200 chars
                'City',                     // 50 chars
                'CountryCode',              // Refer To COUNTRY CODE Sheet - 3 chars
                'StateCode',                // Refer to STATE CODE Sheet - 2 chars
            ];

            // Add headers to row 1
            $sheet->fromArray([$headers], null, 'A1');

            // Build data row
            $dataRow = [
                // TIN (20 chars) - Tax Identification Number
                $this->limitChars($eInvoiceDetail->tax_identification_number ?? '', 20),

                // IdentityNo (30 chars) - Business Register Number
                $this->limitChars($eInvoiceDetail->business_register_number ?? '', 30),

                // Name (100 chars) - Company Name
                $this->limitChars($eInvoiceDetail->company_name ?? $lead->company_name ?? '', 100),

                // IdentityType - Based on business category
                $this->getIdentityType($eInvoiceDetail->business_category ?? ''),

                // TaxClassification (Integer, 10 chars) - Based on business category
                $this->getTaxClassification($eInvoiceDetail->business_category ?? ''),

                // GSTRegisterNo (20 chars) - Empty for now
                $this->limitChars('', 20),

                // SSTRegisterNo (20 chars) - Empty for now
                $this->limitChars('', 20),

                // TourismTaxRegisterNo (20 chars) - Empty for now
                $this->limitChars('', 20),

                // MSICCode (5 chars)
                $this->limitChars($eInvoiceDetail->msic_code ?? '', 5),

                // BusinessActivityDesc (100 chars) - Use industry or default
                $this->limitChars('Sales', 100),

                // DebtorCode (12 chars) - Generate based on lead ID
                $this->limitChars('300-' . str_pad($lead->id, 4, '0', STR_PAD_LEFT), 12),

                // CreditorCode (12 chars) - Empty for now
                $this->limitChars('', 12),

                // TradeName (100 chars) - Same as company name
                $this->limitChars($eInvoiceDetail->company_name ?? $lead->company_name ?? '', 100),

                // Address (200 chars) - Combined address
                $this->limitChars($this->getCombinedAddress($eInvoiceDetail), 200),

                // PostCode (10 chars)
                $this->limitChars($eInvoiceDetail->postcode ?? '', 10),

                // Phone (25 chars) - From company detail or lead
                $this->limitChars($companyDetail->contact_no ?? $lead->phone ?? '', 25),

                // EmailAddress (200 chars) - From company detail or lead
                $this->limitChars($companyDetail->email ?? $lead->email ?? '', 200),

                // City (50 chars)
                $this->limitChars($eInvoiceDetail->city ?? '', 50),

                // CountryCode (3 chars)
                $this->getCountryCode($eInvoiceDetail->country ?? ''),

                // StateCode (2 chars)
                $this->getStateCode($eInvoiceDetail->state ?? ''),
            ];

            // Add data to row 2
            $sheet->fromArray([$dataRow], null, 'A2');

            // Style the header row
            $lastCol = count($headers);
            $lastColLetter = $this->getColumnLetter($lastCol);

            $sheet->getStyle('A1:' . $lastColLetter . '1')->applyFromArray([
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'c0c0c0'],
                ],
                'font' => [
                    'color' => ['rgb' => '000000'],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);

            // Style the data row
            $sheet->getStyle('A2:' . $lastColLetter . '2')->applyFromArray([
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'D9D9D9'],
                    ],
                ],
            ]);

            // Auto-size columns
            for ($i = 1; $i <= $lastCol; $i++) {
                $colLetter = $this->getColumnLetter($i);
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'einvoice_export_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempFile);

            // Create filename
            $companyName = $eInvoiceDetail->company_name ?? $lead->company_name ?? 'Company';
            $filename = 'E_Invoice_Details_' . str_replace(' ', '_', $companyName) . '_' . date('Y-m-d_H-i-s') . '.xlsx';

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
     * Limit string to specified character count
     */
    private function limitChars($string, $limit)
    {
        return substr($string, 0, $limit);
    }

    /**
     * Get identity type based on business category
     */
    private function getIdentityType($businessCategory)
    {
        switch (strtolower($businessCategory)) {
            case 'business':
                return 'MyKAD'; // For business registration
            case 'government':
                return 'MyKAD'; // Government entities
            default:
                return 'MyKAD';
        }
    }

    /**
     * Get tax classification based on business category
     */
    private function getTaxClassification($businessCategory)
    {
        switch (strtolower($businessCategory)) {
            case 'business':
                return 1; // Business
            case 'government':
                return 2; // Government
            default:
                return 1; // Default to business
        }
    }

    /**
     * Get combined address
     */
    private function getCombinedAddress($eInvoiceDetail)
    {
        if (!$eInvoiceDetail) {
            return '';
        }

        $addressParts = array_filter([
            $eInvoiceDetail->address_1,
            $eInvoiceDetail->address_2,
            $eInvoiceDetail->postcode . ' ' . $eInvoiceDetail->city,
            $eInvoiceDetail->state,
            $eInvoiceDetail->country
        ]);

        return implode(', ', $addressParts);
    }

    /**
     * Get country code from country name
     */
    private function getCountryCode($countryName)
    {
        if (empty($countryName)) {
            return 'MYS'; // Default to Malaysia
        }

        $filePath = storage_path('app/public/json/CountryCodes.json');

        if (file_exists($filePath)) {
            $countriesContent = file_get_contents($filePath);
            $countries = json_decode($countriesContent, true);

            foreach ($countries as $country) {
                if (strtolower($country['Country']) === strtolower($countryName)) {
                    return $country['Code'];
                }
            }
        }

        return 'MYS'; // Default fallback
    }

    /**
     * Get state code from state name
     */
    private function getStateCode($stateName)
    {
        if (empty($stateName)) {
            return '01'; // Default to Selangor
        }

        $filePath = storage_path('app/public/json/StateCodes.json');

        if (file_exists($filePath)) {
            $statesContent = file_get_contents($filePath);
            $states = json_decode($statesContent, true);

            foreach ($states as $state) {
                if (strtolower($state['State']) === strtolower($stateName)) {
                    return str_pad($state['Code'], 2, '0', STR_PAD_LEFT); // Ensure 2 digits
                }
            }
        }

        return '01'; // Default fallback (Selangor)
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
