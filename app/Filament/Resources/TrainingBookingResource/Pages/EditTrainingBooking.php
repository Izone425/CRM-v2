<?php

namespace App\Filament\Resources\TrainingBookingResource\Pages;

use App\Filament\Resources\TrainingBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTrainingBooking extends EditRecord
{
    protected static string $resource = TrainingBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
