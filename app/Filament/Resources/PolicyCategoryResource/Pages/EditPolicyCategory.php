<?php

namespace App\Filament\Resources\PolicyCategoryResource\Pages;

use App\Filament\Resources\PolicyCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPolicyCategory extends EditRecord
{
    protected static string $resource = PolicyCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
