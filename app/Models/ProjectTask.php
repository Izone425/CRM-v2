<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'module',
        'module_order',
        'phase_name',
        'task_name',
        'order',
        'percentage',
        'default_duration',
        'description',
    ];

    protected $casts = [
        'module_order' => 'integer',
        'order' => 'integer',
        'percentage' => 'integer',
        'default_duration' => 'integer',
    ];

    public function projectPlans(): HasMany
    {
        return $this->hasMany(ProjectPlan::class);
    }

    public static function getModules(): array
    {
        return [
            'phase_1' => 'Phase 1: Implementation',
            'phase_2' => 'Phase 2: Configuration',
            'phase_3' => 'Phase 3: Training',
            'phase_4' => 'Phase 4: Go-Live',
            'phase_5' => 'Phase 5: Support',
        ];
    }

    public static function getModuleOrder(string $module): int
    {
        $orders = [
            'phase_1' => 1,
            'phase_2' => 2,
            'phase_3' => 3,
            'phase_4' => 4,
            'phase_5' => 5,
        ];

        return $orders[$module] ?? 99;
    }
}
