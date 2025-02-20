<?php

namespace App\Filament\Resources\QuotationResource\Pages;

use App\Classes\Encryptor;
use App\Filament\Resources\QuotationResource;
use App\Models\ActivityLog;
use App\Services\QuotationService;
use Carbon\Carbon;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Http\RedirectResponse;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;
    protected static ?string $title = 'Create New Quotation';
    protected static bool $canCreateAnother = false;

    protected function beforeFill(): void
    {
        $leadId = request()->query('lead_id');

        if ($leadId) {
            $this->form->fill([
                'lead_id' => $leadId, // Pre-fill the lead_id field
            ]);
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->url(static::getResource()::getUrl())
                ->icon('heroicon-o-chevron-left')
                ->button()
                ->color('info'),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sales_person_id'] = auth()->user()->id;
        $data['quotation_date'] = Carbon::createFromFormat('j M Y',$data['quotation_date'])->format('Y-m-d');

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
                ->success()
                ->title('Quotation created')
                ->body('The quotation #'.$this->record->quotation_reference_no.' has been created successfully.');
    }

    protected function afterCreate(): void
    {
        /**
         * if quotation_reference_no is not set
         */
        if (!$this->record->quotation_reference_no) {
            $quotationService = new QuotationService;
            $this->record->quotation_reference_no = $quotationService->update_reference_no($this->record);
            $this->record->save();

            $lead = $this->record->lead; // Assuming the 'lead' relationship exists in Quotation
            if ($lead) {
                if ($lead->lead_status === 'RFQ-Transfer') {
                    $lead->update([
                        'lead_status' => 'Pending Demo',
                        'remark' => null,
                        'follow_up_date' => today(),
                    ]);
                }else if($lead->lead_status === 'RFQ-Follow Up'){
                    $lead->update([
                        'lead_status' => 'Hot',
                        'remark' => null,
                        'follow_up_date' => today(),
                    ]);
                }
            }

            // Step 3: Update the latest ActivityLog for this Lead
            $latestActivityLog = ActivityLog::where('subject_id', $lead->id ?? null)
                ->latest('created_at')
                ->first();

            if ($latestActivityLog) {
                $newDescription = 'Quotation Sent. '. $this->record->quotation_reference_no;

                // Check if description needs updating
                if ($latestActivityLog->description !== $newDescription) {
                    $latestActivityLog->update([
                        'description' => $newDescription,
                    ]);

                    // Log the activity for auditing
                    activity()
                        ->causedBy(auth()->user()) // Log current user
                        ->performedOn($lead)       // Associated Lead
                        ->withProperties([
                            'old_description' => $latestActivityLog->getOriginal('description'),
                            'new_description' => $newDescription,
                        ]);
                }
            }
                // $max_num = 9999;

                // $starting_number = 1000;
                // $reference_number = $starting_number + $this->record->id;

                // $year = now()->format('y');

                // $num = $reference_number%$max_num == 0 ? $max_num : ($reference_number%$max_num);

                // $this->record->quotation_reference_no = $year . sprintf('%04d',$num) . '/' . Str::upper(auth()->user()->code);
                // $this->record->save();
        }
    }

    protected function beforeCreate(): void
    {

    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
