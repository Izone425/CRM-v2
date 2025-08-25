<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ImplementerCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static string $view = 'filament.pages.implementer-calendar';
    protected static ?string $navigationGroup = 'Calendar';
    protected static ?string $navigationLabel = "Weekly Calendar V1";


    public function getTitle(): string | Htmlable
    {
        return __("");
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.implementer-calendar');
    }

    public function mount(): void {}
}
