<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellerHandover extends Model
{
    use HasFactory;

    protected $fillable = [
        'reseller_id',
        'reseller_name',
        'reseller_company_name',
        'subscriber_id',
        'subscriber_name',
        'subscriber_status',
        'attendance_qty',
        'leave_qty',
        'claim_qty',
        'payroll_qty',
        'reseller_remark',
        'admin_reseller_remark',
        'timetec_proforma_invoice',
        'status',
        'confirmed_proceed_at',
        'autocount_invoice',
        'reseller_invoice',
        'autocount_invoice_number',
        'reseller_option',
        'completed_at',
        'reseller_normal_invoice',
        'reseller_payment_slip',
        'official_receipt_number'
    ];

    protected $casts = [
        'attendance_qty' => 'integer',
        'leave_qty' => 'integer',
        'claim_qty' => 'integer',
        'payroll_qty' => 'integer',
        'confirmed_proceed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Set the reseller company name to uppercase
     */
    public function setResellerCompanyNameAttribute($value)
    {
        $this->attributes['reseller_company_name'] = strtoupper($value);
    }

    /**
     * Set the subscriber name to uppercase
     */
    public function setSubscriberNameAttribute($value)
    {
        $this->attributes['subscriber_name'] = strtoupper($value);
    }

    /**
     * Get the formatted FB ID
     */
    public function getFbIdAttribute()
    {
        $year = $this->created_at ? $this->created_at->format('y') : date('y');
        return 'FB_' . $year . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the encrypted invoice URL
     */
    public function getInvoiceUrlAttribute()
    {
        if (!$this->timetec_proforma_invoice || !$this->subscriber_id) {
            return null;
        }

        // Get the f_id from crm_expiring_license table using invoice number and company id
        $license = DB::connection('frontenddb')
            ->table('crm_invoice_details')
            ->where('f_invoice_no', $this->timetec_proforma_invoice)
            ->first(['f_id']);

        if (!$license || !$license->f_id) {
            return null;
        }

        $aesKey = 'Epicamera@99';
        try {
            $encrypted = openssl_encrypt($license->f_id, "AES-128-ECB", $aesKey);
            $encryptedBase64 = base64_encode($encrypted);
            return 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . $encryptedBase64;
        } catch (\Exception $e) {
            Log::error('License ID encryption failed: ' . $e->getMessage(), [
                'license_id' => $license->f_id,
                'invoice_no' => $this->timetec_proforma_invoice
            ]);
            return null;
        }
    }

    /**
     * Get all files for this handover in a formatted array
     */
    public function getAllFilesForModal(): array
    {
        $files = [];

        // Helper function to decode JSON or return single value
        $decodeFiles = function($field) {
            if (!$field) return [];
            return is_string($field) && json_decode($field)
                ? json_decode($field, true)
                : [$field];
        };

        // PDF File
        foreach ($decodeFiles($this->pdf_file) as $index => $file) {
            $count = count($decodeFiles($this->pdf_file));
            $files[] = [
                'name' => 'PDF File' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Autocount Invoice
        foreach ($decodeFiles($this->autocount_invoice) as $index => $file) {
            $count = count($decodeFiles($this->autocount_invoice));
            $files[] = [
                'name' => 'Autocount Invoice' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Reseller Invoice
        foreach ($decodeFiles($this->reseller_invoice) as $index => $file) {
            $count = count($decodeFiles($this->reseller_invoice));
            $files[] = [
                'name' => 'Reseller Invoice' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Reseller Normal Invoice
        foreach ($decodeFiles($this->reseller_normal_invoice) as $index => $file) {
            $count = count($decodeFiles($this->reseller_normal_invoice));
            $files[] = [
                'name' => 'Reseller Normal Invoice' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Reseller Payment Slip
        foreach ($decodeFiles($this->reseller_payment_slip) as $index => $file) {
            $count = count($decodeFiles($this->reseller_payment_slip));
            $files[] = [
                'name' => 'Reseller Payment Slip' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        return $files;
    }

    /**
     * Get categorized files for modal display
     */
    public function getCategorizedFilesForModal(): array
    {
        $categorized = [
            'pending_confirmation' => [],
            'pending_timetec_invoice' => [],
            'pending_reseller_invoice' => [],
            'pending_timetec_license' => [],
            'completed' => [],
        ];

        // Helper function to decode JSON or return single value
        $decodeFiles = function($field) {
            if (!$field) return [];
            return is_string($field) && json_decode($field)
                ? json_decode($field, true)
                : [$field];
        };

        // Pending Confirmation Stage - PDF File
        foreach ($decodeFiles($this->pdf_file) as $index => $file) {
            $count = count($decodeFiles($this->pdf_file));
            $categorized['pending_confirmation'][] = [
                'name' => 'PDF File' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Pending TimeTec Invoice Stage - Autocount Invoice & Reseller Invoice
        foreach ($decodeFiles($this->autocount_invoice) as $index => $file) {
            $count = count($decodeFiles($this->autocount_invoice));
            $categorized['pending_timetec_invoice'][] = [
                'name' => 'Autocount Invoice' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        foreach ($decodeFiles($this->reseller_invoice) as $index => $file) {
            $count = count($decodeFiles($this->reseller_invoice));
            $categorized['pending_timetec_invoice'][] = [
                'name' => 'Sample Reseller Invoice' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Pending Reseller Invoice Stage - Reseller Normal Invoice
        foreach ($decodeFiles($this->reseller_normal_invoice) as $index => $file) {
            $count = count($decodeFiles($this->reseller_normal_invoice));
            $categorized['pending_reseller_invoice'][] = [
                'name' => 'Reseller Normal Invoice' . ($count > 1 ? ' #' . ($index + 1) : ''),
                'path' => $file,
                'url' => asset('storage/' . $file),
            ];
        }

        // Payment Slip categorization based on reseller_option
        if ($this->reseller_option === 'reseller_normal_invoice_with_payment_slip') {
            // If with payment slip option, put in pending_reseller_invoice stage
            foreach ($decodeFiles($this->reseller_payment_slip) as $index => $file) {
                $count = count($decodeFiles($this->reseller_payment_slip));
                $categorized['pending_reseller_invoice'][] = [
                    'name' => 'Reseller Payment Slip' . ($count > 1 ? ' #' . ($index + 1) : ''),
                    'path' => $file,
                    'url' => asset('storage/' . $file),
                ];
            }
        } else {
            // If normal invoice only, put in completed stage
            foreach ($decodeFiles($this->reseller_payment_slip) as $index => $file) {
                $count = count($decodeFiles($this->reseller_payment_slip));
                $categorized['completed'][] = [
                    'name' => 'Reseller Payment Slip' . ($count > 1 ? ' #' . ($index + 1) : ''),
                    'path' => $file,
                    'url' => asset('storage/' . $file),
                ];
            }
        }

        // Pending TimeTec License Stage - No files, only official receipt number shown in modal
        // Files are not needed in this stage

        return $categorized;
    }
}
