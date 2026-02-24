<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrAutoRenewal extends Model
{
    protected $table = 'hr_auto_renewals';

    protected $fillable = [
        'invoice_no',
        'company_name',
        'country',
        'next_billing_date',
        'status',
        'is_enabled',
        'software_handover_id',
        'handover_id',
    ];

    protected $casts = [
        'next_billing_date' => 'date',
        'is_enabled' => 'boolean',
    ];
}
