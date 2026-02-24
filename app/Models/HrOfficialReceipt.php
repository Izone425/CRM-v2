<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrOfficialReceipt extends Model
{
    protected $table = 'hr_official_receipts';

    protected $fillable = [
        'or_no',
        'receipt_date',
        'company_name',
        'subscriber_name',
        'description',
        'currency',
        'amount',
        'status',
        'created_by',
        'invoice_no',
        'payment_method',
        'ref_no',
        'autocount_invoice_no',
        'software_handover_id',
        'handover_id',
    ];

    protected $casts = [
        'receipt_date' => 'date',
        'amount' => 'decimal:2',
    ];
}
