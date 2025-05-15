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
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.demo-ranking');
    }

    protected static string $view = 'filament.pages.demo-ranking';
}
