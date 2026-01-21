<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HrAccountManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static string $view = 'filament.pages.hr-account-management';
    protected static ?string $navigationLabel = 'Account Management';
    protected static ?string $title = 'Account Management';
    protected static ?int $navigationSort = 2;

    // Hide from navigation (accessed via sidebar only)
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-account-management';

    public function mount()
    {
        // Initialize any data needed for the page
    }
}
