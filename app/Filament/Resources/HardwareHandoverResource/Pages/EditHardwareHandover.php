<?php

namespace App\Filament\Resources\HardwareHandoverResource\Pages;

use App\Filament\Resources\HardwareHandoverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHardwareHandover extends EditRecord
{
    protected static string $resource = HardwareHandoverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
