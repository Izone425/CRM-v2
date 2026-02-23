<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrSalesInvoiceItem;
use Carbon\Carbon;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class ExpiringInvoicesTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                HrSalesInvoiceItem::query()
                    ->where('status', '!=', 'CANCEL')
            )
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('currency')
                    ->label('Currency')
                    ->options(fn () => HrSalesInvoiceItem::distinct()->pluck('currency', 'currency')->filter()->toArray())
                    ->placeholder('All Currencies'),

                SelectFilter::make('created_by_name')
                    ->label('Sales Person')
                    ->options(fn () => HrSalesInvoiceItem::distinct()->pluck('created_by_name', 'created_by_name')->filter()->toArray())
                    ->placeholder('All Sales Persons'),

                SelectFilter::make('license_type')
                    ->label('Product')
                    ->options(fn () => HrSalesInvoiceItem::distinct()->pluck('license_type', 'license_type')->filter()->toArray())
                    ->placeholder('All Products'),

                Filter::make('expiring_in')
                    ->form([
                        \Filament\Forms\Components\Select::make('months')
                            ->label('Expiring In')
                            ->options([
                                '1' => '1 Month',
                                '2' => '2 Months',
                                '3' => '3 Months',
                            ])
                            ->default('3'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['months'])) {
                            return $query;
                        }
                        $months = (int) $data['months'];
                        return $query
                            ->whereDate('end_date', '>=', Carbon::today())
                            ->whereDate('end_date', '<=', Carbon::today()->addMonths($months));
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (empty($data['months'])) {
                            return null;
                        }
                        $months = $data['months'];
                        return 'Expiring in ' . $months . ' month' . ((int) $months > 1 ? 's' : '');
                    }),
            ])
            ->columns([
                TextColumn::make('invoice_no')
                    ->label('Invoice No')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->size('sm')
                    ->url(fn (HrSalesInvoiceItem $record) => url('/admin/view-sales-invoice?' . http_build_query([
                        'invoiceNo' => $record->invoice_no,
                        'softwareHandoverId' => $record->software_handover_id,
                        'from' => 'expiring-invoices',
                    ]))),

                TextColumn::make('invoice_date')
                    ->label('Date')
                    ->sortable()
                    ->date('Y-m-d')
                    ->size('sm'),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->color('primary')
                    ->size('sm')
                    ->url(fn (HrSalesInvoiceItem $record) => $record->handover_id
                        ? url('/admin/hr-company-license-details?' . http_build_query([
                            'handoverId' => $record->handover_id,
                            'softwareHandoverId' => $record->software_handover_id,
                        ]))
                        : null),

                TextColumn::make('invoice_amount')
                    ->label('Invoice Amount')
                    ->sortable()
                    ->size('sm')
                    ->formatStateUsing(fn ($record) => $record->invoice_amount
                        ? $record->currency . ' ' . number_format($record->invoice_amount, 2)
                        : '-'),

                TextColumn::make('total_user')
                    ->label('Unit')
                    ->sortable()
                    ->size('sm')
                    ->alignCenter(),

                TextColumn::make('payer_name')
                    ->label('Payer Name')
                    ->getStateUsing(fn (HrSalesInvoiceItem $record) => $record->company_name)
                    ->sortable(query: fn (Builder $query, string $direction) => $query->orderBy('company_name', $direction))
                    ->searchable(query: fn (Builder $query, string $search) => $query->orWhere('company_name', 'like', "%{$search}%"))
                    ->wrap()
                    ->size('sm')
                    ->placeholder('-'),

                TextColumn::make('license_type')
                    ->label('Product Name')
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('start_date')
                    ->label('Start Date')
                    ->sortable()
                    ->date('Y-m-d')
                    ->size('sm'),

                TextColumn::make('end_date')
                    ->label('Expiry Date')
                    ->sortable()
                    ->date('Y-m-d')
                    ->size('sm'),

                TextColumn::make('created_by_name')
                    ->label('Created By')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->size('sm')
                    ->placeholder('-'),
            ])
            ->striped()
            ->defaultSort('end_date', 'asc');
    }

    public function exportCsv()
    {
        $records = $this->getFilteredTableQuery()->orderBy('end_date', 'asc')->get();

        $filename = 'expiring-invoices-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'No', 'Invoice No', 'Date', 'Company Name', 'Invoice Amount',
                'Unit', 'Payer Name', 'Product Name', 'Start Date',
                'Expiry Date', 'Created By',
            ]);

            foreach ($records as $index => $record) {
                fputcsv($file, [
                    $index + 1,
                    $record->invoice_no,
                    $record->invoice_date?->format('Y-m-d'),
                    $record->company_name,
                    $record->currency . ' ' . number_format($record->invoice_amount ?? 0, 2),
                    $record->total_user,
                    $record->company_name,
                    $record->license_type . ' (' . ($record->user_limit ?: 1) . ' User License)',
                    $record->start_date?->format('Y-m-d'),
                    $record->end_date?->format('Y-m-d'),
                    $record->created_by_name ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.expiring-invoices-table');
    }
}
