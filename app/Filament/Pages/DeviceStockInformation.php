<?php

namespace App\Filament\Pages;

use App\Models\Inventory;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DeviceStockInformation extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Device Stock Information';
    protected static ?string $title = 'Device Stock Information';

    protected static string $view = 'filament.pages.device-stock-information';

    // This is the missing piece - define the route explicitly
    protected static ?string $slug = 'device-stock-information';

    public function table(Table $table): Table
    {
        return $table
            ->query(Inventory::query())
            ->columns([
                TextColumn::make('name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('new')
                    ->label('New')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('in_stock')
                    ->label('In Stock')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->state(function (Inventory $record): int {
                        return $record->new + $record->in_stock;
                    })
                    ->numeric()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderByRaw("(new + in_stock) {$direction}");
                    }),
            ])
            ->defaultSort('name')
            ->paginated([10, 25, 50, 100]);
    }
}
