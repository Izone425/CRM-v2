<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DebtorAgingDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'dtl_key',
        'doc_key',
        'debtor_aging_id',
        'description',
        'local_tax',
        'local_net_amount',
        'knock_off_amount',
        'local_sub_total',
        'tax_rate',
    ];
}
