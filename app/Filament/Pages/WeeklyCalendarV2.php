<?php

namespace App\Filament\Pages;

use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\View;

class WeeklyCalendarV2 extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Calendar';
    protected static ?string $navigationLabel = "Weekly Calendar V2";

    protected static string $view = 'filament.pages.weekly-calendar-v2';

    public function getTitle(): string | Htmlable
    {
        return __("");
    }
}
