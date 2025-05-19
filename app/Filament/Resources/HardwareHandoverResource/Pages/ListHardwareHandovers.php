<?php

namespace App\Filament\Resources\HardwareHandoverResource\Pages;

use App\Filament\Resources\HardwareHandoverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHardwareHandovers extends ListRecords
{
    protected static string $resource = HardwareHandoverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
