<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class RankingFormPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.ranking-form-page';

    protected static ?string $slug = "calendar/ranking-form";

    protected static ?string $title = 'Ranking Page';
    protected static bool $shouldRegisterNavigation = false;
}
