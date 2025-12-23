<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinanceHandover extends Model
{
    use HasFactory;

    protected $table = 'finance_handovers';

    protected $fillable = [
        'lead_id',
        'reseller_id',
        'created_by',
        'pic_name',
        'pic_phone',
        'pic_email',
        'invoice_by_customer',
        'payment_by_customer',
        'invoice_by_reseller',
        'status',
        'submitted_at',
        'remarks',
        'related_hardware_handovers',
    ];

    protected $casts = [
        'invoice_by_customer' => 'array',
        'payment_by_customer' => 'array',
        'invoice_by_reseller' => 'array',
        'submitted_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'related_hardware_handovers' => 'array',
    ];

    protected $dates = [
        'submitted_at',
        'created_at',
        'updated_at',
    ];

    /**
     * Get the lead that owns the finance handover
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id');
    }

    /**
     * Get the reseller associated with the finance handover
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }

    /**
     * Get the user who created the finance handover
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get formatted handover ID attribute
     * Format: FN_YYXXXX (where YY is year and XXXX is padded ID)
     *
     * @return string
     */
    public function getFormattedHandoverIdAttribute(): string
    {
        $year = $this->created_at ? $this->created_at->format('y') : now()->format('y');
        $maxNum = 9999; // Maximum 4-digit number
        $num = $this->id % $maxNum == 0 ? $maxNum : ($this->id % $maxNum);

        return sprintf('FN_%02d%04d', $year, $num);
    }

    /**
     * Static method to generate formatted handover ID
     *
     * @param int $id
     * @param string|null $createdAt
     * @return string
     */
    public static function generateFormattedId(int $id, ?string $createdAt = null): string
    {
        $year = $createdAt
            ? \Carbon\Carbon::parse($createdAt)->format('y')
            : now()->format('y');

        $maxNum = 9999;
        $num = $id % $maxNum == 0 ? $maxNum : ($id % $maxNum);

        return sprintf('FN_%02d%04d', $year, $num);
    }

    /**
     * Get formatted finance handover ID (legacy method for backward compatibility)
     */
    public function getFormattedIdAttribute(): string
    {
        return $this->formatted_handover_id;
    }

    /**
     * Get invoice by customer files as array
     */
    public function getInvoiceByCustomerFilesAttribute(): array
    {
        if (!$this->invoice_by_customer) {
            return [];
        }

        $files = is_string($this->invoice_by_customer)
            ? json_decode($this->invoice_by_customer, true)
            : $this->invoice_by_customer;

        return is_array($files) ? $files : [];
    }

    /**
     * Get payment by customer files as array
     */
    public function getPaymentByCustomerFilesAttribute(): array
    {
        if (!$this->payment_by_customer) {
            return [];
        }

        $files = is_string($this->payment_by_customer)
            ? json_decode($this->payment_by_customer, true)
            : $this->payment_by_customer;

        return is_array($files) ? $files : [];
    }

    /**
     * Get invoice by reseller files as array
     */
    public function getInvoiceByResellerFilesAttribute(): array
    {
        if (!$this->invoice_by_reseller) {
            return [];
        }

        $files = is_string($this->invoice_by_reseller)
            ? json_decode($this->invoice_by_reseller, true)
            : $this->invoice_by_reseller;

        return is_array($files) ? $files : [];
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'New' => 'success',
            'Processing' => 'warning',
            'Completed' => 'info',
            'Rejected' => 'danger',
            default => 'secondary',
        };
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by lead
     */
    public function scopeByLead($query, int $leadId)
    {
        return $query->where('lead_id', $leadId);
    }

    /**
     * Scope for filtering by created user
     */
    public function scopeByCreator($query, int $userId)
    {
        return $query->where('created_by', $userId);
    }

    /**
     * Check if finance handover can be edited
     */
    public function canEdit(): bool
    {
        return $this->status === 'New';
    }

    /**
     * Check if finance handover can be deleted
     */
    public function canDelete(): bool
    {
        return $this->status === 'New';
    }

    /**
     * Get all files count
     */
    public function getTotalFilesCountAttribute(): int
    {
        return count($this->invoice_by_customer_files) +
               count($this->payment_by_customer_files) +
               count($this->invoice_by_reseller_files);
    }
}
