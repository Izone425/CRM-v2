<?php

namespace App\Filament\Resources\PaymentSettingResource\Pages;

use App\Filament\Resources\PaymentSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPaymentSetting extends EditRecord
{
    protected static string $resource = PaymentSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No delete action - single settings record only
        ];
    }

    protected function getRedirectUrl(): ?string
    {
        // Stay on the same page after saving
        return null;
    }
}
