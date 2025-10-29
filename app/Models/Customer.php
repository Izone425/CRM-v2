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
        'plain_password',
        'company_name',
        'sw_id',
        'phone',
        'activation_token',
        'token_expires_at',
        'status',
        'email_verified_at',
        'last_login_at',
        'lead_id',
        'original_email',
        'able_set_meeting',
        'tutorial_completed',
        'tutorial_step',
    ];

    protected $hidden = [
        'password',
        'plain_password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Get the lead associated with the customer
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the software handover through lead
     */
    public function softwareHandover()
    {
        return $this->hasOneThrough(SoftwareHandover::class, Lead::class, 'id', 'lead_id', 'lead_id', 'id');
    }
}
