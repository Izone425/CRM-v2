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

    public $shahilahLeads = 0;
    public $shahilahPercentage = 0;
    public $adminShahilahLeadStats = [];
    public $activeLeadsDataShahilah = [];
    public $totalActiveLeadsShahilah = 0;
    public $transferStagesDataShahilah = [];
    public $totalTransferLeadsShahilah = 0;
    public $inactiveLeadDataShahilah = [];
    public $totalInactiveLeadsShahilah = 0;

    public $sheenaLeads = 0;
    public $sheenaPercentage = 0;
    public $adminSheenaLeadStats = [];
    public $activeLeadsDataSheena = [];
    public $totalActiveLeadsSheena = 0;
    public $transferStagesDataSheena = [];
    public $totalTransferLeadsSheena = 0;
    public $inactiveLeadDataSheena = [];
    public $totalInactiveLeadsSheena = 0;

    public $showSlideOver = false;
    public $slideOverTitle = 'Leads';
    public $leadList = [];

    public Carbon $currentDate;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.sales-admin-analysis-v2');
    }

    public function mount()
    {
        $this->currentDate = Carbon::now();
        $this->selectedMonth = session('selectedMonth', $this->currentDate->format('Y-m'));

        $this->fetchLeads();
        $this->fetchLeadsByCategory();
        $this->fetchLeadsByAdminJaja();
        $this->fetchLeadsByAdminAfifah();
        $this->fetchLeadsByAdminShahilah();
        $this->fetchActiveLeadsJaja();
        $this->fetchActiveLeadsAfifah();
        $this->fetchActiveLeadsShahilah();
        $this->fetchTransferLeadsJaja();
        $this->fetchTransferLeadsAfifah();
        $this->fetchTransferLeadsShahilah();
        $this->fetchInactiveLeadsJaja();
        $this->fetchInactiveLeadsAfifah();
        $this->fetchInactiveLeadsShahilah();
        $this->fetchLeadsByAdminSheena();
        $this->fetchActiveLeadsSheena();
        $this->fetchTransferLeadsSheena();
        $this->fetchInactiveLeadsSheena();
    }

    public function updatedSelectedMonth($month)
    {
        $this->selectedMonth = $month;
        session(['selectedMonth' => $month]);

        $this->fetchLeads();
        $this->fetchLeadsByCategory();
        $this->fetchLeadsByAdminJaja();
        $this->fetchLeadsByAdminAfifah();
        // $this->fetchLeadsByAdminShahilah();
        $this->fetchActiveLeadsJaja();
        $this->fetchActiveLeadsAfifah();
        // $this->fetchActiveLeadsShahilah();
        $this->fetchTransferLeadsJaja();
        $this->fetchTransferLeadsAfifah();
        // $this->fetchTransferLeadsShahilah();
        $this->fetchInactiveLeadsJaja();
        $this->fetchInactiveLeadsAfifah();
        // $this->fetchInactiveLeadsShahilah();
        $this->fetchLeadsByAdminSheena();
        $this->fetchActiveLeadsSheena();
        $this->fetchTransferLeadsSheena();
        $this->fetchInactiveLeadsSheena();
    }

    public function fetchLeads()
    {
        $query = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $query->where('lead_status', '!=', 'Closed');

        $leads = $query->get();

        // Calculate individual lead counts
        $this->newLeads = $leads->where('categories', 'New')->count();
        $this->jajaLeads = $leads->where('lead_owner', 'Nurul Najaa Nadiah')->count();
        // $this->afifahLeads = $leads->where('lead_owner', 'Siti Afifah')->count();
        $this->afifahLeads = $leads->whereIn('lead_owner', ['Siti Afifah', 'Fatimah Nurnabilah', 'Norhaiyati', 'Farah', 'Siti Shahilah'])->count();
        // $this->shahilahLeads = $leads->where('lead_owner', 'Siti Shahilah')->count();
        $this->sheenaLeads = $leads->where('lead_owner', 'Sheena Liew')->count();

        // Calculate total as the sum of individual counts
        $this->totalLeads = $this->newLeads + $this->jajaLeads + $this->afifahLeads + $this->sheenaLeads;

        // Calculate percentages
        $this->newPercentage = $this->totalLeads > 0 ? round(($this->newLeads / $this->totalLeads) * 100, 2) : 0;
        $this->jajaPercentage = $this->totalLeads > 0 ? round(($this->jajaLeads / $this->totalLeads) * 100, 2) : 0;
        $this->afifahPercentage = $this->totalLeads > 0 ? round(($this->afifahLeads / $this->totalLeads) * 100, 2) : 0;
        $this->shahilahPercentage = $this->totalLeads > 0 ? round(($this->shahilahLeads / $this->totalLeads) * 100, 2) : 0;
        $this->sheenaPercentage = $this->totalLeads > 0 ? round(($this->sheenaLeads / $this->totalLeads) * 100, 2) : 0;
    }

    public function fetchLeadsByCategory()
    {
        $start = null;
        $end = null;

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $start = $date->startOfMonth()->toDateString();
            $end = $date->endOfMonth()->toDateString();
        }

        $this->categoriesData = [
            'New' => Lead::query()
                ->where('categories', 'New')
                ->where('lead_status', 'None')
                ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)'])
                ->when($start && $end, fn ($query) =>
                    $query->whereDate('created_at', '>=', $start)
                        ->whereDate('created_at', '<=', $end)
                )
                ->count(),

            'Active' => Lead::query()
                ->whereNotIn('categories', ['Inactive', 'New'])
                ->whereNull('salesperson')
                ->whereNotNull('lead_owner')
                ->where('lead_owner', '!=', 'Chee Chan')
                ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)'])
                ->when($start && $end, fn ($query) =>
                    $query->whereDate('created_at', '>=', $start)
                        ->whereDate('created_at', '<=', $end)
                )
                ->count(),

            'Sales' => Lead::query()
                ->whereNotNull('salesperson')
                ->where('categories', '!=', 'Inactive')
                ->whereNotNull('lead_owner')
                ->where('lead_owner', '!=', 'Chee Chan')
                ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)'])
                ->when($start && $end, fn ($query) =>
                    $query->whereDate('created_at', '>=', $start)
                        ->whereDate('created_at', '<=', $end)
                )
                ->count(),

            'Inactive' => Lead::query()
                ->where('categories', 'Inactive')
                ->whereNotNull('lead_owner')
                ->where('lead_status', '!=', 'Closed')
                ->where('lead_owner', '!=', 'Chee Chan')
                ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)'])
                ->when($start && $end, fn ($query) =>
                    $query->whereDate('created_at', '>=', $start)
                        ->whereDate('created_at', '<=', $end)
                )
                ->count(),
        ];
    }

    public function fetchLeadsByAdminJaja()
    {
        $queryBase = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $leadCategories = ['Active', 'Sales', 'Inactive'];

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Nurul Najaa Nadiah');

        // Clone and count each category separately
        $salesLeadsCount = (clone $queryBase)
            ->where('categories', '=', 'Active')
            ->whereNotNull('salesperson')
            ->count();

        $activeLeadsCount = (clone $queryBase)
            ->whereNull('salesperson')
            ->where('categories', '=', 'Active')
            ->count();

        $inactiveLeadsCount = (clone $queryBase)
            ->where('categories', 'Inactive')
            ->where('lead_status', '!=', 'Closed')
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

    public function fetchLeadsByAdminSheena()
    {
        $queryBase = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $leadCategories = ['Active', 'Sales', 'Inactive'];

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Sheena Liew');

        // Clone and count each category separately
        $salesLeadsCount = (clone $queryBase)
            ->where('categories', '=', 'Active')
            ->whereNotNull('salesperson')
            ->count();

        $activeLeadsCount = (clone $queryBase)
            ->whereNull('salesperson')
            ->where('categories', '=', 'Active')
            ->count();

        $inactiveLeadsCount = (clone $queryBase)
            ->where('categories', 'Inactive')
            ->where('lead_status', '!=', 'Closed')
            ->count();

        // Ensure all categories exist, even if zero
        $this->adminSheenaLeadStats = array_merge(
            array_fill_keys($leadCategories, 0),
            [
                'Active' => $activeLeadsCount,
                'Sales' => $salesLeadsCount,
                'Inactive' => $inactiveLeadsCount,
            ]
        );
    }

    public function fetchLeadsByAdminShahilah()
    {
        $queryBase = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $leadCategories = ['Active', 'Sales', 'Inactive'];

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Siti Shahilah');

        // Clone and count each category separately
        $salesLeadsCount = (clone $queryBase)
            ->where('categories', '=', 'Active')
            ->whereNotNull('salesperson')
            ->count();

        $activeLeadsCount = (clone $queryBase)
            ->whereNull('salesperson')
            ->where('categories', '=', 'Active')
            ->count();

        $inactiveLeadsCount = (clone $queryBase)
            ->where('categories', 'Inactive')
            ->where('lead_status', '!=', 'Closed')
            ->count();

        // Ensure all categories exist, even if zero
        $this->adminShahilahLeadStats = array_merge(
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
        $queryBase = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $leadCategories = ['Active', 'Sales', 'Inactive'];

        // Filter for specific lead owner
        $queryBase->whereIn('lead_owner', ['Siti Afifah', 'Fatimah Nurnabilah', 'Norhaiyati', 'Farah', 'Siti Shahilah']);

        // Clone and count each category separately
        $salesLeadsCount = (clone $queryBase)
            ->where('categories', '=', 'Active')
            ->whereNotNull('salesperson')
            ->count();

        $activeLeadsCount = (clone $queryBase)
            ->whereNull('salesperson')
            ->where('categories', '=', 'Active')
            ->count();

        $inactiveLeadsCount = (clone $queryBase)
            ->where('categories', 'Inactive')
            ->where('lead_status', '!=', 'Closed')
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
        $queryBase = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Nurul Najaa Nadiah');

        // Define active lead categories using queryBase cloning
        $this->activeLeadsDataJaja = [
            'Active 24 Below' => (clone $queryBase)
                ->whereIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '0')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->count(),

            'Active 25 Above' => (clone $queryBase)
                ->whereNotIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '0')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->count(),

            'Call Attempt 24 Below' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->whereIn('company_size', ['1-24', '20-24'])
                ->where('categories', '=', 'Active')
                ->count(),

            'Call Attempt 25 Above' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->whereNotIn('company_size', ['1-24', '20-24'])
                ->count(),
        ];

        // Sum up all active lead data for Jaja
        $this->totalActiveLeadsJaja = array_sum($this->activeLeadsDataJaja);
    }

    public function fetchActiveLeadsSheena()
    {
        $queryBase = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Sheena Liew');

        // Define active lead categories using queryBase cloning
        $this->activeLeadsDataSheena = [
            'Active 24 Below' => (clone $queryBase)
                ->whereIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '0')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->count(),

            'Active 25 Above' => (clone $queryBase)
                ->whereNotIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '0')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->count(),

            'Call Attempt 24 Below' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->whereIn('company_size', ['1-24', '20-24'])
                ->where('categories', '=', 'Active')
                ->count(),

            'Call Attempt 25 Above' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->whereNotIn('company_size', ['1-24', '20-24'])
                ->count(),
        ];

        // Sum up all active lead data for Shahilah
        $this->totalActiveLeadsSheena = array_sum($this->activeLeadsDataSheena);
    }

    public function fetchActiveLeadsShahilah()
    {
        $queryBase = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Siti Shahilah');

        // Define active lead categories using queryBase cloning
        $this->activeLeadsDataShahilah = [
            'Active 24 Below' => (clone $queryBase)
                ->whereIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '0')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->count(),

            'Active 25 Above' => (clone $queryBase)
                ->whereNotIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '0')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->count(),

            'Call Attempt 24 Below' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->whereIn('company_size', ['1-24', '20-24'])
                ->where('categories', '=', 'Active')
                ->count(),

            'Call Attempt 25 Above' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->whereNotIn('company_size', ['1-24', '20-24'])
                ->count(),
        ];

        // Sum up all active lead data for Shahilah
        $this->totalActiveLeadsShahilah = array_sum($this->activeLeadsDataShahilah);
    }

    public function fetchActiveLeadsAfifah()
    {
        $queryBase = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        // Filter for specific lead owner
        $queryBase->whereIn('lead_owner', ['Siti Afifah', 'Fatimah Nurnabilah', 'Norhaiyati', 'Farah', 'Siti Shahilah']);

        // Define active lead categories using queryBase cloning
        $this->activeLeadsDataAfifah = [
            'Active 24 Below' => (clone $queryBase)
                ->whereIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '0')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->count(),

            'Active 25 Above' => (clone $queryBase)
                ->whereNotIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '0')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->count(),

            'Call Attempt 24 Below' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->whereIn('company_size', ['1-24', '20-24'])
                ->where('categories', '=', 'Active')
                ->count(),

            'Call Attempt 25 Above' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->whereNotIn('company_size', ['1-24', '20-24'])
                ->count(),
        ];

        // Sum up all active lead data for Afifah
        $this->totalActiveLeadsAfifah = array_sum($this->activeLeadsDataAfifah);
    }

    public function fetchTransferLeadsJaja()
    {
        $queryBaseJaja = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBaseJaja->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        // Filter for specific lead owner
        $queryBaseJaja->where('lead_owner', 'Nurul Najaa Nadiah');

        // Fetch transfer lead counts by stage
        $this->transferStagesDataJaja = [
            'Transfer' => (clone $queryBaseJaja)
                ->where('categories', 'Active')
                ->whereNotNull('salesperson')
                ->where('stage', 'Transfer')
                ->count(),

            'Demo' => (clone $queryBaseJaja)
                ->where('categories', 'Active')
                ->whereNotNull('salesperson')
                ->where('stage', 'Demo')
                ->count(),

            'Follow Up' => (clone $queryBaseJaja)
                ->where('categories', 'Active')
                ->whereNotNull('salesperson')
                ->where('stage', 'Follow Up')
                ->count(),
        ];

        // Calculate total transfer-related leads
        $this->totalTransferLeadsJaja = array_sum($this->transferStagesDataJaja);
    }

    public function fetchTransferLeadsSheena()
    {
        $queryBase = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Sheena Liew');

        // Fetch transfer lead counts by stage
        $this->transferStagesDataSheena = [
            'Transfer' => (clone $queryBase)
                ->where('categories', 'Active')
                ->whereNotNull('salesperson')
                ->where('stage', 'Transfer')
                ->count(),

            'Demo' => (clone $queryBase)
                ->where('categories', 'Active')
                ->whereNotNull('salesperson')
                ->where('stage', 'Demo')
                ->count(),

            'Follow Up' => (clone $queryBase)
                ->where('categories', 'Active')
                ->whereNotNull('salesperson')
                ->where('stage', 'Follow Up')
                ->count(),
        ];

        // Calculate total transfer-related leads
        $this->totalTransferLeadsSheena = array_sum($this->transferStagesDataSheena);
    }

    public function fetchTransferLeadsShahilah()
    {
        $queryBase = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Siti Shahilah');

        // Fetch transfer lead counts by stage
        $this->transferStagesDataShahilah = [
            'Transfer' => (clone $queryBase)
                ->where('categories', 'Active')
                ->whereNotNull('salesperson')
                ->where('stage', 'Transfer')
                ->count(),

            'Demo' => (clone $queryBase)
                ->where('categories', 'Active')
                ->whereNotNull('salesperson')
                ->where('stage', 'Demo')
                ->count(),

            'Follow Up' => (clone $queryBase)
                ->where('categories', 'Active')
                ->whereNotNull('salesperson')
                ->where('stage', 'Follow Up')
                ->count(),
        ];

        // Calculate total transfer-related leads
        $this->totalTransferLeadsShahilah = array_sum($this->transferStagesDataShahilah);
    }

    public function fetchTransferLeadsAfifah()
    {
        $queryBaseAfifah = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBaseAfifah->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        // Filter for specific lead owner
        $queryBaseAfifah->whereIn('lead_owner', ['Siti Afifah', 'Fatimah Nurnabilah', 'Norhaiyati', 'Farah', 'Siti Shahilah']);

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
        $queryBaseJaja = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBaseJaja->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        // Filter for specific lead owner
        $queryBaseJaja->where('lead_owner', 'Nurul Najaa Nadiah');

        // Apply additional filters for inactive leads where salesperson is NULL
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

    public function fetchInactiveLeadsSheena()
    {
        $queryBase = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Sheena Liew');

        // Apply additional filters for inactive leads where salesperson is NULL
        $query = (clone $queryBase)
            ->whereIn('lead_status', ['Junk', 'On Hold', 'Lost', 'No Response']); // Filter inactive statuses

        $this->totalInactiveLeadsSheena = $query->count();

        $leadStatusDataRaw = $query
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // Ensure all statuses exist in the correct order (fill missing ones with 0)
        $this->inactiveLeadDataSheena = array_merge([
            'Junk' => 0,
            'On Hold' => 0,
            'Lost' => 0,
            'No Response' => 0
        ], $leadStatusDataRaw);
    }

    public function fetchInactiveLeadsShahilah()
    {
        // Base query for Siti Shahilah
        $queryBase = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Siti Shahilah');

        // Apply additional filters for inactive leads where salesperson is NULL
        $query = (clone $queryBase)
            ->whereIn('lead_status', ['Junk', 'On Hold', 'Lost', 'No Response']); // Filter inactive statuses

        // Count total inactive leads for Shahilah
        $this->totalInactiveLeadsShahilah = $query->count();

        // Fetch grouped lead status counts for Shahilah
        $leadStatusDataRaw = $query
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // Ensure all statuses exist in the correct order (fill missing ones with 0)
        $this->inactiveLeadDataShahilah = array_merge([
            'Junk' => 0,
            'On Hold' => 0,
            'Lost' => 0,
            'No Response' => 0
        ], $leadStatusDataRaw);
    }

    public function fetchInactiveLeadsAfifah()
    {
        // Base query for Siti Afifah
        $queryBaseAfifah = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBaseAfifah->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        // Filter for specific lead owner
        $queryBaseAfifah->whereIn('lead_owner', ['Siti Afifah', 'Fatimah Nurnabilah', 'Norhaiyati', 'Farah', 'Siti Shahilah']);

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

    private function groupLeadsByCompanySize($leads)
    {
        // Group leads by company size
        $groupedLeads = $leads->groupBy(function ($lead) {
            $size = $lead->company_size ?? 'Unknown';

            // Standardize company size grouping
            if ($size == '1-24') {
                return '1-24';
            }elseif ($size == '20-24') {
                return '20-24';
            } elseif ($size == '25-99') {
                return '25-99';
            } elseif ($size == '100-500') {
                return '100-500';
            } elseif ($size == '501 and Above' || $size == '500+') {
                return '501 and Above';
            } else {
                return 'Unknown';
            }
        });

        // Sort groups in a logical order
        $sortOrder = ['1-24','20-24', '25-99', '100-500', '501 and Above', 'Unknown'];
        $sortedGroups = collect();

        foreach ($sortOrder as $size) {
            if ($groupedLeads->has($size)) {
                $sortedGroups[$size] = $groupedLeads[$size];
            }
        }

        return $sortedGroups;
    }

    // Update all the slide-over methods using this function
    public function openLeadBreakdownSlideOver($label)
    {
        $query = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }
        $query->where('lead_status', '!=', 'Closed');

        if ($label === 'New') {
            $query->where('categories', 'New');
        } elseif ($label === 'Jaja') {
            $query->where('lead_owner', 'Nurul Najaa Nadiah');
        } elseif ($label === 'Backup Admin') {
            $query->whereIn('lead_owner', ['Siti Afifah', 'Fatimah Nurnabilah', 'Norhaiyati', 'Farah', 'Siti Shahilah']);
        } elseif ($label === 'Sheena') {
            $query->where('lead_owner', 'Sheena Liew');
        } else {
            $this->leadList = collect(); // empty
            $this->slideOverTitle = 'Invalid lead group';
            $this->showSlideOver = true;
            return;
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "{$label} Leads";
        $this->showSlideOver = true;
    }

    public function openLeadCategorySlideOver($category)
    {
        $query = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $start = $date->startOfMonth()->toDateString();
            $end = $date->endOfMonth()->toDateString();

            $query->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end);
        }

        // Apply specific category filter
        if ($category === 'New') {
            $query->where('categories', 'New');
        } elseif ($category === 'Active') {
            $query->whereNotIn('categories', ['Inactive', 'New'])
                ->whereNull('salesperson');
        } elseif ($category === 'Sales') {
            $query->where('categories', '!=', 'Inactive')
                ->whereNotNull('salesperson');
        } elseif ($category === 'Inactive') {
            $query->where('categories', 'Inactive');
        } else {
            $this->leadList = collect();
            $this->slideOverTitle = 'Invalid Category';
            $this->showSlideOver = true;
            return;
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "{$category} Leads";
        $this->showSlideOver = true;
    }

    public function openJajaLeadCategorySlideOver($category)
    {
        $query = Lead::query()
            ->where('lead_owner', 'Nurul Najaa Nadiah')
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        if ($category === 'Active') {
            $query->where('categories', 'Active')->whereNull('salesperson');
        } elseif ($category === 'Sales') {
            $query->where('categories', 'Active')->whereNotNull('salesperson');
        } elseif ($category === 'Inactive') {
            $query->where('categories', 'Inactive')
                ->where('lead_status', '!=', 'Closed');
        } else {
            $this->leadList = collect();
            $this->slideOverTitle = 'Invalid category';
            $this->showSlideOver = true;
            return;
        }

        // Get leads with company details
        $leads = $query->with('companyDetail')->get();

        // Group leads by company size
        $groupedLeads = $leads->groupBy(function ($lead) {
            $size = $lead->company_size ?? 'Unknown';

            // Standardize company size grouping
            if ($size == '1-24') {
                return '1-24';
            }elseif ($size == '20-24') {
                return '20-24';
            } elseif ($size == '25-99') {
                return '25-99';
            } elseif ($size == '100-500') {
                return '100-500';
            } elseif ($size == '501 and Above' || $size == '500+') {
                return '501 and Above';
            } else {
                return 'Unknown';
            }
        });

        // Sort groups in a logical order
        $sortOrder = ['1-24','20-24', '25-99', '100-500', '501 and Above', 'Unknown'];
        $sortedGroups = collect();

        foreach ($sortOrder as $size) {
            if ($groupedLeads->has($size)) {
                $sortedGroups[$size] = $groupedLeads[$size];
            }
        }
        $this->leadList = $sortedGroups;
        $this->slideOverTitle = "Jaja - {$category} Leads";
        $this->showSlideOver = true;
    }

    public function openAfifahLeadCategorySlideOver($category)
    {
        $query = Lead::query()
            ->whereIn('lead_owner', ['Siti Afifah', 'Fatimah Nurnabilah', 'Norhaiyati', 'Farah', 'Siti Shahilah'])
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        if ($category === 'Active') {
            $query->where('categories', '=', 'Active')
                ->whereNull('salesperson');
        } elseif ($category === 'Sales') {
            $query->where('categories', '=', 'Active')
                ->whereNotNull('salesperson');
        } elseif ($category === 'Inactive') {
            $query->where('categories', 'Inactive')
                ->where('lead_status', '!=', 'Closed');
        } else {
            $this->leadList = collect();
            $this->slideOverTitle = 'Invalid category';
            $this->showSlideOver = true;
            return;
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "{$category} Leads (Backup Admin)";
        $this->showSlideOver = true;
    }

    public function openActiveLeadsJajaSlideOver($label)
    {
        $query = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $query->where('lead_owner', 'Nurul Najaa Nadiah')
            ->where('categories', 'Active')
            ->whereNull('salesperson');

        if ($label === 'Active 24 Below') {
            $query->whereIn('company_size', ['1-24', '20-24'])
                ->where(function ($q) {
                    $q->whereNull('done_call')->orWhere('done_call', 0);
                });
        } elseif ($label === 'Active 25 Above') {
            $query->whereNotIn('company_size', ['1-24', '20-24'])
                ->where(function ($q) {
                    $q->whereNull('done_call')->orWhere('done_call', 0);
                });
        } elseif ($label === 'Call Attempt 24 Below') {
            $query->whereIn('company_size', ['1-24', '20-24'])->where('done_call', 1);
        } elseif ($label === 'Call Attempt 25 Above') {
            $query->whereNotIn('company_size', ['1-24', '20-24'])
                ->where('done_call', 1);
        } else {
            $this->leadList = collect();
            $this->slideOverTitle = 'Invalid Lead Group';
            $this->showSlideOver = true;
            return;
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "Jaja - {$label}";
        $this->showSlideOver = true;
    }

    public function openActiveLeadsAfifahSlideOver($label)
    {
        $query = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $query->whereIn('lead_owner', ['Siti Afifah', 'Fatimah Nurnabilah', 'Norhaiyati', 'Farah', 'Siti Shahilah'])
              ->whereNull('salesperson')
              ->where('categories', '=', 'Active');

        if ($label === 'Active 24 Below') {
            $query->whereIn('company_size', ['1-24', '20-24'])
                  ->where('done_call', '=', '0');
        } elseif ($label === 'Active 25 Above') {
            $query->whereNotIn('company_size', ['1-24', '20-24'])
                  ->where('done_call', '=', '0');
        } elseif ($label === 'Call Attempt 24 Below') {
            $query->whereIn('company_size', ['1-24', '20-24'])
                  ->where('done_call', '=', '1');
        } elseif ($label === 'Call Attempt 25 Above') {
            $query->whereNotIn('company_size', ['1-24', '20-24'])
                  ->where('done_call', '=', '1');
        } else {
            $this->leadList = collect();
            $this->slideOverTitle = 'Invalid status';
            $this->showSlideOver = true;
            return;
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "{$label} (Backup Admin)";
        $this->showSlideOver = true;
    }

    public function openTransferLeadsJajaSlideOver($label)
    {
        $query = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $query->where('lead_owner', 'Nurul Najaa Nadiah')
            ->where('categories', 'Active')
            ->whereNotNull('salesperson')
            ->where('stage', $label);

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "{$label} Leads (Jaja)";
        $this->showSlideOver = true;
    }

    public function openTransferLeadsAfifahSlideOver($stage)
    {
        $query = Lead::query()
            ->whereIn('lead_owner', ['Siti Afifah', 'Fatimah Nurnabilah', 'Norhaiyati', 'Farah', 'Siti Shahilah'])
            ->where('categories', 'Active')
            ->whereNotNull('salesperson')
            ->where('stage', $stage)
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "Backup Admin - $stage Leads";
        $this->showSlideOver = true;
    }

    public function openInactiveLeadsJajaSlideOver($status)
    {
        $query = Lead::query()
            ->where('lead_owner', 'Nurul Najaa Nadiah')
            ->where('lead_status', $status);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "Jaja - {$status} Leads";
        $this->showSlideOver = true;
    }

    public function openInactiveLeadsAfifahSlideOver($status)
    {
        $query = Lead::query()
            ->whereIn('lead_owner', ['Siti Afifah', 'Fatimah Nurnabilah', 'Norhaiyati',' Farah', 'Siti Shahilah'])
            ->where('lead_status', $status)
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "Backup Admin - {$status} Leads";
        $this->showSlideOver = true;
    }

    public function openSheenaLeadCategorySlideOver($category)
    {
        $query = Lead::query()
            ->where('lead_owner', 'Sheena Liew')
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        if ($category === 'Active') {
            $query->where('categories', '=', 'Active')
                ->whereNull('salesperson');
        } elseif ($category === 'Sales') {
            $query->where('categories', '=', 'Active')
                ->whereNotNull('salesperson');
        } elseif ($category === 'Inactive') {
            $query->where('categories', 'Inactive')
                ->where('lead_status', '!=', 'Closed');
        } else {
            $this->leadList = collect();
            $this->slideOverTitle = 'Invalid category';
            $this->showSlideOver = true;
            return;
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "{$category} Leads (Sheena)";
        $this->showSlideOver = true;
    }

    public function openActiveLeadsSheenaSlideOver($label)
    {
        $query = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $query->where('lead_owner', 'Sheena Liew')
            ->whereNull('salesperson')
            ->where('categories', '=', 'Active');

        if ($label === 'Active 24 Below') {
            $query->whereIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '0');
        } elseif ($label === 'Active 25 Above') {
            $query->whereNotIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '0');
        } elseif ($label === 'Call Attempt 24 Below') {
            $query->whereIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '1');
        } elseif ($label === 'Call Attempt 25 Above') {
            $query->whereNotIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '1');
        } else {
            $this->leadList = collect();
            $this->slideOverTitle = 'Invalid status';
            $this->showSlideOver = true;
            return;
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "{$label} (Sheena)";
        $this->showSlideOver = true;
    }

    public function openTransferLeadsSheenaSlideOver($stage)
    {
        $query = Lead::query()
            ->where('lead_owner', 'Sheena Liew')
            ->where('categories', 'Active')
            ->whereNotNull('salesperson')
            ->where('stage', $stage)
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "Sheena - $stage Leads";
        $this->showSlideOver = true;
    }

    public function openInactiveLeadsSheenaSlideOver($status)
    {
        $query = Lead::query()
            ->where('lead_owner', 'Sheena Liew')
            ->where('lead_status', $status);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "Sheena - {$status} Leads";
        $this->showSlideOver = true;
    }

    public function openShahilahLeadCategorySlideOver($category)
    {
        $query = Lead::query()
            ->where('lead_owner', 'Siti Shahilah')
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        if ($category === 'Active') {
            $query->where('categories', '=', 'Active')
                ->whereNull('salesperson');
        } elseif ($category === 'Sales') {
            $query->where('categories', '=', 'Active')
                ->whereNotNull('salesperson');
        } elseif ($category === 'Inactive') {
            $query->where('categories', 'Inactive')
                ->where('lead_status', '!=', 'Closed');
        } else {
            $this->leadList = collect();
            $this->slideOverTitle = 'Invalid category';
            $this->showSlideOver = true;
            return;
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "{$category} Leads (Shahilah)";
        $this->showSlideOver = true;
    }

    public function openActiveLeadsShahilahSlideOver($label)
    {
        $query = Lead::query()
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $query->where('lead_owner', 'Siti Shahilah')
            ->whereNull('salesperson')
            ->where('categories', '=', 'Active');

        if ($label === 'Active 24 Below') {
            $query->whereIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '0');
        } elseif ($label === 'Active 25 Above') {
            $query->whereNotIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '0');
        } elseif ($label === 'Call Attempt 24 Below') {
            $query->whereIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '1');
        } elseif ($label === 'Call Attempt 25 Above') {
            $query->whereNotIn('company_size', ['1-24', '20-24'])
                ->where('done_call', '=', '1');
        } else {
            $this->leadList = collect();
            $this->slideOverTitle = 'Invalid status';
            $this->showSlideOver = true;
            return;
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "{$label} (Shahilah)";
        $this->showSlideOver = true;
    }

    public function openTransferLeadsShahilahSlideOver($stage)
    {
        $query = Lead::query()
            ->where('lead_owner', 'Siti Shahilah')
            ->where('categories', 'Active')
            ->whereNotNull('salesperson')
            ->where('stage', $stage)
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "Shahilah - $stage Leads";
        $this->showSlideOver = true;
    }

    public function openInactiveLeadsShahilahSlideOver($status)
    {
        $query = Lead::query()
            ->where('lead_owner', 'Siti Shahilah')
            ->where('lead_status', $status)
            ->whereNotIn('lead_code', ['Apollo', 'Existing Customer (Migration)']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $leads = $query->with('companyDetail')->get();
        $this->leadList = $this->groupLeadsByCompanySize($leads);
        $this->slideOverTitle = "Shahilah - {$status} Leads";
        $this->showSlideOver = true;
    }
}
