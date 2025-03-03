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
    protected static ?string $title = 'Sales Admin Analysis V1';
    protected static string $view = 'filament.pages.sales-admin-analysis-v1';

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

    public function mount()
    {
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
            $query->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        $leads = $query->get();
        $this->totalLeads = $leads->count();
        $this->newLeads = $leads->where('categories', 'New')->count();
        $this->jajaLeads = $leads->where('lead_owner', '[ADMIN] JAJA')->count();
        $this->afifahLeads = $leads->where('lead_owner', '[ADMIN] AFIFAH')->count();

        $this->newPercentage = $this->totalLeads > 0 ? round(($this->newLeads / $this->totalLeads) * 100, 2) : 0;
        $this->jajaPercentage = $this->totalLeads > 0 ? round(($this->jajaLeads / $this->totalLeads) * 100, 2) : 0;
        $this->afifahPercentage = $this->totalLeads > 0 ? round(($this->afifahLeads / $this->totalLeads) * 100, 2) : 0;
    }

    public function fetchLeadsByCategory()
    {
        $query = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        $categories = ['New', 'Active', 'Sales', 'Inactive'];

        // Count New Leads (Only leads where categories = 'New')
        $newCount = Lead::where('categories', 'New')
            ->where('lead_owner', null)
            ->where('salesperson', null)->count();

        // Define Active Leads (Salesperson must be NULL, lead_owner must be NOT NULL, and done_call must be NULL or 0)
        $activeCount = Lead::whereNull('salesperson')
            ->whereNotNull('lead_owner')
            ->where('categories', '!=', 'Inactive')
            ->where(function ($query) {
                $query->whereNull('done_call')
                    ->orWhere('done_call', 0);
            })
            ->count();

        // Define Sales Leads (Salesperson must be NOT NULL and categories must not be Inactive)
        $salesCount = Lead::whereNotNull('salesperson')
            ->where('categories', '!=', 'Inactive')
            ->count();

        // Define Inactive Leads (Only leads where categories = 'Inactive')
        $inactiveCount = Lead::where('categories', 'Inactive')->count();

        // Ensure all categories exist, even if zero
        $this->categoriesData = array_merge(
            array_fill_keys($categories, 0),
            [
                'New' => $newCount,
                'Active' => $activeCount,
                'Sales' => $salesCount,
                'Inactive' => $inactiveCount,
            ]
        );
    }

    public function fetchLeadsByCompanySize()
    {
        $query = Lead::query();

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
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
        $dateRange = null;

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $dateRange = [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')];
        }

        // Define active lead categories with month filter applied
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
                ->when($dateRange, function ($query) use ($dateRange) {
                    return $query->whereBetween('created_at', $dateRange);
                })
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
                ->when($dateRange, function ($query) use ($dateRange) {
                    return $query->whereBetween('created_at', $dateRange);
                })
                ->count(),

            'Call Attempt 24 Below' => Lead::query()
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->where('company_size', '=', '1-24')
                ->where('categories', '!=', 'Inactive')
                ->when($dateRange, function ($query) use ($dateRange) {
                    return $query->whereBetween('created_at', $dateRange);
                })
                ->count(),

            'Call Attempt 25 Above' => Lead::query()
                ->where('done_call', '=', '1')
                ->whereNull('salesperson')
                ->whereBetween('call_attempt', [1, 10])
                ->where('categories', '!=', 'Inactive')
                ->where('company_size', '!=', '1-24')
                ->when($dateRange, function ($query) use ($dateRange) {
                    return $query->whereBetween('created_at', $dateRange);
                })
                ->count(),
        ];

        // Sum up all active lead data to get totalActiveLeads
        $this->totalActiveLeads = array_sum($this->activeLeadsData);
    }

    public function fetchTransferLead()
    {
        $query = Lead::query()
            ->whereNotNull('salesperson') // Ensure salesperson is NOT NULL
            ->whereIn('stage', ['Transfer', 'Demo', 'Follow Up']); // Filter stages

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
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
        $this->transferStagesData = array_merge(['Transfer' => 0, 'Demo' => 0, 'Follow Up' => 0], $stagesDataRaw);
    }

    public function fetchInactiveLead()
    {
        $query = Lead::query()
            ->whereIn('lead_status', ['Junk', 'On Hold', 'Lost', 'No Response']); // Filter inactive statuses

        if (!empty($this->selectedMonth)) {
            $date = Carbon::parse($this->selectedMonth);
            $query->whereBetween('created_at', [$date->startOfMonth()->format('Y-m-d'), $date->endOfMonth()->format('Y-m-d')]);
        }

        // Count total inactive leads
        $this->totalInactiveLeads = $query->count();

        // Fetch grouped lead status counts
        $leadStatusDataRaw = $query
            ->select('lead_status', DB::raw('COUNT(*) as total'))
            ->groupBy('lead_status')
            ->pluck('total', 'lead_status')
            ->toArray();

        // Ensure all statuses exist in the correct order (fill missing ones with 0)
        $this->inactiveLeadData = array_merge([
            'Junk' => 0,
            'On Hold' => 0,
            'Lost' => 0,
            'No Response' => 0
        ], $leadStatusDataRaw);
    }
}
