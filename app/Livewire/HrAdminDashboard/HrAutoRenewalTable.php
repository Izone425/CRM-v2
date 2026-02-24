<?php

namespace App\Livewire\HrAdminDashboard;

use App\Models\HrAutoRenewal;
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

class HrAutoRenewalTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    public function table(Table $table): Table
    {
        return $table
            ->query(HrAutoRenewal::query())
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50, 100])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'PENDING' => 'Pending',
                    ])
                    ->placeholder('All Status'),

                SelectFilter::make('country')
                    ->label('Country')
                    ->options(fn () => HrAutoRenewal::distinct()->pluck('country', 'country')->filter()->toArray())
                    ->placeholder('All Countries'),

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
                                fn (Builder $query, $date): Builder => $query->whereDate('next_billing_date', '>=', $date),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('next_billing_date', '<=', $date),
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
                TextColumn::make('invoice_no')
                    ->label('Invoice No')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary')
                    ->size('sm')
                    ->url(fn (HrAutoRenewal $record) => $record->software_handover_id
                        ? url('/admin/view-sales-invoice?' . http_build_query([
                            'invoiceNo' => $record->invoice_no,
                            'softwareHandoverId' => $record->software_handover_id,
                            'from' => 'auto-renewal',
                        ]))
                        : null),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->sortable()
                    ->searchable()
                    ->wrap()
                    ->size('sm')
                    ->color('primary')
                    ->url(fn (HrAutoRenewal $record) => $record->handover_id
                        ? url('/admin/hr-company-license-details?' . http_build_query([
                            'handoverId' => $record->handover_id,
                            'softwareHandoverId' => $record->software_handover_id,
                        ]))
                        : null),

                TextColumn::make('country')
                    ->label('Country')
                    ->sortable()
                    ->size('sm'),

                TextColumn::make('next_billing_date')
                    ->label('Next Billing Date')
                    ->sortable()
                    ->date('Y-m-d')
                    ->size('sm'),

                TextColumn::make('created_at')
                    ->label('Created Time')
                    ->sortable()
                    ->date('Y-m-d')
                    ->size('sm'),

                TextColumn::make('status')
                    ->label('Status')
                    ->sortable()
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'PENDING' => 'warning',
                        default => 'gray',
                    }),

                ViewColumn::make('is_enabled')
                    ->label('Action')
                    ->view('livewire.hr-admin-dashboard.partials.auto-renewal-toggle'),
            ])
            ->striped()
            ->defaultSort('next_billing_date', 'asc');
    }

    public function toggleAutoRenewal(int $recordId): void
    {
        $record = HrAutoRenewal::find($recordId);
        if ($record) {
            $record->update(['is_enabled' => !$record->is_enabled]);
        }
    }

    public function exportCsv()
    {
        $records = $this->getFilteredTableQuery()->orderBy('next_billing_date', 'asc')->get();

        $filename = 'auto-renewals-' . now()->format('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($records) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'No', 'Invoice No', 'Company Name', 'Country',
                'Next Billing Date', 'Created Time', 'Status', 'Enabled',
            ]);

            foreach ($records as $index => $record) {
                fputcsv($file, [
                    $index + 1,
                    $record->invoice_no,
                    $record->company_name,
                    $record->country ?? '-',
                    $record->next_billing_date?->format('Y-m-d'),
                    $record->created_at?->format('Y-m-d'),
                    $record->status,
                    $record->is_enabled ? 'Yes' : 'No',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function render()
    {
        return view('livewire.hr-admin-dashboard.hr-auto-renewal-table');
    }
}
