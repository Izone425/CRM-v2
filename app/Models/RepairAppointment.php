<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class RepairAppointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'lead_id',
        'repair_handover_id',
        'type',
        'appointment_type',
        'date',
        'start_time',
        'end_time',
        'technician',
        'remarks',
        'cancelled_remarks',
        'title',
        'causer_id',
        'categories',
        'required_attendees',
        'optional_attendees',
        'location',
        'event_id',
        'details',
        'status',
        'device_model',
        'attachment',
    ];

    protected $casts = [
        'date' => 'date',
        'device_model' => 'array', // Add this line
        'attachment' => 'array',
    ];

    public function setRemarksAttribute($value)
    {
        $this->attributes['remarks'] = strtoupper($value);
    }

    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //         ->logOnly(['id',
    //             'lead_id',
    //             'type',
    //             'appointment_type',
    //             'date',
    //             'start_time',
    //             'end_time',
    //             'salesperson',
    //             'remarks',
    //             'title',
    //             'categories',
    //             'required_attendees',
    //             'optional_attendees',
    //             'location',
    //             'details',
    //             'status'
    //         ]);
    // }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id', 'id');
    }

    public function adminRepair(): BelongsTo
    {
        return $this->belongsTo(AdminRepair::class, 'repair_handover', 'id');
    }
}
