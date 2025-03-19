<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class SalesForecastSummary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.sales-forecast-summary';
    protected static ?string $navigationLabel = 'Sales Forecast Summary';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        return auth()->user()->role_id != '2';
    }
}
