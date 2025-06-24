<?php

namespace App\Filament\Resources\TrainingBookingResource\Pages;

use App\Filament\Resources\TrainingBookingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTrainingBookings extends ListRecords
{
    protected static string $resource = TrainingBookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
