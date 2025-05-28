<?php

namespace App\Filament\Resources\HardwareHandoverResource\Pages;

use App\Filament\Resources\HardwareHandoverResource;
use Filament\Resources\Pages\ViewRecord;

class ViewHardwareHandover extends ViewRecord
{
    protected static string $resource = HardwareHandoverResource::class;

    public function getTitle(): string
    {
        // Format ID with 250 prefix and pad with zeros to ensure at least 3 digits
        $formattedId = '250' . str_pad($this->record->id, 3, '0', STR_PAD_LEFT);

        return "View Software Handover {$formattedId}";
    }
}
