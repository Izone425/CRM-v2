<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

class SearchLead extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Search Lead';
    protected static ?string $title = 'Search Lead';
    protected static string $view = 'filament.pages.search-lead';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.search-lead');
    }
}
