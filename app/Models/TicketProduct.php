<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketProduct extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'products';

    protected $fillable = [
        'name',
        'product_code',
        'company_db',
        'company_table',
        'company_name_column',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
