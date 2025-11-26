<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Request extends Model
{
    use HasFactory;

    protected $table = 'requests';

    protected $fillable = [
        'lead_id',
        'requested_by',
        'current_owner_id',
        'requested_owner_id',
        'reason',
        'status',
        'request_type', // ✅ New: 'change_owner' or 'bypass_duplicate'
        'duplicate_info', // ✅ New: Store duplicate information
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'duplicate_info' => 'array', // ✅ Auto cast JSON to array
    ];

    // Relationships
    public function lead() {
        return $this->belongsTo(\App\Models\Lead::class);
    }

    public function requestedBy() {
        return $this->belongsTo(\App\Models\User::class, 'requested_by');
    }

    public function currentOwner() {
        return $this->belongsTo(\App\Models\User::class, 'current_owner_id');
    }

    public function requestedOwner() {
        return $this->belongsTo(\App\Models\User::class, 'requested_owner_id');
    }

    public function reviewedBy() {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    // ✅ Scopes for filtering
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeBypassDuplicate($query)
    {
        return $query->where('request_type', 'bypass_duplicate');
    }

    public function scopeChangeOwner($query)
    {
        return $query->where('request_type', 'change_owner');
    }
}
