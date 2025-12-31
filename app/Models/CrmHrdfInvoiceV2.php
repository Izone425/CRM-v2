<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class CrmHrdfInvoiceV2 extends Model
{
    use HasFactory;
    protected $table = 'crm_hrdf_invoice_v2s';
    protected $fillable = [
        'invoice_no',
        'invoice_date',
        'company_name',
        'handover_type',
        'handover_id',
        'tt_invoice_number',
        'subtotal',
        'total_amount',
        'debtor_code',
        'salesperson',
        'status',
        'handover_data',
        'hrdf_grant_id',
        'proforma_invoice_data'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'handover_data' => 'array',
        'proforma_invoice_data' => 'integer'
    ];

    // Generate formatted invoice number
    public static function generateInvoiceNumber(): string
    {
        $year = Carbon::now()->format('y');
        $month = Carbon::now()->format('m');

        // Get the last invoice for this year/month
        $lastInvoice = static::where('invoice_no', 'like', "EHIN{$year}{$month}%")
            ->orderBy('invoice_no', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = intval(substr($lastInvoice->invoice_no, -4));
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0100';
        }

        return "EHIN{$year}{$month}-{$nextNumber}";
    }

    // Relationship to get handover details based on type
    public function getHandoverAttribute()
    {
        return match($this->handover_type) {
            'SW' => SoftwareHandover::find($this->handover_id),
            'HW' => HardwareHandover::find($this->handover_id),
            'RW' => RenewalHandover::find($this->handover_id),
            default => null
        };
    }

    // Get formatted handover ID
    public function getFormattedHandoverIdAttribute(): string
    {
        $prefix = match($this->handover_type) {
            'SW' => 'SW_',
            'HW' => 'HW_',
            'RW' => 'RW_',
            default => 'SW_'
        };

        return $prefix . str_pad($this->handover_id, 6, '0', STR_PAD_LEFT);
    }

    // Scopes
    public function scopeByType($query, $type)
    {
        return $query->where('handover_type', $type);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('invoice_date', Carbon::now()->month)
                    ->whereYear('invoice_date', Carbon::now()->year);
    }

    public function scopeThisYear($query)
    {
        return $query->whereYear('invoice_date', Carbon::now()->year);
    }

    // Relationships
    public function hrdfClaim()
    {
        return $this->belongsTo(HrdfClaim::class, 'hrdf_grant_id', 'hrdf_grant_id');
    }
}
