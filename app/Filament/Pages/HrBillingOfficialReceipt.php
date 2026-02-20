<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HrBillingOfficialReceipt extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static string $view = 'filament.pages.hr-billing-official-receipt';
    protected static ?string $navigationLabel = 'Official Receipt';
    protected static ?string $title = 'Official Receipt';
    protected static ?int $navigationSort = 8;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-billing-official-receipt';
}
