<?php

namespace App\Filament\Resources\HardwareAttachmentResource\Pages;

use App\Filament\Resources\HardwareAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHardwareAttachments extends ListRecords
{
    protected static string $resource = HardwareAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
