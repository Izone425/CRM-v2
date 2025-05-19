<?php

namespace App\Filament\Resources\SoftwareHandoverResource\Pages;

use App\Filament\Resources\SoftwareHandoverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoftwareHandover extends EditRecord
{
    protected static string $resource = SoftwareHandoverResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
