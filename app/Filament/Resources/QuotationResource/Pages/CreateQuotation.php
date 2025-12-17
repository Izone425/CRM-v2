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
use Illuminate\Support\Facades\Log;

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

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Actions\Action::make('back')
    //             ->url(static::getResource()::getUrl())
    //             ->icon('heroicon-o-chevron-left')
    //             ->button()
    //             ->color('info'),
    //     ];
    // }

    public function getBreadcrumbs(): array
    {
        return [];
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

            $lead = $this->record->lead; // Assuming the 'lead' relationship exists in Quotation

            if ($lead) {
                // ✅ STEP 1: First, unmark ALL other quotations of the same sales_type as NOT final
                $lead->quotations()
                    ->where('sales_type', $this->record->sales_type)
                    ->where('id', '!=', $this->record->id) // Exclude current quotation
                    ->update(['mark_as_final' => 0]);

                // ✅ STEP 2: Always mark the NEW quotation as final (latest one wins)
                $this->record->mark_as_final = 1;

                Log::info('Quotation final status updated', [
                    'quotation_id' => $this->record->id,
                    'lead_id' => $lead->id,
                    'sales_type' => $this->record->sales_type,
                    'action' => 'Marked as final, unmarked previous quotations'
                ]);

                // Update lead status based on current status
                if ($lead->lead_status === 'RFQ-Transfer') {
                    $lead->update([
                        'lead_status' => 'Pending Demo',
                        'remark' => null,
                        'follow_up_date' => today(),
                    ]);
                } else if($lead->lead_status === 'RFQ-Follow Up') {
                    $lead->update([
                        'lead_status' => 'Hot',
                        'remark' => null,
                        'follow_up_date' => today(),
                    ]);
                }
            }

            // Save the quotation with updated values
            $this->record->save();

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
        }

        // Generate PDF after all database updates are complete
        $this->generateQuotationPDF($this->record);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    private function generateQuotationPDF($quotation): void
    {
        try {
            Log::info("Starting PDF generation for quotation {$quotation->id}");

            // Encrypt the quotation ID as expected by the controller
            $encryptedId = encrypt($quotation->id);

            // Use your existing PDF generation controller
            $controller = new \App\Http\Controllers\GenerateQuotationPdfController();
            $response = $controller->__invoke($encryptedId);

            Log::info("PDF generation completed for quotation {$quotation->id}");

        } catch (\Exception $e) {
            Log::error("Failed to auto-generate PDF for quotation {$quotation->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            // Don't throw the exception to prevent breaking the quotation creation flow
            // The PDF can be generated manually later if needed
        }
    }
}
