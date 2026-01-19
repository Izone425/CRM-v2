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
        'company_name',
        'website_url',
        'support_email',
        'support_phone',
        'company_address',
        'company_logo',

        // Payment Gateway
        'payment_gateway',
        'gateway_api_key',
        'gateway_secret_key',
        'gateway_merchant_id',
        'gateway_test_mode',
        'gateway_webhook_url',
        'gateway_settings',

        // Invoice Information
        'invoice_prefix',
        'invoice_next_number',
        'invoice_number_format',
        'invoice_due_days',
        'invoice_terms',
        'invoice_footer',
        'invoice_currency',
        'invoice_tax_rate',
        'invoice_tax_label',

        // Commission Settings
        'commission_type',
        'commission_rate',
        'reseller_commission_rate',
        'distributor_commission_rate',
        'referral_commission_rate',
        'commission_calculation',
        'commission_payout_days',
        'tier_based_commission',

        // Audit fields
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'gateway_test_mode' => 'boolean',
        'gateway_settings' => 'array',
        'tier_based_commission' => 'array',
        'invoice_tax_rate' => 'decimal:2',
        'commission_rate' => 'decimal:2',
        'reseller_commission_rate' => 'decimal:2',
        'distributor_commission_rate' => 'decimal:2',
        'referral_commission_rate' => 'decimal:2',
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
     * Generate next invoice number
     */
    public function generateInvoiceNumber(): string
    {
        $format = $this->invoice_number_format ?? 'INV-{YEAR}-{MONTH}-{NUMBER}';
        $number = str_pad($this->invoice_next_number, 5, '0', STR_PAD_LEFT);

        $invoiceNumber = str_replace(
            ['{YEAR}', '{MONTH}', '{NUMBER}', '{PREFIX}'],
            [date('Y'), date('m'), $number, $this->invoice_prefix],
            $format
        );

        // Increment the next number
        $this->increment('invoice_next_number');

        return $invoiceNumber;
    }

    /**
     * Get payment gateway display name
     */
    public function getGatewayNameAttribute(): string
    {
        return match($this->payment_gateway) {
            'stripe' => 'Stripe',
            'paypal' => 'PayPal',
            'billplz' => 'Billplz',
            'ipay88' => 'iPay88',
            'senangpay' => 'SenangPay',
            'molpay' => 'MOLPay',
            'manual' => 'Manual Payment',
            default => 'Unknown',
        };
    }
}
