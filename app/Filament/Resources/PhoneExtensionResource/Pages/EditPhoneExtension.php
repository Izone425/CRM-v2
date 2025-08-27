<?php

namespace App\Filament\Resources\PhoneExtensionResource\Pages;

use App\Filament\Resources\PhoneExtensionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPhoneExtension extends EditRecord
{
    protected static string $resource = PhoneExtensionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
