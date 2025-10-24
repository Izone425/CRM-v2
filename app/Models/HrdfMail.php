<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class HrdfMail extends Model
{
    protected $fillable = [
        'message_id',
        'subject',
        'from_email',
        'from_name',
        'to_recipients',
        'cc_recipients',
        'bcc_recipients',
        'received_date',
        'sent_date',
        'body_preview',
        'body_content',
        'body_type',
        'has_attachments',
        'importance',
        'is_read',
        'approval_date',
        'status',
        'raw_email_data',
    ];

    protected $casts = [
        'to_recipients' => 'array',
        'cc_recipients' => 'array',
        'bcc_recipients' => 'array',
        'received_date' => 'datetime',
        'sent_date' => 'datetime',
        'approval_date' => 'date',
        'has_attachments' => 'boolean',
        'is_read' => 'boolean',
        'raw_email_data' => 'array',
    ];

    // Parse HRDCorp data from email content - only extract what's in fillable
    public static function parseHrdfData($bodyContent)
    {
        $data = [];

        // Extract approval date
        if (preg_match('/Approved\s+Date\s*:\s*(\d{2}\/\d{2}\/\d{4})/', $bodyContent, $matches)) {
            try {
                $data['approval_date'] = Carbon::createFromFormat('d/m/Y', $matches[1]);
            } catch (\Exception $e) {
                // Skip if date parsing fails
            }
        }

        return $data;
    }

    // Scope for HRDCorp emails
    public function scopeHrdcorpEmails($query)
    {
        return $query->where('from_email', 'like', '%hrdcorp.gov.my%')
                    ->orWhere('subject', 'like', '%SBL-Khas Approved%');
    }

    // Scope for pending applications
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function claimTracker()
    {
        return $this->hasOne(HrdfClaim::class, 'hrdf_mail_id');
    }

    // Update the method to auto-create claim tracker
    public function createClaimTracker()
    {
        if (!$this->claimTracker && $this->status === 'pending') {
            return HrdfClaim::createFromHrdfMail($this);
        }

        return $this->claimTracker;
    }

    public function scopeClaimApprovalEmails($query)
    {
        return $query->where('status', 'claim_approved');
    }
}
