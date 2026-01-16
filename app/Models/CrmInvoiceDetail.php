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
        return $query->selectRaw('
                MIN(crm_invoice_details.f_id) as f_id,
                crm_invoice_details.f_invoice_no,
                crm_invoice_details.f_currency,
                MIN(crm_invoice_details.f_status) as f_status,
                MIN(crm_invoice_details.f_auto_count_inv) as f_auto_count_inv,
                MIN(crm_invoice_details.f_created_time) as f_created_time,
                MIN(crm_invoice_details.f_company_id) as f_company_id,
                MIN(crm_invoice_details.f_payer_id) as f_payer_id,
                MIN(crm_invoice_details.f_name) as f_name,
                MIN(crm_invoice_details.f_payment_method) as f_payment_method,
                SUM(crm_invoice_details.f_sales_amount) as f_sales_amount,
                MAX(company.f_company_name) as company_name,
                MAX(subscriber.f_company_name) as subscriber_name
            ')
            ->leftJoin('crm_customer as company', 'crm_invoice_details.f_company_id', '=', 'company.company_id')
            ->leftJoin('crm_customer as subscriber', 'crm_invoice_details.f_payer_id', '=', 'subscriber.company_id')
            ->whereIn('crm_invoice_details.f_currency', ['MYR', 'USD'])
            ->where('crm_invoice_details.f_status', 0)
            ->whereNull('crm_invoice_details.f_auto_count_inv')
            ->where('crm_invoice_details.f_id', '>', '0000040131')
            ->where('crm_invoice_details.f_id', '!=', '0000042558')
            ->groupBy('crm_invoice_details.f_invoice_no', 'crm_invoice_details.f_currency')
            ->orderByRaw('MIN(crm_invoice_details.f_created_time) ASC');
    }
}
