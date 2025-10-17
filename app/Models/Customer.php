<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guard = 'customer';

    protected $fillable = [
        'name',
        'email',
        'password',
        'company_name',
        'phone',
        'activation_token',
        'token_expires_at',
        'status',
        'email_verified_at',
        'last_login_at',
        'lead_id'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];
}
