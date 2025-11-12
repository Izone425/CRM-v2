<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketModule extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'modules';

    protected $fillable = ['name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
