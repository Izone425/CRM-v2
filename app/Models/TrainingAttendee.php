<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAttendee extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_booking_id',
        'name',
        'email',
        'phone',
        'ic_number',
        'position',
        'department',
        'attendance_status',
        'registered_at'
    ];

    protected $casts = [
        'registered_at' => 'datetime'
    ];

    // Relationships
    public function trainingBooking(): BelongsTo
    {
        return $this->belongsTo(TrainingBooking::class);
    }

    // Scopes
    public function scopeRegistered($query)
    {
        return $query->where('attendance_status', 'REGISTERED');
    }

    public function scopeAttended($query)
    {
        return $query->where('attendance_status', 'ATTENDED');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('attendance_status', ['REGISTERED', 'ATTENDED']);
    }

    // Accessors
    public function getStatusBadgeColorAttribute()
    {
        return match($this->attendance_status) {
            'REGISTERED' => 'info',
            'ATTENDED' => 'success',
            'NOT_ATTENDED' => 'warning',
            'CANCELLED' => 'danger',
            default => 'secondary'
        };
    }

    public function getStatusLabelAttribute()
    {
        return match($this->attendance_status) {
            'REGISTERED' => 'Registered',
            'ATTENDED' => 'Attended',
            'NOT_ATTENDED' => 'Not Attended',
            'CANCELLED' => 'Cancelled',
            default => $this->attendance_status
        };
    }
}
