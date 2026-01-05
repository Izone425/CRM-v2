<?php
// filepath: /var/www/html/timeteccrm/app/Filament/Resources/LeadResource/RelationManagers/RenewalHandoverRelationManager.php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Models\CrmHrdfInvoice;
use App\Models\Lead;
use App\Models\QuotationDetail;
use App\Models\RenewalHandover; // ✅ Add this import
use App\Services\AutoCountIntegrationService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\HeaderAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class RenewalHandoverRelationManager extends RelationManager
{
    protected static string $relationship = 'renewalHandover';
    protected static ?string $title = 'Renewal Handover';
    protected static ?string $modelLabel = 'Renewal';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('formatted_handover_id')
            ->columns([
                Tables\Columns\TextColumn::make('formatted_handover_id')
                    ->label('Handover ID')
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->limit(30),

                // ✅ Make quotations count clickable
                Tables\Columns\TextColumn::make('total_quotations')
                    ->label('Quotations')
                    ->badge()
                    ->color('info')
                    ->getStateUsing(function (RenewalHandover $record): string {
                        return (string) count($record->selected_quotation_ids ?? []);
                    })
                    ->action(
                        Tables\Actions\Action::make('view_quotations')
                            ->modalHeading('Quotation References')
                            ->modalContent(function (RenewalHandover $record): \Illuminate\View\View {
                                if (empty($record->selected_quotation_ids)) {
                                    return view('filament.modals.empty-state', [
                                        'message' => 'No quotations found for this handover.'
                                    ]);
                                }

                                $quotations = \App\Models\Quotation::whereIn('id', $record->selected_quotation_ids)
                                    ->get(['id', 'pi_reference_no']);

                                return view('filament.modals.quotations-list', [
                                    'quotations' => $quotations,
                                    'handover' => $record
                                ]);
                            })
                            ->modalWidth('lg')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                    ),

                // ✅ Make invoices count clickable
                Tables\Columns\TextColumn::make('total_invoices')
                    ->label('Invoices')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(function (RenewalHandover $record): string {
                        return (string) count($record->invoice_numbers ?? []);
                    })
                    ->action(
                        Tables\Actions\Action::make('view_invoices')
                            ->modalHeading('Invoice Numbers')
                            ->modalContent(function (RenewalHandover $record): \Illuminate\View\View {
                                if (empty($record->invoice_numbers)) {
                                    return view('filament.modals.empty-state', [
                                        'message' => 'No invoices found for this handover.'
                                    ]);
                                }

                                return view('filament.modals.invoices-list', [
                                    'invoices' => $record->invoice_numbers,
                                    'handover' => $record
                                ]);
                            })
                            ->modalWidth('lg')
                            ->modalSubmitAction(false)
                            ->modalCancelActionLabel('Close')
                    ),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount (with Tax)') // ✅ Updated label to clarify it includes tax
                    ->money('MYR')
                    ->sortable()
                    ->getStateUsing(function (RenewalHandover $record): float {
                        // ✅ Recalculate to ensure it includes tax
                        if (empty($record->selected_quotation_ids)) {
                            return 0;
                        }

                        return \App\Models\QuotationDetail::whereIn('quotation_id', $record->selected_quotation_ids)
                            ->sum('total_after_tax'); // ✅ Sum with tax included
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'processing' => 'warning',
                        'failed' => 'danger',
                        'pending' => 'gray',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('creator.name')
                    ->label('Created By')
                    ->limit(20),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->headerActions([
                Action::make('create_hrdf_invoices')
                    ->label('Renewal Handover')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Create HRDF Invoices for Renewal')
                    ->modalWidth('xl')
                    ->form([
                        Grid::make(1)->schema([
                            Select::make('selected_proforma_invoices')
                                ->label('Select Proforma Invoice (HRDF)')
                                ->required()
                                ->options(function () {
                                    try {
                                        $lead = $this->getOwnerRecord();

                                        if (!$lead) return [];

                                        // ✅ Only show quotations that haven't generated AutoCount invoices yet
                                        $quotations = \App\Models\Quotation::with('subsidiary', 'lead.companyDetail')
                                            ->where('lead_id', $lead->id)
                                            ->where('status', 'accepted')
                                            ->where('sales_type', 'RENEWAL SALES')
                                            ->where('autocount_generated_pi', false) // ✅ Exclude already processed
                                            ->orderBy('created_at', 'desc')
                                            ->get();

                                        $options = [];
                                        foreach ($quotations as $quotation) {
                                            $label = $quotation->pi_reference_no ?? "Quotation ID: {$quotation->id}";

                                            // Prioritize subsidiary company name if available
                                            if ($quotation->subsidiary && !empty($quotation->subsidiary->company_name)) {
                                                $label .= ' - ' . $quotation->subsidiary->company_name;
                                            } elseif ($quotation->lead && $quotation->lead->companyDetail) {
                                                $label .= ' - ' . $quotation->lead->companyDetail->company_name;
                                            }

                                            $options[$quotation->id] = $label;
                                        }

                                        return $options;
                                    } catch (\Exception $e) {
                                        Log::error('Error loading quotations: ' . $e->getMessage());
                                        return [];
                                    }
                                })
                                ->helperText('Only showing quotations that haven\'t generated AutoCount invoices yet')
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function (callable $get, callable $set) {
                                    $this->updateHrdfGrantIdRepeater($get, $set);
                                })
                                ->placeholder('Select one proforma invoice'),

                            Repeater::make('hrdf_grant_ids')
                                ->label('HRDF Grant IDs')
                                ->schema([
                                    TextInput::make('proforma_invoice_name')
                                        ->label('Proforma Invoice')
                                        ->disabled()
                                        ->dehydrated(false),
                                    TextInput::make('hrdf_grant_id')
                                        ->label('HRDF Grant ID')
                                        ->placeholder('Enter HRDF Grant ID')
                                        ->required()
                                        ->live(debounce: 500)
                                        ->rules([
                                            function () {
                                                return function (string $attribute, $value, \Closure $fail) {
                                                    if (empty($value)) {
                                                        return;
                                                    }

                                                    $hrdfClaim = \App\Models\HrdfClaim::where('hrdf_grant_id', $value)->first();

                                                    if (!$hrdfClaim) {
                                                        $fail('HRDF Grant ID not found in HRDF Claims.');
                                                        return;
                                                    }

                                                    // Check if required fields have values
                                                    $requiredFields = [
                                                        'invoice_amount' => 'Invoice Amount',
                                                        'upfront_payment' => 'Upfront Payment',
                                                        'pax' => 'Pax'
                                                    ];

                                                    $missingFields = [];
                                                    foreach ($requiredFields as $field => $label) {
                                                        if (empty($hrdfClaim->$field) || (is_numeric($hrdfClaim->$field) && $hrdfClaim->$field <= 0)) {
                                                            $missingFields[] = $label;
                                                        }
                                                    }

                                                    if (!empty($missingFields)) {
                                                        $fail('HRDF Grant ID is missing required data: ' . implode(', ', $missingFields));
                                                    }
                                                };
                                            },
                                        ])
                                ])
                                ->columns(2)
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->visible(fn (callable $get) => !empty($get('selected_proforma_invoices')))
                                ->helperText('Enter HRDF Grant ID for each selected proforma invoice'),

                            // Section::make('Invoice Preview')
                            //     ->schema([
                            //         Placeholder::make('invoice_preview')
                            //             ->label('')
                            //             ->content(function (callable $get) {
                            //                 $selectedPIs = $get('selected_proforma_invoices');

                            //                 if (empty($selectedPIs)) {
                            //                     return 'Please select proforma invoices to preview.';
                            //                 }

                            //                 try {
                            //                     $lead = $this->getOwnerRecord();

                            //                     if (!$lead) {
                            //                         return 'Lead not found.';
                            //                     }

                            //                     $preview = $this->generateRenewalInvoicePreview($lead, $selectedPIs);

                            //                     if (empty($preview['invoices'])) {
                            //                         return $preview['message'] ?? 'No items to display';
                            //                     }

                            //                     // Get company name from first quotation's subsidiary if available
                            //                     $displayCompanyName = $lead->companyDetail->company_name;
                            //                     if (!empty($selectedPIs)) {
                            //                         $firstQuotation = \App\Models\Quotation::with('subsidiary', 'lead.companyDetail')->find($selectedPIs[0]);
                            //                         if ($firstQuotation && $firstQuotation->subsidiary && !empty($firstQuotation->subsidiary->company_name)) {
                            //                             $displayCompanyName = $firstQuotation->subsidiary->company_name;
                            //                         } elseif ($firstQuotation && $firstQuotation->lead && $firstQuotation->lead->companyDetail && !empty($firstQuotation->lead->companyDetail->company_name)) {
                            //                             $displayCompanyName = $firstQuotation->lead->companyDetail->company_name;
                            //                         }
                            //                     }

                            //                     $html = '<div class="space-y-4">';
                            //                     $html .= '<div><strong>Debtor:</strong> ARM-P0062 - PEMBANGUNAN SUMBER MANUSIA BERHAD</div>';
                            //                     $html .= '<div><strong>Company:</strong> ' . $displayCompanyName . '</div>';
                            //                     $html .= '<div><strong>Support Person:</strong> FATIMAH</div>'; // ✅ Show support person
                            //                     $html .= '<div><strong>Salesperson:</strong> None (Renewal)</div>'; // ✅ Show no salesperson
                            //                     $html .= '<div><strong>Total Invoices:</strong> ' . $preview['total_invoices'] . '</div>';

                            //                     // Show each invoice separately
                            //                     foreach ($preview['invoices'] as $index => $invoice) {
                            //                         $html .= '<div class="p-3 mt-4 border rounded bg-gray-50">';
                            //                         $html .= '<div class="font-semibold text-blue-600">Invoice ' . ($index + 1) . ' (Renewal)</div>';
                            //                         $html .= '<div><strong>Document No:</strong> ' . $invoice['invoice_no'] . '</div>';
                            //                         $html .= '<div><strong>Support:</strong> FATIMAH</div>'; // ✅ Show support per invoice
                            //                         $html .= '<div class="mt-2">';
                            //                         $html .= '<div class="mb-2 text-sm font-semibold">Items:</div>';

                            //                         foreach ($invoice['items'] as $item) {
                            //                             $html .= '<div class="flex justify-between py-1 text-sm">';
                            //                             $html .= '<div class="flex items-center gap-2">';
                            //                             $html .= '<span class="px-2 py-1 font-mono text-xs bg-gray-100 rounded">' . $item['code'] . '</span>';
                            //                             $html .= '<span class="text-gray-600">× ' . number_format($item['quantity']) . '</span>';
                            //                             $html .= '</div>';
                            //                             $html .= '<span class="font-semibold">RM ' . number_format($item['amount'], 2) . '</span>';
                            //                             $html .= '</div>';
                            //                         }

                            //                         $html .= '</div>';
                            //                         $html .= '<div class="flex justify-between pt-2 mt-2 font-semibold border-t">';
                            //                         $html .= '<span>Invoice Total:</span><span>RM ' . number_format($invoice['total'], 2) . '</span>';
                            //                         $html .= '</div></div>';
                            //                     }

                            //                     // Show grand total if multiple invoices
                            //                     if ($preview['total_invoices'] > 1) {
                            //                         $html .= '<div class="flex justify-between pt-2 mt-4 text-lg font-bold border-t-2 border-blue-500">';
                            //                         $html .= '<span>Grand Total:</span><span>RM ' . number_format($preview['grand_total'], 2) . '</span>';
                            //                         $html .= '</div>';
                            //                     }

                            //                     $html .= '</div>';

                            //                     return new HtmlString($html);
                            //                 } catch (\Exception $e) {
                            //                     Log::error('Error generating preview: ' . $e->getMessage());
                            //                     return 'Error generating preview.';
                            //                 }
                            //             })
                            //     ])
                            //     ->visible(fn (callable $get) => !empty($get('selected_proforma_invoices'))),
                        ]),
                    ])
                    ->action(function (array $data): void {
                        try {
                            $lead = $this->getOwnerRecord();

                            if (!$lead) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Lead not found')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $selectedPI = $data['selected_proforma_invoices'];

                            if (empty($selectedPI)) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Please select a proforma invoice')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // ✅ Create RenewalHandover record first
                            $totalAmount = 0;

                            // Get company name from selected quotation's subsidiary if available
                            $companyName = $lead->companyDetail->company_name;
                            $selectedQuotation = \App\Models\Quotation::with('subsidiary', 'lead.companyDetail')->find($selectedPI);
                            if ($selectedQuotation && $selectedQuotation->subsidiary && !empty($selectedQuotation->subsidiary->company_name)) {
                                $companyName = $selectedQuotation->subsidiary->company_name;
                            } elseif ($selectedQuotation && $selectedQuotation->lead && $selectedQuotation->lead->companyDetail && !empty($selectedQuotation->lead->companyDetail->company_name)) {
                                $companyName = $selectedQuotation->lead->companyDetail->company_name;
                            }

                            // ✅ UPDATED: Sum up total_after_tax for the selected quotation
                            $totalAmount = \App\Models\QuotationDetail::where('quotation_id', $selectedPI)
                                ->sum('total_after_tax'); // ✅ Changed from total_before_tax

                            $renewalHandover = RenewalHandover::create([
                                'lead_id' => $lead->id,
                                'company_name' => $companyName,
                                'selected_quotation_ids' => [$selectedPI], // ✅ Wrap in array for consistency
                                'total_amount' => $totalAmount, // ✅ Now includes tax
                                'status' => 'new',
                                'created_by' => auth()->id(),
                                'tt_invoice_number' => '', // Empty since we removed license number field
                                'hrdf_grant_ids' => $data['hrdf_grant_ids'] ?? [], // ✅ Save HRDF grant IDs
                            ]);

                            Notification::make()
                                ->title('Created Successfully')
                                ->body('Renewal Handover ' . $renewalHandover->handover_id . ' created successfully.')
                                ->success()
                                ->send();

                            // ✅ Create the renewal invoices using AutoCount
                            // $result = $this->createRenewalInvoices($lead, $selectedPIs, $renewalHandover);

                            // if ($result['success']) {
                            //     // ✅ Update RenewalHandover as completed
                            //     $renewalHandover->markAsCompleted(
                            //         $result['invoice_numbers'],
                            //         $result['autocount_response'] ?? null
                            //     );

                            //     $notificationBody = "Created {$result['total_invoices']} invoice(s) successfully";
                            //     if (isset($result['invoice_numbers'])) {
                            //         $notificationBody .= "\nInvoice Numbers: " . implode(', ', $result['invoice_numbers']);
                            //     }

                            //     Notification::make()
                            //         ->title('HRDF Invoices Created Successfully')
                            //         ->body($notificationBody)
                            //         ->success()
                            //         ->send();

                            //     Log::info('Renewal HRDF invoices created successfully', [
                            //         'renewal_handover_id' => $renewalHandover->handover_id,
                            //         'lead_id' => $lead->id,
                            //         'company_name' => $lead->companyDetail->company_name,
                            //         'selected_pis' => $selectedPIs,
                            //         'invoice_numbers' => $result['invoice_numbers'] ?? [],
                            //     ]);
                            // } else {
                            //     // ✅ Mark RenewalHandover as failed
                            //     $renewalHandover->markAsFailed($result['error'] ?? 'Unknown error');

                            //     Notification::make()
                            //         ->title('Failed to Create HRDF Invoices')
                            //         ->body($result['error'] ?? 'Unknown error occurred')
                            //         ->danger()
                            //         ->send();

                            //     Log::error('Failed to create renewal HRDF invoices', [
                            //         'renewal_handover_id' => $renewalHandover->handover_id,
                            //         'lead_id' => $lead->id,
                            //         'error' => $result['error'] ?? 'Unknown error',
                            //     ]);
                            // }

                        } catch (\Exception $e) {
                            Log::error('Exception in renewal invoice creation: ' . $e->getMessage());

                            Notification::make()
                                ->title('Error')
                                ->body('An unexpected error occurred: ' . $e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading('No Renewals Found')
            ->emptyStateDescription('Use the "Renewal Handover" button above to create HRDF invoices for renewals.')
            ->emptyStateIcon('heroicon-o-document-text');
    }

    /**
     * Generate invoice preview for renewal
     */
    protected function generateRenewalInvoicePreview(Lead $lead, array $quotationIds): array
    {
        if (empty($quotationIds)) {
            return [
                'invoices' => [],
                'total_invoices' => 0,
                'grand_total' => 0,
                'message' => 'No quotation IDs provided'
            ];
        }

        $invoiceNumbers = $this->generateMultipleInvoiceNumbers(count($quotationIds));
        $invoices = [];
        $grandTotal = 0;

        foreach ($quotationIds as $index => $quotationId) {
            $details = QuotationDetail::where('quotation_id', $quotationId)
                ->with('product')
                ->get();

            // Group items by product code and unit price
            $groupedItems = [];
            $invoiceTotal = 0;

            foreach ($details as $detail) {
                $productCode = $detail->product->code ?? 'Item-' . $detail->product_id;
                $unitPrice = (float) $detail->unit_price;
                // ✅ UPDATED: Use total_after_tax instead of total_before_tax
                $amount = (float) $detail->total_after_tax; // ✅ Changed to include tax
                $quantity = (float) $detail->quantity;

                $key = $productCode . '|' . $unitPrice;

                if (isset($groupedItems[$key])) {
                    $groupedItems[$key]['quantity'] += $quantity;
                    $groupedItems[$key]['amount'] += $amount;
                } else {
                    $groupedItems[$key] = [
                        'code' => $productCode,
                        'quantity' => $quantity,
                        'unit_price' => $unitPrice,
                        'amount' => $amount // ✅ Now includes tax
                    ];
                }

                $invoiceTotal += $amount; // ✅ Total now includes tax
            }

            $items = array_values($groupedItems);

            $invoices[] = [
                'invoice_no' => $invoiceNumbers[$index],
                'items' => $items,
                'total' => $invoiceTotal, // ✅ Total with tax
                'quotation_ids' => [$quotationId],
                'support_person' => 'FATIMAH',
            ];

            $grandTotal += $invoiceTotal; // ✅ Grand total with tax
        }

        return [
            'invoices' => $invoices,
            'total_invoices' => count($invoices),
            'grand_total' => $grandTotal, // ✅ Includes tax
            'support_person' => 'FATIMAH'
        ];
    }

    /**
     * Create renewal invoices via AutoCount API
     */
    protected function createRenewalInvoices(Lead $lead, array $quotationIds, RenewalHandover $renewalHandover): array
    {
        try {
            $autoCountIntegrationService = app(AutoCountIntegrationService::class);

            $result = [
                'success' => false,
                'invoice_numbers' => [],
                'total_invoices' => 0,
                'error' => null,
                'autocount_response' => []
            ];

            // ✅ Check if any quotations already have AutoCount invoices generated
            $alreadyProcessed = \App\Models\Quotation::whereIn('id', $quotationIds)
                ->where('autocount_generated_pi', true)
                ->pluck('pi_reference_no')
                ->toArray();

            if (!empty($alreadyProcessed)) {
                $result['error'] = 'The following quotations already have AutoCount invoices: ' . implode(', ', $alreadyProcessed);
                return $result;
            }

            // ✅ Pre-generate ALL invoice numbers at once to avoid gaps
            $invoiceNumbers = $this->generateMultipleInvoiceNumbers(count($quotationIds));
            $createdInvoices = [];

            foreach ($quotationIds as $index => $quotationId) {
                // ✅ Use the pre-generated invoice number
                $invoiceNo = $invoiceNumbers[$index];

                // Get quotation details
                $details = QuotationDetail::where('quotation_id', $quotationId)
                    ->with('product')
                    ->get();

                if ($details->isEmpty()) {
                    continue;
                }

                // Get company name from quotation subsidiary if available
                $quotation = \App\Models\Quotation::with('subsidiary', 'lead.companyDetail')->find($quotationId);
                $customerName = $lead->companyDetail->company_name;
                if ($quotation) {
                    if ($quotation->subsidiary_id && $quotation->subsidiary) {
                        $customerName = $quotation->subsidiary->company_name;
                    } elseif ($quotation->lead && $quotation->lead->companyDetail) {
                        $customerName = $quotation->lead->companyDetail->company_name;
                    }
                }

                // ✅ Prepare invoice data for AutoCount API with FATIMAH as support
                $invoiceData = [
                    'company' => 'TIMETEC CLOUD Sandbox',
                    'customer_code' => 'ARM-P0062',
                    'document_no' => $invoiceNo,
                    'document_date' => now()->format('Y-m-d'),
                    'description' => 'Renewal Invoice - ' . $customerName,
                    'salesperson' => null, // ✅ No salesperson for renewals
                    'round_method' => 0,
                    'inclusive' => true,
                    'details' => $this->getInvoiceDetailsFromQuotation($quotationId),
                    'udfSupport' => 'FATIMAH', // ✅ Add udfSupport field for FATIMAH
                    'uDFCustomerName' => $customerName,
                    'uDFLicenseNumber' => '', // Empty since license number field was removed
                ];

                // Use the AutoCountInvoiceService
                $autoCountService = app(\App\Services\AutoCountInvoiceService::class);
                $invoiceResult = $autoCountService->createInvoice($invoiceData);

                if (!$invoiceResult['success']) {
                    $result['error'] = "Failed to create invoice {$invoiceNo}: " . $invoiceResult['error'];
                    return $result;
                }

                $result['autocount_response'][] = $invoiceResult;
                $createdInvoices[] = $invoiceNo;

                // ✅ Create CrmHrdfInvoice record with FATIMAH as salesperson (support)
                $total = \App\Models\QuotationDetail::where('quotation_id', $quotationId)
                    ->sum('total_after_tax'); // ✅ Changed from total_before_tax to total_after_tax

                // Get company name from quotation subsidiary if available (same as AutoCount logic)
                $quotationRecord = \App\Models\Quotation::with('subsidiary', 'lead.companyDetail')->find($quotationId);
                $customerName = $lead->companyDetail->company_name;
                if ($quotationRecord && $quotationRecord->subsidiary && !empty($quotationRecord->subsidiary->company_name)) {
                    $customerName = $quotationRecord->subsidiary->company_name;
                } elseif ($quotationRecord && $quotationRecord->lead && $quotationRecord->lead->companyDetail && !empty($quotationRecord->lead->companyDetail->company_name)) {
                    $customerName = $quotationRecord->lead->companyDetail->company_name;
                }

                // Get FATIMAH's autocount_name
                $fatimahUser = \App\Models\User::where('name', 'FATIMAH')->first();
                $fatimahAutoCountName = $fatimahUser?->autocount_name ?? 'FATIMAH';

                CrmHrdfInvoice::create([
                    'invoice_no' => $invoiceNo,
                    'invoice_date' => now()->toDateString(),
                    'company_name' => $customerName,
                    'handover_type' => 'RW',
                    'salesperson' => $fatimahAutoCountName, // ✅ Use FATIMAH's autocount_name
                    'handover_id' => $renewalHandover->id, // ✅ Use renewal handover ID
                    'quotation_id' => $quotationId, // ✅ Store quotation ID for "View PI" functionality
                    'debtor_code' => 'ARM-P0062',
                    'total_amount' => $total, // ✅ Now includes tax
                    'tt_invoice_number' => '', // Empty since license number field was removed
                ]);

                Log::info('Renewal HRDF Invoice record created', [
                    'invoice_no' => $invoiceNo,
                    'renewal_handover_id' => $renewalHandover->id,
                    'quotation_id' => $quotationId,
                    'total_after_tax' => $total, // ✅ Log with tax included
                    'handover_type' => 'RW',
                ]);

                // ✅ Mark the quotation as having AutoCount invoice generated
                \App\Models\Quotation::where('id', $quotationId)->update([
                    'autocount_generated_pi' => true
                ]);

                Log::info('Renewal HRDF Invoice record created', [
                    'invoice_no' => $invoiceNo,
                    'renewal_handover_formatted_id' => $renewalHandover->handover_id,
                    'lead_id' => $lead->id,
                    'quotation_id' => $quotationId,
                    'company_name' => $lead->companyDetail->company_name,
                    'total_amount' => $total,
                    'handover_type' => 'RW',
                    'support_person' => 'FATIMAH', // ✅ Log support person
                    'salesperson' => 'None (Renewal)', // ✅ Log no salesperson
                    'autocount_generated_pi' => true
                ]);
            }

            $result['success'] = true;
            $result['invoice_numbers'] = $createdInvoices;
            $result['total_invoices'] = count($createdInvoices);

            return $result;

        } catch (\Exception $e) {
            Log::error('Renewal invoice creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    protected function generateMultipleInvoiceNumbers(int $count): array
    {
        $year = date('y');
        $month = date('m');
        $yearMonth = $year . $month;

        // Get latest sequence from CRM HRDF invoices table - ONE TIME ONLY
        $latestInvoice = CrmHrdfInvoice::where('invoice_no', 'LIKE', "EHIN{$yearMonth}-%")
            ->orderByRaw('CAST(SUBSTRING(invoice_no, -4) AS UNSIGNED) DESC')
            ->first();

        $startSequence = 1;
        if ($latestInvoice) {
            preg_match("/EHIN{$yearMonth}-(\d+)/", $latestInvoice->invoice_no, $matches);
            $startSequence = (isset($matches[1]) ? intval($matches[1]) : 0) + 1;
        }

        // Generate all invoice numbers sequentially
        $invoiceNumbers = [];
        for ($i = 0; $i < $count; $i++) {
            $sequence = str_pad($startSequence + $i, 4, '0', STR_PAD_LEFT);
            $invoiceNumbers[] = "EHIN{$yearMonth}-{$sequence}";
        }

        return $invoiceNumbers;
    }

    protected function generateRenewalInvoiceNumber(int $index = 0): string
    {
        $year = date('y');
        $month = date('m');
        $yearMonth = $year . $month;

        $latestInvoice = CrmHrdfInvoice::where('invoice_no', 'LIKE', "EHIN{$yearMonth}-%")
            ->orderByRaw('CAST(SUBSTRING(invoice_no, -4) AS UNSIGNED) DESC')
            ->first();

        $baseSequence = 1;
        if ($latestInvoice) {
            preg_match("/EHIN{$yearMonth}-(\d+)/", $latestInvoice->invoice_no, $matches);
            $baseSequence = (isset($matches[1]) ? intval($matches[1]) : 0) + 1;
        }

        $nextSequence = $baseSequence + $index;
        $sequence = str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
        return "EHIN{$yearMonth}-{$sequence}";
    }

    protected function getInvoiceDetailsFromQuotation(int $quotationId): array
    {
        $details = QuotationDetail::where('quotation_id', $quotationId)
            ->with('product')
            ->get();

        $invoiceDetails = [];
        foreach ($details as $detail) {
            $product = $detail->product;
            $account = $this->getAccountFromProduct($product);

            // ✅ Tax information
            $taxCode = '';
            $taxRate = 0;
            if ($product && $product->taxable) {
                $taxCode = 'SV-8';
                $taxRate = 8;
            }

            // ✅ Calculate tax-inclusive unit price for AutoCount
            $baseUnitPrice = (float) $detail->unit_price;
            $taxInclusiveUnitPrice = $baseUnitPrice;

            if ($product && $product->taxable && $taxRate > 0) {
                // Calculate tax-inclusive price: base price * (1 + tax rate)
                $taxInclusiveUnitPrice = $baseUnitPrice * (1 + ($taxRate / 100));
            }

            $invoiceDetails[] = [
                'account' => $account,
                'itemCode' => $product->code ?? 'ITEM-' . $product->id,
                'location' => 'HQ',
                'quantity' => (float) $detail->quantity,
                'uom' => 'UNIT',
                'unitPrice' => $taxInclusiveUnitPrice, // ✅ Send tax-inclusive price to AutoCount
                'taxCode' => $taxCode,
                'taxRate' => $taxRate,
            ];
        }

        return $invoiceDetails;
    }

    protected function getAccountFromProduct($product): string
    {
        if ($product && $product->gl_posting) {
            $glPosting = trim($product->gl_posting);
            if (preg_match('/^\d{5}-\d{3}$/', $glPosting)) {
                return $glPosting;
            }
        }

        return '40000-000'; // Default account
    }

    protected function getAutoCountSalesperson(Lead $lead): string
    {
        if ($lead->salesperson) {
            $user = \App\Models\User::find($lead->salesperson);
            if ($user && $user->autocount_name) {
                return $user->autocount_name;
            }
        }

        return 'ADMIN'; // Default fallback
    }

    protected function getSalespersonName(Lead $lead): string
    {
        if ($lead->salesperson) {
            $user = \App\Models\User::find($lead->salesperson);
            if ($user) {
                return $user->name;
            }
        }

        return 'Unknown Salesperson';
    }

    protected function updateHrdfGrantIdRepeater(callable $get, callable $set): void
    {
        $selectedQuotation = $get('selected_proforma_invoices'); // Now single value
        $currentGrantIds = $get('hrdf_grant_ids') ?? [];

        if (empty($selectedQuotation)) {
            $set('hrdf_grant_ids', []);
            return;
        }

        // Create a lookup map for existing grant IDs to preserve user input
        $existingGrantIds = [];
        foreach ($currentGrantIds as $item) {
            if (isset($item['quotation_id'])) {
                $existingGrantIds[$item['quotation_id']] = $item['hrdf_grant_id'] ?? '';
            }
        }

        // Generate new repeater item for the single selected quotation
        $newGrantIds = [];
        try {
            $quotation = \App\Models\Quotation::with('subsidiary', 'lead.companyDetail')->find($selectedQuotation);
            if ($quotation) {
                $label = $quotation->pi_reference_no ?? "Quotation ID: {$quotation->id}";

                // Prioritize subsidiary company name if available
                if ($quotation->subsidiary && !empty($quotation->subsidiary->company_name)) {
                    $label .= ' - ' . $quotation->subsidiary->company_name;
                } elseif ($quotation->lead && $quotation->lead->companyDetail) {
                    $label .= ' - ' . $quotation->lead->companyDetail->company_name;
                }

                $newGrantIds[] = [
                    'quotation_id' => $selectedQuotation,
                    'proforma_invoice_name' => $label,
                    'hrdf_grant_id' => $existingGrantIds[$selectedQuotation] ?? '', // Preserve existing value
                ];
            }
        } catch (\Exception $e) {
            // Log error and continue with basic info
            $newGrantIds[] = [
                'quotation_id' => $selectedQuotation,
                'proforma_invoice_name' => "Quotation ID: {$selectedQuotation}",
                'hrdf_grant_id' => $existingGrantIds[$selectedQuotation] ?? '',
            ];
        }

        $set('hrdf_grant_ids', $newGrantIds);
    }
}
