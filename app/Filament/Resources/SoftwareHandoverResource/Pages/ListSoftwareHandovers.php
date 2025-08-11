<?php

namespace App\Filament\Resources\SoftwareHandoverResource\Pages;

use App\Filament\Resources\SoftwareHandoverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSoftwareHandovers extends ListRecords
{
    protected static string $resource = SoftwareHandoverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
