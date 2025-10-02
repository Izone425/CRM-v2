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
        'invoice_file', // Add this field
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'completed_at' => 'datetime',
        'rejected_at' => 'datetime',
        'proforma_invoice_product' => 'array',
        'proforma_invoice_hrdf' => 'array',
        'payment_slip_file' => 'array',
        'confirmation_order_file' => 'array',
        'invoice_file' => 'array', // Add this cast
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
}
