<?php
namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Classes\Encryptor;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table as TablesTable;
use App\Enums\QuotationStatusEnum;
use App\Filament\Resources\QuotationResource\Pages;
use App\Filament\Resources\QuotationResource\RelationManagers;
use App\Models\ActivityLog;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Setting;
use App\Services\QuotationService;
use Carbon\Carbon;
use Coolsam\FilamentFlatpickr\Forms\Components\Flatpickr;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\View as ViewComponent;
use Filament\Notifications\Livewire\Notifications;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Grouping\Group;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;

class QuotationRelationManager extends RelationManager
{
    protected static string $relationship = 'quotations'; // Define the relationship name in the Lead model

    #[On('refresh-quotations')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->modifyQueryUsing(function ($query) {
                // Get current authenticated user
                $user = auth()->user();

                // If user is not a manager (role_id 3), only show their own quotations
                if ($user->role_id !== 3) {
                    $query->where('sales_person_id', $user->id);
                }

                // Always apply the default sort order
                $query->orderBy('created_at', 'desc');
            })
            ->headerActions([
                Action::make('createQuotation')
                    ->label('Add Quotation')
                    // ->color(fn () => $this->isCompanyAddressIncomplete() ? 'gray' : 'primary')
                    ->color('primary')
                    ->visible(fn () => in_array(auth('web')->user()->role_id, [1, 2, 3]))
                    ->action(function () {
                        // Check if company address is incomplete
                        // if ($this->isCompanyAddressIncomplete()) {
                        //     Notification::make()
                        //         ->danger()
                        //         ->title('Incomplete Company Address')
                        //         ->body('Please complete the company address details before creating a quotation. (At least 1 field)')
                        //         ->send();

                        //     return;
                        // }

                        // If address is complete, redirect to the quotation creation page
                        $leadId = Encryptor::encrypt($this->getOwnerRecord()->id);
                        $url = route('filament.admin.resources.quotations.create', ['lead_id' => $leadId]);

                        // Use JavaScript to open in a new tab
                        $this->js("window.open('{$url}', '_blank')");
                    }),
            ])
            ->groups([
                Group::make('sales_person_id')
                    ->label('')
                    ->orderQueryUsing(fn (Builder $query, string $direction) => $query->orderBy('sales_person_id', $direction))
                    ->getTitleFromRecordUsing(function ($record) {
                        $sales = $record->sales_person;

                        if ($sales?->role_id === 2) {
                            return 'Presales Quotation';
                        }else if ($sales?->role_id === 1 && $sales?->additional_role == 1) {
                            return 'Postsales Quotation';
                        } else{
                            return 'Others';
                        }
                    })
                    ->collapsible() // Optional: makes the groups collapsible
            ])
            ->defaultGroup('sales_person_id')
            ->groupingSettingsHidden()
            ->emptyState(fn () => view('components.empty-state-question'))
            ->columns([
                TextColumn::make('quotation_reference_no')
                    ->label('Ref No'),
                TextColumn::make('quotation_date')
                    ->label('Date')
                    ->formatStateUsing(fn($state) => $state->format('j M Y')),
                TextColumn::make('quotation_type')
                    ->label('Type')
                    ->formatStateUsing(fn($state) => match($state) {
                        'product' => 'Product',
                        'hrdf' => 'HRDF',
                    }),
                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Lead')
                    ->formatStateUsing(fn($state): string => Str::upper($state)),
                    // ->summarize([
                    //     Count::make()
                    // ]),
                TextColumn::make('currency')
                    ->alignCenter(),
                TextColumn::make('items_sum_total_before_tax')
                    ->label('Value (Before Tax)')
                    ->sum('items','total_before_tax')
                    // ->summarize([
                    //     Sum::make()
                    //         ->label('Total')
                    //         ->formatStateUsing(fn($state) => number_format($state,2,'.','')),
                    // ])
                    //->formatStateUsing(fn(Model $record, $state) => $record->currency . ' ' . $state)
                    ->alignRight(),
                TextColumn::make('sales_person.name')
                    ->label('Sales Person'),
                    // ->summarize([
                    //     Count::make()
                    // ]),
                TextColumn::make('status')
                    // ->options([
                    //     'new' => 'New',
                    //     'email_sent' => 'Email Sent',
                    //     'accepted' => 'Accepted',
                    //     'rejected' => 'Rejected',
                    // ])
                    // ->disabled(
                    //     function(Quotation $quotation) {
                    //         $lastUpdatedAt = Carbon::parse($quotation->updated_at);
                    //         /**
                    //          * hide duplicate button if it was updated less than 48 hours
                    //          * ago
                    //          */
                    //         return $lastUpdatedAt->diffInHours(now()) > 48;
                    //     }
                    // )
                    ->formatStateUsing(fn($state) => match($state->value) {
                        'new' => 'New',
                        'email_sent' => 'Email Sent',
                        'accepted' => 'Accepted',
                        // 'rejected' => 'Rejected',
                    })
                    ->color(fn($state) => match($state->value) {
                        'new' => 'warning',
                        'email_sent' => 'primary',
                        'accepted' => 'success',
                        // 'rejected' => 'danger',
                    })
            ])
            ->filters([
                // SelectFilter::make('quotation_reference_no')
                //     ->label('Ref No')
                //     ->searchable()
                //     ->getSearchResultsUsing(fn(Quotation $quotation, ?string $search, QuotationService $quotationService): array => $quotationService->searchQuotationByReferenceNo($quotation, $search))
                //     ->getOptionLabelsUsing(fn(Quotation $quotation, QuotationService $quotationService): array => $quotationService->getQuotationList($quotation)),
                // // Filter::make('quotation_reference_no')
                // //     ->form([
                // //         Select::make('quotation_reference_no')
                // //             ->label('Ref No')
                // //             ->placeholder('Search by ref no')
                // //             ->options(fn(Quotation $quotation, QuotationService $quotationService): array => $quotationService->getQuotationList($quotation))
                // //             ->searchable(),
                // //     ])
                // //     ->query(fn(Builder $query, array $data, QuotationService $quotationService): Builder => $quotationService->searchQuotationByReferenceNo($query, $data)),
                // Filter::make('quotation_date')
                //     ->label('Date')
                //     ->form([
                //         Flatpickr::make('quotation_date')
                //             ->label('Date')
                //             ->dateFormat('j M Y')
                //             ->allowInput()
                //     ])
                //     ->query(fn(Builder $query, array $data, QuotationService $quotationService): Builder => $quotationService->searchQuotationByDate($query, $data)),
                // SelectFilter::make('quotation_type')
                //     ->label('Type')
                //     ->searchable()
                //     ->options([
                //         'product' => 'Product',
                //         'hrdf' => 'HRDF',
                //         // 'other' => 'Others'
                //     ]),
                // SelectFilter::make('company_id')
                //     ->label('Company')
                //     ->relationship('company', 'name')
                //     ->searchable()
                //     ->getSearchResultsUsing(
                //         fn(Lead $lead, ?string $search, QuotationService $quotationService): array => $quotationService->searchLeadByName($lead, $search)
                //     )
                //     ->getOptionLabelUsing(
                //         fn(Lead $lead, $value, QuotationService $quotationService): string => $quotationService->getLeadName($lead, $value)
                //     ),
                // SelectFilter::make('sales_person_id')
                //     ->label('Sales Person')
                //     ->relationship('sales_person', 'name')
                //     ->searchable()
                //     ->preload()
                //     ->getSearchResultsUsing(
                //         fn(User $user, ?string $search, QuotationService $quotationService): array => $quotationService->searchSalesPersonName($user, $search)
                //     )
                //     ->getOptionLabelUsing(
                //         fn(User $user, $value, QuotationService $quotationService): string => $quotationService->getSalesPersonName($user, $value)
                //     ),
                // SelectFilter::make('status')
                //     ->label('Status')
                //     ->searchable()
                //     ->options([
                //         'new' => 'New',
                //         'email_sent' => 'Email Sent',
                //         'accepted' => 'Accepted',
                //         // 'rejected' => 'Rejected',
                //     ]),
                // SelectFilter::make('sales_type')
                //     ->label('Sales Type')
                //     ->options([
                //         'NEW SALES' => 'NEW SALES',
                //         'RENEWAL SALES' => 'RENEWAL SALES',
                //     ])
                //     ->searchable(),
                // SelectFilter::make('hrdf_status')
                //     ->label('HRDF Status')
                //     ->options([
                //         'HRDF' => 'HRDF',
                //         'NON HRDF' => 'NON HRDF',
                //     ])
                //     ->searchable(),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(6)
            ->actions([
                ActionGroup::make([
                    // Tables\Actions\EditAction::make()
                    //     ->color('danger')
                    //     ->hidden(
                    //         function(Quotation $quotation) {
                    //             $lastUpdatedAt = Carbon::parse($quotation->updated_at);
                    //             /**
                    //              * hide edit button if it was updated more than 48 hours
                    //              * ago
                    //              */
                    //             return $lastUpdatedAt->diffInHours(now()) > 48;
                    //         }
                    //     ),
                    Tables\Actions\Action::make('Edit')
                        ->icon('heroicon-m-pencil-square')
                        ->color('danger')
                        ->hidden(
                            function(Quotation $quotation) {
                                $lastUpdatedAt = Carbon::parse($quotation->updated_at);
                                /**
                                 * hide edit button if it was updated more than 48 hours
                                 * ago
                                 */
                                return $lastUpdatedAt->diffInHours(now()) > 48;
                            }
                        )
                        ->action(function (Quotation $record) {
                            // Redirect to the create route with the encrypted lead ID
                            return redirect()->route('filament.admin.resources.quotations.edit', [
                                'record' => $record->id,
                            ]);
                        }),
                    Tables\Actions\Action::make('duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('warning')
                        // ->hidden(
                        //     function(Quotation $quotation) {
                        //         $lastUpdatedAt = Carbon::parse($quotation->updated_at);
                        //         /**
                        //          * hide duplicate button if it was updated less than 48 hours
                        //          * ago
                        //          */
                        //         return $lastUpdatedAt->diffInHours(now()) < 48;
                        //     }
                        // )
                        ->action(fn(Quotation $quotation, QuotationService $quotationService) => $quotationService->duplicate($quotation)),
                    // Tables\Actions\Action::make('preview_pdf')
                    //     ->label('Preview PDF')
                    //     ->icon('heroicon-o-viewfinder-circle')
                    //     ->infolist([
                    //         PdfViewerEntry::make('file')
                    //             ->label(fn(Quotation $quotation): string => Str::slug($quotation->company->name) . '_' . quotation_reference_no($quotation->id) . '_' . Str::lower($quotation->sales_person->code) . '.pdf')
                    //             ->fileUrl(
                    //                 function(Quotation $quotation) {
                    //                     $quotationFilename = Str::slug($quotation->company->name) . '_' . quotation_reference_no($quotation->id) . '_' . Str::lower($quotation->sales_person->code) . '.pdf';
                    //                     info("Quotation: {$quotationFilename}");
                    //                     return Storage::url('/quotations/'.$quotationFilename);
                    //                 }
                    //             )
                    //             ->columnSpanFull(),
                    // ]),
                    // Tables\Actions\Action::make('pdf')
                    //     ->label('PDF')
                    //     ->color('success')
                    //     ->icon('heroicon-o-arrow-down-on-square')
                    //     ->url(fn (Quotation $record) => route('pdf.print-quotation', $record))
                    //     ->openUrlInNewTab(),
                    // Tables\Actions\Action::make('Quotation')
                    //     ->label('Preview')
                    //     ->color('success')
                    //     ->icon('heroicon-o-arrow-down-on-square')
                    //     ->infolist([
                    //         PdfViewerEntry::make('')
                    //             // ->label(fn(Quotation $quotation): string => Str::slug($quotation->company->name) . '_' . quotation_reference_no($quotation->id) . '_' . Str::lower($quotation->sales_person->code) . '.pdf')
                    //             ->fileUrl(
                    //                 function(Quotation $quotation, GeneratePDFService $generatePDFService, QuotationService $quotationService) {
                    //                     $generatePDFService->generateQuotation($quotation, $quotationService);
                    //                     // $quotationFilename = Str::slug($quotation->company->name) . '_' . quotation_reference_no($quotation->id) . '_' . Str::lower($quotation->sales_person->code) . '.pdf';
                    //                     $quotationFilename = $quotationService->update_reference_no($quotation);
                    //                     $quotationFilename = Str::replace('/','_',$quotationFilename);
                    //                     $quotationFilename .= '_' . Str::upper(Str::replace('-','_',Str::slug($quotation->company->name))) . '.pdf';
                    //                     return Storage::url('/quotations/'.$quotationFilename);
                    //                 }
                    //             )
                    //             ->columnSpanFull()
                    //             ->minHeight('80svh'),
                    //     ]),
                    Tables\Actions\Action::make('View PDF')
                        ->label('Preview')
                        ->icon('heroicon-o-arrow-down-on-square')
                        ->color('success')
                        ->url(fn(Quotation $quotation) => route('pdf.print-quotation-v2', $quotation))
                        ->openUrlInNewTab(),
                    Tables\Actions\Action::make('Accept')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->form([
                            FileUpload::make('attachment')
                                ->label('Upload Confirmation Order Document')
                                ->acceptedFileTypes(['application/pdf','image/jpg','image/jpeg'])
                                ->uploadingMessage('Uploading document...')
                                ->previewable(false)
                                ->preserveFilenames()
                                ->disk('public')
                                ->directory('confirmation_orders')
                        ])
                        ->action(
                            function (Quotation $quotation, QuotationService $quotationService, array $data) {
                                $quotation->confirmation_order_document = $data['attachment'];
                                $quotation->pi_reference_no = $quotationService->update_pi_reference_no($quotation);
                                $quotation->status = QuotationStatusEnum::accepted;
                                $quotation->save();

                                $notifyUsers = User::whereIn('role_id',['2'])->get();
                                $currentUser = User::find(auth('web')->user()->id);
                                $notifyUsers = $notifyUsers->push($currentUser);

                                $lead = $quotation->lead; // Assuming the 'lead' relationship exists in Quotation

                                // Create a new ActivityLog entry with the desired description
                                ActivityLog::create([
                                    'subject_id' => $lead->id, // Associate the log with the lead
                                    'description' => 'Order Uploaded. Pending Approval to close lead.',
                                    'causer_id' => auth()->id(), // Log the current user's ID
                                    'causer_type' => get_class(auth()->user()), // Log the user's model class
                                    'properties' => json_encode([ // Serialize properties to JSON
                                        'attributes' => [ // Store as a JSON array under "attributes"
                                            'quotation_reference_no' => $quotation->quotation_reference_no,
                                            'lead_status' => $lead->lead_status,
                                            'stage' => $lead->stage,
                                        ],
                                    ]),
                                ]);

                                // Optionally log activity for auditing
                                activity()
                                    ->causedBy(auth()->user())
                                    ->performedOn($lead)
                                    ->withProperties([
                                        'attributes' => [ // Store as a JSON array under "attributes"
                                            'quotation_reference_no' => $quotation->quotation_reference_no,
                                            'lead_status' => $lead->lead_status,
                                            'stage' => $lead->stage,
                                        ],
                                    ]);

                                Notification::make()
                                    ->success()
                                    ->title('Confirmation Order Document Uploaded!')
                                    ->body('Confirmation order document for quotation ' . $quotation->quotation_reference_no . ' has been uploaded successfully!')
                                    ->sendToDatabase($notifyUsers)
                                    ->send();
                            }
                        )
                        // ->url(fn(Quotation $quotation) => route('pdf.print-proforma-invoice', $quotation))
                        // ->openUrlInNewTab()
                        ->closeModalByClickingAway(false)
                        ->modalWidth(MaxWidth::Medium)
                        ->visible(fn(Quotation $quotation) =>
                            $quotation->status !== QuotationStatusEnum::accepted &&
                            $quotation->lead?->lead_status === 'Closed'
                        ),
                    Tables\Actions\Action::make('proforma_invoice')
                        ->label('Proforma Invoice')
                        ->color('primary')
                        ->icon('heroicon-o-document-text')
                        ->url(fn(Quotation $quotation) => route('pdf.print-proforma-invoice-v2', $quotation))
                        ->openUrlInNewTab()
                        ->hidden(fn(Quotation $quotation) => $quotation->status != QuotationStatusEnum::accepted),
                    Tables\Actions\Action::make('send_quotation')
                        ->label('Send Quotation')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->modalHeading('Send Quotation')
                        ->modalDescription(function (Quotation $record) {
                            $email = $record->lead?->companyDetail?->email ?? $record->lead?->email ?? null;
                            if (!$email) {
                                return 'Lead has no email address. Please update the lead details first.';
                            }
                            return "This will send the quotation to {$email}.\n\nYou can add a personalized message below:";
                        })
                        ->modalSubmitActionLabel('Send')
                        ->form([
                            Forms\Components\Textarea::make('message')
                                ->label('Message (Optional)')
                                ->placeholder('Add a personalized message to include in the email...')
                                ->columnSpanFull(),
                            Forms\Components\Checkbox::make('cc_self')
                                ->label('Send a copy to myself')
                                ->default(true),
                        ])
                        ->action(function (Quotation $record, array $data) {
                            $email = $record->lead?->companyDetail?->email ?? $record->lead?->email ?? null;

                            if (!$email) {
                                Notification::make()
                                    ->danger()
                                    ->title('Missing Email Address')
                                    ->body('The lead has no email address. Please update the lead details first.')
                                    ->send();
                                return;
                            }

                            try {
                                // Generate the PDF (you can use your existing service)
                                $pdfPath = $this->generateQuotationPdf($record);

                                // Send the email with the quotation
                                $this->sendQuotationEmail(
                                    $record,
                                    $email,
                                    $data['message'] ?? '',
                                    $pdfPath,
                                    $data['cc_self'] ?? false
                                );

                                // Update quotation status to email_sent
                                $record->status = QuotationStatusEnum::email_sent;
                                $record->save();

                                // Create activity log for the lead
                                ActivityLog::create([
                                    'subject_id' => $record->lead->id,
                                    'description' => 'Quotation ' . $record->quotation_reference_no . ' sent to client via email.',
                                    'causer_id' => auth()->id(),
                                    'causer_type' => get_class(auth()->user()),
                                    'properties' => json_encode([
                                        'attributes' => [
                                            'quotation_reference_no' => $record->quotation_reference_no,
                                            'email' => $email,
                                        ],
                                    ]),
                                ]);

                                // Log activity for auditing
                                activity()
                                    ->causedBy(auth()->user())
                                    ->performedOn($record->lead)
                                    ->withProperties([
                                        'attributes' => [
                                            'quotation_reference_no' => $record->quotation_reference_no,
                                            'email' => $email,
                                        ],
                                    ]);

                                Notification::make()
                                    ->success()
                                    ->title('Quotation Sent')
                                    ->body('The quotation has been successfully sent to ' . $email)
                                    ->send();

                            } catch (\Exception $e) {
                                Notification::make()
                                    ->danger()
                                    ->title('Error Sending Quotation')
                                    ->body('An error occurred: ' . $e->getMessage())
                                    ->send();
                            }
                        })
                        ->visible(false)
                        // ->visible(function (Quotation $record) {
                        //     // Only show for quotations that haven't been sent yet or are in "new" status
                        //     return $record->status === QuotationStatusEnum::new;
                        // })
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->size(ActionSize::ExtraSmall)
                ->color('primary')
                ->button(),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('markAsFinal')
                    ->label('Mark as Final Quotation')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark Selected Quotation as Final')
                    ->modalDescription(function (Collection $records) {
                        if ($records->count() > 1) {
                            return 'You can only mark one quotation as final at a time. Please select only one quotation.';
                        }
                        return 'Are you sure you want to mark this quotation as final? This will remove final status from any previously marked quotations.';
                    })
                    ->modalSubmitActionLabel('Yes, Mark as Final')
                    ->action(function (Collection $records): void {
                        // Check if more than one record is selected
                        if ($records->count() > 1) {
                            Notification::make()
                                ->danger()
                                ->title("Too Many Selections")
                                ->body("You can only mark one quotation as final at a time. Please select only one quotation.")
                                ->duration(5000)
                                ->send();
                            return;
                        }

                        // Get the selected quotation
                        $quotation = $records->first();

                        // Get the parent lead
                        $lead = $this->getOwnerRecord();

                        // First reset all existing final quotations for this lead
                        $resetCount = 0;
                        $lead->quotations()
                            ->where('mark_as_final', 1)
                            ->where('id', '!=', $quotation->id)
                            ->each(function ($q) use (&$resetCount) {
                                $q->updateQuietly(['mark_as_final' => 0]);
                                $resetCount++;
                            });

                        // Now mark the selected quotation as final
                        $quotation->updateQuietly(['mark_as_final' => 1]);

                        // Display a notification with the results
                        Notification::make()
                            ->title("Quotation Marked as Final")
                            ->body("Quotation #{$quotation->id} for {$quotation->lead->companyDetail->company_name} has been marked as final. {$resetCount} previous final quotation(s) have been reset.")
                            ->success()
                            ->duration(5000)
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->hidden(function (RelationManager $livewire): bool {
                        return $livewire->checkRecordCount() == 0;
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotations::route('/'),
            'create' => Pages\CreateQuotation::route('/create'),
            'edit' => Pages\EditQuotation::route('/{record}/edit'),
        ];
    }

    public static function recalculateAllRows($get, $set, $field=null, $state=null): void
    {
        $items = $get('../../items');

        $subtotal = 0;
        $grandTotal = 0;
        $totalTax = 0;
        $product = null;

        foreach ($items as $index => $item) {

            if (array_key_exists('product_id',$item)) {
                info("Product ID: {$item['product_id']}");
                if ($item['product_id']) {
                    $product_id = $item['product_id'];
                    $product = Product::find($product_id);
                    $set("../../description", $product->description);
                    $set("../../items.{$index}.description", $product->description);
                } else {
                    $quantity = 0;
                    $unit_price = 0;
                }
            }

            if ($product?->solution == 'hrdf') {
                // $itemQuantity = $get("../../items.{$index}.quantity");
                $itemQuantity = 0;
                if ($item['product_id'] != null) {
                    $itemQuantity = $get("../../items.{$index}.quantity");
                }
                info("Quantity: {$itemQuantity}");
                // $set("../../items.{$index}.quantity",$get("../../number_of_participant"));
                $set("../../items.{$index}.quantity", $itemQuantity);
            }

            if ($product?->solution == 'software' || $product?->solution == 'hardware') {
                // $set("../../items.{$index}.quantity",$get("../../items.{$index}.quantity"));
                $set("../../items.{$index}.quantity",$get("../../items.{$index}.quantity") ?? $product?->quantity);
                if ($get("../../items.{$index}.subscription_period") == 0) {
                    $set("../../items.{$index}.subscription_period", $get("../../subscription_period"));
                }
            }

            $quantity = (float) $get("../../items.{$index}.quantity");
            if (!$quantity) {
                $quantity = (float) $get("../../headcount");
                $set("../../items.{$index}.quantity", $quantity);
            }
            $subscription_period =  $get("../../items.{$index}.subscription_period");
            // info("Unit Price: {$item['unit_price']}");
            $unit_price = 0;
            if (array_key_exists('unit_price',$item)) {
                $unit_price = (float) $item['unit_price'];
                //info("Unit Price 1: {$unit_price} ({$index})");
                if ($item['unit_price'] == 0.00 && $item['product_id'] != null) {
                    $unit_price = (float) $product?->unit_price;
                    //info("Unit Price 2: {$unit_price} ({$index})");
                }
            }

            $set("../../items.{$index}.unit_price", $unit_price);

            // Calculate total before tax
            $total_before_tax = (int) $quantity * (float) $unit_price;
            if ($product && $product->solution == 'software') {
                /**
                 * include subscription period in calculation for software
                 */
                $total_before_tax = (int) $quantity * (int) $subscription_period * (float) $unit_price;
            }

            $subtotal += $total_before_tax;
            // Calculate taxation amount
            $taxation_amount = 0;
            if ($product?->taxable) {
                $sstRate = $get('../../sst_rate');
                $taxation_amount = $total_before_tax * ($sstRate / 100);
                $totalTax += $taxation_amount;
            }

            if (array_key_exists('description',$item)) {
                $description = trim($item['description']);
                if (Str::length($description) == 0 && $field == 'product_id') {
                    $description = $product?->description;
                }
            } else {
                $description = $product?->description;
            }

            $set("../../items.{$index}.description", $product?->description);
            $set("../../description", $product?->description);
            // Calculate total after tax
            $total_after_tax = $total_before_tax + $taxation_amount;
            $grandTotal += $total_after_tax;
            // Update the form values
            $set("../../items.{$index}.unit_price", number_format($unit_price, 2, '.', ''));
            $set("../../items.{$index}.total_before_tax", number_format($total_before_tax, 2, '.', ''));
            $set("../../items.{$index}.taxation", number_format($taxation_amount, 2, '.', ''));
            $set("../../items.{$index}.total_after_tax", number_format($total_after_tax, 2, '.', ''));
        }

        /**
         * Update summary
         */
        $set('../../sub_total', number_format($subtotal, 2, '.', ''));
        $set('../../tax_amount', number_format($totalTax, 2, '.', ''));
        $set('../../total', number_format($grandTotal, 2, '.', ''));
    }

    public static function updateSubscriptionPeriodInAllRows(Forms\Get $get, Forms\Set $set, ?string $state): void
    {
        $set('../../base_subscription', $state);
        $set('../*.subscription_period', $state);
    }

    public static function updateFields(?string $field, Forms\Get $get, Forms\Set $set, ?string $state): void
    {
        /**
         * if both $field and $state are not null
         */
        if ($field && $state) {
            $productId = $get('product_id');
            /**
             * if there is a change in product
             */
            if ($field == 'product_id') {
                $productId = $state;
                $product = Product::find($productId);
                $set('quantity',1);
                if ($product->solution == 'software') {
                    //$set('quantity',$get('../../headcount'));
                    $set('subscription_period',$get('../../base_subscription'));
                }

                $set('unit_price', $product->unit_price);
            } else {
                $product = Product::find($productId);
            }

            $quantity = $get('quantity');
            /**
             * if there is a change in quantity
             */
            if ($field == 'quantity') {
                $quantity = $state;
            }

            $subscription = $get('subscription_period');
            /**
             * if there is a change in subscription period
             */
            if ($field == 'subscription_period') {
                //$set('../../base_subscription', $state);
                $set('../*.subscription_period', $state);
                $subscription = $state;
            }

            $unitPrice = $get('unit_price');
            /**
             * if there is a change in unit price
             */
            if ($field == 'unit_price') {
                $unitPrice = $state;
            }

            $totalBeforeTax = $quantity * $unitPrice;
            /**
             * if product is a software, we include subscription period in the calculation
             * of total value before tax
             */
            if ($product->solution == 'software') {

                $totalBeforeTax = $quantity * $unitPrice * $subscription;
            } else {
                /**
                 * subscription period is not applicable to hardware,
                 * hence we set it to null
                 */
                $set('subscription_period',null);
            }

            /**
             * if the product is not subject to SST,
             * total value before tax and after tax are the same
             */
            $totalAfterTax = $totalBeforeTax;

            $set('description', $product->description);
            $set('total_before_tax', number_format($totalBeforeTax,2,'.',''));

            $set('taxation', null);
            /**
             * if product is subjected to SST
             */
            if ($product?->taxable) {
                $sstRate = $get('../../sst_rate');
                $taxValue = $totalBeforeTax * ($sstRate/100);
                $totalAfterTax = $totalBeforeTax + $taxValue;

                $set('taxation', number_format($taxValue,2,'.',''));
            }

            $set('total_after_tax', number_format($totalAfterTax,2,'.',''));
        }

        if (!$field && !$state) {
            $selectedProducts = collect($get('items'))->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity']));
        } else {
            $selectedProducts = collect($get('../../items'))->filter(fn($item) => !empty($item['product_id']) && !empty($item['quantity']));
        }

        // Retrieve prices for all selected products
        //$prices = Product::find($selectedProducts->pluck('product_id'))->pluck('unit_price', 'id');
        // Calculate subtotal based on the selected products and quantities
        $taxAmount = $selectedProducts->reduce(function($taxAmount,$product) {
            return $taxAmount + $product['taxation'];
        });

        $subtotal = $selectedProducts->reduce(function ($subtotal,$product) {
            return $subtotal + $product['total_before_tax'];
        }, 0);

        $total = $selectedProducts->reduce(function ($total,$product) {
            return $total + $product['total_after_tax'];
        }, 0);

        $sstRate = Setting::where('name','sst_rate')->first()->value;

        if (!$field && !$state) {
            $set('sub_total', number_format($subtotal, 2, '.', ''));
            $set('tax_amount', number_format($taxAmount, 2, '.' ,''));
            $set('total', number_format($total, 2, '.', ''));
        } else {
            // Update the state with the new values
            $set('../../sub_total', number_format($subtotal, 2, '.', ''));
            $set('../../tax_amount', number_format($taxAmount, 2, '.' ,''));
            $set('../../total', number_format($total, 2, '.', ''));
        }
    }

    public static function recalculateAllRowsFromParent($get, $set): void
    {
        $items = $get('items');

        $subtotal = 0;
        $grandTotal = 0;
        $totalTax = 0;

        foreach ($items as $index => $item) {
            if (array_key_exists('product_id',$item)) {
                $product_id = $item['product_id'];
                $product = Product::find($product_id);
            }

            $set("items.{$index}.quantity",$get("num_of_participant") ?? 0);
            if ($product?->solution == 'software' || $product?->solution == 'hardware') {
                $set("items.{$index}.quantity",$get("items.{$index}.quantity"));
                $set("items.{$index}.subscription_period", $get("base_subscription"));
            }

            $quantity = $get("items.{$index}.quantity");
            $subscription_period =  $get("items.{$index}.subscription_period");
            // $subscription_period = $get("base_subscription");
            $unit_price = 0;
            if (array_key_exists('unit_price', $item)) {
                $unit_price = $item['unit_price'];
                if ($unit_price == 0.00) {
                    $unit_price = $product?->unit_price;
                }
            }

            $set("items.{$index}.unit_price",$unit_price);
            //unit_price = $get("items.{$index}.unit_price");

            // Calculate total before tax
            $total_before_tax = (int) $quantity * (float) $unit_price;
            if ($product && $product->solution == 'software') {
                /**
                 * include subscription period in calculation for software
                 */
                $total_before_tax = (int) $quantity * (int) $subscription_period * (float) $unit_price;
            }

            $subtotal += $total_before_tax;
            // Calculate taxation amount
            $taxation_amount = 0;
            if ($product?->taxable) {
                $sstRate = $get('sst_rate');
                $taxation_amount = $total_before_tax * ($sstRate / 100);
                $totalTax += $taxation_amount;
            }

            $set("items.{$index}.description", $product?->description);

            // Calculate total after tax
            $total_after_tax = $total_before_tax + $taxation_amount;
            $grandTotal += $total_after_tax;
            // Update the form values
            $set("items.{$index}.unit_price", number_format($unit_price, 2, '.', ''));
            $set("items.{$index}.total_before_tax", number_format($total_before_tax, 2, '.', ''));
            $set("items.{$index}.taxation", number_format($taxation_amount, 2, '.', ''));
            $set("items.{$index}.total_after_tax", number_format($total_after_tax, 2, '.', ''));
        }

        /**
         * Update summary
         */
        $set('sub_total', number_format($subtotal, 2, '.', ''));
        $set('tax_amount', number_format($totalTax, 2, '.', ''));
        $set('total', number_format($grandTotal, 2, '.', ''));
    }

    protected function isCompanyAddressIncomplete(): bool
    {
        $company = $this->getOwnerRecord()?->companyDetail;

        if (!$company) return true;

        $isEmpty = fn ($value) => blank($value) || $value === '-';

        return $isEmpty($company->company_address1)
            && $isEmpty($company->company_address2)
            && $isEmpty($company->state)
            && $isEmpty($company->postcode);
    }

    protected function checkRecordCount(): int
    {
        return $this->getOwnerRecord()->quotations()->count();
    }
}
