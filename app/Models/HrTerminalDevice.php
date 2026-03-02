<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HrTerminalDevice extends Model
{
    use HasFactory;

    protected $table = 'hr_terminal_devices';

    protected $fillable = [
        'software_handover_id',
        'handover_id',
        'company_name',
        'invoice_no',
        'model',
        'serial_no',
        'backend_device_id',
        'status',
    ];
}
