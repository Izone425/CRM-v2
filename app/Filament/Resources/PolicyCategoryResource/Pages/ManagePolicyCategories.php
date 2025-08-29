<?php

namespace App\Filament\Resources\PolicyCategoryResource\Pages;

use App\Filament\Resources\PolicyCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePolicyCategories extends ManageRecords
{
    protected static string $resource = PolicyCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
