<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Lead;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\DB;

class SalesAdminAnalysisV1 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-numbered-list';
    protected static ?string $navigationGroup = 'Analysis';
    protected static ?string $navigationLabel = 'Sales Admin Analysis V1';
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.sales-admin-analysis-v1';

    public $selectedMonth;

    public $totalLeads = 0;
    public $newLeads = 0;
    public $jajaLeads = 0;
    public $afifahLeads = 0;
    public $shahilahLeads = 0;
    public $noneLeads = 0;

    public $newPercentage = 0;
    public $jajaPercentage = 0;
    public $afifahPercentage = 0;
    public $shahilahPercentage = 0;
    public $nonePercentage = 0;
    public $categoriesData = [];
    public $companySizeData = [];
    public $totalActiveLeads = 0;
    public $stagesData = [];
    public $activeLeadsData = [];

    public $totalTransferLeads = 0;
    public $transferStagesData = [];

    public $totalInactiveLeads = 0;
    public $inactiveLeadData = [];

    public Carbon $currentDate;

    public $showSlideOver = false;
    public $leadList = [];
    public $slideOverTitle = 'Leads';

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
        $this->fetchLeadsByCompanySize();
        $this->fetchActiveLeads();
        $this->fetchTransferLead();
        $this->fetchInactiveLead();
    }

    public function updatedSelectedMonth($month)
    {
        $this->selectedMonth = $month;
        session(['selectedMonth' => $month]);

        $this->fetchLeads();
        $this->fetchLeadsByCategory();
        $this->fetchLeadsByCompanySize();
        $this->fetchActiveLeads();
        $this->fetchTransferLead();
        $this->fetchInactiveLead();
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
        $this->totalLeads = $leads->count();
        $this->newLeads = $leads->where('categories', 'New')->count();
        $this->jajaLeads = $leads->where('lead_owner', 'Nurul Najaa Nadiah')->count();
        $this->afifahLeads = $leads->where('lead_owner', 'Siti Afifah')->count();
        $this->shahilahLeads = $leads->where('lead_owner', 'Siti Shahilah')->count();
        $this->noneLeads = $leads->whereNull('lead_owner')->whereNotNull('salesperson')->count();

        $this->newPercentage = $this->totalLeads > 0 ? round(($this->newLeads / $this->totalLeads) * 100, 2) : 0;
        $this->jajaPercentage = $this->totalLeads > 0 ? round(($this->jajaLeads / $this->totalLeads) * 100, 2) : 0;
        $this->afifahPercentage = $this->totalLeads > 0 ? round(($this->afifahLeads / $this->totalLeads) * 100, 2) : 0;
        $this->shahilahPercentage = $this->totalLeads > 0 ? round(($this->shahilahLeads / $this->totalLeads) * 100, 2) : 0;
        $this->nonePercentage = $this->totalLeads > 0 ? round(($this->noneLeads / $this->totalLeads) * 100, 2) : 0; 
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
                ->whereNull('lead_owner')
                ->whereNull('salesperson')
                ->when($start && $end, fn ($q) => $q->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end))
                ->count(),

            'Active' => Lead::query()
                ->whereNull('salesperson')
                ->whereNotNull('lead_owner')
                ->where('categories', '!=', 'Inactive')
                ->when($start && $end, fn ($q) => $q->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end))
                ->count(),

            'Sales' => Lead::query()
                ->whereNotNull('salesperson')
                ->where('categories', '!=', 'Inactive')
                ->when($start && $end, fn ($q) => $q->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end))
                ->count(),

            'Inactive' => Lead::query()
                ->where('categories', 'Inactive')
                ->when($start && $end, fn ($q) => $q->whereDate('created_at', '>=', $start)->whereDate('created_at', '<=', $end))
                ->count(),
        ];
    }

    public function fetchLeadsByCompanySize()
    {
        $query = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);

            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                  ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());            
        }

        // Define default company size labels
        $defaultCompanySizes = [
            'Small' => 0,
            'Medium' => 0,
            'Large' => 0,
            'Enterprise' => 0,
        ];

        // Fetch leads and count based on the company size label
        $companySizeCounts = $query->get()
            ->groupBy(fn ($lead) => $lead->company_size_label)
            ->map(fn ($group) => $group->count())
            ->toArray();

        // Merge default sizes with actual data to ensure all labels exist
        $this->companySizeData = array_merge($defaultCompanySizes, $companySizeCounts);
    }

    public function fetchActiveLeads()
    {
        $start = null;
        $end = null;

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $start = $date->startOfMonth()->toDateString();
            $end = $date->endOfMonth()->toDateString();
        }

        // Define active lead categories with safe date filtering
        $this->activeLeadsData = [
            'Active 24 Below' => Lead::query()
                ->where('company_size', '=', '1-24')
                ->whereNull('salesperson')
                ->whereNotNull('lead_owner')
                ->where('categories', '!=', 'Inactive')
                ->where(function ($query) {
                    $query->whereNull('done_call')
                        ->orWhere('done_call', 0);
                })
                ->when($start && $end, fn ($query) =>
                    $query->whereDate('created_at', '>=', $start)
                        ->whereDate('created_at', '<=', $end)
                )
                ->count(),

            'Active 25 Above' => Lead::query()
                ->where('company_size', '!=', '1-24')
                ->whereNull('salesperson')
                ->whereNotNull('lead_owner')
                ->where('categories', '!=', 'Inactive')
                ->where(function ($query) {
                    $query->whereNull('done_call')
                        ->orWhere('done_call', 0);
                })
                ->when($start && $end, fn ($query) =>
                    $query->whereDate('created_at', '>=', $start)
                        ->whereDate('created_at', '<=', $end)
                )
                ->count(),

            'Call Attempt 24 Below' => Lead::query()
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->whereNotNull('lead_owner')
                ->where('company_size', '=', '1-24')
                ->where('categories', '!=', 'Inactive')
                ->when($start && $end, fn ($query) =>
                    $query->whereDate('created_at', '>=', $start)
                        ->whereDate('created_at', '<=', $end)
                )
                ->count(),

            'Call Attempt 25 Above' => Lead::query()
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->whereNotNull('lead_owner')
                ->whereBetween('call_attempt', [1, 10])
                ->where('company_size', '!=', '1-24')
                ->where('categories', '!=', 'Inactive')
                ->when($start && $end, fn ($query) =>
                    $query->whereDate('created_at', '>=', $start)
                        ->whereDate('created_at', '<=', $end)
                )
                ->count(),
        ];

        // Calculate the total
        $this->totalActiveLeads = array_sum($this->activeLeadsData);
    }

    public function fetchTransferLead()
    {
        $query = Lead::query()
            ->whereNotNull('salesperson') // Ensure salesperson is NOT NULL
            ->whereIn('stage', ['Transfer', 'Demo', 'Follow Up']); // Filter stages

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $start = $date->startOfMonth()->toDateString();
            $end = $date->endOfMonth()->toDateString();

            $query->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end);
        }

        // Count total transfer-related leads
        $this->totalTransferLeads = $query->count();

        // Fetch grouped stage counts
        $stagesDataRaw = $query
            ->select('stage', DB::raw('COUNT(*) as total'))
            ->groupBy('stage')
            ->pluck('total', 'stage')
            ->toArray();

        // Ensure all stages exist in the correct order (fill missing ones with 0)
        $this->transferStagesData = array_merge(
            ['Transfer' => 0, 'Demo' => 0, 'Follow Up' => 0],
            $stagesDataRaw
        );
    }

    public function fetchInactiveLead()
    {
        $query = Lead::query()
            ->whereIn('lead_status', ['Junk', 'Lost', 'On Hold', 'No Response']);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $start = $date->startOfMonth()->toDateString();
            $end = $date->endOfMonth()->toDateString();

            $query->whereDate('created_at', '>=', $start)
                ->whereDate('created_at', '<=', $end);
        }

        // Count total inactive leads
        $this->totalInactiveLeads = $query->count();

        // Fetch grouped lead status counts
        $leadStatusDataRaw = $query
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // Fill in missing statuses with 0
        $this->inactiveLeadData = array_merge([
            'Junk' => 0,
            'Lost' => 0,
            'On Hold' => 0,
            'No Response' => 0
        ], $leadStatusDataRaw);
    }

    public function openLeadOwnerSlideOver($label)
    {
        $user = Auth::user();
    
        $query = Lead::query();
    
        // Apply owner filter based on label
        switch ($label) {
            case 'Jaja':
                $query->where('lead_owner', 'Nurul Najaa Nadiah');
                $this->slideOverTitle = 'Leads for Jaja';
                break;
            case 'Afifah':
                $query->where('lead_owner', 'Siti Afifah');
                $this->slideOverTitle = 'Leads for Afifah';
                break;
            case 'Shahilah':
                $query->where('lead_owner', 'Siti Shahilah');
                $this->slideOverTitle = 'Leads for Shahilah';
                break;
            case 'None':
                $query->whereNull('lead_owner')
                    ->whereNotNull('salesperson');
                $this->slideOverTitle = 'Unassigned Leads';
                break;
            case 'New':
                $query->where('categories', 'New');
                $this->slideOverTitle = 'New Leads';
                break;
            default:
                $this->leadList = collect();
                $this->slideOverTitle = 'Unknown Category';
                $this->showSlideOver = true;
                return;
        }
    
        // Apply month filter
        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('created_at', [
                $date->startOfMonth()->format('Y-m-d'),
                $date->endOfMonth()->format('Y-m-d'),
            ]);
        }
    
        // Get results with relationship
        $this->leadList = $query->with('companyDetail')->get();
        $this->showSlideOver = true;
    }    

    public function openCategorySlideOver($category)
    {
        $query = Lead::query();

        // Map each category to the correct filtering logic
        switch ($category) {
            case 'New':
                $query->where('categories', 'New')
                    ->whereNull('lead_owner')
                    ->whereNull('salesperson');
                $this->slideOverTitle = 'New Leads';
                break;

            case 'Active':
                $query->whereNull('salesperson')
                    ->whereNotNull('lead_owner')
                    ->where('categories', '!=', 'Inactive');
                $this->slideOverTitle = 'Active Leads';
                break;

            case 'Sales':
                $query->whereNotNull('salesperson')
                    ->where('categories', '!=', 'Inactive');
                $this->slideOverTitle = 'Sales Leads';
                break;

            case 'Inactive':
                $query->where('categories', 'Inactive');
                $this->slideOverTitle = 'Inactive Leads';
                break;

            default:
                $this->leadList = collect();
                $this->slideOverTitle = 'Unknown Category';
                $this->showSlideOver = true;
                return;
        }

        // Filter by selected month
        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $this->leadList = $query->with('companyDetail')->get();
        $this->showSlideOver = true;
    }

    public function openCompanySizeSlideOver($label)
    {
        $user = Auth::user();

        // Map label to actual `company_size` values
        $sizeMap = [
            'Small' => '1-24',
            'Medium' => '25-99',
            'Large' => '100-500',
            'Enterprise' => '501 and Above',
        ];

        $companySize = $sizeMap[$label] ?? null;

        if (!$companySize) {
            $this->leadList = collect(); // empty fallback
            $this->slideOverTitle = 'Unknown Company Size';
            $this->showSlideOver = true;
            return;
        }

        $query = Lead::where('company_size', $companySize);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = ucfirst($label) . ' Company Leads';
        $this->showSlideOver = true;
    }

    public function openActiveStageSlideOver($label)
    {
        $query = Lead::query()
            ->whereNull('salesperson')
            ->whereNotNull('lead_owner')
            ->where('categories', '!=', 'Inactive');

        // Apply label-specific filters
        switch ($label) {
            case 'Active 24 Below':
                $query->where('company_size', '1-24')
                    ->where(function ($query) {
                        $query->whereNull('done_call')
                                ->orWhere('done_call', 0);
                    });
                break;

            case 'Active 25 Above':
                $query->where('company_size', '!=', '1-24')
                    ->where(function ($query) {
                        $query->whereNull('done_call')
                                ->orWhere('done_call', 0);
                    });
                break;

            case 'Call Attempt 24 Below':
                $query->where('company_size', '1-24')
                    ->where('done_call', 1);
                break;

            case 'Call Attempt 25 Above':
                $query->where('company_size', '!=', '1-24')
                    ->where('done_call', 1)
                    ->whereBetween('call_attempt', [1, 10]);
                break;

            default:
                $this->leadList = collect();
                $this->slideOverTitle = 'Unknown Stage';
                $this->showSlideOver = true;
                return;
        }

        // Apply selected month filtering
        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = $label . ' Leads';
        $this->showSlideOver = true;
    }

    public function openTransferStageSlideOver($stage)
    {
        $query = Lead::query()
            ->whereNotNull('salesperson')
            ->where('stage', $stage);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "$stage Stage Leads";
        $this->showSlideOver = true;
    }

    public function openInactiveStatusSlideOver($status)
    {
        $query = Lead::query()
            ->where('lead_status', $status);

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereDate('created_at', '>=', $date->startOfMonth()->toDateString())
                ->whereDate('created_at', '<=', $date->endOfMonth()->toDateString());
        }

        $this->leadList = $query->with('companyDetail')->get();
        $this->slideOverTitle = "$status Inactive Leads";
        $this->showSlideOver = true;
    }
}
