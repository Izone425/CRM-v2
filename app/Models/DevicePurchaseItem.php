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

    /**
     * Set languages attribute to uppercase
     */
    public function setLanguagesAttribute($value)
    {
        $this->attributes['languages'] = strtoupper($value);
    }

    /**
     * Set po_no attribute to uppercase
     */
    public function setPoNoAttribute($value)
    {
        $this->attributes['po_no'] = strtoupper($value);
    }

    /**
     * Set order_no attribute to uppercase
     */
    public function setOrderNoAttribute($value)
    {
        $this->attributes['order_no'] = strtoupper($value);
    }
}
