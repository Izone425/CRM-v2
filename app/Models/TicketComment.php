<?php
// filepath: /var/www/html/timetec-crm/app/Models/TicketComment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketComment extends Model
{
    use HasFactory;

    protected $connection = 'ticketingsystem_live';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'comment',
        'is_edited',
        'edited_at',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_edited' => 'boolean',
        'edited_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a fresh timestamp for the model.
     * Automatically adjusts timestamps to UTC-8 (Malaysia time)
     */
    public function freshTimestamp(): \Illuminate\Support\Carbon
    {
        return now()->subHours(8);
    }
}
