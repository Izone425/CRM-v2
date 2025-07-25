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

    // Add this method to provide custom view data
    protected function getViewData(): array
    {
        return [
            'tabsContainerClass' => 'multiline-tabs',
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
            'ta100cr' => Tab::make('TA100C / R')->query(fn ($query) => $query->where('device_model', 'TA100C / R')),
            'ta100cmf' => Tab::make('TA100C / MF')->query(fn ($query) => $query->where('device_model', 'TA100C / MF')),
            'ta100chid' => Tab::make('TA100C / HID')->query(fn ($query) => $query->where('device_model', 'TA100C / HID')),
            'ta100crw' => Tab::make('TA100C / R / W')->query(fn ($query) => $query->where('device_model', 'TA100C / R / W')),
            'ta100cmfw' => Tab::make('TA100C / MF / W')->query(fn ($query) => $query->where('device_model', 'TA100C / MF / W')),
            'ta100chidw' => Tab::make('TA100C / HID / W')->query(fn ($query) => $query->where('device_model', 'TA100C / HID / W')),
            'ta100cw' => Tab::make('TA100C / W')->query(fn ($query) => $query->where('device_model', 'TA100C / W')),
        ];
    }
}
