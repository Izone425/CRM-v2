<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HrDevices extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-device-phone-mobile';
    protected static string $view = 'filament.pages.hr-devices';
    protected static ?string $navigationLabel = 'Devices';
    protected static ?string $title = 'Terminal Devices';
    protected static ?int $navigationSort = 4;

    // Hide from navigation (accessed via sidebar only)
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-devices';
}
