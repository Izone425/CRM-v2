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
        'lead_id',
        'kick_off_date',
        'buffer_license_start',
        'buffer_license_end',
        'paid_license_start',
        'paid_license_end',
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
    ];

    /**
     * Get the lead that this license certificate belongs to.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the creator of this license certificate.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this license certificate.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get the software handovers associated with this certificate.
     */
    public function softwareHandovers()
    {
        return $this->hasMany(SoftwareHandover::class, 'license_certificate_id');
    }
}
