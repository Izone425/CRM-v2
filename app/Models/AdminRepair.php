<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminRepair extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'pic_name',
        'pic_phone',
        'pic_email',
        'device_model',
        'device_serial',
        'remarks',
        'attachments',
        'video_files',
        'zoho_ticket',
        'status',
        'assigned_to',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'remarks' => 'array',
        'attachments' => 'array',
        'video_files' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Default status for new repair tickets
    protected $attributes = [
        'status' => 'New',
    ];

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
}
