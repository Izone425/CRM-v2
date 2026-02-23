<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Request;

class ViewOfficialReceipt extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.view-official-receipt';
    protected static ?string $title = 'Official Receipt';
    protected static ?string $slug = 'view-official-receipt';
    protected static bool $shouldRegisterNavigation = false;

    public ?string $orNo = null;
    public ?string $from = null;

    public function mount(): void
    {
        $this->orNo = Request::query('orNo') ? (string) Request::query('orNo') : null;
        $this->from = Request::query('from') ? (string) Request::query('from') : null;
    }

    public function getTitle(): string
    {
        return 'Official Receipt';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin/hr-billing-official-receipt') => 'Official Receipt',
            '#' => $this->orNo ?? 'View',
        ];
    }
}
