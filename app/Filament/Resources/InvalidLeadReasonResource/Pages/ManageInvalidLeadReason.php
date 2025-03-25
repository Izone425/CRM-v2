<?php

namespace App\Filament\Resources\InvalidLeadReasonResource\Pages;

use App\Filament\Resources\InvalidLeadReasonResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageInvalidLeadReason extends ManageRecords
{
    protected static string $resource = InvalidLeadReasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New Inactive Lead Reason')
                ->modalHeading('Create New Inactive Lead Reason')
                ->closeModalByClickingAway(false)
                ->createAnother(false),
        ];
    }
}
