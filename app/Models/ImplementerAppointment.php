<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ImplementerAppointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'lead_id',
        'type',
        'appointment_type',
        'date',
        'start_time',
        'end_time',
        'implementer',
        'session',
        'remarks',
        'title',
        'required_attendees',
        'status',
        'request_status',
        'selected_year',
        'selected_week',
        'event_id',
        'meeting_link',
        'causer_id',
        'software_handover_id',
        'cancelled_by',
        'cancelled_at',
        'implementer_remark',
    ];

    protected $casts = [
        'date' => 'date',
        'implementer_assigned_date' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'required_attendees' => 'array',
        'optional_attendees' => 'array',
    ];

    protected $appends = [
        'formatted_date',
        'formatted_time_range',
    ];

    /**
     * Convert remarks to uppercase when setting
     */
    public function setRemarksAttribute($value)
    {
        $this->attributes['remarks'] = $value ? strtoupper($value) : null;
    }

    /**
     * Convert title to uppercase when setting
     */
    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value ? strtoupper($value) : null;
    }

    /**
     * Get the formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->date ? Carbon::parse($this->date)->format('d M Y') : null;
    }

    /**
     * Get the formatted time range
     */
    public function getFormattedTimeRangeAttribute()
    {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }

        $startTime = Carbon::parse($this->start_time)->format('h:i A');
        $endTime = Carbon::parse($this->end_time)->format('h:i A');

        return "{$startTime} - {$endTime}";
    }

    // /**
    //  * Get activity log options
    //  */
    // public function getActivitylogOptions(): LogOptions
    // {
    //     return LogOptions::defaults()
    //         ->logOnly([
    //             'id',
    //             'lead_id',
    //             'type',
    //             'appointment_type',
    //             'date',
    //             'start_time',
    //             'end_time',
    //             'implementer',
    //             'session',
    //             'remarks',
    //             'status'
    //         ])
    //         ->logOnlyDirty()
    //         ->dontSubmitEmptyLogs();
    // }

    /**
     * Get the lead that owns the appointment
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function softwareHandover()
    {
        return $this->belongsTo(SoftwareHandover::class, 'software_handover_id');
    }

    /**
     * Get the user who created the appointment
     */
    public function causer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    /**
     * Get the user who cancelled the appointment
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Get the user who completed the appointment
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Scope a query to only include active appointments.
     */
    public function scopeActive($query)
    {
        return $query->where('status', '!=', 'Cancelled');
    }

    /**
     * Scope a query to only include upcoming appointments.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', Carbon::today()->toDateString())
                    ->where('status', '!=', 'Completed')
                    ->where('status', '!=', 'Cancelled');
    }

    /**
     * Scope a query to only include completed appointments.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'Completed');
    }

    /**
     * Scope a query to only include appointments for a specific implementer.
     */
    public function scopeForImplementer($query, $implementerName)
    {
        return $query->where('implementer', $implementerName);
    }

    /**
     * Mark the appointment as completed
     */
    public function markAsCompleted($userId = null)
    {
        $this->update([
            'status' => 'Completed',
            'completed_at' => now(),
            'completed_by' => $userId ?? auth()->id(),
        ]);

        return $this;
    }

    /**
     * Mark the appointment as cancelled
     */
    public function markAsCancelled($userId = null)
    {
        $this->update([
            'status' => 'Cancelled',
            'cancelled_at' => now(),
            'cancelled_by' => $userId ?? auth()->id(),
        ]);

        return $this;
    }

    /**
     * Check if the appointment is upcoming
     */
    public function isUpcoming()
    {
        return Carbon::parse($this->date) >= Carbon::today() &&
               $this->status !== 'Completed' &&
               $this->status !== 'Cancelled';
    }
}
