<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_code',
        'allowed_users',
    ];

    protected $casts = [
        'allowed_users' => 'array',
    ];

    public function lead(): HasMany
    {
        return $this->hasMany(Lead::class, 'lead_code', 'lead_code');
    }

    public function getAllowedUsersAttribute($value)
    {
        // Return the raw JSON value if needed
        return $value;
    }

    public function allowedUsers()
    {
        // If allowed_users is null, return empty collection
        if (!$this->allowed_users) {
            return collect();
        }

        // Get user models based on IDs in allowed_users
        return User::whereIn('id', $this->allowed_users)->get();
    }
}
