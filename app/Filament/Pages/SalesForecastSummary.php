<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class SalesForecastSummary extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.sales-forecast-summary';
    protected static ?string $navigationLabel = 'Forecast Analysis';
    protected static ?string $navigationGroup = 'Sales Forecast';
    protected static ?string $title = '';
    protected static ?string $slug = 'forecast-analysis';
    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.forecast-analysis');
    }
}
