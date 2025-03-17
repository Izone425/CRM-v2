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
    protected static ?string $navigationLabel = 'Sales Admin Analysis V2';
    protected static ?string $title = '';
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

    public $adminAfifahLeadStats = [];

    public $activeLeadsDataJaja = [];
    public $totalActiveLeadsJaja = 0;

    public $activeLeadsDataAfifah = [];
    public $totalActiveLeadsAfifah = 0;

    public $transferStagesDataJaja = [];
    public $totalTransferLeadsJaja = 0;

    public $transferStagesDataAfifah = [];
    public $totalTransferLeadsAfifah = 0;

    public $inactiveLeadDataJaja = [];
    public $totalInactiveLeadsJaja = 0;

    public $inactiveLeadDataAfifah = [];
    public $totalInactiveLeadsAfifah = 0;

    public Carbon $currentDate;

    public static function canAccess(): bool
    {
        return auth()->user()->role_id != '2';
    }

    public function mount()
    {
        $this->currentDate = Carbon::now();
        $this->selectedMonth = session('selectedMonth', $this->currentDate->format('Y-m'));

        $this->fetchLeads();
        $this->fetchLeadsByCategory();
        $this->fetchLeadsByAdminJaja();
        $this->fetchLeadsByAdminAfifah();
        $this->fetchActiveLeadsJaja();
        $this->fetchActiveLeadsAfifah();
        $this->fetchTransferLeadsJaja();
        $this->fetchTransferLeadsAfifah();
        $this->fetchInactiveLeadsJaja();
        $this->fetchInactiveLeadsAfifah();
    }

    public function updatedSelectedMonth($month)
    {
        $this->selectedMonth = $month;
        session(['selectedMonth' => $month]);

        $this->fetchLeads();
        $this->fetchLeadsByCategory();
        $this->fetchLeadsByAdminJaja();
        $this->fetchLeadsByAdminAfifah();
        $this->fetchActiveLeadsJaja();
        $this->fetchActiveLeadsAfifah();
        $this->fetchTransferLeadsJaja();
        $this->fetchTransferLeadsAfifah();
        $this->fetchInactiveLeadsJaja();
        $this->fetchInactiveLeadsAfifah();
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

        $leadCategories = ['Active', 'Sales', 'Inactive'];

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Nurul Najaa Nadiah');

        // Clone and count each category separately
        $salesLeadsCount = (clone $queryBase)
            ->where('categories', '!=', 'Inactive')
            ->whereNotNull('salesperson')
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
                'Active' => $activeLeadsCount,
                'Sales' => $salesLeadsCount,
                'Inactive' => $inactiveLeadsCount,
            ]
        );
    }

    public function fetchLeadsByAdminAfifah()
    {
        $queryBase = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $queryBase->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        $leadCategories = ['Active', 'Sales', 'Inactive'];

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Siti Afifah');

        // Clone and count each category separately
        $salesLeadsCount = (clone $queryBase)
            ->where('categories', '!=', 'Inactive')
            ->whereNotNull('salesperson')
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
        $this->adminAfifahLeadStats = array_merge(
            array_fill_keys($leadCategories, 0),
            [
                'Active' => $activeLeadsCount,
                'Sales' => $salesLeadsCount,
                'Inactive' => $inactiveLeadsCount,
            ]
        );
    }

    public function fetchActiveLeadsJaja()
    {
        $queryBase = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $queryBase->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Nurul Najaa Nadiah');

        // Define active lead categories using queryBase cloning
        $this->activeLeadsDataJaja = [
            'Active 24 Below' => (clone $queryBase)
                ->where('company_size', '=', '1-24')
                ->whereNull('salesperson')
                ->whereNotNull('lead_owner')
                ->where('categories', '!=', 'Inactive')
                ->where(function ($query) {
                    $query->whereNull('done_call')->orWhere('done_call', 0);
                })
                ->count(),

            'Active 25 Above' => (clone $queryBase)
                ->where('company_size', '!=', '1-24')
                ->whereNull('salesperson')
                ->whereNotNull('lead_owner')
                ->where('categories', '!=', 'Inactive')
                ->where(function ($query) {
                    $query->whereNull('done_call')->orWhere('done_call', 0);
                })
                ->count(),

            'Call Attempt 24 Below' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->where('company_size', '=', '1-24')
                ->where('categories', '!=', 'Inactive')
                ->count(),

            'Call Attempt 25 Above' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->whereBetween('call_attempt', [1, 10])
                ->where('categories', '!=', 'Inactive')
                ->where('company_size', '!=', '1-24')
                ->count(),
        ];

        // Sum up all active lead data for Jaja
        $this->totalActiveLeadsJaja = array_sum($this->activeLeadsDataJaja);
    }

    public function fetchActiveLeadsAfifah()
    {
        $queryBase = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $queryBase->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Siti Afifah');

        // Define active lead categories using queryBase cloning
        $this->activeLeadsDataAfifah = [
            'Active 24 Below' => (clone $queryBase)
                ->where('company_size', '=', '1-24')
                ->whereNull('salesperson')
                ->whereNotNull('lead_owner')
                ->where('categories', '!=', 'Inactive')
                ->where(function ($query) {
                    $query->whereNull('done_call')->orWhere('done_call', 0);
                })
                ->count(),

            'Active 25 Above' => (clone $queryBase)
                ->where('company_size', '!=', '1-24')
                ->whereNull('salesperson')
                ->whereNotNull('lead_owner')
                ->where('categories', '!=', 'Inactive')
                ->where(function ($query) {
                    $query->whereNull('done_call')->orWhere('done_call', 0);
                })
                ->count(),

            'Call Attempt 24 Below' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->where('company_size', '=', '1-24')
                ->where('categories', '!=', 'Inactive')
                ->count(),

            'Call Attempt 25 Above' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->whereBetween('call_attempt', [1, 10])
                ->where('categories', '!=', 'Inactive')
                ->where('company_size', '!=', '1-24')
                ->count(),
        ];

        // Sum up all active lead data for Afifah
        $this->totalActiveLeadsAfifah = array_sum($this->activeLeadsDataAfifah);
    }

    public function fetchTransferLeadsJaja()
    {
        $queryBaseJaja = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $queryBaseJaja->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Filter for specific lead owner
        $queryBaseJaja->where('lead_owner', 'Nurul Najaa Nadiah');

        // Fetch transfer lead counts by stage
        $this->transferStagesDataJaja = [
            'Transfer' => (clone $queryBaseJaja)
                ->whereNotNull('salesperson')
                ->where('stage', 'Transfer')
                ->count(),

            'Demo' => (clone $queryBaseJaja)
                ->whereNotNull('salesperson')
                ->where('stage', 'Demo')
                ->count(),

            'Follow Up' => (clone $queryBaseJaja)
                ->whereNotNull('salesperson')
                ->where('stage', 'Follow Up')
                ->count(),
        ];

        // Calculate total transfer-related leads
        $this->totalTransferLeadsJaja = array_sum($this->transferStagesDataJaja);
    }

    public function fetchTransferLeadsAfifah()
    {
        $queryBaseAfifah = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $queryBaseAfifah->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Filter for specific lead owner
        $queryBaseAfifah->where('lead_owner', 'Siti Afifah');

        // Fetch transfer lead counts by stage
        $this->transferStagesDataAfifah = [
            'Transfer' => (clone $queryBaseAfifah)
                ->whereNotNull('salesperson')
                ->where('stage', 'Transfer')
                ->count(),

            'Demo' => (clone $queryBaseAfifah)
                ->whereNotNull('salesperson')
                ->where('stage', 'Demo')
                ->count(),

            'Follow Up' => (clone $queryBaseAfifah)
                ->whereNotNull('salesperson')
                ->where('stage', 'Follow Up')
                ->count(),
        ];

        // Calculate total transfer-related leads
        $this->totalTransferLeadsAfifah = array_sum($this->transferStagesDataAfifah);
    }

    public function fetchInactiveLeadsJaja()
    {
        // Base query for Nurul Najaa Nadiah
        $queryBaseJaja = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $queryBaseJaja->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Filter for specific lead owner
        $queryBaseJaja->where('lead_owner', 'Nurul Najaa Nadiah');

        // Apply additional filters for inactive leads
        $queryJaja = (clone $queryBaseJaja)
            ->whereIn('lead_status', ['Junk', 'On Hold', 'Lost', 'No Response']); // Filter inactive statuses

        // Count total inactive leads for Jaja
        $this->totalInactiveLeadsJaja = $queryJaja->count();

        // Fetch grouped lead status counts for Jaja
        $leadStatusDataRawJaja = $queryJaja
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // Ensure all statuses exist in the correct order (fill missing ones with 0)
        $this->inactiveLeadDataJaja = array_merge([
            'Junk' => 0,
            'On Hold' => 0,
            'Lost' => 0,
            'No Response' => 0
        ], $leadStatusDataRawJaja);
    }

    public function fetchInactiveLeadsAfifah()
    {
        // Base query for Siti Afifah
        $queryBaseAfifah = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $queryBaseAfifah->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Filter for specific lead owner
        $queryBaseAfifah->where('lead_owner', 'Siti Afifah');

        // Apply additional filters for inactive leads
        $queryAfifah = (clone $queryBaseAfifah)
            ->whereIn('lead_status', ['Junk', 'On Hold', 'Lost', 'No Response']); // Filter inactive statuses

        // Count total inactive leads for Afifah
        $this->totalInactiveLeadsAfifah = $queryAfifah->count();

        // Fetch grouped lead status counts for Afifah
        $leadStatusDataRawAfifah = $queryAfifah
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // Ensure all statuses exist in the correct order (fill missing ones with 0)
        $this->inactiveLeadDataAfifah = array_merge([
            'Junk' => 0,
            'On Hold' => 0,
            'Lost' => 0,
            'No Response' => 0
        ], $leadStatusDataRawAfifah);
    }
}
