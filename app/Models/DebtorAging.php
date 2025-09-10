<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtorAging extends Model
{
    protected $table = 'debtor_agings';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'debtor_code',
        'company_name',
        'invoice_number',
        'invoice_date',
        'invoice_amount',
        'currency_code',
        'exchange_rate',
        'outstanding',
        'salesperson',
        'payment_status',
        'invoice_type'
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'invoice_amount' => 'float',
        'outstanding' => 'float',
        'exchange_rate' => 'float'
    ];
}
