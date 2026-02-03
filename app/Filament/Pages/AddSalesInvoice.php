<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Request;

class AddSalesInvoice extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-plus';
    protected static string $view = 'filament.pages.add-sales-invoice';
    protected static ?string $title = 'Add Sales Invoice';
    protected static ?string $slug = 'add-sales-invoice';
    protected static bool $shouldRegisterNavigation = false;

    public ?int $softwareHandoverId = null;

    public function mount(): void
    {
        $this->softwareHandoverId = Request::query('softwareHandoverId') ? (int) Request::query('softwareHandoverId') : null;
    }

    public function getTitle(): string
    {
        return 'Add Sales Invoice';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
