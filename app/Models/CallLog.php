<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CallLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'caller_number',
        'receiver_number',
        'call_duration',
        'call_status',
        'call_type',
        'started_at',
        'call_recording_url',
        'notes',
        'lead_id',   // Optional - for linking to a lead
        'user_id',   // Optional - for linking to a user
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'call_duration' => 'integer',
    ];

    // Relationships
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
