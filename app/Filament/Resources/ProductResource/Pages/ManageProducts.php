<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Database\Eloquent\Builder;

class ManageProducts extends ManageRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Product')
                ->modalHeading('Create New Product')
                ->closeModalByClickingAway(false)
                ->createAnother(false),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('all')
                        ->label('All'),
            'software' => Tab::make('software')
                        ->label('Software')
                        ->modifyQueryUsing(fn(Builder $query) => $query->where('solution','software')),
            'hardware' => Tab::make('hardware')
                        ->label('Hardware')
                        ->modifyQueryUsing(fn(Builder $query) => $query->where('solution','hardware')),
            'hrdf' => Tab::make('hrdf')
                        ->label('HRDF')
                        ->modifyQueryUsing(fn(Builder $query) => $query->where('solution','hrdf')),
            'other' => Tab::make('other')
                        ->label('Other')
                        ->modifyQueryUsing(fn(Builder $query) => $query->where('solution','other')),
        ];
    }
}
