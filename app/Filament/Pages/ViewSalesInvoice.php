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
    public ?float $total = null;
    public ?string $currency = null;
    public ?string $status = null;
    public ?string $invoiceDate = null;
    public ?string $dueDate = null;
    public ?string $from = null;

    public function mount(): void
    {
        $this->quotationId = Request::query('quotationId') ? (int) Request::query('quotationId') : null;
        $this->softwareHandoverId = Request::query('softwareHandoverId') ? (int) Request::query('softwareHandoverId') : null;
        $this->invoiceNo = Request::query('invoiceNo') ? (string) Request::query('invoiceNo') : null;
        $this->total = Request::query('total') ? (float) Request::query('total') : null;
        $this->currency = Request::query('currency') ? (string) Request::query('currency') : null;
        $this->status = Request::query('status') ? (string) Request::query('status') : null;
        $this->invoiceDate = Request::query('invoiceDate') ? (string) Request::query('invoiceDate') : null;
        $this->dueDate = Request::query('dueDate') ? (string) Request::query('dueDate') : null;
        $this->from = Request::query('from') ? (string) Request::query('from') : null;
    }

    public function getTitle(): string
    {
        return 'Sales Invoice';
    }

    public function getBreadcrumbs(): array
    {
        if ($this->from === 'billing') {
            return [
                url('/admin/hr-billing-sales-invoice') => 'Sales of Invoice',
                '#' => 'Sales Invoice',
            ];
        }

        if ($this->from === 'expiring-invoices') {
            return [
                url('/admin/hr-billing-expiring-invoices') => 'Expiring Invoices',
                '#' => 'Sales Invoice',
            ];
        }

        if ($this->from === 'official-receipt') {
            return [
                url('/admin/hr-billing-official-receipt') => 'Official Receipt',
                '#' => 'Sales Invoice',
            ];
        }

        $breadcrumbs = [
            url('/admin/hr-license') => 'All Licenses',
        ];

        if ($this->softwareHandoverId) {
            $tab = $this->from === 'products' ? 'products' : 'invoice';
            $breadcrumbs[url('/admin/hr-company-license-details?softwareHandoverId=' . $this->softwareHandoverId . '&tab=' . $tab)] = 'Company Details';
        }

        $breadcrumbs['#'] = 'Sales Invoice';

        return $breadcrumbs;
    }
}
