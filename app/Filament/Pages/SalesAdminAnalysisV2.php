<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class SalesAdminAnalysisV2 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationGroup = 'Analysis';
    protected static ?string $title = 'Sales Admin Analysis V2';
    protected static string $view = 'filament.pages.sales-admin-analysis-v2';

    public $selectedMonth;

    public $totalLeads = 0;
    public $newLeads = 0;
    public $jajaLeads = 0;
    public $afifahLeads = 0;

    public $newPercentage = 0;
    public $jajaPercentage = 0;
    public $afifahPercentage = 0;
    public $categoriesData = [];
    public $companySizeData = [];
    public $totalActiveLeads = 0;
    public $stagesData = [];
    public $activeLeadsData = [];

    public $totalTransferLeads = 0;
    public $transferStagesData = [];

    public $totalInactiveLeads = 0;
    public $inactiveLeadData = [];

    public $adminJajaLeadStats = [];

    public static function canAccess(): bool
    {
        return auth()->user()->role_id != '2';
    }

    public function mount()
    {
        $this->fetchLeads();
        $this->fetchLeadsByCategory();
        $this->fetchLeadsByAdminJaja();
    }

    public function updatedSelectedMonth($month)
    {
        $this->selectedMonth = $month;
        session(['selectedMonth' => $month]);

        $this->fetchLeads();
        $this->fetchLeadsByCategory();
        $this->fetchLeadsByAdminJaja();
    }

    public function fetchLeads()
    {
        $query = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        $leads = $query->get();
        $this->totalLeads = $leads->count();
        $this->newLeads = $leads->where('categories', 'New')->count();
        $this->jajaLeads = $leads->where('lead_owner', 'Nurul Najaa Nadiah')->count();
        $this->afifahLeads = $leads->where('lead_owner', 'Siti Afifah')->count();

        $this->newPercentage = $this->totalLeads > 0 ? round(($this->newLeads / $this->totalLeads) * 100, 2) : 0;
        $this->jajaPercentage = $this->totalLeads > 0 ? round(($this->jajaLeads / $this->totalLeads) * 100, 2) : 0;
        $this->afifahPercentage = $this->totalLeads > 0 ? round(($this->afifahLeads / $this->totalLeads) * 100, 2) : 0;
    }

    public function fetchLeadsByCategory()
    {
        $dateRange = null;

        // Apply date range filter if a month is selected
        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $dateRange = [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')];
        }

        // Define category counts with month filter applied
        $this->categoriesData = [
            'New' => Lead::query()
                ->where('categories', 'New')
                ->whereNull('lead_owner')
                ->whereNull('salesperson')
                ->when($dateRange, function ($query) use ($dateRange) {
                    return $query->whereBetween('created_at', $dateRange);
                })
                ->count(),

            'Active' => Lead::query()
                ->whereNull('salesperson')
                ->whereNotNull('lead_owner')
                ->where('categories', '!=', 'Inactive')
                ->where(function ($query) {
                    $query->whereNull('done_call')
                        ->orWhere('done_call', 0);
                })
                ->when($dateRange, function ($query) use ($dateRange) {
                    return $query->whereBetween('created_at', $dateRange);
                })
                ->count(),

            'Sales' => Lead::query()
                ->whereNotNull('salesperson')
                ->where('categories', '!=', 'Inactive')
                ->when($dateRange, function ($query) use ($dateRange) {
                    return $query->whereBetween('created_at', $dateRange);
                })
                ->count(),

            'Inactive' => Lead::query()
                ->where('categories', 'Inactive')
                ->when($dateRange, function ($query) use ($dateRange) {
                    return $query->whereBetween('created_at', $dateRange);
                })
                ->count(),
        ];
    }

    public function fetchLeadsByAdminJaja()
    {
        $queryBase = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $queryBase->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        $leadCategories = ['New', 'Active', 'Inactive'];

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Nurul Najaa Nadiah');

        // Clone and count each category separately
        $newLeadsCount = (clone $queryBase)
            ->where('categories', 'New')
            ->whereNull('salesperson')
            ->count();

        $activeLeadsCount = (clone $queryBase)
            ->whereNull('salesperson')
            ->where('categories', '!=', 'Inactive')
            ->where(function ($query) {
                $query->whereNull('done_call')->orWhere('done_call', 0);
            })
            ->count();

        $inactiveLeadsCount = (clone $queryBase)
            ->where('categories', 'Inactive')
            ->count();

        // Ensure all categories exist, even if zero
        $this->adminJajaLeadStats = array_merge(
            array_fill_keys($leadCategories, 0),
            [
                'New' => $newLeadsCount,
                'Active' => $activeLeadsCount,
                'Inactive' => $inactiveLeadsCount,
            ]
        );
    }
}
