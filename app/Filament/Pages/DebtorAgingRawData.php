<?php
namespace App\Filament\Pages;

use App\Models\DebtorAging;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DebtorAgingRawData extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Debtor Aging Raw Data';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.debtor-aging-raw-data';

    public function table(Table $table): Table
    {
        // Get the current date for aging calculations
        $currentDate = Carbon::now();

        return $table
            ->query(DebtorAging::query())
            ->columns([
                TextColumn::make('debtor_code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('invoice_date')
                    ->label('Invoice Date')
                    ->date('Y-m-d')
                    ->sortable(),

                TextColumn::make('currency_code')
                    ->label('Currency')
                    ->sortable(),

                TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('support')
                    ->label('Support')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('balance_in_rm')
                    ->label('Bal in RM')
                    ->numeric(2)
                    ->money('MYR') // Always display in MYR
                    ->state(function ($record) {
                        // Calculate the balance in RM by multiplying outstanding by exchange_rate
                        if ($record->currency_code === 'MYR') {
                            // If already in MYR, return as is
                            return $record->outstanding;
                        }

                        // Apply exchange rate conversion
                        if ($record->outstanding && $record->exchange_rate) {
                            return $record->outstanding * $record->exchange_rate;
                        }

                        return 0;
                    })
                    ->sortable(),
            ])
            // ->filters([
            //     Filter::make('due_date')
            //         ->form([
            //             DatePicker::make('due_date_from')
            //                 ->label('From'),
            //             DatePicker::make('due_date_to')
            //                 ->label('To'),
            //         ])
            //         ->query(function (Builder $query, array $data): Builder {
            //             return $query
            //                 ->when(
            //                     $data['due_date_from'],
            //                     fn (Builder $query, $date): Builder => $query->whereDate('due_date', '>=', $date),
            //                 )
            //                 ->when(
            //                     $data['due_date_to'],
            //                     fn (Builder $query, $date): Builder => $query->whereDate('due_date', '<=', $date),
            //                 );
            //         }),

            //     SelectFilter::make('salesperson')
            //         ->options(function () {
            //             return DebtorAging::distinct()->pluck('salesperson', 'salesperson')->toArray();
            //         })
            //         ->searchable()
            //         ->multiple(),

            //     SelectFilter::make('currency_code')
            //         ->options(function () {
            //             return DebtorAging::distinct()->pluck('currency_code', 'currency_code')->toArray();
            //         })
            //         ->label('Currency')
            //         ->multiple(),

            //     // Filter to only show records with outstanding amounts
            //     Filter::make('has_outstanding')
            //         ->label('Only Outstanding')
            //         ->query(fn (Builder $query): Builder => $query->where('outstanding', '>', 0))
            //         ->default(true),
            // ])
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50, 100])
            ->paginationPageOptions([10, 25, 50, 100])
            ->defaultSort('invoice_date', 'desc');
    }
}
