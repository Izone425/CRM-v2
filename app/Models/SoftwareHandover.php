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

        // // Section 5: Module Subscription
        // 'attendance_headcount',
        // 'attendance_subscription_months',
        // 'attendance_purchase_type',
        // 'leave_headcount',
        // 'leave_subscription_months',
        // 'leave_purchase_type',
        // 'claim_headcount',
        // 'claim_subscription_months',
        // 'claim_purchase_type',
        // 'payroll_headcount',
        // 'payroll_subscription_months',
        // 'payroll_purchase_type',
        // 'appraisal_headcount',
        // 'appraisal_subscription_months',
        // 'appraisal_purchase_type',
        // 'recruitment_headcount',
        // 'recruitment_subscription_months',
        // 'recruitment_purchase_type',
        // 'power_bi_headcount',
        // 'power_bi_subscription_months',
        // 'power_bi_purchase_type',

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

        'remark',
    ];

    protected $casts = [
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
