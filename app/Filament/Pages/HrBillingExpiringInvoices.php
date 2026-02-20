<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HrBillingExpiringInvoices extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clock';
    protected static string $view = 'filament.pages.hr-billing-expiring-invoices';
    protected static ?string $navigationLabel = 'Expiring Invoices';
    protected static ?string $title = 'Expiring Invoices';
    protected static ?int $navigationSort = 7;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-billing-expiring-invoices';
}
