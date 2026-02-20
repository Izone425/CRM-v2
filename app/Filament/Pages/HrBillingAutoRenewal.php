<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HrBillingAutoRenewal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static string $view = 'filament.pages.hr-billing-auto-renewal';
    protected static ?string $navigationLabel = 'Auto Renewal';
    protected static ?string $title = 'Auto Renewal';
    protected static ?int $navigationSort = 11;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-billing-auto-renewal';
}
