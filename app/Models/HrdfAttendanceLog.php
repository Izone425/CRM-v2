<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HrdfAttendanceLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'training_date_1',
        'training_date_2',
        'training_date_3',
        'submitted_by',
        'salesperson_id',
        'grant_id',
        'document_paths', // ✅ Single column for all 4 files
        'status',
        'completed_at',
    ];

    protected $casts = [
        'training_date_1' => 'date',
        'training_date_2' => 'date',
        'training_date_3' => 'date',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'document_paths' => 'array', // ✅ Auto decode JSON to array
    ];

    public function submittedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function getTrainingDatesAttribute(): string
    {
        $dates = array_filter([
            $this->training_date_1?->format('d/m/Y'),
            $this->training_date_2?->format('d/m/Y'),
            $this->training_date_3?->format('d/m/Y'),
        ]);

        return implode(', ', $dates);
    }

    public function getFormattedLogIdAttribute(): string
    {
        $year = $this->created_at ? $this->created_at->format('y') : now()->format('y');
        $maxNum = 9999;
        $num = $this->id % $maxNum == 0 ? $maxNum : ($this->id % $maxNum);

        return sprintf('LOG_%02d%04d', $year, $num);
    }

    // ✅ Helper to get individual document paths
    public function getJd14FormPathAttribute()
    {
        return $this->document_paths[0] ?? null;
    }

    public function getAttendanceDay1PathAttribute()
    {
        return $this->document_paths[1] ?? null;
    }

    public function getAttendanceDay2PathAttribute()
    {
        return $this->document_paths[2] ?? null;
    }

    public function getAttendanceDay3PathAttribute()
    {
        return $this->document_paths[3] ?? null;
    }
}
