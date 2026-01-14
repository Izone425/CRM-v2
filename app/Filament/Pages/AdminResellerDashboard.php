<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class AdminResellerDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.admin-reseller-dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        return false; // Hide from main navigation since we're using custom sidebar
    }
}
