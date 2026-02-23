<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrSalesInvoice extends Model
{
    use HasFactory;

    protected $table = 'hr_sales_invoices';

    protected $fillable = [
        'software_handover_id',
        'handover_id',
        'invoice_no',
        'invoice_date',
        'company_name',
        'country',
        'reseller',
        'reseller_software_handover_id',
        'reseller_handover_id',
        'sales_amount',
        'currency',
        'commission',
        'pi_no',
        'invoice_amount',
        'line_items',
        'payment_method',
        'auto_renewal',
        'created_by_name',
        'status',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'sales_amount' => 'decimal:2',
        'commission' => 'decimal:2',
        'invoice_amount' => 'decimal:2',
        'line_items' => 'array',
    ];
}
