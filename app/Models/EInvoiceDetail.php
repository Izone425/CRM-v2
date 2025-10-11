<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EInvoiceDetail extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'e_invoice_details';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'lead_id',
        'company_name',
        'business_register_number',
        'tax_identification_number',
        'address_1',
        'address_2',
        'business_category',
        'postcode',
        'currency',
        'city',
        'business_type',
        'state',
        'msic_code',
        'country',
        'billing_category',
        'finance_person_name',
        'finance_person_email',
        'finance_person_contact',
        'finance_person_position',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'business_category' => 'string',
        'currency' => 'string',
        'business_type' => 'string',
        'billing_category' => 'string',
    ];

    /**
     * Get the lead that owns the e-invoice details.
     */
    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Get business category options
     */
    public static function getBusinessCategoryOptions(): array
    {
        return [
            'business' => 'Business',
            'government' => 'Government',
        ];
    }

    /**
     * Get currency options
     */
    public static function getCurrencyOptions(): array
    {
        return [
            'MYR' => 'MYR',
            'USD' => 'USD',
        ];
    }

    /**
     * Get business type options
     */
    public static function getBusinessTypeOptions(): array
    {
        return [
            'local_business' => 'Local Business',
            'foreign_business' => 'Foreign Business',
        ];
    }

    /**
     * Get billing category options
     */
    public static function getBillingCategoryOptions(): array
    {
        return [
            'billing_to_subscriber' => 'Billing to Subscriber',
            'billing_to_reseller' => 'Billing to Reseller',
        ];
    }

    /**
     * Get states from JSON file
     */
    public static function getStateOptions(): array
    {
        $filePath = storage_path('app/public/json/StateCodes.json');

        if (file_exists($filePath)) {
            $statesContent = file_get_contents($filePath);
            $states = json_decode($statesContent, true);

            return collect($states)->mapWithKeys(function ($state) {
                return [$state['Code'] => ucfirst(strtolower($state['State']))];
            })->toArray();
        }

        return [
            'SEL' => 'Selangor', // Fallback default
        ];
    }

    /**
     * Get countries from JSON file
     */
    public static function getCountryOptions(): array
    {
        $filePath = storage_path('app/public/json/CountryCodes.json');

        if (file_exists($filePath)) {
            $countriesContent = file_get_contents($filePath);
            $countries = json_decode($countriesContent, true);

            return collect($countries)->mapWithKeys(function ($country) {
                return [$country['Code'] => ucfirst(strtolower($country['Country']))];
            })->toArray();
        }

        return [
            'MYS' => 'Malaysia', // Fallback default
        ];
    }
}
