<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LicenseCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'software_handover_id',
        'buffer_license_set_id',
        'buffer_license_data',
        'kick_off_date',
        'buffer_license_start',
        'buffer_license_end',
        'buffer_months',
        'paid_license_start',
        'paid_license_end',
        'paid_months',
        'next_renewal_date',
        'license_years',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'kick_off_date' => 'datetime',
        'buffer_license_start' => 'datetime',
        'buffer_license_end' => 'datetime',
        'paid_license_start' => 'datetime',
        'paid_license_end' => 'datetime',
        'next_renewal_date' => 'datetime',
        'buffer_license_data' => 'array', // ✅ Cast to array
    ];

    // ...existing relationships...
}
