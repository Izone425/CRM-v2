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
    ];

    protected $casts = [
        'f_status' => 'integer',
    ];

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
            ])
            ->whereIn('f_currency', ['MYR', 'USD'])
            ->where('f_status', 0)
            ->whereNull('f_auto_count_inv')
            ->where('f_id', '>', '0000040131')
            ->where('f_id', '!=', '0000042558')
            ->groupBy('f_invoice_no', 'f_currency', 'f_status', 'f_auto_count_inv', 'f_id', 'f_created_time');
    }
}
