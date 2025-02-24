<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductService;
use Filament\Forms;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    // public static function canAccess(): bool
    // {
    //     return auth()->user()->role_id != '2';
    // }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('code')
                    ->required(fn (Page $livewire) => ($livewire instanceof CreateRecord))
                    ->disabledOn('edit'),
                Select::make('solution')
                    ->placeholder('Select a solution')
                    ->options([
                        'software' => 'Software',
                        'hardware' => 'Hardware',
                        'hrdf' => 'HRDF',
                        'other' => 'Other'
                    ]),
                RichEditor::make('description'),
                TextInput::make('unit_price')
                        ->label('Cost (RM)'),
                Toggle::make('taxable')
                        ->label('Taxable?')
                        ->inline(false),
                Toggle::make('is_active')
                        ->label('Is Active?')
                        ->inline(false)
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(false)
            ->columns([
                TextColumn::make('code')
                    ->width(100),
                TextColumn::make('solution')
                    ->width(100),
                TextColumn::make('description')
                    ->html()
                    ->width(500)
                    ->wrap(),
                TextColumn::make('unit_price')
                    ->label('Cost (RM)')
                    ->width(100),
                ToggleColumn::make('taxable')
                    ->label('Taxable?')
                    ->width(100)
                    ->disabled(),
                ToggleColumn::make('is_active')
                    ->label('Is Active?')
                    ->width(100)
                    ->disabled()
            ])
            ->filters([
                Filter::make('code')
                    ->form([
                        Select::make('code')
                            ->options(fn(Product $product, ProductService $productService): array => $productService->getCode($product))
                            ->searchable()
                    ])
                    ->query(fn(Builder $query, array $data, ProductService $productService): Builder => $productService->filterByCode($query, $data)),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading('Edit Product')
                    ->closeModalByClickingAway(false)
                    ->hidden(fn(): bool => auth()->user()->role == User::IS_USER),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
