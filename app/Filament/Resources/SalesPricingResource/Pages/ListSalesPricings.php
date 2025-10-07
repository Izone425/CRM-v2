<?php

namespace App\Filament\Resources\SalesPricingResource\Pages;

use App\Filament\Resources\SalesPricingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSalesPricings extends ListRecords
{
    protected static string $resource = SalesPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
