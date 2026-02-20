<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HrBilling extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static string $view = 'filament.pages.hr-billing';
    protected static ?string $navigationLabel = 'Billing';
    protected static ?string $title = 'Billing';
    protected static ?int $navigationSort = 5;

    // Hide from navigation (accessed via sidebar only)
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-billing';
}
