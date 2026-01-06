<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HeadcountHandover extends Model
{
    use SoftDeletes;

    protected $table = 'headcount_handovers';

    protected $fillable = [
        'lead_id',
        'proforma_invoice_product',
        'proforma_invoice_hrdf',
        'payment_slip_file',
        'confirmation_order_file',
        'salesperson_remark',
        'status',
        'submitted_at',
        'created_by',
        'reject_reason',
        'completed_by',
        'completed_at',
        'rejected_by',
        'rejected_at',
        'invoice_file',
        'product_pi_invoice_data', // Product PI tracking data
        'hrdf_pi_invoice_data', // HRDF PI tracking data
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'proforma_invoice_product' => 'array',
        'proforma_invoice_hrdf' => 'array',
        'payment_slip_file' => 'array',
        'confirmation_order_file' => 'array',
        'invoice_file' => 'array',
        'product_pi_invoice_data' => 'array', // Product PI tracking data
        'hrdf_pi_invoice_data' => 'array', // HRDF PI tracking data
    ];

    // Mutator to automatically convert remark to uppercase
    public function setSalespersonRemarkAttribute($value)
    {
        $this->attributes['salesperson_remark'] = $value ? Str::upper($value) : $value;
    }

    public function setRejectReasonAttribute($value)
    {
        $this->attributes['reject_reason'] = $value ? Str::upper($value) : $value;
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    /**
     * Get formatted handover ID attribute
     * Format: HC_YYXXXX (where YY is year and XXXX is padded ID)
     *
     * @return string
     */
    public function getFormattedHandoverIdAttribute(): string
    {
        $year = $this->created_at ? $this->created_at->format('y') : now()->format('y');
        $maxNum = 9999; // Maximum 4-digit number
        $num = $this->id % $maxNum == 0 ? $maxNum : ($this->id % $maxNum);

        return sprintf('HC_%02d%04d', $year, $num);
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

        return sprintf('HC_%02d%04d', $year, $num);
    }
}
