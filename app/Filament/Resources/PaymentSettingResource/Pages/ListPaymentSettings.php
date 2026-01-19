<?php

namespace App\Filament\Resources\PaymentSettingResource\Pages;

use App\Filament\Resources\PaymentSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPaymentSettings extends ListRecords
{
    protected static string $resource = PaymentSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
