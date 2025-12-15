<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'attention_to',
        'status',
        'remark',
        'attachments',
        'completed_by',
        'completed_at',
        'duration_minutes',
        'admin_remark',
        'admin_attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'admin_attachments' => 'array',
        'completed_at' => 'datetime',
    ];

    // ✅ Remove the boot method and generateTicketId method

    /**
     * Get the formatted ticket ID
     */
    public function getFormattedTicketIdAttribute(): string
    {
        $year = $this->created_at ? $this->created_at->format('y') : date('y');
        return "T-AD-{$year}" . str_pad($this->id, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get the ticket display ID (for use in tables)
     */
    public function getTicketDisplayIdAttribute(): string
    {
        return $this->formatted_ticket_id;
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attentionTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'attention_to');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'in_progress' => 'info',
            'completed' => 'success',
            default => 'gray',
        };
    }

    public function calculateDuration(): int
    {
        if ($this->completed_at && $this->created_at) {
            return $this->created_at->diffInMinutes($this->completed_at);
        }
        return 0;
    }

    public function markCompleted(User $user, string $adminRemark = null, array $adminAttachments = []): void
    {
        $this->update([
            'status' => 'completed',
            'completed_by' => $user->id,
            'completed_at' => now(),
            'duration_minutes' => $this->calculateDuration(),
            'admin_remark' => $adminRemark,
            'admin_attachments' => $adminAttachments,
        ]);
    }

    /**
     * Get formatted duration for display
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_minutes) {
            return 'N/A';
        }

        $hours = floor($this->duration_minutes / 60);
        $minutes = $this->duration_minutes % 60;

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }
}
