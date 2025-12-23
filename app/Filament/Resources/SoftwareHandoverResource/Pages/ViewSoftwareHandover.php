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
        return "Software Handover {$this->record->formatted_handover_id}";
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
