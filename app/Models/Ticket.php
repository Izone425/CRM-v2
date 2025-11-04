<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'product',
        'module',
        'device_type',
        'priority',
        'company_name',
        'zoho_ticket_number',
        'title',
        'mobile_type',
        'browser_type',
        'version_screenshot',
        'device_id',
        'os_version',
        'app_version',
        'windows_os_version',
        'description',
        'status',
        'assigned_to',
        'reported_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
