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
            Section::make('Upload HRDF Document')
                ->schema([
                    Grid::make(1)
                        ->schema([
                            // Box 1 - JD14 Form (Compulsory)
                            FileUpload::make('jd14_form_files')
                                ->label('Box 1: Upload JD14 Form + 3 Days (COMPULSORY)')
                                ->disk('public')
                                ->directory('handovers/hrdf/jd14_forms')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(4)
                                ->required()
                                ->acceptedFileTypes(['application/pdf'])
                                ->helperText('Upload JD14 Form files (Maximum 4 PDF files)')
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

                                    return "{$formattedId}-HRDF-JD14-{$timestamp}-{$random}.{$extension}";
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
                                ->label('Box 2: AutoCount Invoice (COMPULSORY)')
                                ->disk('public')
                                ->directory('handovers/hrdf/autocount_invoices')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(1)
                                ->required()
                                ->acceptedFileTypes(['application/pdf'])
                                ->helperText('Upload AutoCount Invoice (Maximum 1 PDF file)')
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

                                    return "{$formattedId}-HRDF-AUTOCOUNT-{$timestamp}-{$random}.{$extension}";
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
                                ->label('Box 3: HRDF Grant Approval Letter (COMPULSORY)')
                                ->disk('public')
                                ->directory('handovers/hrdf/grant_approvals')
                                ->visibility('public')
                                ->multiple()
                                ->maxFiles(1)
                                ->required()
                                ->acceptedFileTypes(['application/pdf'])
                                ->helperText('Upload HRDF Grant Approval Letter (Maximum 1 PDF file)')
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

                                    return "{$formattedId}-HRDF-GRANT-{$timestamp}-{$random}.{$extension}";
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
                    Grid::make(2)
                        ->schema([
                            // HRDF Grant ID - Most Important Field
                            TextInput::make('hrdf_grant_id')
                                ->label('HRDF Grant ID')
                                ->required()
                                ->placeholder('Enter HRDF Grant ID')
                                ->maxLength(50)
                                ->regex('/^[a-zA-Z0-9]+$/')
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

                            // Salesperson Remark - Optional
                            Textarea::make('salesperson_remark')
                                ->label('Remark')
                                ->placeholder('Optional remarks from salesperson...')
                                ->rows(4)
                                ->maxLength(1000)
                                ->helperText('Optional field - Add any additional notes or remarks')
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
                        ]),
                ]),
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
                ->icon('heroicon-o-pencil')
                ->color('gray')
                ->visible(function () use ($leadStatus, $isCompanyDetailsIncomplete) {
                    return $leadStatus !== 'Closed' || $isCompanyDetailsIncomplete;
                })
                ->action(function () {
                    Notification::make()
                        ->warning()
                        ->title('Action Required')
                        ->body('Please close the lead and complete the company details before proceeding with the HRDF handover.')
                        ->persistent()
                        ->send();
                }),

            // Action 2: Actual form when requirements are met
            Tables\Actions\Action::make('AddHRDFHandover')
                ->label('Add HRDF Handover')
                ->icon('heroicon-o-pencil')
                ->color('primary')
                ->visible(function () use ($leadStatus, $isCompanyDetailsIncomplete) {
                    return $leadStatus === 'Closed' && !$isCompanyDetailsIncomplete;
                })
                ->slideOver()
                ->modalHeading('HRDF Handover Submission')
                ->modalWidth(MaxWidth::FourExtraLarge)
                ->modalSubmitActionLabel('Submit HRDF Handover')
                ->form($this->defaultForm())
                ->action(function (array $data): void {
                    $data['created_by'] = auth()->id();
                    $data['lead_id'] = $this->getOwnerRecord()->id;
                    $data['status'] = 'New';
                    $data['submitted_at'] = now();

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

                    try {
                        // Format handover ID
                        $handoverId = 'HRDF_250' . str_pad($handover->id, 3, '0', STR_PAD_LEFT);

                        // Get company name from CompanyDetail
                        $companyDetail = \App\Models\CompanyDetail::where('lead_id', $handover->lead_id)->first();
                        $companyName = $companyDetail ? $companyDetail->company_name : 'Unknown Company';

                        // Get salesperson name
                        $lead = $this->getOwnerRecord();
                        $salesperson = $lead->salesperson ? User::find($lead->salesperson)->name : 'Unknown';

                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error("Failed to send HRDF handover notification email", [
                            'error' => $e->getMessage(),
                            'handover_id' => $handover->id ?? null
                        ]);
                    }

                    Notification::make()
                        ->title('HRDF Handover Created Successfully')
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
                    ->label('HRDF ID')
                    ->formatStateUsing(function ($state, HRDFHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // Format ID with HRDF prefix and pad with zeros to ensure at least 3 digits
                        return 'HRDF_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHRDFHandoverDetails')
                            ->modalHeading('HRDF Handover Details')
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
                        ->modalHeading('HRDF Handover Details')
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
                            $formattedId = 'HRDF_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                            return "Edit HRDF Handover {$formattedId}";
                        })
                        ->label('Edit HRDF Handover')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Save Changes')
                        ->visible(fn (HRDFHandover $record): bool => in_array($record->status, ['Draft', 'New']))
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
