<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralLedgerCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'cpm_code',
        'carpark',
        'contract_type',
        'establishment_fee',
        'late_payment',
        'reserved_number_signage_fee',
        'clamping_fee',
        'season_parking_fee',
        'casual_parking_fee',
        'deposit_fee',
        'pass_card_replacement_fee',
        'tax_account_number',
        'forfeited_deposit_account',
    ];
}
