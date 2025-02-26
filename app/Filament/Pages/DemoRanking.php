<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class DemoRanking extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationGroup = 'Calendar';
    protected static ?string $navigationLabel = "Demo Ranking";

    public static function canAccess(): bool
    {
        return auth()->user()->role_id == '3' || auth()->user()->id == 1 || auth()->user()->id == 25;
    }

    protected static string $view = 'filament.pages.demo-ranking';
}
