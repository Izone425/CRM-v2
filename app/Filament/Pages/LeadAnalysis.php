<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use App\Filament\Widgets\LeadChartWidget;

class LeadAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static string $view = 'filament.pages.lead-analysis';
    protected static ?string $navigationLabel = 'Lead Analysis';
    protected static ?string $title = 'Lead Analysis';
    protected static ?int $navigationSort = 8;
}
