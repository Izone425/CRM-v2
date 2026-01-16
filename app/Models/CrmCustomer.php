<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmCustomer extends Model
{
    protected $connection = 'frontenddb';
    protected $table = 'crm_customer';
    protected $primaryKey = 'company_id';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'f_company_name',
    ];

    /**
     * Get invoice details where this customer is the company
     */
    public function invoicesAsCompany()
    {
        return $this->hasMany(CrmInvoiceDetail::class, 'f_company_id', 'company_id');
    }

    /**
     * Get invoice details where this customer is the payer
     */
    public function invoicesAsSubscriber()
    {
        return $this->hasMany(CrmInvoiceDetail::class, 'f_payer_id', 'company_id');
    }
}
