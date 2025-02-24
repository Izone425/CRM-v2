<?php

namespace App\Filament\Pages;

use App\Enums\QuotationStatusEnum;
use Filament\Pages\Page;
use Filament\Tables\Table;

use App\Models\Quotation;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Tables\Columns\TextColumn;

class ProformaInvoices extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.proforma-invoices';

    // public static function canAccess(): bool
    // {
    //     return auth()->user()->role_id != '2';
    // }
}
