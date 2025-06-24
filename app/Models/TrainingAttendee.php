<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingAttendee extends Model
{
    protected $fillable = [
        'booking_id',
        'name',
        'email',
        'phone',
        'status'
    ];

    public $timestamps = false;

    public function booking(): BelongsTo
    {
        return $this->belongsTo(TrainingBooking::class);
    }
}
