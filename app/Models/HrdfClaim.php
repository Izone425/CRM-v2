<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class HrdfClaim extends Model
{
    use HasFactory;

    protected $table = 'hrdf_claims';

    protected $fillable = [
        'sales_person',
        'company_name',
        'invoice_amount',
        'invoice_number',
        'sales_remark',
        'claim_status',
        'hrdf_grant_id',
        'hrdf_training_date',
        'hrdf_claim_id',
        'programme_name',
        'approved_date',
        'email_processed_at',
        'hrdf_mail_id',
        'submitted_at',
        'approved_at',
        'received_at',
        'upfront_payment',
        'pax'
    ];

    protected $casts = [
        'invoice_amount' => 'decimal:2',
        'upfront_payment' => 'decimal:2',
        'pax' => 'integer',
        'approved_date' => 'date',
        'email_processed_at' => 'datetime',
        'hrdf_training_date' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'company_name', 'company_name');
    }

    public function hrdfMail()
    {
        return $this->belongsTo(HrdfMail::class, 'hrdf_mail_id');
    }

    // Scopes
    public function scopeReceived($query)
    {
        return $query->where('claim_status', 'RECEIVED');
    }

    public function scopePending($query)
    {
        return $query->where('claim_status', 'PENDING');
    }

    public function scopeSubmitted($query)
    {
        return $query->where('claim_status', 'SUBMITTED');
    }

    public function scopeApproved($query)
    {
        return $query->where('claim_status', 'APPROVED');
    }

    public function scopeByCompany($query, $companyName)
    {
        return $query->where('company_name', $companyName);
    }

    // Auto-create from HRDF Mail - only use fillable fields
    public static function createFromHrdfMail(HrdfMail $hrdfMail)
    {
        // Extract data using line numbers instead of structured data
        $extractedData = self::extractDataByLineNumbers($hrdfMail->body_content);

        $data = [
            'sales_person' => 'AUTO-PARSED',
            'company_name' => $extractedData['company_name'] ?? 'Unknown Company',
            'invoice_amount' => $extractedData['invoice_amount'] ?? 0,
            'invoice_number' => null,
            'sales_remark' => 'Auto-created from email ID: ' . $hrdfMail->id,
            'claim_status' => 'PENDING',
            'hrdf_grant_id' => $extractedData['hrdf_grant_id'] ?? 'GRANT-' . $hrdfMail->id,
            'hrdf_training_date' => $extractedData['hrdf_training_date'] ?? '',
            'hrdf_claim_id' => null,
            'programme_name' => $extractedData['programme_name'] ?? 'Unknown Programme',
            'approved_date' => $extractedData['approved_date'] ?? $hrdfMail->received_date->toDateString(),
            'email_processed_at' => now(),
            'upfront_payment' => $extractedData['upfront_payment'] ?? 0,
            'pax' => $extractedData['pax'] ?? 0,
        ];

        // Only add hrdf_mail_id if the column exists
        if (Schema::hasColumn('hrdf_claims', 'hrdf_mail_id')) {
            $data['hrdf_mail_id'] = $hrdfMail->id;
        }

        return self::create($data);
    }

    // Extract data from email body content
    private static function extractDataByLineNumbers($bodyContent)
    {
        $data = [];

        // If content still contains HTML, clean it first
        if (strpos($bodyContent, '<') !== false) {
            $bodyContent = self::cleanBodyContent($bodyContent);
        }

        // Split content into lines
        $lines = explode("\n", $bodyContent);

        if (isset($lines[8])) {
            $data['company_name'] = strtoupper(trim($lines[8]));
        }

        // Extract other fields using pattern matching
        foreach ($lines as $line) {
            $line = trim($line);

            // Application number - hrdf_grant_id
            if (preg_match('/APPLICATION\s+NUMBER\s*:\s*([A-Z0-9_]+)/', $line, $matches)) {
                $data['hrdf_grant_id'] = trim($matches[1]);
            }

            // Programme name
            if (preg_match('/PROGRAMME\s+NAME\s*:\s*(.+)/i', $line, $matches)) {
                $data['programme_name'] = trim($matches[1]);
            }

            // Training date - DATE OF PROGRAM : From : 21/10/2025 To : 23/10/2025
            if (preg_match('/DATE\s+OF\s+PROGRAM\s*:\s*From\s*:\s*(\d{2}\/\d{2}\/\d{4})\s*To\s*:\s*(\d{2}\/\d{2}\/\d{4})/', $line, $matches)) {
                $data['hrdf_training_date'] = $matches[1] . ' To : ' . $matches[2];
            }

            // Total amount - TOTAL AMOUNT (RM) : 12,960.00
            if (preg_match('/TOTAL\s+AMOUNT\s*\(RM\)\s*:\s*([\d,\.]+)/', $line, $matches)) {
                $data['invoice_amount'] = (float) str_replace(',', '', $matches[1]);
            }

            // Upfront Payment - Upfront Payment to Training Provider ? RM 3,888.00
            if (preg_match('/Upfront\s+Payment\s+to\s+Training\s+Provider\s*\?\s*RM\s*([\d,\.]+)/', $line, $matches)) {
                $data['upfront_payment'] = (float) str_replace(',', '', $matches[1]);
            }

            // Number of People/Pax - Multiple patterns to handle different table formats

            // Pattern 1: Course Fee 4 1,080.00 3.0 12,960.00
            if (preg_match('/Course\s+Fee\s+(\d+)\s+[\d,\.]+\s+[\d\.]+\s+[\d,\.]+/', $line, $matches)) {
                $data['pax'] = (int) $matches[1];
            }

            // Pattern 2: Course Fee followed by number on same line with any spacing
            if (preg_match('/Course\s+Fee\s+(\d+)/', $line, $matches)) {
                $data['pax'] = (int) $matches[1];
            }

            // Pattern 3: Just a number after Course Fee (flexible spacing)
            if (preg_match('/Course\s*Fee[\s\t]*(\d+)/', $line, $matches)) {
                $data['pax'] = (int) $matches[1];
            }

            // Pattern 4: No. of People with number
            if (preg_match('/No\.\s*of\s*People\s*(\d+)/', $line, $matches)) {
                $data['pax'] = (int) $matches[1];
            }

            // Pattern 5: Table format where we look for lines that start with just a number (likely pax)
            if (preg_match('/^(\d+)$/', $line, $matches) && !isset($data['pax'])) {
                // Only capture if it's a reasonable number for participants (1-100)
                $num = (int) $matches[1];
                if ($num >= 1 && $num <= 100) {
                    $data['pax'] = $num;
                }
            }

            // Approval date
            if (preg_match('/Approved\s+Date\s*:\s*(\d{2}\/\d{2}\/\d{4})/', $line, $matches)) {
                try {
                    $data['approved_date'] = Carbon::createFromFormat('d/m/Y', $matches[1])->toDateString();
                } catch (\Exception $e) {
                    // Skip if date parsing fails
                }
            }
        }

        return $data;
    }

    private static function cleanBodyContent($htmlContent)
    {
        // Convert &nbsp; to regular spaces
        $htmlContent = str_replace('&nbsp;', ' ', $htmlContent);

        // Convert other HTML entities
        $htmlContent = html_entity_decode($htmlContent, ENT_QUOTES, 'UTF-8');

        // Add line breaks before closing tags
        $htmlContent = preg_replace('/<\/(div|p|br|tr|td|th|li|h[1-6])>/i', "\n", $htmlContent);
        $htmlContent = preg_replace('/<(br|hr)\s*\/?>/i', "\n", $htmlContent);

        // Remove HTML tags
        $cleanContent = strip_tags($htmlContent);

        // Convert multiple spaces to single space
        $cleanContent = preg_replace('/\s+/', ' ', $cleanContent);

        // Split into lines and clean each line
        $lines = explode("\n", $cleanContent);
        $cleanLines = [];
        foreach ($lines as $line) {
            $cleanLines[] = trim($line);
        }

        return implode("\n", $cleanLines);
    }

    // Update status
    public function updateStatus($newStatus, $notes = null)
    {
        $this->claim_status = strtoupper($newStatus);

        if ($notes) {
            $this->sales_remark = $notes;
        }

        $this->save();

        return $this;
    }

    // Map invoice details
    public function mapInvoiceDetails($invoiceNumber, $salesperson = null)
    {
        $this->invoice_number = $invoiceNumber;

        if ($salesperson) {
            $this->sales_person = $salesperson;
        }

        if (!$this->hrdf_claim_id) {
            $this->hrdf_claim_id = 'CLAIM-' . $this->id . '-' . date('Y');
        }

        $this->save();

        return $this;
    }

    public function hrdfHandover()
    {
        return $this->hasOne(HRDFHandover::class, 'hrdf_grant_id', 'hrdf_grant_id');
    }

    public function hrdfInvoices()
    {
        return $this->hasMany(CrmHrdfInvoiceV2::class, 'hrdf_grant_id', 'hrdf_grant_id');
    }
}
