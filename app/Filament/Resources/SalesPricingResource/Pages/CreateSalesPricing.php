<?php
// filepath: /var/www/html/timeteccrm/app/Filament/Resources/SalesPricingResource/Pages/CreateSalesPricing.php
namespace App\Filament\Resources\SalesPricingResource\Pages;

use App\Filament\Resources\SalesPricingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSalesPricing extends CreateRecord
{
    protected static string $resource = SalesPricingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['last_updated_by'] = auth()->id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
