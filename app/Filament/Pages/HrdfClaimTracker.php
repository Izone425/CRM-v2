<?php

namespace App\Filament\Pages;

use App\Models\HrdfClaim;
use App\Models\Invoice;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HrdfClaimTracker extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'HRDF Claim Tracker';
    protected static ?string $title = 'HRDF Claim Tracker';
    protected static string $view = 'filament.pages.hrdf-claim-tracker';

    public function table(Table $table): Table
    {
        return $table
            ->query(HrdfClaim::query())
            ->columns([
                // Column 1 - HRDF Grant ID
                TextColumn::make('hrdf_grant_id')
                    ->label('HRDF Grant ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('medium'),

                // Column 2 - Company Name
                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->limit(30),

                // Column 3 - Invoice Amount
                TextColumn::make('invoice_amount')
                    ->label('Invoice Amount')
                    ->money('MYR')
                    ->sortable()
                    ->alignEnd(),

                // Column 4 - HRDF Training Date
                TextColumn::make('hrdf_training_date')
                    ->label('Training Date'),

                // Column 5 - HRDF Claim Status
                BadgeColumn::make('claim_status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'PENDING',
                        'primary' => 'SUBMITTED',
                        'success' => 'APPROVED',
                        'info' => 'RECEIVED',
                    ])
                    ->sortable(),

                // Column 6 - Invoice Number (with matching logic)
                // SelectColumn::make('invoice_number')
                //     ->label('Invoice Number')
                //     ->options(function (HrdfClaim $record): array {
                //         return $this->getMatchingInvoices($record);
                //     })
                //     ->selectablePlaceholder('Select Invoice')
                //     ->afterStateUpdated(function (HrdfClaim $record, $state) {
                //         $this->updateInvoiceMapping($record, $state);
                //     }),

                // Column 7 - Sales Person (Auto update after invoice selection)
                TextColumn::make('sales_person')
                    ->label('SalesPerson'),

                // Column 8 - HRDF Claim ID (Auto mapping)
                TextColumn::make('hrdf_claim_id')
                    ->label('HRDF Claim ID')
                    ->default(fn (HrdfClaim $record) => $record->hrdf_claim_id ?: 'N/A')
                    ->copyable(),
            ])
            ->filters([
                // Add filters for better data management
            ])
            ->actions([
                ActionGroup::make([
                    // Action::make('mark_as_submitted')
                    //     ->label('Mark as Submitted')
                    //     ->icon('heroicon-o-paper-airplane')
                    //     ->color('primary')
                    //     ->form([
                    //         TextInput::make('hrdf_claim_id')
                    //             ->label('HRDF Claim ID')
                    //             ->required()
                    //             ->placeholder('Enter HRDF Claim ID')
                    //             ->maxLength(100)
                    //             ->helperText('This will be used to update both HRDF Claim and Handover records'),

                    //         TextInput::make('invoice_number')
                    //             ->label('Invoice Number')
                    //             ->required()
                    //             ->placeholder('Enter Invoice Number')
                    //             ->maxLength(50)
                    //             ->helperText('AutoCount invoice number for this claim'),
                    //     ])
                    //     ->action(function (HrdfClaim $record, array $data): void {
                    //         try {
                    //             // Start database transaction
                    //             DB::transaction(function () use ($record, $data) {
                    //                 // 1. Update HRDF Claim
                    //                 $record->update([
                    //                     'claim_status' => 'SUBMITTED',
                    //                     'hrdf_claim_id' => strtoupper($data['hrdf_claim_id']),
                    //                     'invoice_number' => $data['invoice_number'],
                    //                 ]);

                    //                 // 2. Find and update corresponding HRDF Handover
                    //                 $hrdfHandover = \App\Models\HRDFHandover::where('hrdf_grant_id', $record->hrdf_grant_id)
                    //                     ->first();

                    //                 if ($hrdfHandover) {
                    //                     $hrdfHandover->update([
                    //                         'status' => 'New',
                    //                         'hrdf_claim_id' => strtoupper($data['hrdf_claim_id']),
                    //                         'autocount_invoice_number' => $data['invoice_number'],
                    //                     ]);

                    //                     Log::info('HRDF Handover updated to NEW status', [
                    //                         'handover_id' => $hrdfHandover->id,
                    //                         'hrdf_grant_id' => $record->hrdf_grant_id,
                    //                         'hrdf_claim_id' => $data['hrdf_claim_id'],
                    //                         'invoice_number' => $data['invoice_number']
                    //                     ]);
                    //                 } else {
                    //                     Log::warning('No HRDF Handover found for grant ID', [
                    //                         'hrdf_grant_id' => $record->hrdf_grant_id,
                    //                         'hrdf_claim_id' => $data['hrdf_claim_id']
                    //                     ]);
                    //                 }
                    //             });

                    //             Notification::make()
                    //                 ->title('Claim Marked as Submitted')
                    //                 ->body("HRDF Claim {$record->hrdf_grant_id} has been marked as submitted. HRDF Handover status updated to NEW.")
                    //                 ->success()
                    //                 ->send();

                    //         } catch (\Exception $e) {
                    //             Log::error('Failed to mark HRDF claim as submitted', [
                    //                 'hrdf_grant_id' => $record->hrdf_grant_id,
                    //                 'error' => $e->getMessage(),
                    //                 'trace' => $e->getTraceAsString()
                    //             ]);

                    //             Notification::make()
                    //                 ->title('Error')
                    //                 ->body('Failed to update records. Please try again.')
                    //                 ->danger()
                    //                 ->send();
                    //         }
                    //     })
                    //     ->visible(fn (HrdfClaim $record): bool => $record->claim_status === 'PENDING'),

                    // Action::make('view_details')
                    //     ->label('View Details')
                    //     ->icon('heroicon-o-eye')
                    //     ->color('gray')
                    //     ->modalContent(fn (HrdfClaim $record): string => view('filament.modals.hrdf-claim-details', compact('record'))->render())
                    //     ->modalHeading(fn (HrdfClaim $record): string => 'HRDF Claim Details - ' . $record->hrdf_grant_id)
                    //     ->modalWidth('4xl'),

                    // Action::make('add_remark')
                    //     ->label('Add Remark')
                    //     ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    //     ->color('warning')
                    //     ->form([
                    //         Textarea::make('sales_remark')
                    //             ->label('Add Remark')
                    //             ->placeholder('Enter your remark here...')
                    //             ->default(fn (HrdfClaim $record) => $record->sales_remark)
                    //             ->maxLength(500)
                    //             ->required(),
                    //     ])
                    //     ->action(function (HrdfClaim $record, array $data): void {
                    //         $record->update([
                    //             'sales_remark' => $data['sales_remark']
                    //         ]);

                    //         Notification::make()
                    //             ->title('Remark Added')
                    //             ->body('Remark has been saved successfully.')
                    //             ->success()
                    //             ->send();
                    //     }),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->size('sm')
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([50, 100]);
    }

    /**
     * Get matching invoices based on company name and amount
     */
    // private function getMatchingInvoices(HrdfClaim $record): array
    // {
    //     $invoices = Invoice::where('company_name', $record->company_name)
    //         ->where('total_amount', $record->invoice_amount)
    //         ->whereNull('hrdf_claim_id') // Only show unmapped invoices
    //         ->get();

    //     return $invoices->pluck('invoice_number', 'invoice_number')->toArray();
    // }

    /**
     * Update invoice mapping and auto-update sales person and HRDF claim ID
     */
    private function updateInvoiceMapping(HrdfClaim $record, ?string $invoiceNumber): void
    {
        if (!$invoiceNumber) {
            return;
        }

        // Find the invoice
        $invoice = Invoice::where('invoice_number', $invoiceNumber)->first();

        if (!$invoice) {
            Notification::make()
                ->title('Invoice not found')
                ->danger()
                ->send();
            return;
        }

        // Update the HRDF claim
        $record->mapInvoiceDetails(
            $invoiceNumber,
            $invoice->sales_person ?? $record->sales_person
        );

        // Update the invoice with HRDF claim reference
        $invoice->update([
            'hrdf_claim_id' => $record->hrdf_claim_id,
            'hrdf_grant_id' => $record->hrdf_grant_id,
        ]);

        // Auto-update status if needed
        if ($record->claim_status === 'PENDING') {
            $record->updateStatus('SUBMITTED');
        }
    }
}
