<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Str;

class BankDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'referral_name',
        'tin',
        'hp_number',
        'email',
        'referral_address',
        'postcode',
        'city',
        'state',
        'country',
        'referral_bank_name',
        'beneficiary_name',
        'bank_name',
        'bank_account_no',
    ];

    /**
     * Convert referral_name to uppercase when saving to database
     */
    protected function referralName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? Str::upper($value) : null,
        );
    }

    /**
     * Convert referral_address to uppercase when saving to database
     */
    protected function referralAddress(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? Str::upper($value) : null,
        );
    }

    /**
     * Convert city to uppercase when saving to database
     */
    protected function city(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? Str::upper($value) : null,
        );
    }

    /**
     * Convert state to uppercase when saving to database
     */
    protected function state(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? Str::upper($value) : null,
        );
    }

    /**
     * Convert country to uppercase when saving to database
     */
    protected function country(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? Str::upper($value) : null,
        );
    }

    /**
     * Convert referral_bank_name to uppercase when saving to database
     */
    protected function referralBankName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? Str::upper($value) : null,
        );
    }

    /**
     * Convert beneficiary_name to uppercase when saving to database
     */
    protected function beneficiaryName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? Str::upper($value) : null,
        );
    }

    /**
     * Convert bank_name to uppercase when saving to database
     */
    protected function bankName(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value,
            set: fn ($value) => $value ? Str::upper($value) : null,
        );
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class, 'lead_id', 'id');
    }
}
