<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketLog extends Model
{
    use HasFactory;

    protected $connection = 'ticketingsystem_live';
    protected $table = 'ticket_logs';

    protected $fillable = [
        'ticket_id',
        'old_value',
        'new_value',
        'updated_by',
        'user_name',
        'user_role',
        'change_type',
        'source',
    ];

    protected $casts = [
        'ticket_id' => 'integer',
        'updated_by' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ✅ Relationships
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
