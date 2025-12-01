<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductHasModule extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'product_has_modules';

    protected $fillable = [
        'product_id',
        'module_id',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function module()
    {
        return $this->belongsTo(TicketModule::class, 'module_id');
    }
}
