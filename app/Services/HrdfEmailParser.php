<?php
// filepath: /var/www/html/timeteccrm/app/Services/HrdfEmailParser.php

namespace App\Services;

use App\Models\HrdfClaim;
use Carbon\Carbon;
use Illuminate\Support\Str;

class HrdfEmailParser
{
    public function parseApprovalEmail($emailContent, $subject, $from, $receivedAt)
    {
        // Verify this is an HRDF approval email
        if (!$this->isHrdfApprovalEmail($subject, $from)) {
            return false;
        }

        $parsedData = $this->extractDataFromEmail($emailContent);

        if ($parsedData) {
            return $this->updateHrdfClaim($parsedData);
        }

        return false;
    }

    private function isHrdfApprovalEmail($subject, $from)
    {
        return Str::contains($subject, 'SBL-Khas Approved.') &&
               Str::contains($from, 'noreply@notifications.hrdcorp.gov.my');
    }

    private function extractDataFromEmail($emailContent)
    {
        $data = [];

        // Extract Company Name
        if (preg_match('/^([A-Z\s&.-]+(?:SDN\.?\s*BHD\.?|BHD\.?|SDN\.?))\s*$/m', $emailContent, $matches)) {
            $data['company_name'] = trim($matches[1]);
        }

        // Extract Application Number (HRDF Grant ID)
        if (preg_match('/APPLICATION NUMBER\s*:\s*([A-Z0-9_]+)/i', $emailContent, $matches)) {
            $data['hrdf_grant_id'] = trim($matches[1]);
        }

        // Extract Training Dates
        if (preg_match('/DATE OF PROGRAM\s*:\s*From\s*:\s*(\d{2}\/\d{2}\/\d{4})\s*To\s*:\s*(\d{2}\/\d{2}\/\d{4})/i', $emailContent, $matches)) {
            $data['training_start_date'] = Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');
            $data['training_end_date'] = Carbon::createFromFormat('d/m/Y', $matches[2])->format('Y-m-d');
            $data['hrdf_training_date'] = $matches[1] . ' To : ' . $matches[2];
        }

        // Extract Approved Amount
        if (preg_match('/TOTAL AMOUNT \(RM\)\s*:\s*([\d,]+\.?\d*)/i', $emailContent, $matches)) {
            $data['invoice_amount'] = (float) str_replace(',', '', $matches[1]);
        }

        // Extract Programme Name
        if (preg_match('/PROGRAMME NAME\s*:\s*(.+)/i', $emailContent, $matches)) {
            $data['programme_name'] = trim($matches[1]);
        }

        // Extract Approved Date
        if (preg_match('/Approved Date\s*:\s*(\d{2}\/\d{2}\/\d{4})/i', $emailContent, $matches)) {
            $data['approved_date'] = Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');
        }

        return !empty($data) ? $data : null;
    }

    private function updateHrdfClaim($parsedData)
    {
        try {
            // Find existing HRDF claim by company name and grant ID
            $hrdfClaim = HrdfClaim::where('company_name', $parsedData['company_name'])
                ->orWhere('hrdf_grant_id', $parsedData['hrdf_grant_id'])
                ->first();

            if ($hrdfClaim) {
                // Update existing claim
                $hrdfClaim->update([
                    'hrdf_grant_id' => $parsedData['hrdf_grant_id'] ?? $hrdfClaim->hrdf_grant_id,
                    'invoice_amount' => $parsedData['invoice_amount'] ?? $hrdfClaim->invoice_amount,
                    'hrdf_training_date' => $parsedData['hrdf_training_date'] ?? $hrdfClaim->hrdf_training_date,
                    'claim_status' => 'RECEIVED',
                    'approved_date' => $parsedData['approved_date'] ?? now()->format('Y-m-d'),
                    'programme_name' => $parsedData['programme_name'] ?? null,
                    'updated_at' => now(),
                ]);

                return [
                    'success' => true,
                    'action' => 'updated',
                    'claim_id' => $hrdfClaim->id,
                    'message' => 'HRDF claim updated successfully'
                ];
            } else {
                // Create new claim if not found
                $newClaim = HrdfClaim::create([
                    'company_name' => $parsedData['company_name'],
                    'hrdf_grant_id' => $parsedData['hrdf_grant_id'],
                    'invoice_amount' => $parsedData['invoice_amount'] ?? 0,
                    'hrdf_training_date' => $parsedData['hrdf_training_date'],
                    'claim_status' => 'RECEIVED',
                    'approved_date' => $parsedData['approved_date'] ?? now()->format('Y-m-d'),
                    'programme_name' => $parsedData['programme_name'] ?? null,
                    'sales_person' => 'AUTO-PARSED', // You might want to match this
                    'created_at' => now(),
                ]);

                return [
                    'success' => true,
                    'action' => 'created',
                    'claim_id' => $newClaim->id,
                    'message' => 'New HRDF claim created successfully'
                ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
