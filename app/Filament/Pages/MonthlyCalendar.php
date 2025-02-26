<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class MonthlyCalendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Calendar';
    protected static ?string $navigationLabel = "Monthly Calendar";

    protected static string $view = 'filament.pages.monthly-calendar';
}
