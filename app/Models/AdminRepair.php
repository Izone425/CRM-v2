<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminRepair extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'lead_id',
        'handover_pdf',
        'pic_name',
        'pic_phone',
        'pic_email',
        'device_model',
        'device_serial',
        'devices',
        'remarks',
        'repair_remark',
        'spare_parts',
        'attachments',
        'invoice_date',
        'quotation_product',
        'quotation_hrdf',
        'new_attachment_file',
        'invoice_file',
        'sales_order_file',
        'payment_slip_file',
        'video_files',
        'zoho_ticket',
        'address',
        'status',
        'devices_warranty',
        'assigned_to',
        'pending_confirmation_date',
        'submitted_at',
        'created_by',
        'updated_by',
        'completed_at',
        'completed_date',
        'completed_by',
        'spare_parts_unused',
        'onsite_repair_remark',
        'delivery_order_files',
        'repair_form_files',
        'repair_image_files',
    ];

    protected $casts = [
        'remarks' => 'array',
        'repair_remark' => 'array',
        'attachments' => 'array',
        'video_files' => 'array',
        'devices' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'invoice_file' => 'array',
        'sales_order_file' => 'array',
        'payment_slip_file' => 'array',
        'spare_parts_used' => 'array',
        'spare_parts_unused' => 'array',
        'repair_completion_remarks' => 'array',
        'delivery_order_files' => 'array',
        'repair_form_files' => 'array',
        'repair_image_files' => 'array',
        'completed_date' => 'datetime',
    ];

    // Default status for new repair tickets
    protected $attributes = [
        'status' => 'Draft',
    ];

    public function setOnsiteRepairRemarkAttribute($value)
    {
        $this->attributes['onsite_repair_remark'] = $value ? strtoupper($value) : null;
    }

    public function lead()
    {
        return $this->belongsTo(Lead::class, 'id');
    }

    // Relationship to Company Details
    public function companyDetail()
    {
        return $this->belongsTo(CompanyDetail::class, 'company_id');
    }

    // Relationship to User (assigned technician)
    public function assignedTechnician()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function repairAppointment(): HasMany
    {
        return $this->hasMany(RepairAppointment::class, 'lead_id', 'id');
    }

    // Relationship to User (creator)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relationship to User (last updater)
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Add relationship to User (completer)
    public function completer()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function setPicNameAttribute($value)
    {
        $this->attributes['pic_name'] = strtoupper($value);
    }

    public function setAddressAttribute($value)
    {
        $this->attributes['address'] = $value ? strtoupper($value) : null;
    }
}
