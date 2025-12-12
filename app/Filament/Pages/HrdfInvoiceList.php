<?php

namespace App\Filament\Pages;

use App\Models\CrmHrdfInvoice;
use App\Models\SoftwareHandover;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class HrdfInvoiceList extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'HRDF Invoices';
    protected static ?string $title = 'HRDF Invoice List';
    protected static string $view = 'filament.pages.hrdf-invoice-list';

    public function table(Table $table): Table
    {
        return $table
            ->query(CrmHrdfInvoice::query()->with('handover'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->color('primary'),

                TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->company_name),

                TextColumn::make('handover_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'SW' => 'success',
                        'HW' => 'warning',
                        'RW' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'SW' => 'Software',
                        'HW' => 'Hardware',
                        'RW' => 'Renewal',
                        default => $state,
                    }),

                TextColumn::make('handover_id')
                    ->label('Handover ID')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->handover && $record->handover->formatted_handover_id) {
                            return $record->handover->formatted_handover_id;
                        }
                        return 'SW_' . str_pad($state, 6, '0', STR_PAD_LEFT);
                    })
                    ->color('info'),

                TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('debtor_code')
                    ->label('Debtor Code')
                    ->searchable(),

                TextColumn::make('total_amount')
                    ->label('Amount (RM)')
                    ->money('MYR')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('handover_type')
                    ->label('Handover Type')
                    ->options([
                        'SW' => 'Software',
                        'HW' => 'Hardware',
                        'RW' => 'Renewal',
                    ])
                    ->multiple(),

                SelectFilter::make('salesperson')
                    ->label('Salesperson')
                    ->options(function () {
                        return CrmHrdfInvoice::distinct()
                            ->pluck('salesperson', 'salesperson')
                            ->filter()
                            ->toArray();
                    })
                    ->searchable()
                    ->multiple(),
            ])
            ->defaultPaginationPageOption(50)
            ->paginated([50, 100]);
    }
}
