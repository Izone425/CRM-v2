<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketRemote extends Model
{
    use HasFactory;

    protected $connection = 'ticketingsystem_live';
    protected $table = 'tickets';

    protected $fillable = [
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
        'product',
        'module',
        'module_id',
        'zoho_ticket_number',
        'priority',
        'priority_id',
        'zoho_ticket_id',
        'company_name',
        'description',
        'zoho_id',
        'requestor_id',
        'assignee_id',
        'created_date',
        'eta_release',
        'live_release',
        'device_type',
        'mobile_type',
        'browser_type',
        'device_id',
        'os_version',
        'app_version',
        'windows_os_version',
        'version_screenshot',
        'created_by',
    ];

    protected $casts = [
        'created_date' => 'date',
        'eta_release' => 'date',
        'live_release' => 'date',
        'passed_at' => 'datetime',
        'completion_date' => 'date',
        'isPassed' => 'integer', // ✅ Changed from boolean to integer
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class, 'ticket_id')->orderBy('created_at', 'desc');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'ticket_id')->orderBy('created_at', 'desc');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
