<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrLicense extends Model
{
    use HasFactory;

    protected $table = 'hr_licenses';

    protected $fillable = [
        'software_handover_id',
        'handover_id',
        'type',
        'invoice_no',
        'auto_count_invoice_no',
        'company_name',
        'license_type',
        'unit',
        'user_limit',
        'total_user',
        'total_login',
        'month',
        'start_date',
        'end_date',
        'status',
        'auto_renewal',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'unit' => 'integer',
        'user_limit' => 'integer',
        'total_user' => 'integer',
        'total_login' => 'integer',
        'month' => 'integer',
    ];

    /**
     * Get the software handover associated with this license.
     */
    public function softwareHandover()
    {
        return $this->belongsTo(SoftwareHandover::class);
    }
}
