<?php
namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Invoices';
    protected static ?string $navigationGroup = 'Finance';
    protected static ?int $navigationSort = 30;

    // Map of salesperson names to their user IDs
    protected static $salespersonUserIds = [
        'MUIM' => 6,
        'YASMIN' => 7,
        'FARHANAH' => 8,
        'JOSHUA' => 9,
        'AZIZ' => 10,
        'BARI' => 11,
        'VINCE' => 12,
        'WIRSON' => 25,
        'JONATHAN' => 18,
        'TINA' => 21,
    ];

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Form fields will go here
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->columns([
                Tables\Columns\TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->sortable(),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('invoice_date')
                    ->label('Date')
                    ->dateTime('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('invoice_no')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Local Subtotal')
                    ->money('MYR')
                    ->sortable()
                    ->getStateUsing(function (Invoice $record): float {
                        // Calculate the sum for this invoice_no
                        return Invoice::where('invoice_no', $record->invoice_no)->sum('invoice_amount');
                    }),

                Tables\Columns\BadgeColumn::make('payment_status')
                    ->label('Payment Status')
                    ->colors([
                        'danger' => 'UnPaid',
                        'warning' => 'Partial Payment',
                        'success' => 'Full Payment',
                    ])
                    ->sortable(),
            ])
            ->defaultSort('invoice_date', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                // Use proper aggregation functions for grouped data
                return $query->select([
                    DB::raw('MIN(id) as id'), // Take the smallest id for each group
                    'salesperson',
                    'invoice_no',
                    'invoice_date',
                    DB::raw('SUM(invoice_amount) as total_invoice_amount')
                ])
                ->groupBy('invoice_no', 'salesperson', 'invoice_date')
                ->orderBy('invoice_date', 'desc');
            })
            ->filters([
                SelectFilter::make('salesperson')
                    ->label('Salesperson')
                    ->options(function () {
                        // Get all unique salesperson values from the invoices table
                        try {
                            return Invoice::query()
                                ->select('salesperson')
                                ->distinct()
                                ->whereNotNull('salesperson')
                                ->where('salesperson', '!=', '')
                                ->orderBy('salesperson')
                                ->pluck('salesperson', 'salesperson')
                                ->toArray();
                        } catch (\Exception $e) {
                            // In case of database error, return empty array
                            return [];
                        }
                    })
                    ->visible(fn () => Auth::check() && Auth::user()->role_id === 3),

                SelectFilter::make('year')
                    ->label('Year')
                    ->options(function () {
                        return Invoice::selectRaw('YEAR(invoice_date) as year')
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year', 'year')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query, $year): Builder => $query->whereYear('invoice_date', $year)
                            );
                    }),

                SelectFilter::make('month')
                    ->label('Month')
                    ->options([
                        '1' => 'January',
                        '2' => 'February',
                        '3' => 'March',
                        '4' => 'April',
                        '5' => 'May',
                        '6' => 'June',
                        '7' => 'July',
                        '8' => 'August',
                        '9' => 'September',
                        '10' => 'October',
                        '11' => 'November',
                        '12' => 'December',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['value'],
                                fn (Builder $query, $month): Builder => $query->whereMonth('invoice_date', $month)
                            );
                    }),

                // SelectFilter::make('invoice_type')
                //     ->label('Invoice Type')
                //     ->options([
                //         'EP' => 'Product (EP)',
                //         'EH' => 'HRDF (EH)',
                //         'ECN' => 'Credit Note (ECN)',
                //     ])
                //     ->query(function (Builder $query, array $data): Builder {
                //         return $query
                //             ->when(
                //                 $data['value'],
                //                 function (Builder $query, $type): Builder {
                //                     // Assuming invoice_no format starts with the type code
                //                     return $query->where('invoice_no', 'like', $type . '%');
                //                 }
                //             );
                //     }),

                // SelectFilter::make('payment_status')
                //     ->label('Payment Status')
                //     ->options([
                //         'UnPaid' => 'UnPaid',
                //         'Partial Payment' => 'Partial Payment',
                //         'Full Payment' => 'Full Payment',
                //     ]),
            ])
            ->actions([
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Filter to show only the logged-in user's data for salespersons
        if (Auth::check() && Auth::user()->role_id === 2) {
            $userId = Auth::id();

            // Find the salesperson name that corresponds to the current user ID
            $salespersonName = array_search($userId, static::$salespersonUserIds);

            if ($salespersonName) {
                // Filter invoices to only show those belonging to this salesperson
                $query->where('salesperson', $salespersonName);
            } else {
                // If the user ID is not in our mapping, don't show any results
                // This ensures users only see their own data
                $query->where('id', 0); // This will return no results
            }
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            // 'create' => Pages\CreateInvoice::route('/create'),
            // 'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
