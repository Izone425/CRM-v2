<?php

namespace App\Filament\Resources\DeviceModelResource\Pages;

use App\Filament\Resources\DeviceModelResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDeviceModel extends EditRecord
{
    protected static string $resource = DeviceModelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
