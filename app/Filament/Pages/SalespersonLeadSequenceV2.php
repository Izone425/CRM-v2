<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;

class SalespersonLeadSequenceV2 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.salesperson-lead-sequence-v2';
    protected static ?string $navigationLabel = 'Salesperson Audit V2';
    protected static ?string $title = '';

    // All salespersons combined (previously rank1 and rank2)
    public $allSalespersons = [];
    public $sizes = ['1-24', '25-99', '100-500', '501 and Above'];

    public $demoStats = [];
    public $rfqStats = [];
    public $salespersonNames = [];

    public $latestDemoInfo = null;
    public $latestRfqInfo = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.salesperson-lead-sequence-v2');
    }

    public function mount()
    {
        // Combine all salespersons from both ranks
        $allSalespersonNames = [
            'Vince Leong',
            'Wan Amirul Muim',
            'Joshua Ho',
            'Muhammad Khoirul Bariah',
            'Abdul Aziz',
            'Yasmin',
            'Farhanah Jamil'
        ];

        $this->allSalespersons = User::whereIn('name', $allSalespersonNames)
            ->orderByRaw("FIELD(name, '" . implode("','", $allSalespersonNames) . "')")
            ->pluck('id')
            ->toArray();

        // Map user IDs to names for display
        $this->salespersonNames = User::whereIn('id', $this->allSalespersons)
            ->pluck('name', 'id')
            ->toArray();

        // Define the start date
        $startDate = Carbon::parse('2025-06-30');

        $this->fetchDemoStats($startDate);
        $this->fetchRfqStats($startDate);

        // Latest Demo (combined)
        $latestDemo = Appointment::query()
            ->whereIn('salesperson', $this->allSalespersons)
            ->whereIn('status', ['New', 'Done'])
            ->whereHas('lead', function($q) use ($startDate) {
                $q->where('created_at', '>=', $startDate);
            })
            ->latest('date')
            ->first();

        if ($latestDemo) {
            $salespersonName = $this->salespersonNames[$latestDemo->salesperson] ?? $latestDemo->salesperson;
            $companyName = optional($latestDemo->lead)->companyDetail->company_name ?? 'N/A';
            $this->latestDemoInfo = [
                'salesperson' => $salespersonName,
                'company' => $companyName,
                'date' => $latestDemo->date,
            ];
        }

        // Latest RFQ (combined)
        $latestRfqLog = ActivityLog::query()
            ->whereRaw("LOWER(description) LIKE ?", ['%rfq only%'])
            ->whereIn('properties->attributes->salesperson', $this->allSalespersons)
            // ->whereHas('subject', function($q) use ($startDate) {
            //     $q->where('created_at', '>=', $startDate);
            // })
            ->latest('created_at')
            ->first();

        if ($latestRfqLog) {
            $properties = is_string($latestRfqLog->properties)
                ? json_decode($latestRfqLog->properties, true)
                : $latestRfqLog->properties;
            $spId = $properties['attributes']['salesperson'] ?? null;
            $leadId = $properties['attributes']['id'] ?? null;
            $salespersonName = $this->salespersonNames[$spId] ?? $spId;
            $companyName = 'N/A';
            if ($leadId) {
                $companyDetail = \App\Models\CompanyDetail::where('lead_id', $leadId)->first();
                $companyName = $companyDetail ? $companyDetail->company_name : 'N/A';
            }
            $this->latestRfqInfo = [
                'salesperson' => $salespersonName,
                'company' => $companyName,
                'date' => $latestRfqLog->created_at,
            ];
        }
    }

    private function fetchDemoStats($startDate)
    {
        // Get all user IDs with role_id 1
        $causerIds = User::where('role_id', 1)->pluck('id')->toArray();

        $stats = [];
        foreach ($this->allSalespersons as $spId) {
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
        $this->demoStats = $stats;
    }

    private function fetchRfqStats($startDate)
    {
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

        foreach ($this->allSalespersons as $spId) {
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
        $this->rfqStats = $stats;
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
            'allSalespersons' => $this->allSalespersons,
            'sizes' => $this->sizes,
            'demoStats' => $this->demoStats,
            'rfqStats' => $this->rfqStats,
            'salespersonNames' => $this->salespersonNames,
            'latestDemoInfo' => $this->latestDemoInfo,
            'latestRfqInfo' => $this->latestRfqInfo,
        ];
    }
}
