<?php
// filepath: /var/www/html/timeteccrm/app/Models/HrdfClaim.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class HrdfClaim extends Model
{
    use HasFactory;

    protected $table = 'hrdf_claims'; // Adjust table name if different

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
    ];

    protected $casts = [
        'invoice_amount' => 'decimal:2',
        'approved_date' => 'date',
        'email_processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function lead()
    {
        return $this->belongsTo(Lead::class, 'company_name', 'company_name');
    }

    // Scopes
    public function scopeReceived($query)
    {
        return $query->where('claim_status', 'RECEIVED');
    }

    public function scopeByCompany($query, $companyName)
    {
        return $query->where('company_name', $companyName);
    }

    // Accessors
    public function getFormattedAmountAttribute()
    {
        return 'RM ' . number_format($this->invoice_amount, 2);
    }

    public function getTrainingDateRangeAttribute()
    {
        if (!$this->hrdf_training_date) {
            return null;
        }

        // Parse the date range format "07/10/2025 To : 09/10/2025"
        if (preg_match('/(\d{2}\/\d{2}\/\d{4})\s*To\s*:\s*(\d{2}\/\d{2}\/\d{4})/', $this->hrdf_training_date, $matches)) {
            $startDate = Carbon::createFromFormat('d/m/Y', $matches[1]);
            $endDate = Carbon::createFromFormat('d/m/Y', $matches[2]);

            return [
                'start' => $startDate,
                'end' => $endDate,
                'formatted' => $startDate->format('d M Y') . ' - ' . $endDate->format('d M Y')
            ];
        }

        return null;
    }
}
