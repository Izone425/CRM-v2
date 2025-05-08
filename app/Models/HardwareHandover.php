<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HardwareHandover extends Model
{
    use HasFactory;

    protected $table = 'hardware_handovers';

    protected $fillable = [
        'lead_id',
        'created_by',
        'status',
        'handover_pdf',

        // Section 1: Company Details
        'company_name',
        'industry',
        'headcount',
        'country',
        'state',
        'salesperson',

        // Section 2: Superadmin Details
        'pic_name',
        'pic_phone',
        'email',
        'password',

        // Section 3: Invoice Details
        'company_name_invoice',
        'company_address',
        'salesperson_invoice',
        'pic_name_invoice',
        'pic_email_invoice',
        'pic_phone_invoice',

        // Section 4: Implementation PICs
        'implementation_pics',

        // Section 5: Module Subscription (now as a single JSON field)
        'modules',

        // Section 6: Other Details
        'customization_details',
        'enhancement_details',
        'special_remark',
        'device_integration',
        'existing_hr_system',
        'experience_implementing_hr_system',
        'vip_package',
        'fingertec_device',

        // Section 7: Onsite Package
        'onsite_kick_off_meeting',
        'onsite_webinar_training',
        'onsite_briefing',

        // Section 8: Payment Terms
        'payment_term', // Options: full_payment, payment_via_ibgc, payment_via_term

        // Section 9: Proforma Invoices
        'proforma_invoice_number',

        // Section 10: Attachments
        'confirmation_order_file',
        'payment_slip_file',

        // Section 11: Installation Details
        'installation_special_remark',
        'installation_media',

        'remark',
    ];

    protected $casts = [
        'installation_media' => 'array',
        'confirmation_order_file' => 'array',
        'payment_slip_file' => 'array',
        'implementation_pics' => 'array',
        'modules' => 'array',
        'onsite_kick_off_meeting' => 'boolean',
        'onsite_webinar_training' => 'boolean',
        'onsite_briefing' => 'boolean',
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
