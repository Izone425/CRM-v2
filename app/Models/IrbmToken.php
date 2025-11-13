<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IrbmToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'production_mode',
        'company_name',
        'client_id',
        'client_secret',
        'client_secret2',
        'token',
    ];

    protected $casts = [
        'production_mode' => 'boolean',
    ];
}
