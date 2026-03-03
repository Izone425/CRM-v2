<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HrBillingSalesInvoice extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.hr-billing-sales-invoice';
    protected static ?string $navigationLabel = 'Sales Invoice';
    protected static ?string $title = 'Sales of Invoice';
    protected static ?int $navigationSort = 6;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-billing-sales-invoice';

    public static function canAccess(): bool
    {
        return auth()->user()?->hasRouteAccess('filament.admin.pages.hr-billing-sales-invoice') ?? false;
    }
}
