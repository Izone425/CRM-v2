<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateSoftwareHandoverPdfController;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\SoftwareHandover;
use App\Models\User;
use App\Services\CategoryService;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Tables\Actions\Action;
use Livewire\Attributes\On;

class SoftwareHandoverPendingLicense extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;

    public $selectedUser;
    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('refresh-softwarehandover-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]); // Store for consistency

        $this->resetTable(); // Refresh the table
    }

    public function getNewSoftwareHandovers()
    {
        $query = SoftwareHandover::query();
        $query->whereIn('status', ['Completed']);

        // Check for both NULL and false (0) values for license_activated
        $query->where(function ($q) {
            $q->whereNull('license_activated')
            ->orWhere('license_activated', 0)
            ->orWhere('license_activated', false);
        });

        // Add this condition to only include records with license_certification_id not null
        $query->whereNotNull('license_certification_id');

        $query->orderBy('updated_at', 'desc');
        $query->where(function ($q) {
            $q->where('id', '>=', 556);
        });
        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getNewSoftwareHandovers())
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'New' => 'New',
                        'Rejected' => 'Rejected',
                        'Completed' => 'Completed',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id', 15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple(),

                SelectFilter::make('implementer')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::whereIn('role_id', ['4', '5'])
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementer')
                    ->multiple(),

                SortFilter::make("sort_by")
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, SoftwareHandover $record) {
                        // If no state (ID) is provided, return a fallback
                        if (!$state) {
                            return 'Unknown';
                        }

                        // For handover_pdf, extract filename
                        if ($record->handover_pdf) {
                            // Extract just the filename without extension
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        // Format ID with 250 prefix and pad with zeros to ensure at least 3 digits
                        return 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('4xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (SoftwareHandover $record): View {
                                return view('components.software-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),


                TextColumn::make('salesperson')
                    ->label('SalesPerson')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('company_name')
                    ->searchable()
                    ->label('Company Name')
                    ->formatStateUsing(function ($state, $record) {
                        $company = CompanyDetail::where('company_name', $state)->first();

                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();
                        }

                        if ($company) {
                            $shortened = strtoupper(Str::limit($company->company_name, 20, '...'));
                            $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                            return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($state) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $company->company_name . '
                                </a>');
                        }
                        $shortened = strtoupper(Str::limit($state, 20, '...'));
                        return "<span title='{$state}'>{$state}</span>";
                    })
                    ->html(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn(string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('activate_license')
                        ->label('Activate License')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form(function (SoftwareHandover $record) {
                            // Only show payroll code field if tp is true
                            if ($record->tp) {
                                return [
                                    TextInput::make('payroll_code')
                                        ->label('Payroll Code')
                                        ->required()
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                        ->placeholder('Enter the payroll code')
                                ];
                            }

                            // If tp is false, return empty form (just confirmation)
                            return [];
                        })
                        ->requiresConfirmation()
                        ->modalHeading(fn(SoftwareHandover $record) => "Activate License for {$record->company_name}")
                        ->modalDescription(function (SoftwareHandover $record) {
                            if ($record->tp) {
                                return 'Enter the payroll code and confirm license activation. This action is to make sure you have activated the license.';
                            }
                            return 'Confirm license activation. This action is to make sure you have activated the license.';
                        })
                        ->modalSubmitActionLabel('Yes, Activate License')
                        ->modalCancelActionLabel('No, Cancel')
                        ->action(function (SoftwareHandover $record, array $data): void {
                            // Update data based on tp field
                            $updateData = [
                                'license_activated' => true,
                            ];

                            // Only add payroll_code to update if tp is true and payroll_code was provided
                            if ($record->tp && isset($data['payroll_code'])) {
                                $updateData['payroll_code'] = $data['payroll_code'];
                            }

                            $record->update($updateData);

                            Notification::make()
                                ->title('License has been activated successfully')
                                ->success()
                                ->send();
                        }),
                    Action::make('view_license_details')
                        ->label('View License Details')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->modalHeading(fn(SoftwareHandover $record) => "License Details for {$record->company_name}")
                        ->modalWidth('xl')
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Close')
                        ->modalContent(function (SoftwareHandover $record) {
                            // Calculate dates based on company's license data
                            $kickOffDate = $record->kick_off_meeting ?? now();
                            $bufferMonths = 1; // Default buffer

                            if ($record->license_certification_id) {
                                $licenseCertificate = \App\Models\LicenseCertificate::find($record->license_certification_id);

                                if ($licenseCertificate) {
                                    $kickOffDate = $licenseCertificate->kick_off_date;
                                    $bufferStart = $licenseCertificate->buffer_license_start;
                                    $bufferEnd = $licenseCertificate->buffer_license_end;
                                    $paidStart = $licenseCertificate->paid_license_start;
                                    $paidEnd = $licenseCertificate->paid_license_end;
                                    $nextRenewal = $licenseCertificate->next_renewal_date;
                                    $yearPurchase = $licenseCertificate->license_years ?? 1;

                                    return view('components.license-details', [
                                        'company' => $record->company_name,
                                        'kickOffDate' => $kickOffDate ? Carbon::parse($kickOffDate)->format('d M Y') : 'N/A',
                                        'bufferLicense' => $bufferStart && $bufferEnd ?
                                            Carbon::parse($bufferStart)->format('d M Y') . ' – ' .
                                            Carbon::parse($bufferEnd)->format('d M Y') : 'N/A',
                                        'paidLicense' => $paidStart && $paidEnd ?
                                            Carbon::parse($paidStart)->format('d M Y') . ' – ' .
                                            Carbon::parse($paidEnd)->format('d M Y') : 'N/A',
                                        'yearPurchase' => is_numeric($yearPurchase) ?
                                            (int)$yearPurchase . ' year' . ((int)$yearPurchase > 1 ? 's' : '') : $yearPurchase,
                                        'nextRenewal' => $nextRenewal ? Carbon::parse($nextRenewal)->format('d M Y') : 'N/A',
                                    ]);
                                }
                            };
                        }),
                    Action::make('add_admin_remark')
                        ->label('Add Admin Remark')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('primary')
                        ->modalSubmitActionLabel('Save Remark')
                        ->modalWidth(MaxWidth::Medium)
                        ->modalHeading(fn(SoftwareHandover $record) => "Add Admin Remark for {$record->company_name}")
                        ->form([
                            Textarea::make('remark_content')
                                ->label('Remark')
                                ->required()
                                ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                ->afterStateHydrated(fn($state) => Str::upper($state))
                                ->afterStateUpdated(fn($state) => Str::upper($state))
                                ->rows(4)
                                ->placeholder('Enter your remark here...')
                                ->columnSpan(2),

                            // Show existing remarks if any
                            Placeholder::make('existing_remarks')
                                ->label('Existing Remarks')
                                ->content(function (SoftwareHandover $record) {
                                    if (!$record->admin_remarks_license) {
                                        return 'No remarks yet.';
                                    }

                                    $remarks = json_decode($record->admin_remarks_license, true) ?: [];
                                    $html = '';

                                    foreach ($remarks as $index => $remark) {
                                        $number = $index + 1;
                                        $html .= "<div class='p-3 mb-4 border border-gray-200 rounded bg-gray-50'>";
                                        $html .= "<strong>Admin Remark {$number}</strong><br>";
                                        $html .= "By {$remark['author']}<br>";
                                        $html .= "<span class='text-xs text-gray-500'>{$remark['date']}</span><br>";
                                        $html .= "<p class='mt-2'>{$remark['content']}</p>";
                                        $html .= "</div>";
                                    }

                                    return new HtmlString($html);
                                })
                                ->columnSpan(2)
                                ->visible(fn(SoftwareHandover $record) => !empty($record->admin_remarks_license))
                        ])
                        ->action(function (SoftwareHandover $record, array $data): void {
                            // Get existing remarks or create new array
                            $remarks = json_decode($record->admin_remarks_license, true) ?: [];

                            // Add new remark
                            $remarks[] = [
                                'author' => auth()->user()->name,
                                'date' => now()->format('Y-m-d H:i:s'),
                                'content' => strtoupper($data['remark_content'])
                            ];

                            // Update record
                            $record->update([
                                'admin_remarks_license' => json_encode($remarks)
                            ]);

                            Notification::make()
                                ->title('Admin remark added successfully')
                                ->success()
                                ->send();
                        })
                ])->button()

            ]);
    }

    public function render()
    {
        return view('livewire.software-handover-pending-license');
    }
}
