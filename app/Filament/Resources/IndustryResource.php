<?php

namespace App\Filament\Resources;

use App\Filament\Resources\IndustryResource\Pages;

use Filament\Forms\Form;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;

use App\Models\Industry;
use App\Models\User;
use App\Services\IndustryService;
use Illuminate\Database\Eloquent\Builder;

class IndustryResource extends Resource
{
    protected static ?string $model = Industry::class;
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationIcon = 'heroicon-o-home-modern';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.resources.industries.index');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name'),
                Toggle::make('is_active')
                    ->inline(false)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name'),
                ToggleColumn::make('is_active')->label('Active?')
            ])
            ->filters([
                Filter::make('name')
                    ->form([
                        Select::make('name')
                            ->options(fn(IndustryService $industryService): array => $industryService->getList())
                            ->searchable()
                    ])
                    ->query(fn(Builder $query, array $data, IndustryService $industryService): Builder => $industryService->filterByName($query, $data)),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Actions\EditAction::make()
                    ->modalHeading('Edit Industry')
                    ->closeModalByClickingAway(false)
                    ->hidden(fn() => auth()->user()->role == User::IS_USER),
                Actions\DeleteAction::make()
                    ->closeModalByClickingAway(false)
                    ->hidden(fn() => auth()->user()->role == User::IS_USER),
            ])
            ->bulkActions([
                // Actions\BulkActionGroup::make([
                //     Actions\DeleteBulkAction::make(),
                // ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageIndustries::route('/'),
        ];
    }

    // public static function canViewAny(): bool
    // {
    //     return  auth()->user()->role == 'admin' ||
    //             auth()->user()->role == 'manager';
    // }
}
