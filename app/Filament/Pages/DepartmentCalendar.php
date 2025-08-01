<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class DepartmentCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static string $view = 'filament.pages.department-calendar';
    protected static ?string $navigationGroup = 'Calendar';
    protected static ?string $navigationLabel = "All Department Calendar";
    protected static ?int $navigationSort = 5;

    public function getTitle(): string | Htmlable
    {
        return __("");
    }

    public function mount(): void {}
}
