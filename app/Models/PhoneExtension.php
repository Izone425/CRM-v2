<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhoneExtension extends Model
{
    protected $fillable = [
        'extension',
        'name',
        'user_id',
        'is_support_staff',
        'is_active',
    ];

    protected $casts = [
        'is_support_staff' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
