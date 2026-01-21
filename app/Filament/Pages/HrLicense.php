<?php

namespace App\Filament\Pages;

use App\Models\HrLicense as HrLicenseModel;
use Filament\Pages\Page;

class HrLicense extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-check';
    protected static string $view = 'filament.pages.hr-license';
    protected static ?string $navigationLabel = 'License';
    protected static ?string $title = 'All Licenses';
    protected static ?int $navigationSort = 3;

    // Hide from navigation (accessed via sidebar only)
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'hr-license';
}
