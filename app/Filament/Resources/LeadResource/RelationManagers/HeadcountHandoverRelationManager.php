<?php
namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Classes\Encryptor;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table as TablesTable;
use App\Enums\QuotationStatusEnum;
use App\Models\ActivityLog;
use App\Models\Industry;
use App\Models\Lead;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Setting;
use App\Models\HeadcountHandover;
use App\Services\CategoryService;
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
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Checkbox;
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
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Attributes\On;

class HeadcountHandoverRelationManager extends RelationManager
{
    protected static string $relationship = 'headcountHandover'; // Define the relationship name in the Lead model

    #[On('refresh-headcount-handovers')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function defaultForm()
    {
        return [
            Section::make('Part 1: Choose PI (Mandatory)')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            Select::make('proforma_invoice_product')
                                ->required()
                                ->label('Product PI')
                                ->options(function (RelationManager $livewire) {
                                    $leadId = $livewire->getOwnerRecord()->id;
                                    $currentRecordId = null;
                                    if ($livewire->mountedTableActionRecord) {
                                        // Check if it's already a model object
                                        if (is_object($livewire->mountedTableActionRecord)) {
                                            $currentRecordId = $livewire->mountedTableActionRecord->id;
                                        } else {
                                            // If it's a string/ID, use it directly
                                            $currentRecordId = $livewire->mountedTableActionRecord;
                                        }
                                    }

                                    // Get all PI IDs already used in other headcount handovers for this lead
                                    $usedPiIds = [];
                                    $headcountHandovers = HeadcountHandover::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            // Exclude current record if we're editing
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    // Extract used product PI IDs from all handovers
                                    foreach ($headcountHandovers as $handover) {
                                        $piProduct = $handover->proforma_invoice_product;
                                        if (!empty($piProduct)) {
                                            // Handle JSON string format
                                            if (is_string($piProduct)) {
                                                $piIds = json_decode($piProduct, true);
                                                if (is_array($piIds)) {
                                                    $usedPiIds = array_merge($usedPiIds, $piIds);
                                                }
                                            }
                                            // Handle array format
                                            elseif (is_array($piProduct)) {
                                                $usedPiIds = array_merge($usedPiIds, $piProduct);
                                            }
                                        }
                                    }

                                    // Get available product PIs excluding already used ones
                                    return \App\Models\Quotation::where('lead_id', $leadId)
                                        ->where('quotation_type', 'product')
                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                        ->whereNotIn('id', array_filter($usedPiIds)) // Filter out null/empty values
                                        ->pluck('pi_reference_no', 'id')
                                        ->toArray();
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->helperText('Select Product PI (Required)')
                                ->default(function (?HeadcountHandover $record = null) {
                                    if (!$record || !$record->proforma_invoice_product) {
                                        return [];
                                    }
                                    if (is_string($record->proforma_invoice_product)) {
                                        return json_decode($record->proforma_invoice_product, true) ?? [];
                                    }
                                    return is_array($record->proforma_invoice_product) ? $record->proforma_invoice_product : [];
                                }),

                            Select::make('proforma_invoice_hrdf')
                                ->label('HRDF PI')
                                ->options(function (RelationManager $livewire) {
                                    $leadId = $livewire->getOwnerRecord()->id;
                                    $currentRecordId = null;
                                    if ($livewire->mountedTableActionRecord) {
                                        // Check if it's already a model object
                                        if (is_object($livewire->mountedTableActionRecord)) {
                                            $currentRecordId = $livewire->mountedTableActionRecord->id;
                                        } else {
                                            // If it's a string/ID, use it directly
                                            $currentRecordId = $livewire->mountedTableActionRecord;
                                        }
                                    }

                                    // Get all PI IDs already used in other headcount handovers for this lead
                                    $usedPiIds = [];
                                    $headcountHandovers = HeadcountHandover::where('lead_id', $leadId)
                                        ->when($currentRecordId, function ($query) use ($currentRecordId) {
                                            // Exclude current record if we're editing
                                            return $query->where('id', '!=', $currentRecordId);
                                        })
                                        ->get();

                                    // Extract used HRDF PI IDs from all handovers
                                    foreach ($headcountHandovers as $handover) {
                                        $piHrdf = $handover->proforma_invoice_hrdf;
                                        if (!empty($piHrdf)) {
                                            // Handle JSON string format
                                            if (is_string($piHrdf)) {
                                                $piIds = json_decode($piHrdf, true);
                                                if (is_array($piIds)) {
                                                    $usedPiIds = array_merge($usedPiIds, $piIds);
                                                }
                                            }
                                            // Handle array format
                                            elseif (is_array($piHrdf)) {
                                                $usedPiIds = array_merge($usedPiIds, $piHrdf);
                                            }
                                        }
                                    }

                                    // Get available HRDF PIs excluding already used ones
                                    return \App\Models\Quotation::where('lead_id', $leadId)
                                        ->where('quotation_type', 'hrdf')
                                        ->where('status', \App\Enums\QuotationStatusEnum::accepted)
                                        ->whereNotIn('id', array_filter($usedPiIds)) // Filter out null/empty values
                                        ->pluck('pi_reference_no', 'id')
                                        ->toArray();
                                })
                                ->multiple()
                                ->searchable()
                                ->preload()
                                ->helperText('Select HRDF PI (Optional)')
                                ->default(function (?HeadcountHandover $record = null) {
                                    if (!$record || !$record->proforma_invoice_hrdf) {
                                        return [];
                                    }
                                    if (is_string($record->proforma_invoice_hrdf)) {
                                        return json_decode($record->proforma_invoice_hrdf, true) ?? [];
                                    }
                                    return is_array($record->proforma_invoice_hrdf) ? $record->proforma_invoice_hrdf : [];
                                }),
                        ])
                ]),

