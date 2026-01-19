<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class ResellerV2 extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $guard = 'reseller';

    protected $table = 'reseller_v2';

    protected $fillable = [
        'name',
        'email',
        'password',
        'plain_password',
        'company_name',
        'phone',
        'status',
        'email_verified_at',
        'last_login_at',
        'commission_rate',
        'territory',
        'contact_person',
        'address',
        'city',
        'state',
        'country',
        'postal_code',
        'ssm_number',
        'tax_identification_number',
        'sst_category',
        'reseller_id',
        'debtor_code',
    ];

    protected $hidden = [
        'password',
        'plain_password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'commission_rate' => 'decimal:2',
    ];

    /**
     * Get the leads associated with the reseller
     */
    public function leads()
    {
        return $this->hasMany(Lead::class, 'reseller_v2_id');
    }

    /**
     * Get the bound reseller from admin portal
     */
    public function reseller()
    {
        return $this->belongsTo(Reseller::class, 'reseller_id');
    }
}
