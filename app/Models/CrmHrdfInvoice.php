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
        'quotation_id',
        'debtor_code',
        'total_amount',
        'tt_invoice_number',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'total_amount' => 'decimal:2',
    ];

    public function quotation()
    {
        return $this->belongsTo(Quotation::class, 'quotation_id');
    }

    public function handover()
    {
        switch ($this->handover_type) {
            case 'SW':
                return $this->belongsTo(SoftwareHandover::class, 'handover_id', 'id');
            case 'HW':
                return $this->belongsTo(HardwareHandover::class, 'handover_id', 'id');
            case 'RW':
                return $this->belongsTo(RenewalHandover::class, 'handover_id', 'id');
            default:
                return $this->belongsTo(SoftwareHandover::class, 'handover_id', 'id');
        }
    }

    /**
     * ✅ ADD: Specific relationship methods for each handover type
     */
    public function softwareHandover()
    {
        return $this->belongsTo(SoftwareHandover::class, 'handover_id');
    }

    public function hardwareHandover()
    {
        return $this->belongsTo(HardwareHandover::class, 'handover_id');
    }

    public function renewalHandover()
    {
        return $this->belongsTo(RenewalHandover::class, 'handover_id');
    }

    /**
     * ✅ ADD: Get the actual handover record based on type
     */
    public function getActualHandoverAttribute()
    {
        switch ($this->handover_type) {
            case 'SW':
                return $this->softwareHandover;
            case 'HW':
                return $this->hardwareHandover;
            case 'RW':
                return $this->renewalHandover;
            default:
                return $this->softwareHandover;
        }
    }
}
