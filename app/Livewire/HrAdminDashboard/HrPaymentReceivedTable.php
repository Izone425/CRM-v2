<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrOfficialReceipt;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class HrPaymentReceivedTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(HrOfficialReceipt::query())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'Bank Transfer' => 'Bank Transfer',
                        'PayPal' => 'PayPal',
                        'Razer' => 'Razer',
                        'Point' => 'Point',
                        'Credit Card' => 'Credit Card',
                        'Cheque' => 'Cheque',
                        'Cash' => 'Cash',
                    ])
                    ->placeholder('All Payment Methods'),

                SelectFilter::make('currency')
                    ->label('Currency')
                    ->options(fn () => HrOfficialReceipt::distinct()->pluck('currency', 'currency')->filter()->toArray())
                    ->placeholder('All Currencies'),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('From Date'),
                        DatePicker::make('end_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('receipt_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('receipt_date', '<=', $date),
                            );
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['start_date']) && empty($data['end_date'])) {
                            return null;
                        }
                        $parts = [];
                        if ($data['start_date']) {
                            $parts[] = 'From ' . Carbon::parse($data['start_date'])->format('Y-m-d');
                        }
                        if ($data['end_date']) {
                            $parts[] = 'To ' . Carbon::parse($data['end_date'])->format('Y-m-d');
                        }
                        return implode(' ', $parts);
                    }),
            ])
            ->columns([
                TextColumn::make('receipt_date')
                    ->label('Date')
                    ->sortable()
                    ->date('Y-m-d')
                    ->size('sm'),

                TextColumn::make('invoice_no')
                    ->label('Invoice')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->size('sm')
                    ->url(fn (HrOfficialReceipt $record) => $record->software_handover_id
                        ? url('/admin/view-sales-invoice?' . http_build_query([
                            'invoiceNo' => $record->invoice_no,
                            'softwareHandoverId' => $record->software_handover_id,
                            'from' => 'payment',
                        ]))
                        : null),

                TextColumn::make('or_no')
                    ->label('Doc No')
                    ->sortable()
                    ->searchable()
                    ->color('primary')
                    ->size('sm')
                    ->url(fn (HrOfficialReceipt $record) => url('/admin/view-official-receipt?' . http_build_query([
                        'orNo' => $record->or_no,
                        'from' => 'payment',
                    ]))),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->size('sm')
                    ->color('primary')
                    ->url(fn (HrOfficialReceipt $record) => $record->handover_id
                        ? url('/admin/hr-company-license-details?' . http_build_query([
                            'handoverId' => $record->handover_id,
                            'softwareHandoverId' => $record->software_handover_id,
                        ]))
                        : null),

                TextColumn::make('subscriber_name')
                    ->label('Subscriber Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->size('sm')
                    ->placeholder('-'),

                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('ref_no')
                    ->label('Ref No.')
                    ->sortable()
                    ->searchable()
                    ->size('sm')
                    ->wrap()
                    ->placeholder('-'),

                ViewColumn::make('autocount_invoice_no')
                    ->label('AutoCount Invoice No.')
                    ->view('livewire.hr-admin-dashboard.partials.autocount-invoice-cell')
                    ->sortable(),

                TextColumn::make('currency')
                    ->label('Currency')
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->sortable()
                    ->size('sm')
                    ->alignEnd()
                    ->formatStateUsing(fn ($record) => number_format($record->amount, 2)),
            ])
            ->striped()
            ->defaultSort('receipt_date', 'desc');
    }

    public function updateAutocountInvoice(int $recordId, string $value): void
    {
        $record = HrOfficialReceipt::find($recordId);
        if ($record) {
            $record->update(['autocount_invoice_no' => strtoupper(trim($value))]);
        }
    }

    public function exportCsv()
    {
        $records = $this->getFilteredTableQuery()->orderBy('receipt_date', 'desc')->get();

        $filename = 'payment-received-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'No', 'Date', 'Invoice', 'Doc No', 'Company Name',
                'Subscriber Name', 'Payment Method', 'Ref No',
                'AutoCount Invoice No', 'Currency', 'Amount',
            ]);

            foreach ($records as $index => $record) {
                fputcsv($file, [
                    $index + 1,
                    $record->receipt_date?->format('Y-m-d'),
                    $record->invoice_no,
                    $record->or_no,
                    $record->company_name,
                    $record->subscriber_name ?? '-',
                    $record->payment_method,
                    $record->ref_no ?? '-',
                    $record->autocount_invoice_no ?? '-',
                    $record->currency,
                    number_format($record->amount, 2),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.hr-payment-received-table');
    }
}
