<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;

class SalespersonLeadSequence extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.salesperson-lead-sequence';
    protected static ?string $navigationLabel = 'Salesperson Audit List';
    protected static ?string $title = '';

    // Store user IDs for rank1 and rank2
    public $rank1 = [12, 6, 9];
    public $rank2 = [11, 10, 7, 8];
    public $sizes = ['1-24', '25-99', '100-500', '501 and Above'];

    public $rank1DemoStats = [];
    public $rank2DemoStats = [];
    public $rank1RfqStats = [];
    public $rank2RfqStats = [];
    public $salespersonNames = [];

    public $latestDemoInfoRank1 = null;
    public $latestDemoInfoRank2 = null;
    public $latestRfqInfoRank1 = null;
    public $latestRfqInfoRank2 = null;

    public function mount()
    {
        // Find user IDs for rank1 and rank2 by name
        $rank1Names = ['Vince Leong', 'Wan Amirul Muim', 'Joshua Ho']; // Already in correct order
        $rank2Names = ['Muhammad Khoirul Bariah', 'Abdul Aziz', 'Yasmin', 'Farhanah Jamil']; // Already in correct order

        $this->rank1 = User::whereIn('name', $rank1Names)
            ->orderByRaw("FIELD(name, 'Vince Leong', 'Wan Amirul Muim', 'Joshua Ho')")
            ->pluck('id')
            ->toArray();

        $this->rank2 = User::whereIn('name', $rank2Names)
            ->orderByRaw("FIELD(name, 'Muhammad Khoirul Bariah', 'Abdul Aziz', 'Yasmin', 'Farhanah Jamil')")
            ->pluck('id')
            ->toArray();

        // Map user IDs to names for display
        $allIds = array_merge($this->rank1, $this->rank2);
        $this->salespersonNames = User::whereIn('id', $allIds)->pluck('name', 'id')->toArray();

        // Define the start date
        $startDate = Carbon::parse('2025-06-30');

        $this->fetchDemoStats($startDate);
        $this->fetchRfqStats($startDate);

        // Latest Demo for Rank 1
        $latestDemoRank1 = \App\Models\Appointment::query()
            ->whereIn('salesperson', $this->rank1)
            ->whereIn('status', ['New', 'Done'])
            ->whereHas('lead', function($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate);
            })
            ->latest('date')
            ->first();

        if ($latestDemoRank1) {
            $salespersonName = $this->salespersonNames[$latestDemoRank1->salesperson] ?? $latestDemoRank1->salesperson;
            $companyName = optional($latestDemoRank1->lead)->companyDetail->company_name ?? 'N/A';
            $this->latestDemoInfoRank1 = [
                'salesperson' => $salespersonName,
                'company' => $companyName,
                'date' => $latestDemoRank1->date,
            ];
        }

        // Latest Demo for Rank 2
        $latestDemoRank2 = \App\Models\Appointment::query()
            ->whereIn('salesperson', $this->rank2)
            ->whereIn('status', ['New', 'Done'])
            ->whereHas('lead', function($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate);
            })
            ->latest('date')
            ->first();

        if ($latestDemoRank2) {
            $salespersonName = $this->salespersonNames[$latestDemoRank2->salesperson] ?? $latestDemoRank2->salesperson;
            $companyName = optional($latestDemoRank2->lead)->companyDetail->company_name ?? 'N/A';
            $this->latestDemoInfoRank2 = [
                'salesperson' => $salespersonName,
                'company' => $companyName,
                'date' => $latestDemoRank2->date,
            ];
        }

        // Latest RFQ for Rank 1
        $latestRfqLogRank1 = \Spatie\Activitylog\Models\Activity::query()
            ->whereRaw("LOWER(description) LIKE ?", ['%rfq only%'])
            ->whereIn('properties->attributes->salesperson', $this->rank1)
            ->whereHas('subject', function($q) use ($startDate) {
                // Assuming subject is the Lead model
                $q->where('created_at', '>=', $startDate);
            })
            ->latest('created_at')
            ->first();

        if ($latestRfqLogRank1) {
            $properties = is_string($latestRfqLogRank1->properties)
                ? json_decode($latestRfqLogRank1->properties, true)
                : $latestRfqLogRank1->properties;
            $spId = $properties['attributes']['salesperson'] ?? null;
            $leadId = $properties['attributes']['id'] ?? null;
            $salespersonName = $this->salespersonNames[$spId] ?? $spId;
            $companyName = 'N/A';
            if ($leadId) {
                $companyDetail = \App\Models\CompanyDetail::where('lead_id', $leadId)->first();
                $companyName = $companyDetail ? $companyDetail->company_name : 'N/A';
            }
            $this->latestRfqInfoRank1 = [
                'salesperson' => $salespersonName,
                'company' => $companyName,
                'date' => $latestRfqLogRank1->created_at,
            ];
        }

        // Latest RFQ for Rank 2
        $latestRfqLogRank2 = \Spatie\Activitylog\Models\Activity::query()
            ->whereRaw("LOWER(description) LIKE ?", ['%rfq only%'])
            ->whereIn('properties->attributes->salesperson', $this->rank2)
            ->whereHas('subject', function($q) use ($startDate) {
                // Assuming subject is the Lead model
                $q->where('created_at', '>=', $startDate);
            })
            ->latest('created_at')
            ->first();

        if ($latestRfqLogRank2) {
            $properties = is_string($latestRfqLogRank2->properties)
                ? json_decode($latestRfqLogRank2->properties, true)
                : $latestRfqLogRank2->properties;
            $spId = $properties['attributes']['salesperson'] ?? null;
            $leadId = $properties['attributes']['id'] ?? null;
            $salespersonName = $this->salespersonNames[$spId] ?? $spId;
            $companyName = 'N/A';
            if ($leadId) {
                $companyDetail = \App\Models\CompanyDetail::where('lead_id', $leadId)->first();
                $companyName = $companyDetail ? $companyDetail->company_name : 'N/A';
            }
            $this->latestRfqInfoRank2 = [
                'salesperson' => $salespersonName,
                'company' => $companyName,
                'date' => $latestRfqLogRank2->created_at,
            ];
        }
    }

    private function fetchDemoStats($startDate)
    {
        // Get all user IDs with role_id 1
        $causerIds = \App\Models\User::where('role_id', 1)->pluck('id')->toArray();

        foreach (['rank1', 'rank2'] as $rank) {
            $salespersons = $this->$rank;
            $stats = [];
            foreach ($salespersons as $spId) {
                foreach ($this->sizes as $size) {
                    $count = Appointment::query()
                        ->whereIn('status', ['New', 'Done'])
                        ->where('salesperson', $spId)
                        ->whereHas('lead', function($q) use ($size, $startDate) {
                            $q->where('company_size', $size)
                            ->where('created_at', '>=', $startDate);
                        })
                        ->whereIn('causer_id', $causerIds)
                        ->count();
                    $stats[$spId][$size] = $count;
                }
            }
            $this->{$rank . 'DemoStats'} = $stats;
        }
    }

    private function fetchRfqStats($startDate)
    {
        foreach (['rank1', 'rank2'] as $rank) {
            $salespersons = $this->$rank;
            $stats = [];

            // First, get all leads created on or after the start date
            $eligibleLeadIds = \App\Models\Lead::where('created_at', '>=', $startDate)
                ->pluck('id')
                ->toArray();

            // Then get RFQ logs for those leads only
            $logs = ActivityLog::query()
                ->whereRaw("LOWER(description) LIKE ?", ['%rfq only%'])
                ->where(function($query) use ($eligibleLeadIds) {
                    $query->whereIn('subject_id', $eligibleLeadIds)
                        ->where('subject_type', 'App\\Models\\Lead');
                })
                ->get();

            foreach ($salespersons as $spId) {
                foreach ($this->sizes as $size) {
                    $count = $logs->filter(function($log) use ($spId, $size) {
                        $properties = is_string($log->properties)
                            ? json_decode($log->properties, true)
                            : $log->properties;
                        $salesperson = $properties['attributes']['salesperson'] ?? null;
                        $companySize = $properties['attributes']['company_size'] ?? null;
                        return (string)$salesperson === (string)$spId && (string)$companySize === (string)$size;
                    })->count();
                    $stats[$spId][$size] = $count;
                }
            }
            $this->{$rank . 'RfqStats'} = $stats;
        }
    }

    private function getSalespersonColor($salesperson)
    {
        return match($salesperson) {
            'Vince Leong' => [59, 130, 246],           // blue
            'Wan Amirul Muim' => [16, 185, 129],       // green
            'Joshua Ho' => [245, 158, 11],             // yellow
            'Muhammad Khoirul Bariah' => [236, 72, 153], // pink
            'Abdul Aziz' => [139, 92, 246],            // purple
            'Yasmin' => [239, 68, 68],                 // red
            'Farhanah Jamil' => [34, 197, 94],         // light green
            default => [107, 114, 128],                // gray
        };
    }

    protected function getViewData(): array
    {
        return [
            'rank1' => $this->rank1,
            'rank2' => $this->rank2,
            'sizes' => $this->sizes,
            'rank1DemoStats' => $this->rank1DemoStats,
            'rank2DemoStats' => $this->rank2DemoStats,
            'rank1RfqStats' => $this->rank1RfqStats,
            'rank2RfqStats' => $this->rank2RfqStats,
            'salespersonNames' => $this->salespersonNames,
            'latestDemoInfoRank1' => $this->latestDemoInfoRank1,
            'latestDemoInfoRank2' => $this->latestDemoInfoRank2,
            'latestRfqInfoRank1' => $this->latestRfqInfoRank1,
            'latestRfqInfoRank2' => $this->latestRfqInfoRank2,
        ];
    }
}
