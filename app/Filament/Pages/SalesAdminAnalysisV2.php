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

    public $showSlideOver = false;
    public $slideOverTitle = 'Leads';
    public $leadList = [];

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

            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());            
        } 

        $leads = $query->get();
        $this->totalLeads = $leads
            ->filter(fn ($lead) => $lead->lead_owner !== null && $lead->lead_owner !== 'Chee Chan')
            ->count();
        $this->newLeads = $leads->where('categories', 'New')->count();
        $this->jajaLeads = $leads->where('lead_owner', 'Nurul Najaa Nadiah')->count();
        $this->afifahLeads = $leads->where('lead_owner', 'Siti Afifah')->count();

        $this->newPercentage = $this->totalLeads > 0 ? round(($this->newLeads / $this->totalLeads) * 100, 2) : 0;
        $this->jajaPercentage = $this->totalLeads > 0 ? round(($this->jajaLeads / $this->totalLeads) * 100, 2) : 0;
        $this->afifahPercentage = $this->totalLeads > 0 ? round(($this->afifahLeads / $this->totalLeads) * 100, 2) : 0;
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
                ->whereNull('salesperson')
                ->whereNotNull('lead_owner')
                ->where('lead_owner', '!=', 'Chee Chan')
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
                ->when($start && $end, fn ($query) =>
                    $query->whereDate('created_at', '>=', $start)
                        ->whereDate('created_at', '<=', $end)
                )
                ->count(),

            'Inactive' => Lead::query()
                ->where('categories', 'Inactive')
                ->whereNotNull('lead_owner')
                ->where('lead_owner', '!=', 'Chee Chan')
                ->when($start && $end, fn ($query) =>
                    $query->whereDate('created_at', '>=', $start)
                        ->whereDate('created_at', '<=', $end)
                )
                ->count(),
        ];
    }

    public function fetchLeadsByAdminJaja()
    {
        $queryBase = Lead::query();

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

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());            
        } 

        $leadCategories = ['Active', 'Sales', 'Inactive'];

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Siti Afifah');

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

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());            
        } 

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Nurul Najaa Nadiah');

        // Define active lead categories using queryBase cloning
        $this->activeLeadsDataJaja = [
            'Active 24 Below' => (clone $queryBase)
                ->where('company_size', '=', '1-24')
                ->where('done_call', '=', '0')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->count(),

            'Active 25 Above' => (clone $queryBase)
                ->where('company_size', '!=', '1-24')
                ->where('done_call', '=', '0')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->count(),

            'Call Attempt 24 Below' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->where('company_size', '=', '1-24')
                ->where('categories', '=', 'Active')
                ->count(),

            'Call Attempt 25 Above' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
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

            $queryBase->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());            
        } 

        // Filter for specific lead owner
        $queryBase->where('lead_owner', 'Siti Afifah');

        // Define active lead categories using queryBase cloning
        $this->activeLeadsDataAfifah = [
            'Active 24 Below' => (clone $queryBase)
                ->where('company_size', '=', '1-24')
                ->where('done_call', '=', '0')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->count(),

            'Active 25 Above' => (clone $queryBase)
                ->where('company_size', '!=', '1-24')
                ->where('done_call', '=', '0')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
                ->count(),

            'Call Attempt 24 Below' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->where('company_size', '=', '1-24')
                ->where('categories', '=', 'Active')
                ->count(),

            'Call Attempt 25 Above' => (clone $queryBase)
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->where('categories', '=', 'Active')
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

    public function fetchTransferLeadsAfifah()
    {
        $queryBaseAfifah = Lead::query();
        
        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBaseAfifah->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());            
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

    public function fetchInactiveLeadsAfifah()
    {
        // Base query for Siti Afifah
        $queryBaseAfifah = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $queryBaseAfifah->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());            
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

    public function openLeadBreakdownSlideOver($label)
    {
        $query = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        if ($label === 'New') {
            $query->where('categories', 'New');
        } elseif ($label === 'Jaja') {
            $query->where('lead_owner', 'Nurul Najaa Nadiah');
        } elseif ($label === 'Afifah') {
            $query->where('lead_owner', 'Siti Afifah');
        } else {
            $this->leadList = collect(); // empty
            $this->slideOverTitle = 'Invalid lead group';
            $this->showSlideOver = true;
            return;
        }

        // Only count valid lead_owner values like in fetchLeads
        $query->whereNotNull('lead_owner')->where('lead_owner', '!=', 'Chee Chan');

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "{$label} Leads";
        $this->showSlideOver = true;
    }

    public function openLeadCategorySlideOver($category)
    {
        $query = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $start = $date->startOfMonth()->toDateString();
            $end = $date->endOfMonth()->toDateString();

            $query->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end);
        }

        // Apply shared filters
        $query->whereNotNull('lead_owner')->where('lead_owner', '!=', 'Chee Chan');

        // Apply specific category filter
        if ($category === 'New') {
            $query->where('categories', 'New')
                ->whereNull('salesperson');
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

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "{$category} Leads";
        $this->showSlideOver = true;
    }

    public function openJajaLeadCategorySlideOver($category)
    {
        $query = Lead::query()
            ->where('lead_owner', 'Nurul Najaa Nadiah');

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
            $query->where('categories', 'Inactive');
        } else {
            $this->leadList = collect();
            $this->slideOverTitle = 'Invalid category';
            $this->showSlideOver = true;
            return;
        }

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "Jaja - {$category} Leads";
        $this->showSlideOver = true;
    }

    public function openAfifahLeadCategorySlideOver($category)
    {
        $query = Lead::query()
            ->where('lead_owner', 'Siti Afifah');

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
            $query->where('categories', '=', 'Inactive');
        } else {
            $this->leadList = collect();
            $this->slideOverTitle = 'Invalid category';
            $this->showSlideOver = true;
            return;
        }

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "{$category} Leads (Afifah)";
        $this->showSlideOver = true;
    }

    public function openActiveLeadsJajaSlideOver($label)
    {
        $query = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $query->where('lead_owner', 'Nurul Najaa Nadiah')
            ->where('categories', 'Active')
            ->whereNull('salesperson');

        if ($label === 'Active 24 Below') {
            $query->where('company_size', '1-24')
                ->where(function ($q) {
                    $q->whereNull('done_call')->orWhere('done_call', 0);
                });
        } elseif ($label === 'Active 25 Above') {
            $query->where('company_size', '!=', '1-24')
                ->where(function ($q) {
                    $q->whereNull('done_call')->orWhere('done_call', 0);
                });
        } elseif ($label === 'Call Attempt 24 Below') {
            $query->where('company_size', '1-24')->where('done_call', 1);
        } elseif ($label === 'Call Attempt 25 Above') {
            $query->where('company_size', '!=', '1-24')
                ->where('done_call', 1);
        } else {
            $this->leadList = collect();
            $this->slideOverTitle = 'Invalid Lead Group';
            $this->showSlideOver = true;
            return;
        }

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "Jaja - {$label}";
        $this->showSlideOver = true;
    }

    public function openActiveLeadsAfifahSlideOver($label)
    {
        $query = Lead::query();
    
        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }
    
        $query->where('lead_owner', 'Siti Afifah')
              ->whereNull('salesperson')
              ->where('categories', '=', 'Active');
    
        if ($label === 'Active 24 Below') {
            $query->where('company_size', '1-24')
                  ->where('done_call', '=', '0');
        } elseif ($label === 'Active 25 Above') {
            $query->where('company_size', '!=', '1-24')
                  ->where('done_call', '=', '0');
        } elseif ($label === 'Call Attempt 24 Below') {
            $query->where('company_size', '1-24')
                  ->where('done_call', '=', '1');
        } elseif ($label === 'Call Attempt 25 Above') {
            $query->where('company_size', '!=', '1-24')
                  ->where('done_call', '=', '1');
        } else {
            $this->leadList = collect();
            $this->slideOverTitle = 'Invalid status';
            $this->showSlideOver = true;
            return;
        }
    
        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "{$label} (Afifah)";
        $this->showSlideOver = true;
    }
    
    public function openTransferLeadsJajaSlideOver($label)
    {
        $query = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $query->where('lead_owner', 'Nurul Najaa Nadiah')
            ->where('categories', 'Active')
            ->whereNotNull('salesperson')
            ->where('stage', $label);

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "{$label} Leads (Jaja)";
        $this->showSlideOver = true;
    }

    public function openTransferLeadsAfifahSlideOver($stage)
    {
        $query = Lead::query()
            ->where('lead_owner', 'Siti Afifah')
            ->where('categories', 'Active')
            ->whereNotNull('salesperson')
            ->where('stage', $stage);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "Afifah - $stage Leads";
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

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "Jaja - {$status} Leads";
        $this->showSlideOver = true;
    }

    public function openInactiveLeadsAfifahSlideOver($status)
    {
        $query = Lead::query()
            ->where('lead_owner', 'Siti Afifah')
            ->where('lead_status', $status);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "Afifah - {$status} Leads";
        $this->showSlideOver = true;
    }
}
