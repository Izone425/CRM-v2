<?php
// filepath: /var/www/html/timeteccrm/app/Models/RenewalHandover.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class RenewalHandover extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'handover_id',
        'company_name',
        'selected_quotation_ids',
        'invoice_numbers',
        'debtor_code',
        'total_amount',
        'status',
        'created_by',
        'notes',
        'processed_at',
        'autocount_response',
        'tt_invoice_number',
        'hrdf_grant_ids',
    ];

    protected $casts = [
        'selected_quotation_ids' => 'array',
        'invoice_numbers' => 'array',
        'total_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'autocount_response' => 'array',
        'hrdf_grant_ids' => 'array',
    ];

    /**
     * Boot method - Remove the auto-generation since we'll use formatted_handover_id
     */
    protected static function boot()
    {
        parent::boot();
        // Remove the creating event since we'll use the formatted ID approach
    }

    /**
     * Get formatted handover ID similar to SoftwareHandover
     * Format: RW_YYXXXX (where YY is year and XXXX is padded ID)
     */
    public function getFormattedHandoverIdAttribute(): string
    {
        $year = $this->created_at ? $this->created_at->format('y') : now()->format('y');
        $maxNum = 9999; // Maximum 4-digit number
        $num = $this->id % $maxNum == 0 ? $maxNum : ($this->id % $maxNum);

        return sprintf('RW_%02d%04d', $year, $num);
    }

    /**
     * Static method to generate formatted handover ID
     */
    public static function generateFormattedId(int $id, ?string $createdAt = null): string
    {
        $year = $createdAt
            ? Carbon::parse($createdAt)->format('y')
            : now()->format('y');

        $maxNum = 9999;
        $num = $id % $maxNum == 0 ? $maxNum : ($id % $maxNum);

        return sprintf('RW_%02d%04d', $year, $num);
    }

    /**
     * Get handover ID (use formatted version)
     * This allows backward compatibility with existing code
     */
    public function getHandoverIdAttribute(): string
    {
        return $this->formatted_handover_id;
    }

    /**
     * Relationship to Lead
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Relationship to User (creator)
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get quotations for this renewal handover
     * Since we store quotation IDs in a JSON array, we need a custom method
     */
    public function quotations()
    {
        if (empty($this->selected_quotation_ids)) {
            return Quotation::whereRaw('0 = 1'); // Return empty query builder
        }

        return Quotation::whereIn('id', $this->selected_quotation_ids);
    }

    /**
     * Get quotations collection (for when you need the actual data)
     */
    public function getQuotationsDataAttribute()
    {
        if (empty($this->selected_quotation_ids)) {
            return collect();
        }

        return Quotation::whereIn('id', $this->selected_quotation_ids)->get();
    }

    /**
     * Relationship to HRDF invoices
     */
    public function hrdfInvoices()
    {
        return $this->hasMany(CrmHrdfInvoice::class, 'handover_id', 'id')
                   ->where('handover_type', 'RW');
    }

    /**
     * Get formatted status
     */
    public function getFormattedStatusAttribute(): string
    {
        return ucfirst($this->status);
    }

    /**
     * Get total quotations count
     */
    public function getTotalQuotationsAttribute(): int
    {
        return is_array($this->selected_quotation_ids) ? count($this->selected_quotation_ids) : 0;
    }

    /**
     * Get total invoices count
     */
    public function getTotalInvoicesAttribute(): int
    {
        return is_array($this->invoice_numbers) ? count($this->invoice_numbers) : 0;
    }

    /**
     * Scope for completed handovers
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for pending handovers
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Mark handover as completed
     */
    public function markAsCompleted(array $invoiceNumbers, array $autoCountResponse = null): void
    {
        $this->update([
            'status' => 'completed',
            'invoice_numbers' => $invoiceNumbers,
            'processed_at' => now(),
            'autocount_response' => $autoCountResponse,
        ]);
    }

    /**
     * Mark handover as failed
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'processed_at' => now(),
            'autocount_response' => ['error' => $error],
        ]);
    }
}
