<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoftwareHandover extends Model
{
    use HasFactory;

    protected $table = 'software_handovers';

    protected $fillable = [
        'lead_id',
        'created_by',
        'status',
        'handover_pdf',

        // Section 1: Company Details
        'company_name',
        'headcount',
        'pic_name',
        'pic_phone',
        'salesperson',

        // Section 4: Implementation PICs
        'implementation_pics',

        // Section 5: Module Subscription (now as a single JSON field)
        'remarks',

        // Section 6: Other Details
        'training_type',

        // Section 7: Onsite Package
        'onsite_kick_off_meeting',
        'onsite_webinar_training',
        'onsite_briefing',

        // Section 9: Proforma Invoices
        'proforma_invoice_product',
        'proforma_invoice_hrdf',

        // Section 10: Attachments
        'confirmation_order_file',
        'payment_slip_file',
        'hrdf_grant_file',
    ];

    protected $casts = [
        'confirmation_order_file' => 'array',
        'payment_slip_file' => 'array',
        'hrdf_grant_file' => 'array',
        'implementation_pics' => 'array',
        'remarks' => 'array',
    ];

    /**
     * Get the purchase type options
     *
     * @return array
     */
    public static function getPurchaseTypeOptions(): array
    {
        return ['Purchase', 'Free'];
    }

    /**
     * Get the payment term options
     *
     * @return array
     */
    public static function getPaymentTermOptions(): array
    {
        return ['full_payment', 'payment_via_ibgc', 'payment_via_term'];
    }

    /**
     * Get the lead that owns this software handover
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
