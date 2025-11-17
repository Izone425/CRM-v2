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
        'module_name',
        'module_order', // ✅ Added
        'module_percentage',
        'hr_version',
        'task_name',
        'task_percentage',
        'order',
        'is_active',
    ];

    protected $casts = [
        'module_percentage' => 'decimal:1',
        'task_percentage' => 'decimal:1',
        'module_order' => 'integer', // ✅ Added
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    // ✅ Add accessor for percentage (alias for task_percentage)
    public function getPercentageAttribute()
    {
        return $this->task_percentage;
    }

    public function projectPlans(): HasMany
    {
        return $this->hasMany(ProjectPlan::class);
    }

    // Get all active modules (by module_name)
    public static function getActiveModules(): array
    {
        return self::where('is_active', true)
            ->select('module_name', 'module_order')
            ->distinct()
            ->orderBy('module_order')
            ->orderBy('module_name')
            ->get()
            ->pluck('module_name', 'module_name')
            ->toArray();
    }

    // Get tasks for a specific module_name
    public static function getTasksForModuleName(string $moduleName): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('module_name', $moduleName)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();
    }

    // ✅ Extract phase number from module_name
    public static function extractPhaseNumber(string $moduleName): int
    {
        // Extract number from "Phase 1:", "Phase 2:", etc.
        if (preg_match('/Phase\s+(\d+)/i', $moduleName, $matches)) {
            return (int)$matches[1];
        }
        return 999; // Non-phase modules go last
    }

    // ✅ Get module_order from database or calculate it
    public static function getModuleNameOrder(string $moduleName): int
    {
        // Try to get from database first
        $task = self::where('module_name', $moduleName)->first();
        if ($task && $task->module_order) {
            return $task->module_order;
        }

        // Fallback to calculation
        $phaseNumber = self::extractPhaseNumber($moduleName);
        if ($phaseNumber !== 999) {
            return $phaseNumber;
        }

        $orderMap = [
            'Online Kick Off Meeting' => 100,
            'Online Webinar Training' => 101,
            'Attendance Management' => 102,
            'Leave Management' => 103,
            'Claim Management' => 104,
            'Payroll Management' => 105,
        ];

        return $orderMap[$moduleName] ?? 999;
    }

    // Keep old method for backward compatibility
    public static function getModuleOrder(string $module): int
    {
        $orderMap = [
            'phase 1' => 1,
            'phase 2' => 2,
            'attendance' => 3,
            'leave' => 4,
            'claim' => 5,
            'payroll' => 6,
        ];

        return $orderMap[strtolower($module)] ?? 999;
    }
}
