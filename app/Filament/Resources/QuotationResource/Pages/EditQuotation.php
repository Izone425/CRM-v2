<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Filament\Resources\QuotationResource;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\DeleteAction::make(),
            Actions\Action::make('back')
                ->url(static::getResource()::getUrl())
                ->icon('heroicon-o-chevron-left')
                ->button()
                ->color('info'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['quotation_date'] = Carbon::createFromFormat('j M Y',$data['quotation_date'])->format('Y-m-d');

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
                ->success()
                ->title('Quotation saved')
                ->body('The quotation #'.$this->record->quotation_reference_no.' has been saved successfully.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
