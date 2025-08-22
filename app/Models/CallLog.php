<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'caller_number',
        'caller_name',
        'receiver_number',
        'call_duration',
        'call_status',
        'call_type',
        'started_at',
        'end_at',
        'question',
        'task_status',
        'tier1_category_id',   // New field for Tier 1
        'tier2_category_id',   // New field for Tier 2
        'tier3_category_id',   // New field for Tier 3
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'end_at' => 'datetime',
        'call_duration' => 'integer',
    ];

    // Define relationships for each tier
    public function tier1Category(): BelongsTo
    {
        return $this->belongsTo(CallCategory::class, 'tier1_category_id');
    }

    public function tier2Category(): BelongsTo
    {
        return $this->belongsTo(CallCategory::class, 'tier2_category_id');
    }

    public function tier3Category(): BelongsTo
    {
        return $this->belongsTo(CallCategory::class, 'tier3_category_id');
    }

    // Keep the old relationship for backward compatibility
    public function category(): BelongsTo
    {
        return $this->belongsTo(CallCategory::class, 'category_id');
    }
}
