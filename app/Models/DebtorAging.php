<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DebtorAging extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'doc_key',
        'debtor_code',
        'company_name',
        'invoice_date',
        'invoice_number',
        'due_date',
        'aging_date',
        'exchange_rate',
        'currency_code',
        'total',
        'invoice_amount',
        'outstanding',
        'salesperson',
        'support',
        'created_at',
        'updated_at',
    ];
}
