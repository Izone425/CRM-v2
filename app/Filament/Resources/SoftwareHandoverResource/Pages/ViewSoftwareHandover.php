<?php

namespace App\Filament\Resources\SoftwareHandoverResource\Pages;

use App\Filament\Resources\SoftwareHandoverResource;
use App\Filament\Resources\SoftwareResource;
use Filament\Resources\Pages\ViewRecord;

class ViewSoftwareHandover extends ViewRecord
{
    protected static string $resource = SoftwareResource::class;

    public function getTitle(): string
    {
        // Format ID with 250 prefix and pad with zeros to ensure at least 3 digits
        $formattedId = '250' . str_pad($this->record->id, 3, '0', STR_PAD_LEFT);

        return "Software Handover {$formattedId}";
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
