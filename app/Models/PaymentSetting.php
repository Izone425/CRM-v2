<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        // Website Information
        'website_name',
        'website_url',
        'admin_email',
        'disallow_public_email',
        'currency_order_page',
        'disallow_same_ip_signup',

        // Payment Gateway
        'paypal_url',
        'paypal_email',
        'paypal_enable',

        // Invoice Information
        'invoice_title',
        'invoice_company_name',
        'invoice_company_tel',
        'invoice_fax_no',
        'invoice_company_email',
        'invoice_company_address',
        'invoice_postcode',
        'invoice_city',
        'invoice_state',
        'invoice_country',
        'invoice_company_logo',
        'include_bank_details',
        'bank_name',
        'bank_account_no',
        'bank_beneficiary_name',
        'bank_swift_code',

        // Commission Settings
        'distributor_commission_rate',
        'dealer_commission_rate',

        // Audit fields
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'disallow_same_ip_signup' => 'boolean',
        'paypal_enable' => 'boolean',
        'include_bank_details' => 'boolean',
        'distributor_commission_rate' => 'integer',
        'dealer_commission_rate' => 'integer',
    ];

    /**
     * Get the user who created this setting
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated this setting
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Check if PayPal is enabled
     */
    public function isPayPalEnabled(): bool
    {
        return (bool) $this->paypal_enable;
    }

    /**
     * Check if bank details should be included in invoice
     */
    public function shouldIncludeBankDetails(): bool
    {
        return (bool) $this->include_bank_details;
    }
}
