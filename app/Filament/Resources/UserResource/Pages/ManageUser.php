<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\InvalidLeadReasonResource;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUser extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New User')
                ->modalHeading('Create New User')
                ->closeModalByClickingAway(false)
                ->createAnother(false),
        ];
    }
}
