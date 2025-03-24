<?php

namespace App\Filament\Resources\LeadSourceResource\Pages;

use App\Filament\Resources\LeadSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLeadSources extends ManageRecords
{
    protected static string $resource = LeadSourceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Lead Source')
                ->modalHeading('Create New Lead Source')
                ->closeModalByClickingAway(false)
                ->createAnother(false),
        ];
    }
}
