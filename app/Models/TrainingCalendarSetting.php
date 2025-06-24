<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingCalendarSetting extends Model
{
    protected $fillable = [
        'date',
        'status',
        'capacity',
        'created_by',
        'updated_by'
    ];

    public function bookings(): HasMany
    {
        return $this->hasMany(TrainingBooking::class, 'training_date', 'date');
    }

    public function availableSlots(): int
    {
        $bookedSlots = $this->bookings()->confirmed()->sum('pax_count');
        return max(0, $this->capacity - $bookedSlots);
    }
}
