<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DevicePurchaseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'month',
        'model',
        'qty',
        'england',
        'america',
        'europe',
        'australia',
        'sn_no_from',
        'sn_no_to',
        'po_no',
        'order_no',
        'balance_not_order',
        'rfid_card_foc',
        'languages',
        'features',
        'status',
    ];
}
