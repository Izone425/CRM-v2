<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminRenewalLogs extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'lead_id',
        'description',
        'subject_id',
        'causer_id',
        'remark',
        'follow_up_counter',
        'follow_up_date',
        'manual_follow_up_count',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function causer()
    {
        return $this->belongsTo(User::class, 'causer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
