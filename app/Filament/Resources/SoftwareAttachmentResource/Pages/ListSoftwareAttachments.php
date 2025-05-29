<?php

namespace App\Filament\Resources\SoftwareAttachmentResource\Pages;

use App\Filament\Resources\SoftwareAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSoftwareAttachments extends ListRecords
{
    protected static string $resource = SoftwareAttachmentResource::class;
    protected static ?string $title = 'Software Handover Attachments';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
