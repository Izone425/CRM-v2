<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketPriority extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'ticket_priorities';

    protected $fillable = ['name', 'color', 'sort_order', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