            Section::make('Upload Documents (Either One Compulsory)')
                ->description('You must upload at least one of the following documents')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            // Upload Payment - Either one compulsory
                            FileUpload::make('payment_slip_file')
                                ->label('Upload Payment Slip')
                                ->disk('public')
                                ->directory('handovers/headcount/payment_slips')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->helperText('Upload Payment Slip files (Maximum 5 files)')
                                ->openable()
                                ->downloadable()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                    // Get lead ID from ownerRecord
                                    $leadId = $this->getOwnerRecord()->id;
                                    // Format ID with prefix (250) and padding
                                    $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                    // Get extension
                                    $extension = $file->getClientOriginalExtension();
                                    // Generate a unique identifier (timestamp) to avoid overwriting files
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);

                                    return "{$formattedId}-HC-PAYMENT-{$timestamp}-{$random}.{$extension}";
                                })
                                ->default(function (?HeadcountHandover $record = null) {
                                    if (!$record || !$record->payment_slip_file) {
                                        return [];
                                    }
                                    if (is_string($record->payment_slip_file)) {
                                        return json_decode($record->payment_slip_file, true) ?? [];
                                    }
                                    return is_array($record->payment_slip_file) ? $record->payment_slip_file : [];
                                }),

                            // Upload Confirmation Order - Either one compulsory
                            FileUpload::make('confirmation_order_file')
                                ->label('Upload Confirmation Order')
                                ->disk('public')
                                ->directory('handovers/headcount/confirmation_orders')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(5)
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                ->helperText('Upload Confirmation Order files (Maximum 5 files)')
                                ->openable()
                                ->downloadable()
                                ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                    // Get lead ID from ownerRecord
                                    $leadId = $this->getOwnerRecord()->id;
                                    // Format ID with prefix (250) and padding
                                    $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                    // Get extension
                                    $extension = $file->getClientOriginalExtension();
                                    // Generate a unique identifier (timestamp) to avoid overwriting files
                                    $timestamp = now()->format('YmdHis');
                                    $random = rand(1000, 9999);

                                    return "{$formattedId}-HC-CONFIRM-{$timestamp}-{$random}.{$extension}";
                                })
                                ->default(function (?HeadcountHandover $record = null) {
                                    if (!$record || !$record->confirmation_order_file) {
                                        return [];
                                    }
                                    if (is_string($record->confirmation_order_file)) {
                                        return json_decode($record->confirmation_order_file, true) ?? [];
                                    }
                                    return is_array($record->confirmation_order_file) ? $record->confirmation_order_file : [];
                                }),
                        ]),
                ]),

            Section::make('Salesperson Remark')
                ->schema([
                    Textarea::make('salesperson_remark')
                        ->label('Remark')
                        ->placeholder('Optional remarks from salesperson...')
                        ->rows(4)
                        ->maxLength(1000)
                        ->helperText('Optional field - Add any additional notes or remarks')
                        ->default(fn (?HeadcountHandover $record = null) => $record?->salesperson_remark ?? null),
                ]),
        ];
    }

    public function headerActions(): array
    {
        $leadStatus = $this->getOwnerRecord()->lead_status ?? '';
        $isCompanyDetailsIncomplete = $this->isCompanyDetailsIncomplete();

        return [
            // Action 1: Warning notification when requirements are not met
            Tables\Actions\Action::make('HeadcountHandoverWarning')
                ->label('Add Headcount Handover')
                ->icon('heroicon-o-pencil')
                ->color('gray')
                ->visible(function () use ($leadStatus, $isCompanyDetailsIncomplete) {
                    return $leadStatus !== 'Closed' || $isCompanyDetailsIncomplete;
                })
                ->action(function () {
                    Notification::make()
                        ->warning()
                        ->title('Action Required')
                        ->body('Please close the lead and complete the company details before proceeding with the headcount handover.')
                        ->persistent()
                        ->send();
                }),

            // Action 2: Actual form when requirements are met
            Tables\Actions\Action::make('AddHeadcountHandover')
                ->label('Add Headcount Handover')
                ->icon('heroicon-o-pencil')
                ->color('primary')
                ->visible(function () use ($leadStatus, $isCompanyDetailsIncomplete) {
                    return $leadStatus === 'Closed' && !$isCompanyDetailsIncomplete;
                })
                ->slideOver()
                ->modalHeading('Headcount Handover Submission')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->modalSubmitActionLabel('Submit Headcount Handover')
                ->form($this->defaultForm())
                ->action(function (array $data): void {
                    // Validation: Check if at least one document is uploaded
                    $hasPaymentSlip = !empty($data['payment_slip_file']);
                    $hasConfirmationOrder = !empty($data['confirmation_order_file']);

                    if (!$hasPaymentSlip && !$hasConfirmationOrder) {
                        Notification::make()
                            ->danger()
                            ->title('Upload Required')
                            ->body('You must upload at least one document: Payment Slip OR Confirmation Order.')
                            ->persistent()
                            ->send();
                        return;
                    }

                    $data['created_by'] = auth()->id();
                    $data['lead_id'] = $this->getOwnerRecord()->id;
                    $data['status'] = 'New';
                    $data['submitted_at'] = now();

                    // Handle file array encodings
                    foreach (['payment_slip_file', 'confirmation_order_file', 'proforma_invoice_product', 'proforma_invoice_hrdf'] as $field) {
                        if (isset($data[$field]) && is_array($data[$field])) {
                            $data[$field] = json_encode($data[$field]);
                        }
                    }

                    // Create the handover record
                    $nextId = $this->getNextAvailableId();

                    // Create the handover record with specific ID
                    $handover = new HeadcountHandover();
                    $handover->id = $nextId;
                    $handover->fill($data);
                    $handover->save();

                    try {
                        // Format handover ID
                        $handoverId = 'HC_250' . str_pad($handover->id, 3, '0', STR_PAD_LEFT);

                        // Get company name from CompanyDetail
                        $companyDetail = \App\Models\CompanyDetail::where('lead_id', $handover->lead_id)->first();
                        $companyName = $companyDetail ? $companyDetail->company_name : 'Unknown Company';

                        // Get salesperson name
                        $lead = $this->getOwnerRecord();
                        $salesperson = $lead->salesperson ? User::find($lead->salesperson)->name : 'Unknown';

                        \Illuminate\Support\Facades\Log::info("Headcount handover created successfully", [
                            'handover_id' => $handoverId,
                            'company_name' => $companyName,
                            'salesperson' => $salesperson
                        ]);

                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to process headcount handover", [
                            'error' => $e->getMessage(),
                            'handover_id' => $handover->id ?? null
                        ]);
                    }

                    Notification::make()
                        ->title('Headcount Handover Created Successfully')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->headerActions($this->headerActions())
            ->columns([
                TextColumn::make('id')
                    ->label('Headcount ID')
                    ->formatStateUsing(function ($state, HeadcountHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // Format ID with HC prefix and pad with zeros to ensure at least 3 digits
                        return 'HC_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHeadcountHandoverDetails')
                            ->modalHeading('Headcount Handover Details')
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HeadcountHandover $record): View {
                                return view('components.headcount-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('submitted_at')
                    ->label('Date Submitted')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->limit(30),

                TextColumn::make('salesperson_remark')
                    ->label('Remark')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Completed' => new HtmlString('<span style="color: green;">Completed</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->filters([

            ])
            ->filtersFormColumns(6)
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading('Headcount Handover Details')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->visible(fn (HeadcountHandover $record): bool => in_array($record->status, ['New', 'Completed']))
                        ->modalContent(function (HeadcountHandover $record): View {
                            return view('components.headcount-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('edit_headcount_handover')
                        ->modalHeading(function (HeadcountHandover $record): string {
                            $formattedId = 'HC_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                            return "Edit Headcount Handover {$formattedId}";
                        })
                        ->label('Edit Headcount Handover')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save Changes')
                        ->visible(fn (HeadcountHandover $record): bool => in_array($record->status, ['Draft', 'New']))
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->slideOver()
                        ->form($this->defaultForm())
                        ->action(function (HeadcountHandover $record, array $data): void {
                            // Validation: Check if at least one document is uploaded
                            $hasPaymentSlip = !empty($data['payment_slip_file']);
                            $hasConfirmationOrder = !empty($data['confirmation_order_file']);

                            if (!$hasPaymentSlip && !$hasConfirmationOrder) {
                                Notification::make()
                                    ->danger()
                                    ->title('Upload Required')
                                    ->body('You must upload at least one document: Payment Slip OR Confirmation Order.')
                                    ->persistent()
                                    ->send();
                                return;
                            }

                            // Handle file array encodings
                            foreach (['payment_slip_file', 'confirmation_order_file', 'proforma_invoice_product', 'proforma_invoice_hrdf'] as $field) {
                                if (isset($data[$field]) && is_array($data[$field])) {
                                    $data[$field] = json_encode($data[$field]);
                                }
                            }

                            // Update the record
                            $record->update($data);

                            Notification::make()
                                ->title('Headcount handover updated successfully')
                                ->success()
                                ->send();
                        }),

                    Action::make('view_reason')
                        ->label('View Rejection Reason')
                        ->visible(fn (HeadcountHandover $record): bool => $record->status === 'Rejected')
                        ->icon('heroicon-o-magnifying-glass-plus')
                        ->modalHeading('Rejection Reason')
                        ->modalContent(fn ($record) => view('components.view-reason', [
                            'reason' => $record->reject_reason,
                        ]))
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalWidth('3xl')
                        ->color('warning'),

                    Action::make('convert_to_draft')
                        ->label('Convert to Draft')
                        ->icon('heroicon-o-document')
                        ->color('warning')
                        ->visible(fn (HeadcountHandover $record): bool => $record->status === 'Rejected')
                        ->action(function (HeadcountHandover $record): void {
                            $record->update([
                                'status' => 'Draft'
                            ]);

                            Notification::make()
                                ->title('Headcount handover converted to draft')
                                ->success()
                                ->send();
                        }),
                ])->icon('heroicon-m-list-bullet')
                ->size(ActionSize::Small)
                ->label('Actions')
                ->color('primary')
                ->button(),
            ])
            ->bulkActions([
                // No bulk actions needed
            ]);
    }

    protected function isCompanyDetailsIncomplete(): bool
    {
        $lead = $this->getOwnerRecord();
        $companyDetail = $lead->companyDetail ?? null;

        // If no company details exist at all
        if (!$companyDetail) {
            return true;
        }

        // Check if any essential company details are missing
        $requiredFields = [
            'company_name',
            'industry',
            'contact_no',
            'email',
            'name',
            'position',
            'state',
            'postcode',
            'company_address1',
            'company_address2',
        ];

        foreach ($requiredFields as $field) {
            if (empty($companyDetail->$field)) {
                return true;
            }
        }

        // Special check for reg_no_new - must exist and have exactly 12 digits
        if (empty($companyDetail->reg_no_new)) {
            return true;
        }

        // Convert to string and remove any non-digit characters
        $regNoValue = preg_replace('/[^0-9]/', '', $companyDetail->reg_no_new);

        // Check if the resulting string has exactly 12 digits
        if (strlen($regNoValue) !== 12) {
            return true;
        }

        return false;
    }

    private function getNextAvailableId()
    {
        // Get all existing IDs in the table
        $existingIds = HeadcountHandover::pluck('id')->toArray();

        if (empty($existingIds)) {
            return 1; // If table is empty, start with ID 1
        }

        // Find the highest ID currently in use
        $maxId = max($existingIds);

        // Check for gaps from ID 1 to maxId
        for ($i = 1; $i <= $maxId; $i++) {
            if (!in_array($i, $existingIds)) {
                // Found a gap, return this ID
                return $i;
            }
        }

        // No gaps found, return next ID after max
        return $maxId + 1;
    }
}
