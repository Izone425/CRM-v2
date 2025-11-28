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
        'request_type',
        'duplicate_info',
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
}
