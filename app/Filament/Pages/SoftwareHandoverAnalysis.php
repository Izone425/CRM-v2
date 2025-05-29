<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\SoftwareHandover;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class SoftwareHandoverAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $navigationGroup = 'Analysis';
    protected static string $view = 'filament.pages.software-handover-analysis';
    protected static ?string $navigationLabel = 'Software Handover Analysis';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Software Handover Analysis';

    public $selectedMonth;
    public $totalHandovers = 0;
    public Carbon $currentDate;

    // Category counts
    public $categoryCounts = [];
    public $modulesCounts = [];
    public $salesCounts = [];
    public $implementerCounts = [];
    public $statusCounts = [];
    public $statusOngoingCounts = [];
    public $paymentCounts = [];
    public $adminTaskCounts = [];

    public $showSlideOver = false;
    public $handoverList = [];
    public $slideOverTitle = 'Software Handovers';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.software-handover-analysis');
    }

    public function mount()
    {
        $this->currentDate = Carbon::now();
        $this->selectedMonth = session('selectedMonth', $this->currentDate->format('Y-m'));

        $this->fetchAllData();
    }

    public function updatedSelectedMonth($month)
    {
        $this->selectedMonth = $month;
        session(['selectedMonth' => $month]);

        $this->fetchAllData();
    }

    private function fetchAllData()
    {
        // Get total handovers count
        $this->totalHandovers = $this->getBaseQuery()->count();

        // Fetch all data for the dashboard
        $this->fetchCategoryCounts();
        // $this->fetchModulesCounts();
        // $this->fetchSalesCounts();
        // $this->fetchImplementerCounts();
        // $this->fetchStatusCounts();
        // $this->fetchStatusOngoingCounts();
        // $this->fetchPaymentCounts();
        // $this->fetchAdminTaskCounts();
    }

    private function getBaseQuery()
    {
        $query = SoftwareHandover::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        return $query;
    }

    private function fetchCategoryCounts()
    {
        $query = $this->getBaseQuery();

        // Initialize counts for each category
        $small = 0;
        $medium = 0;
        $large = 0;
        $enterprise = 0;

        // Get all handovers with their related leads
        $handovers = $query->with('lead')->get();

        // Categorize each handover based on the company size in the related lead
        foreach ($handovers as $handover) {
            if (!$handover->lead || !isset($handover->lead->company_size)) {
                // Skip if there's no lead or company size
                continue;
            }

            $companySize = $handover->lead->company_size;

            // Categorize based on company size ranges
            if ($companySize >= 1 && $companySize <= 24) {
                $small++;
            } elseif ($companySize >= 25 && $companySize <= 99) {
                $medium++;
            } elseif ($companySize >= 100 && $companySize <= 500) {
                $large++;
            } elseif ($companySize > 500) {
                $enterprise++;
            }
        }

        // Define the counts by category
        $this->categoryCounts = [
            'small' => $small,
            'medium' => $medium,
            'large' => $large,
            'enterprise' => $enterprise,
        ];

        // Add total
        $this->categoryCounts['total'] = array_sum($this->categoryCounts);
    }

    // private function fetchModulesCounts()
    // {
    //     $query = $this->getBaseQuery();

    //     // Count modules
    //     $this->modulesCounts = [
    //         'ta_count' => $query->clone()->where('modules', 'like', '%ta%')->count(),
    //         'tl_count' => $query->clone()->where('modules', 'like', '%tl%')->count(),
    //         'tc_count' => $query->clone()->where('modules', 'like', '%tc%')->count(),
    //         'tp_count' => $query->clone()->where('modules', 'like', '%tp%')->count(),
    //     ];

    //     $this->modulesCounts['total'] = $query->count();
    // }

    // private function fetchSalesCounts()
    // {
    //     $query = $this->getBaseQuery();

    //     // Get counts grouped by salesperson
    //     $salesData = $query->clone()
    //         ->select('salesperson_id', DB::raw('COUNT(*) as total'))
    //         ->groupBy('salesperson_id')
    //         ->get();

    //     $this->salesCounts = [];

    //     foreach ($salesData as $item) {
    //         $user = User::find($item->salesperson_id);
    //         $name = $user ? strtolower($user->name) : 'unknown';
    //         $this->salesCounts[$name] = $item->total;
    //     }

    //     // Add specific salespeople from your image
    //     $requiredSales = ['muim', 'jia_jun', 'yasmin', 'edward', 'sulaiman', 'natt',
    //                      'tamy', 'faza', 'tina', 'jonathan', 'wirson', 'fatimah',
    //                      'farhanah', 'joshua', 'aziz', 'bari', 'vince'];

    //     foreach ($requiredSales as $name) {
    //         if (!isset($this->salesCounts[$name])) {
    //             $this->salesCounts[$name] = 0;
    //         }
    //     }

    //     $this->salesCounts['total'] = $this->totalHandovers;
    // }

    // private function fetchImplementerCounts()
    // {
    //     $query = $this->getBaseQuery();

    //     // Get counts grouped by implementer
    //     $implementerData = $query->clone()
    //         ->select('implementer', DB::raw('COUNT(*) as total'))
    //         ->groupBy('implementer')
    //         ->get();

    //     $this->implementerCounts = [];

    //     foreach ($implementerData as $item) {
    //         $name = $item->implementer ? strtolower($item->implementer) : 'unknown';
    //         $this->implementerCounts[$name] = $item->total;
    //     }

    //     // Add specific implementers from your image
    //     $requiredImplementers = ['amirul', 'bari', 'zulhilmie', 'adzzim', 'azrul',
    //                            'najwa', 'syazana', 'hanif', 'aiman', 'hanis', 'john',
    //                            'alif_faisal', 'shaoinur', 'syamim', 'siew_ling'];

    //     foreach ($requiredImplementers as $name) {
    //         if (!isset($this->implementerCounts[$name])) {
    //             $this->implementerCounts[$name] = 0;
    //         }
    //     }

    //     $this->implementerCounts['total'] = $this->totalHandovers;
    // }

    // private function fetchStatusCounts()
    // {
    //     $query = $this->getBaseQuery();

    //     // Get counts grouped by status
    //     $this->statusCounts = [
    //         'open' => $query->clone()->where('status', 'open')->count(),
    //         'closed' => $query->clone()->where('status', 'closed')->count(),
    //         'delay' => $query->clone()->where('status', 'delay')->count(),
    //         'inactive' => $query->clone()->where('status', 'inactive')->count(),
    //     ];

    //     $this->statusCounts['total'] = array_sum($this->statusCounts);
    // }

    // private function fetchStatusOngoingCounts()
    // {
    //     $this->statusOngoingCounts = [
    //         'closed' => $this->statusCounts['closed'],
    //         'ongoing' => $this->statusCounts['open'] + $this->statusCounts['delay'] + $this->statusCounts['inactive'],
    //     ];
    // }

    // private function fetchPaymentCounts()
    // {
    //     $query = $this->getBaseQuery();

    //     // Get counts grouped by payment status
    //     $this->paymentCounts = [
    //         'full_payment' => $query->clone()->where('payment_status', 'full_payment')->count(),
    //         'partial_payment' => $query->clone()->where('payment_status', 'partial_payment')->count(),
    //         'unpaid' => $query->clone()->where('payment_status', 'unpaid')->count(),
    //         'hrdf_payment' => $query->clone()->where('payment_status', 'hrdf_payment')->count(),
    //         'bad_debtor' => $query->clone()->where('payment_status', 'bad_debtor')->count(),
    //     ];

    //     $this->paymentCounts['total'] = array_sum($this->paymentCounts);
    // }

    // private function fetchAdminTaskCounts()
    // {
    //     $query = $this->getBaseQuery();

    //     // Count admin tasks
    //     $this->adminTaskCounts = [
    //         'kick_off_meeting' => $query->clone()->where('admin_task', 'kick_off_meeting')->count(),
    //     ];

    //     $this->adminTaskCounts['total'] = array_sum($this->adminTaskCounts);
    // }

    // public function openCategorySlideOver($category)
    // {
    //     $query = $this->getBaseQuery()->where('size', $category);
    //     $this->handoverList = $query->with(['lead', 'lead.companyDetail'])->get();
    //     $this->slideOverTitle = ucfirst($category) . ' Category Handovers';
    //     $this->showSlideOver = true;
    // }

    // public function openModuleSlideOver($module)
    // {
    //     $query = $this->getBaseQuery()->where('modules', 'like', "%$module%");
    //     $this->handoverList = $query->with(['lead', 'lead.companyDetail'])->get();
    //     $this->slideOverTitle = strtoupper($module) . ' Module Handovers';
    //     $this->showSlideOver = true;
    // }

    // public function openSalesSlideOver($salesperson)
    // {
    //     // Find user ID for the salesperson name
    //     $user = User::where(DB::raw('LOWER(name)'), 'like', "%$salesperson%")->first();

    //     if ($user) {
    //         $query = $this->getBaseQuery()->where('salesperson_id', $user->id);
    //     } else {
    //         $query = $this->getBaseQuery()->where('salesperson_id', -1); // No matches
    //     }

    //     $this->handoverList = $query->with(['lead', 'lead.companyDetail'])->get();
    //     $this->slideOverTitle = ucfirst($salesperson) . "'s Handovers";
    //     $this->showSlideOver = true;
    // }

    // public function openImplementerSlideOver($implementer)
    // {
    //     $query = $this->getBaseQuery()->where(DB::raw('LOWER(implementer)'), 'like', "%$implementer%");
    //     $this->handoverList = $query->with(['lead', 'lead.companyDetail'])->get();
    //     $this->slideOverTitle = ucfirst($implementer) . "'s Implementations";
    //     $this->showSlideOver = true;
    // }

    // public function openStatusSlideOver($status)
    // {
    //     $query = $this->getBaseQuery()->where('status', $status);
    //     $this->handoverList = $query->with(['lead', 'lead.companyDetail'])->get();
    //     $this->slideOverTitle = ucfirst($status) . ' Status Handovers';
    //     $this->showSlideOver = true;
    // }

    // public function openPaymentSlideOver($paymentStatus)
    // {
    //     $query = $this->getBaseQuery()->where('payment_status', $paymentStatus);
    //     $this->handoverList = $query->with(['lead', 'lead.companyDetail'])->get();
    //     $this->slideOverTitle = str_replace('_', ' ', ucfirst($paymentStatus)) . ' Payment Status';
    //     $this->showSlideOver = true;
    // }

    // public function openAdminTaskSlideOver($task)
    // {
    //     $query = $this->getBaseQuery()->where('admin_task', $task);
    //     $this->handoverList = $query->with(['lead', 'lead.companyDetail'])->get();
    //     $this->slideOverTitle = str_replace('_', ' ', ucfirst($task));
    //     $this->showSlideOver = true;
    // }
}
