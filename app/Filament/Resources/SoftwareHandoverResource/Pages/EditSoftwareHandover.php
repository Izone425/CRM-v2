<?php

namespace App\Filament\Resources\SoftwareHandoverResource\Pages;

use App\Filament\Resources\SoftwareHandoverResource;
use App\Filament\Resources\SoftwareResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSoftwareHandover extends EditRecord
{
    protected static string $resource = SoftwareResource::class;

    public function getTitle(): string
    {
        // Format ID with 250 prefix and pad with zeros to ensure at least 3 digits
        $formattedId = 'SW_250' . str_pad($this->record->id, 3, '0', STR_PAD_LEFT);

        return "Edit Software Handover {$formattedId}";
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
