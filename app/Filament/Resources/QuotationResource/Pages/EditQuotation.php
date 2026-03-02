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

    public function getBreadcrumbs(): array
    {
        return [];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['quotation_date'] = Carbon::createFromFormat('j M Y',$data['quotation_date'])->format('Y-m-d');

        return $data;
    }

    protected function afterSave(): void
    {
        $this->computeAndSaveItemYears($this->record);
    }

    private function computeAndSaveItemYears($quotation): void
    {
        $items = $quotation->items()->orderBy('sort_order')->get();
        $productCounters = [];

        // First pass: count occurrences of each software product
        foreach ($items as $item) {
            $product = $item->product;
            if (!$product || !str_starts_with($product->solution, 'software')) {
                continue;
            }
            $pid = $product->id;
            $productCounters[$pid] = ($productCounters[$pid] ?? 0) + 1;
        }

        // Only set year if any software product appears more than once
        $duplicated = array_filter($productCounters, fn($count) => $count > 1);
        if (empty($duplicated)) {
            return;
        }

        // Second pass: assign year numbers
        $productCounters = [];
        foreach ($items as $item) {
            $product = $item->product;
            if (!$product || !str_starts_with($product->solution, 'software')) {
                continue;
            }
            $pid = $product->id;
            $productCounters[$pid] = ($productCounters[$pid] ?? 0) + 1;

            if (isset($duplicated[$pid])) {
                $item->update(['year' => "Year {$productCounters[$pid]}"]);
            }
        }
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
