<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingBooking extends Model
{
    protected $fillable = [
        'training_date',
        'pax_count',
        'status',
        'additional_notes',
        'created_by'
    ];

    public function attendees(): HasMany
    {
        return $this->hasMany(TrainingAttendee::class, 'booking_id');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }
}
