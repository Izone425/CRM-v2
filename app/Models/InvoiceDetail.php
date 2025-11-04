<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'dtl_key',
        'doc_key',
        'item_code',
        'description',
        'quantity',
        'unit_price',
        'local_sub_total',
        'tax_amount',
        'tax_rate',
        'serial_number_list',
        'desc2'
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'doc_key', 'doc_key');
    }
}
