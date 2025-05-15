<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use App\Models\User;

class ManageUser extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    return UserResource::processPermissionsForSave($data);
                }),
            Actions\Action::make('edit')
                ->url(fn (User $record): string => route('filament.admin.resources.users.edit', $record)),
        ];
    }
}
