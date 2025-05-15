<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MonthlyCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Calendar';
    protected static ?string $navigationLabel = "Monthly Calendar";

    protected static string $view = 'filament.pages.monthly-calendar';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.monthly-calendar');
    }
}
