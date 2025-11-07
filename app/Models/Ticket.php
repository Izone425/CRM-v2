<?php
// filepath: /var/www/html/timeteccrm/app/Models/Ticket.php

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
        'windows_version',
        'version_screenshot',
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
}
