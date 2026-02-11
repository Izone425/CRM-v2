<?php

namespace App\Filament\Pages;

use App\Models\Quotation;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Request;

class EditSalesInvoice extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.edit-sales-invoice';
    protected static ?string $title = 'Edit Sales Invoice';
    protected static ?string $slug = 'edit-sales-invoice';
    protected static bool $shouldRegisterNavigation = false;

    public ?int $quotationId = null;
    public ?int $softwareHandoverId = null;
    public ?string $invoiceNo = null;

    public function mount(): void
    {
        $this->quotationId = Request::query('quotationId') ? (int) Request::query('quotationId') : null;
        $this->softwareHandoverId = Request::query('softwareHandoverId') ? (int) Request::query('softwareHandoverId') : null;

        if ($this->quotationId) {
            $quotation = Quotation::find($this->quotationId);
            $this->invoiceNo = $quotation?->quotation_reference_no;
        }
    }

    public function getTitle(): string
    {
        return $this->invoiceNo
            ? 'Edit Invoice » ' . $this->invoiceNo
            : 'Edit Sales Invoice';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
