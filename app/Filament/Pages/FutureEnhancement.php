<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class FutureEnhancement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    // This should match exactly where your view file is located
    protected static string $view = 'filament.pages.future-enhancement';

    protected static ?string $title = '';

    // Hide from navigation menu since it's only accessed via sidebar links
    protected static bool $shouldRegisterNavigation = false;
}
