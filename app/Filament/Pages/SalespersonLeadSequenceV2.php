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
        // Updated salesperson sequence to match your exact image
        $allSalespersonNames = [
            'Wan Amirul Muim',
            'Joshua Ho',
            'Abdul Aziz',
            'Muhammad Khoirul Bariah',
            'Vince Leong',
            'SalesPerson 6', // This might need to be adjusted based on actual name in DB
            'Yasmin',
            'Farhanah Jamil'
        ];

        // ✅ Get existing salespersons from database
        $existingSalespersons = User::whereIn('name', $allSalespersonNames)
            ->orderByRaw("FIELD(name, '" . implode("','", $allSalespersonNames) . "')")
            ->get();

        // ✅ Create array with both existing and placeholder IDs
        $this->allSalespersons = [];
        $this->salespersonNames = [];

        foreach ($allSalespersonNames as $name) {
            $user = $existingSalespersons->firstWhere('name', $name);

            if ($user) {
                // Real user exists
                $this->allSalespersons[] = $user->id;
                $this->salespersonNames[$user->id] = $user->name;
            } else {
                // Create placeholder for missing salesperson
                $placeholderId = 'placeholder_' . strtolower(str_replace(' ', '_', $name));
                $this->allSalespersons[] = $placeholderId;
                $this->salespersonNames[$placeholderId] = $name;
            }
        }

        // Define the start date
        $startDate = Carbon::parse('2025-12-08');

        $this->fetchDemoStats($startDate);
        $this->fetchRfqStats($startDate);

        // ✅ Latest Demo (only for real users)
        $realUserIds = array_filter($this->allSalespersons, function($id) {
            return !is_string($id) || !str_starts_with($id, 'placeholder_');
        });

        $latestDemo = Appointment::query()
            ->whereIn('salesperson', $realUserIds)
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

        // ✅ Latest RFQ (only for real users)
        $eligibleLeadIds = \App\Models\Lead::where('created_at', '>=', $startDate)
            ->pluck('id')
            ->toArray();

        $latestRfqLog = ActivityLog::query()
            ->whereRaw("LOWER(description) LIKE ?", ['%rfq only%'])
            ->whereIn('properties->attributes->salesperson', $realUserIds)
            ->whereIn('subject_id', $eligibleLeadIds)
            ->where('subject_type', 'App\\Models\\Lead')
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
                // ✅ Skip queries for placeholder IDs
                if (is_string($spId) && str_starts_with($spId, 'placeholder_')) {
                    $stats[$spId][$size] = 0;
                    continue;
                }

                $count = Appointment::query()
                    ->whereIn('status', ['New', 'Done'])
                    ->where('salesperson', $spId)
                    ->whereHas('lead', function($q) use ($size, $startDate) {
                        $q->where('company_size', $size);
                    })
                    ->where('created_at', '>=', $startDate)
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

        $logs = ActivityLog::query()
            ->whereRaw("LOWER(description) LIKE ?", ['%rfq only%'])
            ->where('subject_type', 'App\\Models\\Lead')
            ->where('created_at', '>=', $startDate)
            ->get();

        foreach ($this->allSalespersons as $spId) {
            foreach ($this->sizes as $size) {
                // ✅ Skip queries for placeholder IDs
                if (is_string($spId) && str_starts_with($spId, 'placeholder_')) {
                    $stats[$spId][$size] = 0;
                    continue;
                }

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
            'Wan Amirul Muim' => [16, 185, 129],       // green
            'Joshua Ho' => [245, 158, 11],             // yellow
            'Abdul Aziz' => [139, 92, 246],            // purple
            'Muhammad Khoirul Bariah' => [236, 72, 153], // pink
            'Vince Leong' => [59, 130, 246],           // blue
            'SalesPerson 6' => [168, 85, 247],         // violet
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
