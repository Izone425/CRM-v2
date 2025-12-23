<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class HRDFHandover extends Model
{
    use SoftDeletes;

    protected $table = 'hrdf_handovers';

    protected $fillable = [
        'lead_id',
        'subsidiary_id',
        'hrdf_grant_id',
        'hrdf_claim_id',
        'autocount_invoice_number',
        'jd14_form_files',
        'autocount_invoice_file',
        'hrdf_grant_approval_file',
        'salesperson_remark',
        'status',
        'submitted_at',
        'created_by',
        'reject_reason',
        'completed_by',  // Changed from 'approved_by'
        'completed_at',  // Changed from 'approved_at'
        'rejected_by',
        'rejected_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',  // Changed from 'approved_at'
        'rejected_at' => 'datetime',
        'jd14_form_files' => 'array',
        'autocount_invoice_file' => 'array',
        'hrdf_grant_approval_file' => 'array',
    ];

    // Mutators to automatically convert to uppercase
    public function setHrdfGrantIdAttribute($value)
    {
        $this->attributes['hrdf_grant_id'] = $value ? Str::upper($value) : $value;
    }

    public function setHrdfClaimIdAttribute($value)
    {
        $this->attributes['hrdf_claim_id'] = $value ? Str::upper($value) : $value;
    }

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

    public function completedBy(): BelongsTo  // Changed from 'approvedBy'
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function hrdfClaim()
    {
        return $this->belongsTo(HrdfClaim::class, 'hrdf_grant_id', 'hrdf_grant_id');
    }

    /**
     * Get formatted handover ID attribute
     * Format: HRDF_YYXXXX (where YY is year and XXXX is padded ID)
     *
     * @return string
     */
    public function getFormattedHandoverIdAttribute(): string
    {
        $year = $this->created_at ? $this->created_at->format('y') : now()->format('y');
        $maxNum = 9999; // Maximum 4-digit number
        $num = $this->id % $maxNum == 0 ? $maxNum : ($this->id % $maxNum);

        return sprintf('HRDF_%02d%04d', $year, $num);
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

        return sprintf('HRDF_%02d%04d', $year, $num);
    }
}
