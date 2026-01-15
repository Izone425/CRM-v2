<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ticket extends Model
{
    use HasFactory;

    protected $connection = 'ticketingsystem_live';
    protected $table = 'tickets';

    protected $fillable = [
        'ticket_id',
        'title',
        'status',
        'closure_reason',
        'rejection_reason',
        'submission_id',
        'srs_links',
        'completion_date',
        'kiv_reason',
        'isPassed',
        'passed_at',
        'product_id',
        'module_id',
        'priority_id',
        'company_name',
        'description',
        'zoho_id',
        'requestor_id',
        'assignee_ids',
        'created_date',
        'eta_release',
        'live_release',
        'device_type',
        'mobile_type',
        'browser_type',
        'device_id',
        'os_version',
        'app_version',
        'windows_version',
        'version_screenshot',
        'is_internal',
        'reopen_reason',
    ];

    protected $casts = [
        'created_date' => 'date',
        'eta_release' => 'date',
        'live_release' => 'date',
        'passed_at' => 'datetime',
        'completion_date' => 'date',
        'isPassed' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'srs_links' => 'array',
        'assignee_ids' => 'array',
    ];

    public $incrementing = true;

    // ✅ Relationships
    public function product(): BelongsTo
    {
        return $this->belongsTo(TicketProduct::class, 'product_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(TicketModule::class, 'module_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'priority_id');
    }

    public function requestor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class)->orderBy('created_at', 'desc');
    }

    public function attachments()
    {
        return $this->hasMany(TicketAttachment::class)->orderBy('created_at', 'desc');
    }

    // ✅ Add logs relationship
    public function logs()
    {
        return $this->hasMany(TicketLog::class, 'ticket_id')->orderBy('created_at', 'desc');
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
