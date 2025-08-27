<?php

namespace App\Filament\Resources\PhoneExtensionResource\Pages;

use App\Filament\Resources\PhoneExtensionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPhoneExtensions extends ListRecords
{
    protected static string $resource = PhoneExtensionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
