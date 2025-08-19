<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\View;

class SalespersonCalendarV2 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Calendar';
    protected static ?string $navigationLabel = "Weekly Calendar V2";

    protected static string $view = 'filament.pages.salesperson-calendar-v2';

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

        return $user->hasRouteAccess('filament.admin.pages.salesperson-calendar-v2');
    }
}
