<?php

namespace App\Filament\Resources\CallCategoryResource\Pages;

use App\Filament\Resources\CallCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCallCategories extends ListRecords
{
    protected static string $resource = CallCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
