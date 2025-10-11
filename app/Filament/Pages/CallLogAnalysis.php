<?php

namespace App\Filament\Pages;

use App\Models\CallLog;
use App\Models\CallCategory;
use App\Models\PhoneExtension;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CallLogAnalysis extends Page
{
    protected static ?string $title = '';

    protected static ?string $navigationLabel = 'Call Log Analysis';

    protected static ?string $navigationGroup = 'Communication';

    protected static ?int $navigationSort = 86;

    protected static string $view = 'filament.pages.call-log-analysis';

    public $selectedYear;
    public $selectedMonth;
    public $dateRange = 'custom_range';
    public $startDate;
    public $endDate;
    public $moduleAnalysisData = [];
    public $supportAnalysisData = [];

    public function mount(): void
    {
        // Default to current year and month
        $this->selectedYear = date('Y');
        $this->selectedMonth = date('m');

        // Default date range: 1 month ago to today
        $this->startDate = Carbon::today()->subMonth()->format('Y-m-d');
        $this->endDate = Carbon::today()->format('Y-m-d');
        $this->dateRange = 'custom_range';

        $this->loadAnalysisData();
    }

    public function updatedSelectedYear()
    {
        if ($this->dateRange === 'current_month') {
            $this->updateCurrentMonthDates();
        }
        $this->loadAnalysisData();
    }

    public function updatedSelectedMonth()
    {
        if ($this->dateRange === 'current_month') {
            $this->updateCurrentMonthDates();
        }
        $this->loadAnalysisData();
    }

    private function updateCurrentMonthDates()
    {
        $this->startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->format('Y-m-d');
        $this->endDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth()->format('Y-m-d');
    }

    public function updatedDateRange()
    {
        // Update date range when predefined options are selected
        switch ($this->dateRange) {
            case 'last_2_days':
                $this->startDate = Carbon::yesterday()->format('Y-m-d');
                $this->endDate = Carbon::today()->format('Y-m-d');
                break;
            case 'last_7_days':
                $this->startDate = Carbon::today()->subDays(6)->format('Y-m-d');
                $this->endDate = Carbon::today()->format('Y-m-d');
                break;
            case 'last_30_days':
                $this->startDate = Carbon::today()->subDays(29)->format('Y-m-d');
                $this->endDate = Carbon::today()->format('Y-m-d');
                break;
            case 'current_month':
                $this->startDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->format('Y-m-d');
                $this->endDate = Carbon::create($this->selectedYear, $this->selectedMonth, 1)->endOfMonth()->format('Y-m-d');
                break;
        }
        $this->loadAnalysisData();
    }

    public function updatedStartDate()
    {
        if ($this->startDate && $this->endDate) {
            $this->dateRange = 'custom_range';
            $this->loadAnalysisData();
        }
    }

    public function updatedEndDate()
    {
        if ($this->startDate && $this->endDate) {
            $this->dateRange = 'custom_range';
            $this->loadAnalysisData();
        }
    }

    public function loadAnalysisData()
    {
        $this->moduleAnalysisData = $this->getModuleAnalysis();
        $this->supportAnalysisData = $this->getSupportAnalysis();
    }

    protected function getDateRange()
    {
        // Always use custom start and end dates
        return [
            'start' => Carbon::parse($this->startDate)->startOfDay(),
            'end' => Carbon::parse($this->endDate)->endOfDay()
        ];
    }

    protected function getModuleAnalysis()
    {
        $dateRange = $this->getDateRange();
        $cacheKey = "module_analysis_custom_" . $dateRange['start']->format('Y-m-d') . '_' . $dateRange['end']->format('Y-m-d');

        return Cache::remember($cacheKey, 300, function () use ($dateRange) {
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            // Get all categories for mapping
            $categories = CallCategory::where('tier', 1)->get();
            $categoryMap = $categories->pluck('name', 'id')->toArray();

            // Get support staff extensions
            $supportExtensions = PhoneExtension::where('is_support_staff', true)
                ->where('is_active', true)
                ->pluck('extension')
                ->toArray();

            $receptionExtension = PhoneExtension::where('extension', '100')
                ->value('extension') ?? '100';

            // Generate all dates in the range
            $dates = [];
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $dates[] = $currentDate->format('Y-m-d');
                $currentDate->addDay();
            }

            // Get call data grouped by date and module
            $callData = CallLog::whereBetween('started_at', [$startDate, $endDate])
                ->where(function ($query) use ($supportExtensions, $receptionExtension) {
                    $query->whereIn('caller_number', array_merge([$receptionExtension], $supportExtensions))
                        ->orWhereIn('receiver_number', $supportExtensions);
                })
                ->where('call_status', '!=', 'NO ANSWER')
                ->where(function($query) {
                    $query->where('call_duration', '>=', 5)
                        ->orWhereNull('call_duration');
                })
                ->whereNotNull('tier1_category_id')
                ->selectRaw('DATE(started_at) as call_date, tier1_category_id, COUNT(*) as call_count')
                ->groupBy('call_date', 'tier1_category_id')
                ->get();

            // Initialize result array
            $result = [
                'dates' => $dates,
                'modules' => [],
                'datasets' => []
            ];

            // Define module mapping with colors
            $moduleMapping = [
                'Attendance' => ['color' => '#10B981', 'data' => []],
                'Leave' => ['color' => '#3B82F6', 'data' => []],
                'Claim' => ['color' => '#F59E0B', 'data' => []],
                'Payroll' => ['color' => '#EF4444', 'data' => []],
                'Hardware' => ['color' => '#8B5CF6', 'data' => []],
                'Others' => ['color' => '#6B7280', 'data' => []]
            ];

            // Map category names to modules
            foreach ($categories as $category) {
                $moduleName = $this->mapCategoryToModule($category->name);
                if (!isset($moduleMapping[$moduleName])) {
                    if (!isset($moduleMapping['Others']['categories'])) {
                        $moduleMapping['Others']['categories'] = [];
                    }
                    $moduleMapping['Others']['categories'][] = $category->id;
                } else {
                    if (!isset($moduleMapping[$moduleName]['categories'])) {
                        $moduleMapping[$moduleName]['categories'] = [];
                    }
                    $moduleMapping[$moduleName]['categories'][] = $category->id;
                }
            }

            // Fill data for each module and date
            foreach ($moduleMapping as $module => $config) {
                $moduleData = [];

                foreach ($dates as $date) {
                    $count = 0;

                    // Find calls for this date and module categories
                    if (isset($config['categories'])) {
                        foreach ($config['categories'] as $categoryId) {
                            $call = $callData->where('call_date', $date)
                                ->where('tier1_category_id', $categoryId)
                                ->first();

                            if ($call) {
                                $count += $call->call_count;
                            }
                        }
                    }

                    $moduleData[] = $count;
                }

                $result['modules'][] = $module;
                $result['datasets'][] = [
                    'label' => $module,
                    'data' => $moduleData,
                    'borderColor' => $config['color'],
                    'backgroundColor' => $config['color'] . '20',
                    'tension' => 0.4
                ];
            }

            return $result;
        });
    }

    protected function getSupportAnalysis()
    {
        $dateRange = $this->getDateRange();
        $cacheKey = "support_analysis_custom_" . $dateRange['start']->format('Y-m-d') . '_' . $dateRange['end']->format('Y-m-d');

        return Cache::remember($cacheKey, 300, function () use ($dateRange) {
            $startDate = $dateRange['start'];
            $endDate = $dateRange['end'];

            // Get support staff
            $supportStaff = PhoneExtension::with('user')
                ->where('is_support_staff', true)
                ->where('is_active', true)
                ->get();

            // Generate all dates in the range
            $dates = [];
            $currentDate = $startDate->copy();
            while ($currentDate <= $endDate) {
                $dates[] = $currentDate->format('Y-m-d');
                $currentDate->addDay();
            }

            // Initialize result array
            $result = [
                'dates' => $dates,
                'staff' => [],
                'datasets' => []
            ];

            // Define colors for staff members
            $colors = ['#10B981', '#3B82F6', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6', '#F97316'];
            $colorIndex = 0;

            foreach ($supportStaff as $staff) {
                $staffName = ($staff->user_id && $staff->user) ? $staff->user->name : $staff->name;

                // Get call data for this staff member
                $callData = CallLog::whereBetween('started_at', [$startDate, $endDate])
                    ->where(function ($query) use ($staff) {
                        $query->where(function($subq) use ($staff) {
                            $subq->where('call_type', 'incoming')
                                ->where('receiver_number', $staff->extension);
                        })
                        ->orWhere(function($subq) use ($staff) {
                            $subq->where('call_type', 'outgoing')
                                ->where('caller_number', $staff->extension);
                        })
                        ->orWhere(function($subq) use ($staff) {
                            $subq->where('call_type', 'internal')
                                ->where(function($innerq) use ($staff) {
                                    $innerq->where('caller_number', $staff->extension)
                                        ->orWhere('receiver_number', $staff->extension);
                                });
                        });
                    })
                    ->where('call_status', '!=', 'NO ANSWER')
                    ->where(function($query) {
                        $query->where('call_duration', '>=', 5)
                            ->orWhereNull('call_duration');
                    })
                    ->selectRaw('DATE(started_at) as call_date, COUNT(*) as call_count')
                    ->groupBy('call_date')
                    ->pluck('call_count', 'call_date')
                    ->toArray();

                // Fill data for each date
                $staffData = [];
                foreach ($dates as $date) {
                    $staffData[] = $callData[$date] ?? 0;
                }

                $result['staff'][] = $staffName;
                $result['datasets'][] = [
                    'label' => $staffName,
                    'data' => $staffData,
                    'borderColor' => $colors[$colorIndex % count($colors)],
                    'backgroundColor' => $colors[$colorIndex % count($colors)] . '20',
                    'tension' => 0.4
                ];

                $colorIndex++;
            }

            return $result;
        });
    }

    public function getDateRangeOptions()
    {
        return [
            'custom_range' => 'Custom Range',
            'last_2_days' => 'Last 2 Days',
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',
            'current_month' => 'Current Month'
        ];
    }

    public function getFormattedDateRange()
    {
        if ($this->startDate && $this->endDate) {
            $start = Carbon::parse($this->startDate);
            $end = Carbon::parse($this->endDate);

            if ($start->isSameDay($end)) {
                return $start->format('M j, Y');
            } else {
                return $start->format('M j') . ' - ' . $end->format('M j, Y');
            }
        }

        return 'Select Date Range';
    }

    protected function mapCategoryToModule($categoryName)
    {
        $categoryName = strtolower($categoryName);

        if (strpos($categoryName, 'attendance') !== false || strpos($categoryName, 'punch') !== false) {
            return 'Attendance';
        } elseif (strpos($categoryName, 'leave') !== false || strpos($categoryName, 'annual') !== false) {
            return 'Leave';
        } elseif (strpos($categoryName, 'claim') !== false || strpos($categoryName, 'expense') !== false) {
            return 'Claim';
        } elseif (strpos($categoryName, 'payroll') !== false || strpos($categoryName, 'salary') !== false) {
            return 'Payroll';
        } elseif (strpos($categoryName, 'hardware') !== false || strpos($categoryName, 'device') !== false || strpos($categoryName, 'installation') !== false) {
            return 'Hardware';
        } else {
            return 'Others';
        }
    }

    public function getAvailableYears()
    {
        $years = CallLog::selectRaw('YEAR(started_at) as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->toArray();

        // Ensure current year is included even if no data exists
        if (!in_array(date('Y'), $years)) {
            $years[] = date('Y');
            rsort($years);
        }

        return $years;
    }

    public function getAvailableMonths()
    {
        return [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ];
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.call-log-analysis');
    }
}
