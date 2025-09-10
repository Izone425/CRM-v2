<?php

namespace App\Filament\Resources\ShippingDeviceModelResource\Pages;

use App\Filament\Resources\ShippingDeviceModelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListShippingDeviceModels extends ListRecords
{
    protected static string $resource = ShippingDeviceModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
