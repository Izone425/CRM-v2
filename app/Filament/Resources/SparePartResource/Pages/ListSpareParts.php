<?php

namespace App\Filament\Resources\SparePartResource\Pages;

use App\Filament\Resources\SparePartResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListSpareParts extends ListRecords
{
    protected static string $resource = SparePartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All Device Models'),
            'tc10' => Tab::make('TC10')->query(fn ($query) => $query->where('device_model', 'TC10')),
            'tc20' => Tab::make('TC20')->query(fn ($query) => $query->where('device_model', 'TC20')),
            'faceid5' => Tab::make('FACE ID 5')->query(fn ($query) => $query->where('device_model', 'FACE ID 5')),
            'faceid6' => Tab::make('FACE ID 6')->query(fn ($query) => $query->where('device_model', 'FACE ID 6')),
        ];
    }
}
