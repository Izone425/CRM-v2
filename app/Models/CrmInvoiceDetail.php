<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmInvoiceDetail extends Model
{
    protected $connection = 'frontenddb';
    protected $table = 'crm_invoice_details';
    protected $primaryKey = 'f_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'f_invoice_no',
        'f_currency',
        'f_status',
        'f_auto_count_inv',
        'f_name',
        'f_company_id',
        'f_payer_id',
        'f_sales_amount',
    ];

    protected $casts = [
        'f_status' => 'integer',
        'f_sales_amount' => 'decimal:2',
    ];

    /**
     * Get the company (bill to company)
     */
    public function company()
    {
        return $this->belongsTo(CrmCustomer::class, 'f_company_id', 'company_id');
    }

    /**
     * Get the subscriber (payer)
     */
    public function subscriber()
    {
        return $this->belongsTo(CrmCustomer::class, 'f_payer_id', 'company_id');
    }

    /**
     * Scope to get distinct invoice numbers with filters
     */
    public function scopePendingInvoices($query)
    {
        return $query->select([
                'f_invoice_no',
                'f_currency',
                'f_status',
                'f_auto_count_inv',
                'f_id',
                'f_created_time',
                'f_company_id',
                'f_payer_id',
                'f_name',
                'f_sales_amount',
            ])
            ->whereIn('f_currency', ['MYR', 'USD'])
            ->where('f_status', 0)
            ->whereNull('f_auto_count_inv')
            ->where('f_id', '>', '0000040131')
            ->where('f_id', '!=', '0000042558')
            ->groupBy('f_invoice_no', 'f_currency', 'f_status', 'f_auto_count_inv', 'f_id', 'f_created_time', 'f_company_id', 'f_payer_id', 'f_name', 'f_sales_amount');
    }
}
