<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Filament\Pages\Page;

class SalespersonAuditList extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.salesperson-audit-list';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = '';

    public $rank1 = ['Vince', 'Muim', 'Joshua'];
    public $rank2 = ['Bari', 'Aziz', 'Yasmin', 'Farhanah'];
    public $sizes = ['1-24', '25-99', '100-500', '501 and Above'];

    public $rank1DemoStats = [];
    public $rank2DemoStats = [];
    public $rank1RfqStats = [];
    public $rank2RfqStats = [];

    public function mount()
    {
        $this->fetchDemoStats();
        $this->fetchRfqStats();
    }

    private function fetchDemoStats()
    {
        foreach (['rank1', 'rank2'] as $rank) {
            $salespersons = $this->$rank;
            $stats = [];
            foreach ($salespersons as $sp) {
                foreach ($this->sizes as $size) {
                    $count = Appointment::query()
                        ->whereIn('status', ['New', 'Done'])
                        ->where('salesperson', $sp)
                        ->whereHas('lead', function($q) use ($size) {
                            $q->where('company_size', $size);
                        })
                        ->count();
                    $stats[$sp][$size] = $count;
                }
            }
            $this->{$rank . 'DemoStats'} = $stats;
        }
    }

    private function fetchRfqStats()
    {
        foreach (['rank1', 'rank2'] as $rank) {
            $salespersons = $this->$rank;
            $stats = [];
            $logs = ActivityLog::query()
                ->where('description', 'like', '%RFQ only%')
                ->get();
            foreach ($salespersons as $sp) {
                foreach ($this->sizes as $size) {
                    $count = $logs->filter(function($log) use ($sp, $size) {
                        $salesperson = $log->properties['attributes']['salesperson'] ?? null;
                        $companySize = $log->properties['attributes']['company_size'] ?? null;
                        return $salesperson === $sp && $companySize === $size;
                    })->count();
                    $stats[$sp][$size] = $count;
                }
            }
            $this->{$rank . 'RfqStats'} = $stats;
        }
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
        ];
    }
}
