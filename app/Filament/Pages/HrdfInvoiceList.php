<?php

namespace App\Filament\Pages;

use App\Models\CrmHrdfInvoice;
use App\Models\SoftwareHandover;
use App\Models\Quotation;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;

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
            ->query(
                CrmHrdfInvoice::query()
                    ->with([
                        'quotation',
                        'softwareHandover',
                        'hardwareHandover',
                        'renewalHandover' => function($query) {
                            // Don't eager load quotations here since it's a custom method
                        }
                    ])
            )
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
                        // ✅ Get the actual handover record based on type
                        $handoverRecord = null;

                        switch ($record->handover_type) {
                            case 'SW':
                                $handoverRecord = $record->softwareHandover;
                                break;
                            case 'HW':
                                $handoverRecord = $record->hardwareHandover;
                                break;
                            case 'RW':
                                $handoverRecord = $record->renewalHandover;
                                break;
                        }

                        // ✅ If we have the actual handover record, use its formatted_handover_id
                        if ($handoverRecord && method_exists($handoverRecord, 'getFormattedHandoverIdAttribute')) {
                            return $handoverRecord->formatted_handover_id;
                        }

                        // ✅ Fallback: Generate formatted ID based on handover type
                        switch ($record->handover_type) {
                            case 'SW': // Software
                                return 'SW_' . str_pad($state, 6, '0', STR_PAD_LEFT);
                            case 'HW': // Hardware
                                return 'HW_' . str_pad($state, 6, '0', STR_PAD_LEFT);
                            case 'RW': // Renewal
                                return 'RW_' . str_pad($state, 6, '0', STR_PAD_LEFT);
                            default:
                                return 'SW_' . str_pad($state, 6, '0', STR_PAD_LEFT);
                        }
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (CrmHrdfInvoice $record): View {
                                // ✅ Get the actual handover record based on type
                                $handoverRecord = null;

                                switch ($record->handover_type) {
                                    case 'SW':
                                        $handoverRecord = $record->softwareHandover;
                                        break;
                                    case 'HW':
                                        $handoverRecord = $record->hardwareHandover;
                                        break;
                                    case 'RW':
                                        $handoverRecord = $record->renewalHandover;
                                        break;
                                }

                                if (!$handoverRecord) {
                                    return view('components.handover-not-found')
                                        ->with('extraAttributes', ['record' => $record]);
                                }

                                // ✅ Show different components based on handover type
                                switch ($record->handover_type) {
                                    case 'SW':
                                        return view('components.software-handover')
                                            ->with('extraAttributes', ['record' => $handoverRecord]);

                                    case 'HW':
                                        return view('components.hardware-handover')
                                            ->with('extraAttributes', ['record' => $handoverRecord]);

                                    case 'RW':
                                        return view('components.renewal-handover')
                                            ->with('extraAttributes', ['record' => $handoverRecord]);

                                    default:
                                        return view('components.software-handover')
                                            ->with('extraAttributes', ['record' => $handoverRecord]);
                                }
                            })
                    ),

                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->searchable()
                    ->limit(20),

                TextColumn::make('tt_invoice_number')
                    ->label('TT Invoice')
                    ->searchable(),

                TextColumn::make('debtor_code')
                    ->label('Debtor Code')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

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
            // ✅ ADD: Actions for each row
            ->actions([
                ActionGroup::make([
                    Action::make('view_pi')
                        ->label('View PI')
                        ->icon('heroicon-o-document-text')
                        ->color('primary')
                        ->visible(fn (CrmHrdfInvoice $record) => !is_null($record->quotation_id))
                        ->url(fn(Quotation $quotation) => route('pdf.print-proforma-invoice-v2', $quotation))
                        ->openUrlInNewTab(),
                ])
            ])
            ->defaultPaginationPageOption(50)
            ->paginated([50, 100]);
    }
}
