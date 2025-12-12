<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmHrdfInvoice extends Model
{
    protected $table = 'crm_hrdf_invoices';

    protected $fillable = [
        'invoice_no',
        'invoice_date',
        'company_name',
        'handover_type',
        'salesperson',
        'handover_id',
        'debtor_code',
        'total_amount',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Relationship to SoftwareHandover
     */
    public function handover()
    {
        return $this->belongsTo(SoftwareHandover::class, 'handover_id');
    }
}
