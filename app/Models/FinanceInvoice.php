<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'fc_number',
        'reseller_handover_id',
        'autocount_invoice_number',
        'timetec_invoice_number',
        'reseller_name',
        'subscriber_name',
        'reseller_commission_amount',
        'portal_type',
        'status',
        'created_by',
    ];

    protected $casts = [
        'reseller_commission_amount' => 'decimal:2',
    ];

    protected $appends = ['formatted_id'];

    /**
     * Set the reseller name to uppercase
     */
    public function setResellerNameAttribute($value)
    {
        $this->attributes['reseller_name'] = strtoupper($value);
    }

    /**
     * Set the subscriber name to uppercase
     */
    public function setSubscriberNameAttribute($value)
    {
        $this->attributes['subscriber_name'] = strtoupper($value);
    }

    /**
     * Set the TimeTec invoice number to uppercase
     */
    public function setTimetecInvoiceNumberAttribute($value)
    {
        $this->attributes['timetec_invoice_number'] = strtoupper($value);
    }

    public function getFormattedIdAttribute()
    {
        if (!$this->id || !$this->created_at) {
            return null;
        }

        $year = $this->created_at->format('y'); // Get last 2 digits of year

        // Get the sequential number for this year
        $yearStart = $this->created_at->copy()->startOfYear();
        $yearEnd = $this->created_at->copy()->endOfYear();

        $sequentialNumber = self::whereBetween('created_at', [$yearStart, $yearEnd])
            ->where('id', '<=', $this->id)
            ->count();

        return 'FC_' . $year . str_pad($sequentialNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get a fresh timestamp for the model.
     * Automatically adjusts timestamps to UTC-8 (Malaysia time)
     */
    public function freshTimestamp(): \Illuminate\Support\Carbon
    {
        return now()->subHours(8);
    }

    // Relationships
    public function resellerHandover(): BelongsTo
    {
        return $this->belongsTo(ResellerHandover::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function adminPortalInvoices()
    {
        return $this->hasMany(AdminPortalInvoice::class, 'finance_invoice_id');
    }

    // Generate FC Number
    public static function generateFcNumber(string $portalType = 'reseller'): string
    {
        $year = now()->format('y'); // Get last 2 digits of year
        $prefix = 'FC';

        $latestInvoice = self::whereYear('created_at', now()->year)
            ->where('portal_type', $portalType)
            ->where('fc_number', 'LIKE', $prefix . '_%')
            ->orderBy('fc_number', 'desc')
            ->first();

        $nextNumber = $latestInvoice ? intval(substr($latestInvoice->fc_number, -4)) + 1 : 1;

        return $prefix . '_' . $year . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
