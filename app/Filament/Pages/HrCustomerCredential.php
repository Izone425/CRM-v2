<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HrCustomerCredential extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static string $view = 'filament.pages.hr-customer-credential';
    protected static ?string $navigationLabel = 'Customer Credential';
    protected static ?string $title = 'Customer Credential';
    protected static ?int $navigationSort = 5;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-customer-credential';
}
