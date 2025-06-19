<?php

namespace App\Filament\Resources\AdminRepairResource\Pages;

use App\Filament\Resources\AdminRepairResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminRepairs extends ListRecords
{
    protected static string $resource = AdminRepairResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
