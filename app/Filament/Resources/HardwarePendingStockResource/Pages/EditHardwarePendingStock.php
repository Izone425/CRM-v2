<?php

namespace App\Filament\Resources\HardwarePendingStockResource\Pages;

use App\Filament\Resources\HardwarePendingStockResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHardwarePendingStock extends EditRecord
{
    protected static string $resource = HardwarePendingStockResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
