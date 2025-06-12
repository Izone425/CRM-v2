<?php

namespace App\Filament\Resources\HardwarePendingStockResource\Pages;

use App\Filament\Resources\HardwarePendingStockResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHardwarePendingStocks extends ListRecords
{
    protected static string $resource = HardwarePendingStockResource::class;
    protected static ?string $title = 'Hardware Handover - Pending Stocks';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
