<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HardwareHandover extends Model
{
    use HasFactory;

    protected $table = 'hardware_handovers';

    protected $fillable = [
        // Database columns from schema
        'id',
        'lead_id',
        'created_by',
        'status',
        'handover_pdf',
        'courier',
        'courier_address',
        'installation_type',
        'pic_name',
        'pic_phone',
        'email',
        'installer',
        'reseller',
        'proforma_invoice_hrdf',
        'proforma_invoice_product',
        'confirmation_order_file',
        'hrdf_grant_file',
        'payment_slip_file',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        // Cast JSON-encoded fields as arrays
        'confirmation_order_file' => 'array',
        'hrdf_grant_file' => 'array',
        'payment_slip_file' => 'array',
        'proforma_invoice_product' => 'array',
        'proforma_invoice_hrdf' => 'array',
    ];

    /**
     * Get the lead that owns this hardware handover
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get the user who created this handover
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
