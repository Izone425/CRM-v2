<?php

namespace App\Filament\Resources\HardwareAttachmentResource\Pages;

use App\Filament\Resources\HardwareAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHardwareAttachment extends EditRecord
{
    protected static string $resource = HardwareAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
