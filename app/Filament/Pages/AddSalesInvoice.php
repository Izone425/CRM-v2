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

    // Prefill params for dummy invoice editing
    public ?string $prefillInvoiceNo = null;
    public ?float $prefillTotal = null;
    public ?string $prefillCurrency = null;
    public ?string $prefillInvoiceDate = null;
    public ?float $prefillTaxRate = null;
    public ?string $prefillDescription = null;

    public function mount(): void
    {
        $this->softwareHandoverId = Request::query('softwareHandoverId') ? (int) Request::query('softwareHandoverId') : null;

        // Extract prefill params (passed when editing a dummy invoice)
        $this->prefillInvoiceNo = Request::query('prefillInvoiceNo') ? (string) Request::query('prefillInvoiceNo') : null;
        $this->prefillTotal = Request::query('prefillTotal') !== null ? (float) Request::query('prefillTotal') : null;
        $this->prefillCurrency = Request::query('prefillCurrency') ? (string) Request::query('prefillCurrency') : null;
        $this->prefillInvoiceDate = Request::query('prefillInvoiceDate') ? (string) Request::query('prefillInvoiceDate') : null;
        $this->prefillTaxRate = Request::query('prefillTaxRate') !== null ? (float) Request::query('prefillTaxRate') : null;
        $this->prefillDescription = Request::query('prefillDescription') ? (string) Request::query('prefillDescription') : null;
    }

    public function getTitle(): string
    {
        if ($this->prefillInvoiceNo) {
            return 'Edit Invoice >> ' . $this->prefillInvoiceNo;
        }
        return 'Add Sales Invoice';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
