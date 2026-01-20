<?php

namespace App\Filament\Resources\PaymentSettingResource\Pages;

use App\Filament\Resources\PaymentSettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Assets\Css;

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

    public function getTitle(): string
    {
        return 'Payment Settings';
    }

    public function getHeading(): string
    {
        return 'Payment Settings';
    }

    public function getSubheading(): ?string
    {
        return 'Configure payment gateways, invoices, and commission settings';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }
}
