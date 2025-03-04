<?php
namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use App\Filament\Widgets\LeadChartWidget;

class SalesForecast extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string $view = 'filament.pages.sales-forecast';
    protected static ?string $navigationLabel = 'Sales Forecast';
    protected static ?string $title = 'Sales Forecast';
    protected static ?int $navigationSort = 9;
}
