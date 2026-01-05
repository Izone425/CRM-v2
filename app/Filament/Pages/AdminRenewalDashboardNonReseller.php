<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AdminRenewalDashboardNonReseller extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.adminrenewalnonreseller';
    protected static ?string $title = 'Admin Renewal Dashboard - End User';
    protected static ?string $slug = 'admin-renewal-dashboard-non-reseller';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hide from main navigation since we're using custom sidebar
    }
}
