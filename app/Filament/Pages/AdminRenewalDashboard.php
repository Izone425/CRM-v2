<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AdminRenewalDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static string $view = 'filament.pages.adminrenewal';
    protected static ?string $title = 'Admin Renewal Dashboard - Reseller';
    protected static ?string $slug = 'admin-renewal-dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hide from main navigation since we're using custom sidebar
    }
}
