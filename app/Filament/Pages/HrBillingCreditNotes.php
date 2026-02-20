<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HrBillingCreditNotes extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-minus';
    protected static string $view = 'filament.pages.hr-billing-credit-notes';
    protected static ?string $navigationLabel = 'Credit Notes';
    protected static ?string $title = 'Credit Notes';
    protected static ?int $navigationSort = 12;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-billing-credit-notes';
}
