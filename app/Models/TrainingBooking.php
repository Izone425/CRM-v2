<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingBooking extends Model
{
    use HasFactory;

    protected $fillable = [
        'handover_id',
        'training_session_id',
        'lead_id',
        'training_type',
        'training_category',
        'pic_name',
        'pic_email',
        'pic_phone',
        'status',
        'submitted_by',
        'submitted_at',
        'hrdf_application_status'
    ];

    protected $casts = [
        'submitted_at' => 'datetime'
    ];

    // Relationships
    public function trainingSession(): BelongsTo
    {
        return $this->belongsTo(TrainingSession::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function attendees(): HasMany
    {
        return $this->hasMany(TrainingAttendee::class);
    }

    public function activeAttendees(): HasMany
    {
        return $this->hasMany(TrainingAttendee::class)->active();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'CANCELLED');
    }

    public function scopeHrdf($query)
    {
        return $query->where('training_type', 'HRDF');
    }

    public function scopeWebinar($query)
    {
        return $query->where('training_type', 'WEBINAR');
    }

    // Accessors
    public function getFormattedHandoverIdAttribute()
    {
        return $this->handover_id;
    }

    public function getCompanyNameAttribute()
    {
        return $this->lead->companyDetail->company_name ?? 'Unknown Company';
    }

    public function getStatusBadgeColorAttribute()
    {
        return match($this->status) {
            'ACTIVE', 'BOOKED' => 'success',
            'CANCELLED' => 'danger',
            'APPLY' => 'warning',
            default => 'secondary'
        };
    }

    public function getTrainingTypeLabelAttribute()
    {
        return match($this->training_type) {
            'HRDF' => 'Online HRDF Training',
            'WEBINAR' => 'Online Webinar Training',
            default => $this->training_type
        };
    }

    public function getParticipantCountAttribute()
    {
        return $this->activeAttendees()->count();
    }

    public function getTotalRegisteredAttribute()
    {
        return $this->attendees()->registered()->count();
    }
}
