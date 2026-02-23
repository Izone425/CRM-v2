<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrSalesInvoiceItem extends Model
{
    protected $table = 'hr_sales_invoice_items';

    protected $fillable = [
        'hr_sales_invoice_id',
        'invoice_no',
        'invoice_date',
        'company_name',
        'invoice_amount',
        'currency',
        'software_handover_id',
        'handover_id',
        'created_by_name',
        'status',
        'license_type',
        'user_limit',
        'total_user',
        'unit_price',
        'month',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'invoice_amount' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function salesInvoice()
    {
        return $this->belongsTo(HrSalesInvoice::class, 'hr_sales_invoice_id');
    }
}
