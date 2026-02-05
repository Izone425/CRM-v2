<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Request;

class ViewSalesInvoice extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.view-sales-invoice';
    protected static ?string $title = 'Sales Invoice';
    protected static ?string $slug = 'view-sales-invoice';
    protected static bool $shouldRegisterNavigation = false;

    public ?int $quotationId = null;
    public ?int $softwareHandoverId = null;
    public ?string $invoiceNo = null;

    public function mount(): void
    {
        $this->quotationId = Request::query('quotationId') ? (int) Request::query('quotationId') : null;
        $this->softwareHandoverId = Request::query('softwareHandoverId') ? (int) Request::query('softwareHandoverId') : null;
        $this->invoiceNo = Request::query('invoiceNo') ? (string) Request::query('invoiceNo') : null;
    }

    public function getTitle(): string
    {
        return 'Sales Invoice';
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            url('/admin/hr-license') => 'All Licenses',
        ];

        if ($this->softwareHandoverId) {
            $breadcrumbs[url('/admin/hr-company-license-details?softwareHandoverId=' . $this->softwareHandoverId . '&tab=invoice')] = 'Company Details';
        }

        $breadcrumbs['#'] = 'Sales Invoice';

        return $breadcrumbs;
    }
}
