<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HrBillingCommission extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static string $view = 'filament.pages.hr-billing-commission';
    protected static ?string $navigationLabel = 'Commission';
    protected static ?string $title = 'Commission';
    protected static ?int $navigationSort = 9;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-billing-commission';
}
