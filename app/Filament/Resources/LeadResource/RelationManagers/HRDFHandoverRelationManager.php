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
use App\Models\HRDFHandover;
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

class HRDFHandoverRelationManager extends RelationManager
{
    protected static string $relationship = 'hrdfHandover'; // Define the relationship name in the Lead model

    #[On('refresh-hrdf-handovers')]
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
            Grid::make(3)
                ->schema([
                    Select::make('hrdf_grant_id')
                        ->label('Select HRDF Grant')
                        ->searchable()
                        ->preload(false)
                        ->live()
                        ->placeholder('Search HRDF Grant ID')
                        ->options(function (?HRDFHandover $record = null) {
                            // When editing, preload the current HRDF grant
                            if ($record && $record->hrdf_grant_id) {
                                $claim = \App\Models\HrdfClaim::where('hrdf_grant_id', $record->hrdf_grant_id)->first();
                                if ($claim) {
                                    return [
                                        $claim->hrdf_grant_id => "{$claim->hrdf_grant_id} - {$claim->company_name}"
                                    ];
                                }
                            }
                            return [];
                        })
                        ->getSearchResultsUsing(function (string $search, ?HRDFHandover $record = null) {
                            // Only show results when user types something
                            if (empty(trim($search))) {
                                return [];
                            }

                            // Search by HRDF Grant ID or Company Name
                            $results = \App\Models\HrdfClaim::where(function ($query) use ($search) {
                                $query->where('hrdf_grant_id', 'like', "%{$search}%")
                                    ->orWhere('company_name', 'like', "%{$search}%");
                            })
                            ->whereIn('claim_status', ['PENDING']);

                            // When editing, allow the current grant to be selected even if it has a handover
                            if ($record && $record->hrdf_grant_id) {
                                $results->where(function ($query) use ($record) {
                                    $query->whereDoesntHave('hrdfHandover')
                                        ->orWhere('hrdf_grant_id', $record->hrdf_grant_id);
                                });
                            } else {
                                $results->whereDoesntHave('hrdfHandover');
                            }

                            return $results->limit(20)
                                ->get()
                                ->mapWithKeys(function ($claim) {
                                    return [
                                        $claim->hrdf_grant_id => "{$claim->hrdf_grant_id} - {$claim->company_name}"
                                    ];
                                })
                                ->toArray();
                        })
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state) {
                                // Get the selected HRDF claim details
                                $claim = \App\Models\HrdfClaim::where('hrdf_grant_id', $state)->first();
                                if ($claim) {
                                    // Auto-populate fields from the claim
                                    $set('autocount_invoice_number', $claim->invoice_number);
                                }
                            }
                        })
                        ->default(function (?HRDFHandover $record = null) {
                            // Set default value when editing
                            return $record?->hrdf_grant_id ?? null;
                        })
                        ->required(),

                    TextInput::make('autocount_invoice_number')
                        ->label('AutoCount Invoice Number')
                        ->required()
                        ->maxLength(13)
                        ->extraAlpineAttributes([
                            'x-on:input' => '
                                const start = $el.selectionStart;
                                const end = $el.selectionEnd;
                                const value = $el.value;
                                $el.value = value.toUpperCase();
                                $el.setSelectionRange(start, end);
                            '
                        ])
                        ->default(function (?HRDFHandover $record = null) {
                            // If editing existing record, return the saved value
                            if ($record && $record->autocount_invoice_number) {
                                return $record->autocount_invoice_number;
                            }
                            return null;
                        })
                        ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                        ->validationMessages([
                            'required' => 'AutoCount Invoice Number is required.',
                            'max' => 'AutoCount Invoice Number cannot exceed 50 characters.',
                        ]),

                    Select::make('subsidiary_id')
                        ->label('Subsidiary Company')
                        ->placeholder('Select subsidiary company')
                        ->options(function () {
                            $lead = $this->getOwnerRecord();
                            if (!$lead) {
                                return [];
                            }

                            // ✅ Use lead_id instead of main_company_id or company_id
                            return \App\Models\Subsidiary::where('lead_id', $lead->id)
                                ->pluck('company_name', 'id')
                                ->toArray();
                        })
                        ->searchable()
                        ->preload()
                        ->default(function (?HRDFHandover $record = null) {
                            return $record?->subsidiary_id ?? null;
                        }),
                ]),
            Grid::make(3)
                ->schema([
                    // Box 1 - JD14 Form (Compulsory)
                    FileUpload::make('jd14_form_files')
                        ->label('JD14 Form + 3 Days Attendance Logs')
                        ->disk('public')
                        ->directory('handovers/hrdf/jd14_forms')
                        ->visibility('public')
                        ->multiple()
                        ->maxFiles(4)
                        ->required()
                        ->acceptedFileTypes(['application/pdf'])
                        ->helperText('(Maximum 4 PDF files)')
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                            // Get lead ID from ownerRecord
                            $leadId = $this->getOwnerRecord()->id;
                            // Use standardized format matching HRDFHandover accessor
                            $formattedId = HRDFHandover::generateFormattedId($leadId);
                            // Get extension
                            $extension = $file->getClientOriginalExtension();
                            // Generate a unique identifier (timestamp) to avoid overwriting files
                            $timestamp = now()->format('YmdHis');
                            $random = rand(1000, 9999);

                            return "{$formattedId}-JD14-{$timestamp}-{$random}.{$extension}";
                        })
                        ->default(function (?HRDFHandover $record = null) {
                            if (!$record || !$record->jd14_form_files) {
                                return [];
                            }
                            if (is_string($record->jd14_form_files)) {
                                return json_decode($record->jd14_form_files, true) ?? [];
                            }
                            return is_array($record->jd14_form_files) ? $record->jd14_form_files : [];
                        }),

                    // Box 2 - AutoCount Invoice (Compulsory)
                    FileUpload::make('autocount_invoice_file')
                        ->label('AutoCount Invoice')
                        ->disk('public')
                        ->directory('handovers/hrdf/autocount_invoices')
                        ->visibility('public')
                        ->multiple()
                        ->maxFiles(1)
                        ->required()
                        ->acceptedFileTypes(['application/pdf'])
                        ->helperText('(Maximum 1 PDF file)')
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                            // Get lead ID from ownerRecord
                            $leadId = $this->getOwnerRecord()->id;
                            // Use standardized format matching HRDFHandover accessor
                            $formattedId = HRDFHandover::generateFormattedId($leadId);
                            // Get extension
                            $extension = $file->getClientOriginalExtension();
                            // Generate a unique identifier (timestamp) to avoid overwriting files
                            $timestamp = now()->format('YmdHis');
                            $random = rand(1000, 9999);

                            return "{$formattedId}-AUTOCOUNT-{$timestamp}-{$random}.{$extension}";
                        })
                        ->default(function (?HRDFHandover $record = null) {
                            if (!$record || !$record->autocount_invoice_file) {
                                return [];
                            }
                            if (is_string($record->autocount_invoice_file)) {
                                return json_decode($record->autocount_invoice_file, true) ?? [];
                            }
                            return is_array($record->autocount_invoice_file) ? $record->autocount_invoice_file : [];
                        }),

                    // Box 3 - HRDF Grant Approval Letter (Compulsory)
                    FileUpload::make('hrdf_grant_approval_file')
                        ->label('HRDF Grant Approval Letter')
                        ->disk('public')
                        ->directory('handovers/hrdf/grant_approvals')
                        ->visibility('public')
                        ->multiple()
                        ->maxFiles(1)
                        ->required()
                        ->acceptedFileTypes(['application/pdf'])
                        ->helperText('(Maximum 1 PDF file)')
                        ->openable()
                        ->downloadable()
                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                            // Get lead ID from ownerRecord
                            $leadId = $this->getOwnerRecord()->id;
                            // Use standardized format matching HRDFHandover accessor
                            $formattedId = HRDFHandover::generateFormattedId($leadId);
                            // Get extension
                            $extension = $file->getClientOriginalExtension();
                            // Generate a unique identifier (timestamp) to avoid overwriting files
                            $timestamp = now()->format('YmdHis');
                            $random = rand(1000, 9999);

                            return "{$formattedId}-GRANT-{$timestamp}-{$random}.{$extension}";
                        })
                        ->default(function (?HRDFHandover $record = null) {
                            if (!$record || !$record->hrdf_grant_approval_file) {
                                return [];
                            }
                            if (is_string($record->hrdf_grant_approval_file)) {
                                return json_decode($record->hrdf_grant_approval_file, true) ?? [];
                            }
                            return is_array($record->hrdf_grant_approval_file) ? $record->hrdf_grant_approval_file : [];
                        }),
                ]),
                Grid::make(1)
                    ->schema([
                        // Salesperson Remark - Optional
                        Textarea::make('salesperson_remark')
                            ->label('SalesPerson Remark')
                            ->rows(2)
                            ->maxLength(1000)
                            ->default(fn (?HRDFHandover $record = null) => $record?->salesperson_remark ?? null)
                            ->extraAlpineAttributes([
                                'x-on:input' => '
                                    const start = $el.selectionStart;
                                    const end = $el.selectionEnd;
                                    const value = $el.value;
                                    $el.value = value.toUpperCase();
                                    $el.setSelectionRange(start, end);
                                '
                            ])
                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                    ])->columnSpan(1),
        ];
    }

    public function headerActions(): array
    {
        $leadStatus = $this->getOwnerRecord()->lead_status ?? '';
        $isCompanyDetailsIncomplete = $this->isCompanyDetailsIncomplete();

        return [
            // Action 1: Warning notification when requirements are not met
            Tables\Actions\Action::make('HRDFHandoverWarning')
                ->label('Add HRDF Handover')
                ->icon('heroicon-o-plus')
                ->color('gray')
                ->visible(function () use ($leadStatus, $isCompanyDetailsIncomplete) {
                    return $leadStatus !== 'Closed' || $isCompanyDetailsIncomplete;
                })
                ->action(function () use ($leadStatus, $isCompanyDetailsIncomplete) {
                    $message = '';

                    if ($leadStatus !== 'Closed') {
                        $message .= 'Please close the lead first. ';
                    }

                    if ($isCompanyDetailsIncomplete) {
                        $message .= 'Please complete the company details. ';
                    }

                    Notification::make()
                        ->warning()
                        ->title('Action Required')
                        ->body(trim($message))
                        ->persistent()
                        ->send();
                }),

            // Action 2: Actual form when requirements are met
            Tables\Actions\Action::make('AddHRDFHandover')
                ->label('Add HRDF Handover')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->visible(function () use ($leadStatus, $isCompanyDetailsIncomplete) {
                    return $leadStatus === 'Closed' && !$isCompanyDetailsIncomplete;
                })
                ->slideOver()
                ->modalHeading('HRDF Handover')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->modalSubmitActionLabel('Submit HRDF Handover')
                ->form($this->defaultForm())
                ->action(function (array $data): void {
                    // Get the HRDF claim details
                    $hrdfClaim = \App\Models\HrdfClaim::where('hrdf_grant_id', $data['hrdf_grant_id'])->first();

                    if (!$hrdfClaim) {
                        Notification::make()
                            ->title('Error')
                            ->body('Selected HRDF Grant not found.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $data['created_by'] = auth()->id();
                    $data['lead_id'] = $this->getOwnerRecord()->id; // Use current lead
                    $data['status'] = 'New';
                    $data['submitted_at'] = now();
                    $data['hrdf_claim_id'] = $hrdfClaim->hrdf_claim_id; // Link to the claim

                    // Handle file array encodings
                    foreach (['jd14_form_files', 'autocount_invoice_file', 'hrdf_grant_approval_file'] as $field) {
                        if (isset($data[$field]) && is_array($data[$field])) {
                            $data[$field] = json_encode($data[$field]);
                        }
                    }

                    // Create the handover record
                    $nextId = $this->getNextAvailableId();

                    // Create the handover record with specific ID
                    $handover = new HRDFHandover();
                    $handover->id = $nextId;
                    $handover->fill($data);
                    $handover->save();

                    // ✅ Get lead and extract salesperson name using the existing function
                    $lead = $this->getOwnerRecord();
                    $salesPersonName = auth()->user()->name;

                    // Update the HRDF claim with the lead's salesperson
                    $hrdfClaim->update([
                        'sales_person' => $salesPersonName,
                    ]);

                    Notification::make()
                        ->title('HRDF Handover Created Successfully')
                        ->body("Handover created for HRDF Grant: {$data['hrdf_grant_id']}")
                        ->success()
                        ->send();

                    // Refresh the table to show new record
                    $this->dispatch('refresh-hrdf-handovers');
                }),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->headerActions($this->headerActions())
            ->heading('HRDF Handover')
            ->columns([
                TextColumn::make('id')
                    ->label('HRDF ID')
                    ->formatStateUsing(function ($state, HRDFHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // Use the model's formatted_handover_id accessor
                        return $record->formatted_handover_id;
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHRDFHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HRDFHandover $record): View {
                                return view('components.hrdf-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('submitted_at')
                    ->label('Date Submitted')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->formatStateUsing(function ($state, HRDFHandover $record) {
                        // If subsidiary_id exists and is not null/empty, get subsidiary company name
                        if (!empty($record->subsidiary_id)) {
                            $subsidiary = \App\Models\Subsidiary::find($record->subsidiary_id);
                            if ($subsidiary && !empty($subsidiary->company_name)) {
                                return $subsidiary->company_name;
                            }
                        }

                        // Otherwise, return main company name
                        return $record->lead->companyDetail->company_name ?? 'Unknown Company';
                    }),

                TextColumn::make('hrdf_grant_id')
                    ->label('HRDF Grant ID')
                    ->limit(30),

                TextColumn::make('status')
                    ->label('STATUS')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
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
                        ->modalHeading(false)
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->visible(fn (HRDFHandover $record): bool => in_array($record->status, ['New', 'Completed', 'Approved']))
                        ->modalContent(function (HRDFHandover $record): View {
                            return view('components.hrdf-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('edit_hrdf_handover')
                        ->modalHeading(function (HRDFHandover $record): string {
                            return "Edit HRDF Handover {$record->formatted_handover_id}";
                        })
                        ->label('Edit HRDF Handover')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Submit')
                        ->visible(fn (HRDFHandover $record): bool => in_array($record->status, ['Draft']))
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->slideOver()
                        ->form($this->defaultForm())
                        ->action(function (HRDFHandover $record, array $data): void {
                            // Handle file array encodings
                            foreach (['jd14_form_files', 'autocount_invoice_file', 'hrdf_grant_approval_file'] as $field) {
                                if (isset($data[$field]) && is_array($data[$field])) {
                                    $data[$field] = json_encode($data[$field]);
                                }
                            }

                            $data['status'] = 'New';
                            $data['submitted_at'] = now();

                            // Update the record
                            $record->update($data);

                            Notification::make()
                                ->title('HRDF handover updated successfully')
                                ->success()
                                ->send();
                        }),

                    Action::make('view_reason')
                        ->label('View Rejection Reason')
                        ->visible(fn (HRDFHandover $record): bool => $record->status === 'Rejected')
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
                        ->visible(fn (HRDFHandover $record): bool => $record->status === 'Rejected')
                        ->action(function (HRDFHandover $record): void {
                            $record->update([
                                'status' => 'Draft'
                            ]);

                            Notification::make()
                                ->title('HRDF handover converted to draft')
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

    // protected function hasIncompleteHRDFHandover(): bool
    // {
    //     $lead = $this->getOwnerRecord();

    //     // Check if there are any existing HRDF handovers that are in Draft, New, or Rejected status
    //     $incompleteHandovers = $lead->hrdfHandover()
    //         ->whereIn('status', ['Draft', 'New', 'Rejected'])
    //         ->exists();

    //     return $incompleteHandovers;
    // }

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
        // if (strlen($regNoValue) !== 12) {
        //     return true;
        // }

        return false;
    }

    private function getNextAvailableId()
    {
        // Get all existing IDs in the table
        $existingIds = HRDFHandover::pluck('id')->toArray();

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
