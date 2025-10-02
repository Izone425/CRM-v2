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
        'hrdf_grant_id',
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
}
