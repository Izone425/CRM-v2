<?php

namespace App\Filament\Resources\InstallerResource\Pages;

use App\Filament\Resources\InstallerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditInstaller extends EditRecord
{
    protected static string $resource = InstallerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
