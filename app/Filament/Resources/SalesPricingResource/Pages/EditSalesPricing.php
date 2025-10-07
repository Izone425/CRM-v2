<?php
// filepath: /var/www/html/timeteccrm/app/Filament/Resources/SalesPricingResource/Pages/EditSalesPricing.php
namespace App\Filament\Resources\SalesPricingResource\Pages;

use App\Filament\Resources\SalesPricingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSalesPricing extends EditRecord
{
    protected static string $resource = SalesPricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['last_updated_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
