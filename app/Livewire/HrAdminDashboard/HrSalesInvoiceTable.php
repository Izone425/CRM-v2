<?php

namespace App\Livewire\HrAdminDashboard;

use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Livewire\Component;
use App\Models\HrSalesInvoice;

class HrSalesInvoiceTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(HrSalesInvoice::query())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'PENDING' => 'Pending',
                        'PAID' => 'Paid',
                        'CANCEL' => 'Cancel',
                    ])
                    ->placeholder('All Invoices'),
            ])
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('Invoice No')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->size('sm')
                    ->url(fn (HrSalesInvoice $record) => url("/admin/view-sales-invoice?" . http_build_query([
                        'invoiceNo' => $record->invoice_no,
                        'softwareHandoverId' => $record->software_handover_id,
                        'from' => 'billing',
                    ]))),

                TextColumn::make('invoice_date')
                    ->label('Date')
                    ->sortable()
                    ->date('Y-m-d')
                    ->size('sm'),

                TextColumn::make('company_name')
                    ->label('Company')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->color('primary')
                    ->size('sm')
                    ->url(fn (HrSalesInvoice $record) => $record->handover_id
                        ? url("/admin/hr-company-license-details?" . http_build_query([
                            'handoverId' => $record->handover_id,
                            'softwareHandoverId' => $record->software_handover_id,
                        ]))
                        : null),

                TextColumn::make('country')
                    ->label('Country')
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('reseller')
                    ->label('Reseller')
                    ->sortable()
                    ->searchable()
                    ->color('primary')
                    ->wrap()
                    ->size('sm')
                    ->placeholder('-')
                    ->url(fn (HrSalesInvoice $record) => $record->reseller_handover_id
                        ? url("/admin/hr-company-license-details?" . http_build_query([
                            'handoverId' => $record->reseller_handover_id,
                            'softwareHandoverId' => $record->reseller_software_handover_id,
                        ]))
                        : null),

                TextColumn::make('sales_amount')
                    ->label('Sales Amt')
                    ->sortable()
                    ->wrap()
                    ->size('sm')
                    ->formatStateUsing(fn ($record) => $record->sales_amount
                        ? $record->currency . ' ' . number_format($record->sales_amount, 2)
                        : '-'),

                TextColumn::make('commission')
                    ->label('Commission')
                    ->sortable()
                    ->wrap()
                    ->size('sm')
                    ->formatStateUsing(fn ($record) => $record->commission
                        ? $record->currency . ' ' . number_format($record->commission, 2)
                        : '-'),

                TextColumn::make('pi_no')
                    ->label('PI No.')
                    ->sortable()
                    ->searchable()
                    ->size('sm')
                    ->placeholder('-'),

                TextColumn::make('invoice_amount')
                    ->label('Inv. Amount')
                    ->sortable()
                    ->wrap()
                    ->size('sm')
                    ->formatStateUsing(fn ($record) => $record->invoice_amount
                        ? $record->currency . ' ' . number_format($record->invoice_amount, 2)
                        : '-'),

                TextColumn::make('created_by_name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->size('sm'),

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PAID' => 'success',
                        'PENDING' => 'warning',
                        'CANCEL' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('add_payment')
                    ->label('Add Payment')
                    ->icon('heroicon-o-credit-card')
                    ->color('primary')
                    ->size('sm')
                    ->url(fn (HrSalesInvoice $record) => url("/admin/view-sales-invoice?" . http_build_query([
                        'invoiceNo' => $record->invoice_no,
                        'softwareHandoverId' => $record->software_handover_id,
                        'from' => 'billing',
                    ])))
                    ->visible(fn (HrSalesInvoice $record) => $record->status === 'PENDING'),

                Action::make('view_receipt')
                    ->label('View Receipt')
                    ->icon('heroicon-o-document-text')
                    ->color('success')
                    ->size('sm')
                    ->url(fn (HrSalesInvoice $record) => url("/admin/view-sales-invoice?" . http_build_query([
                        'invoiceNo' => $record->invoice_no,
                        'softwareHandoverId' => $record->software_handover_id,
                        'from' => 'billing',
                    ])))
                    ->visible(fn (HrSalesInvoice $record) => $record->status === 'PAID'),
            ])
            ->actionsColumnLabel('Action')
            ->striped()
            ->defaultSort('invoice_date', 'desc');
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.hr-sales-invoice-table');
    }
}
