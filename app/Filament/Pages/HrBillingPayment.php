<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HrBillingPayment extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static string $view = 'filament.pages.hr-billing-payment';
    protected static ?string $navigationLabel = 'Payment';
    protected static ?string $title = 'Payment';
    protected static ?int $navigationSort = 10;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-billing-payment';
}
