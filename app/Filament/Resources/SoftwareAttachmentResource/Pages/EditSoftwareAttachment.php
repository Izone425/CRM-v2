<?php

namespace App\Filament\Resources\SoftwareAttachmentResource\Pages;

use App\Filament\Resources\SoftwareAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoftwareAttachment extends EditRecord
{
    protected static string $resource = SoftwareAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
