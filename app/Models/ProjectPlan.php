<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class ProjectPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'sw_id',
        'project_task_id',
        'plan_start_date',
        'plan_end_date',
        'plan_duration',
        'actual_start_date',
        'actual_end_date',
        'actual_duration',
        'status',
        'notes',
    ];

    protected $casts = [
        'plan_duration' => 'integer',
        'actual_duration' => 'integer',
        'plan_start_date' => 'date',
        'plan_end_date' => 'date',
        'actual_start_date' => 'date',
        'actual_end_date' => 'date',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function softwareHandover(): BelongsTo
    {
        return $this->belongsTo(SoftwareHandover::class, 'sw_id');
    }

    public function projectTask(): BelongsTo
    {
        return $this->belongsTo(ProjectTask::class);
    }

    // Calculate plan duration when dates are set
    public function calculatePlanDuration(): void
    {
        if ($this->plan_start_date && $this->plan_end_date) {
            $this->plan_duration = $this->plan_start_date->diffInDays($this->plan_end_date) + 1;
            $this->save();
        }
    }

    // Calculate actual duration when dates are set
    public function calculateActualDuration(): void
    {
        if ($this->actual_start_date && $this->actual_end_date) {
            $this->actual_duration = $this->actual_start_date->diffInDays($this->actual_end_date) + 1;
            $this->save();
        }
    }

    // Auto-update status based on actual dates
    public function updateStatusBasedOnDates(): void
    {
        if ($this->actual_end_date) {
            $this->status = 'completed';
        } elseif ($this->actual_start_date) {
            $this->status = 'in_progress';
        } else {
            $this->status = 'pending';
        }
        $this->save();
    }

    // Get percentage from the related ProjectTask
    public function getPercentageAttribute(): int
    {
        return $this->projectTask ? $this->projectTask->task_percentage : 0;
    }

    // Get module percentage
    public function getModulePercentageAttribute(): int
    {
        return $this->projectTask ? $this->projectTask->module_percentage : 0;
    }
}
