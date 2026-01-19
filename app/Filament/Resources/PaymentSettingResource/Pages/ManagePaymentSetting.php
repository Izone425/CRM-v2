<?php

namespace App\Filament\Resources\PaymentSettingResource\Pages;

use App\Filament\Resources\PaymentSettingResource;
use App\Models\PaymentSetting;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePaymentSetting extends ManageRecords
{
    protected static string $resource = PaymentSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions - single settings record only
        ];
    }

    public function mount(): void
    {
        // Get or create the single payment setting record
        $setting = PaymentSetting::firstOrCreate(
            [], // No conditions - get the first record
            [
                'company_name' => config('app.name'),
                'payment_gateway' => 'manual',
                'invoice_prefix' => 'INV',
                'invoice_next_number' => 1,
                'invoice_number_format' => 'INV-{YEAR}-{MONTH}-{NUMBER}',
                'invoice_due_days' => 30,
                'invoice_currency' => 'MYR',
                'invoice_tax_rate' => 0.00,
                'invoice_tax_label' => 'SST',
                'commission_type' => 'percentage',
                'commission_rate' => 0.00,
                'commission_calculation' => 'net',
                'commission_payout_days' => 30,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]
        );

        // Redirect to edit page
        $this->redirect(PaymentSettingResource::getUrl('edit', ['record' => $setting->id]));
    }
}
