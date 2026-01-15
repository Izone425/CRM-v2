<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPortalInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'finance_invoice_id',
        'reseller_name',
        'subscriber_name',
        'autocount_invoice',
        'tt_invoice',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function financeInvoice()
    {
        return $this->belongsTo(FinanceInvoice::class);
    }
}
