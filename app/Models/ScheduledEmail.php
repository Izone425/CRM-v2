<?php

namespace App\Models;

use App\Enums\QuotationStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use App\Observers\QuotationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;

class ScheduledEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_data',
        'scheduled_date',
        'status',
        'sent_at',
    ];
}
