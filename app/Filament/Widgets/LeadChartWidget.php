<?php
namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class LeadChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Lead Status Distribution';

    protected function getData(): array
    {
        $data = Lead::select('lead_status', DB::raw('count(*) as count'))
            ->groupBy('lead_status')
            ->pluck('count', 'lead_status')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Leads',
                    'data' => array_values($data),
                ],
            ],
            'labels' => array_keys($data),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut'; // Supports 'bar', 'line', 'doughnut', 'pie', etc.
    }
}

