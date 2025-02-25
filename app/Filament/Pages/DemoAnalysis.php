<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use App\Filament\Widgets\LeadChartWidget;

class DemoAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.lead-analysis';
    protected static ?string $navigationLabel = 'Demo Analysis';
    protected static ?string $title = 'Demo Analysis';
    protected static ?int $navigationSort = 8;
}
