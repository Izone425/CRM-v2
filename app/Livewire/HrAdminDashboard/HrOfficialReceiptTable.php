<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrOfficialReceipt;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
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

class HrOfficialReceiptTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(HrOfficialReceipt::query())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(25)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'PAID' => 'Paid',
                    ])
                    ->placeholder('All Official Receipt'),

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
                TextColumn::make('or_no')
                    ->label('O/R No')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->size('sm')
                    ->url(fn (HrOfficialReceipt $record) => url('/admin/view-official-receipt?' . http_build_query([
                        'orNo' => $record->or_no,
                        'from' => 'official-receipt',
                    ]))),

                TextColumn::make('receipt_date')
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
                    ->url(fn (HrOfficialReceipt $record) => $record->handover_id
                        ? url('/admin/hr-company-license-details?' . http_build_query([
                            'handoverId' => $record->handover_id,
                            'softwareHandoverId' => $record->software_handover_id,
                        ]))
                        : null),

                TextColumn::make('description')
                    ->label('Description')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->size('sm'),

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

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PAID' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('created_by')
                    ->label('Created By')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->size('sm')
                    ->placeholder('-'),
            ])
            ->striped()
            ->defaultSort('receipt_date', 'desc');
    }

    public function exportCsv()
    {
        $records = $this->getFilteredTableQuery()->orderBy('receipt_date', 'desc')->get();

        $filename = 'official-receipts-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'No', 'O/R No', 'Date', 'Company Name', 'Description',
                'Currency', 'Amount', 'Status', 'Created By',
            ]);

            foreach ($records as $index => $record) {
                fputcsv($file, [
                    $index + 1,
                    $record->or_no,
                    $record->receipt_date?->format('Y-m-d'),
                    $record->company_name,
                    $record->description,
                    $record->currency,
                    number_format($record->amount, 2),
                    $record->status,
                    $record->created_by ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.hr-official-receipt-table');
    }
}
