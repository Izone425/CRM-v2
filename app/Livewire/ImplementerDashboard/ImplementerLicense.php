<?php

namespace App\Livewire\ImplementerDashboard;

use App\Filament\Filters\SortFilter;
use App\Models\CompanyDetail;
use App\Models\SoftwareHandover;
use App\Models\User;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Attributes\On;

class ImplementerLicense extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

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

    #[On('refresh-implementer-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')] // Listen for updates
    public function updateTablesForUser($selectedUser)
    {
        if ($selectedUser) {
            $this->selectedUser = $selectedUser;
            session(['selectedUser' => $selectedUser]); // Store selected user
        } else {
            // Reset to "Your Own Dashboard" (value = 7)
            $this->selectedUser = 7;
            session(['selectedUser' => 7]);
        }

        $this->resetTable(); // Refresh the table
    }

    public function getOverdueSoftwareHandovers()
    {
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->user()->id;

        $query = SoftwareHandover::query()
            ->whereIn('status', ['Completed'])
            ->whereNull('license_certification_id')
            // ->where('id', '>=', 561)
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);

        if ($this->selectedUser === 'all-implementer') {

        }
        elseif (is_numeric($this->selectedUser)) {
            $user = User::find($this->selectedUser);

            if ($user && ($user->role_id === 4 || $user->role_id === 5)) {
                $query->where('implementer', $user->name);
            }
        }
        else {
            $currentUser = auth()->user();

            if ($currentUser->role_id === 4) {
                $query->where('implementer', $currentUser->name);
            }
        }

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getOverdueSoftwareHandovers())
            ->defaultSort('created_at', 'asc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Add this new filter for status
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'Draft' => 'Draft',
                        'New' => 'New',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                        'Completed' => 'Completed',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id',15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple(),

                SelectFilter::make('implementer')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::whereIn('role_id', [4,5])
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementers')
                    ->multiple(),

                SortFilter::make("sort_by"),
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
                    ->visible(fn(): bool => auth()->user()->role_id !== 4),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
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

                TextColumn::make('status_handover')
                    ->label('Status'),
            ])
            // ->filters([
            //     // Filter for Creator
            //     SelectFilter::make('created_by')
            //         ->label('Created By')
            //         ->multiple()
            //         ->options(User::pluck('name', 'id')->toArray())
            //         ->placeholder('Select User'),

            //     // Filter by Company Name
            //     SelectFilter::make('company_name')
            //         ->label('Company')
            //         ->searchable()
            //         ->options(HardwareHandover::distinct()->pluck('company_name', 'company_name')->toArray())
            //         ->placeholder('Select Company'),
            // ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('6xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (SoftwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.software-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('create_license_Duration')
                        ->label('Create License Duration')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn(SoftwareHandover $record): bool =>
                            $record->hr_version == '1' &&
                            $record->status === 'Completed' &&
                            is_null($record->license_certification_id)
                        )
                        ->form([
                            \Filament\Forms\Components\Grid::make(4)
                            ->schema([
                                \Filament\Forms\Components\DatePicker::make('confirmed_kickoff_date')
                                    ->label('Confirmed Kick-off Date')
                                    ->required()
                                    ->native(false)
                                    ->displayFormat('d M Y')
                                    ->default(function (SoftwareHandover $record) {
                                        return $record->kick_off_meeting ?? now();
                                    })
                                    ->columnSpan(1),

                                \Filament\Forms\Components\Select::make('buffer_months')
                                    ->label('Buffer License Duration')
                                    ->options([
                                        '1' => '1 month',
                                        '2' => '2 months',
                                        '3' => '3 months',
                                        '4' => '4 months',
                                        '5' => '5 months',
                                        '6' => '6 months',
                                        '7' => '7 months',
                                        '8' => '8 months',
                                        '9' => '9 months',
                                        '10' => '10 months',
                                        '11' => '11 months',
                                        '12' => '12 months',
                                    ])
                                    ->required()
                                    ->default('1')
                                    ->columnSpan(1),

                                \Filament\Forms\Components\Select::make('paid_license_years')
                                    ->label('Paid License Years')
                                    ->options([
                                        '0' => '0 years',
                                        '1' => '1 year',
                                        '2' => '2 years',
                                        '3' => '3 years',
                                        '4' => '4 years',
                                        '5' => '5 years',
                                        '6' => '6 years',
                                        '7' => '7 years',
                                        '8' => '8 years',
                                        '9' => '9 years',
                                        '10' => '10 years',
                                    ])
                                    ->required()
                                    ->default('1')
                                    ->columnSpan(1),

                                \Filament\Forms\Components\Select::make('paid_license_months')
                                    ->label('Paid License Months')
                                    ->options([
                                        '0' => '0 months',
                                        '1' => '1 month',
                                        '2' => '2 months',
                                        '3' => '3 months',
                                        '4' => '4 months',
                                        '5' => '5 months',
                                        '6' => '6 months',
                                        '7' => '7 months',
                                        '8' => '8 months',
                                        '9' => '9 months',
                                        '10' => '10 months',
                                        '11' => '11 months',
                                    ])
                                    ->required()
                                    ->default('0')
                                    ->columnSpan(1),
                            ]),
                            \Filament\Forms\Components\Section::make('Email Recipients')
                            ->schema([
                                \Filament\Forms\Components\Repeater::make('additional_recipients')
                                    ->hiddenLabel()
                                    ->schema([
                                        \Filament\Forms\Components\TextInput::make('email')
                                            ->label('Email Address')
                                            ->email()
                                            ->required()
                                            ->placeholder('Enter email address')
                                    ])
                                    ->defaultItems(1)  // Set to 1 to show one default item
                                    ->minItems(0)
                                    ->maxItems(10)  // Increased to accommodate more emails
                                    ->columnSpanFull()
                                    ->default(function (SoftwareHandover $record = null) {
                                        if (!$record) {
                                            return [];
                                        }

                                        $recipients = [];

                                        // Get company email from the record
                                        $companyEmail = $record->lead->companyDetail->email ?? $record->lead->email ?? null;

                                        // Process implementation_pics if available
                                        if ($record->implementation_pics) {
                                            try {
                                                // If already an array, use it directly; if string, decode it
                                                $implementationPics = is_array($record->implementation_pics)
                                                    ? $record->implementation_pics
                                                    : json_decode($record->implementation_pics, true);

                                                if (is_array($implementationPics)) {
                                                    foreach ($implementationPics as $pic) {
                                                        // Skip entries with "Resign" status
                                                        if (isset($pic['status']) && strtolower($pic['status']) === 'resign') {
                                                            continue;
                                                        }

                                                        // Extract email from pic_email_impl field
                                                        if (isset($pic['pic_email_impl']) &&
                                                            !empty($pic['pic_email_impl']) &&
                                                            filter_var($pic['pic_email_impl'], FILTER_VALIDATE_EMAIL)) {

                                                            // Check for duplicate emails
                                                            $emailExists = false;
                                                            foreach ($recipients as $recipient) {
                                                                if ($recipient['email'] === $pic['pic_email_impl']) {
                                                                    $emailExists = true;
                                                                    break;
                                                                }
                                                            }

                                                            // Only add if not a duplicate
                                                            if (!$emailExists) {
                                                                $recipients[] = ['email' => $pic['pic_email_impl']];
                                                            }
                                                        }
                                                    }
                                                }
                                            } catch (\Exception $e) {
                                                // Log the error but continue
                                                \Illuminate\Support\Facades\Log::error("Error parsing implementation_pics: " . $e->getMessage());
                                            }
                                        }

                                        // Process additional_pic from company details if available
                                        if ($record->lead && $record->lead->companyDetail && $record->lead->companyDetail->additional_pic) {
                                            try {
                                                // Parse the additional_pic field
                                                $additionalPics = is_array($record->lead->companyDetail->additional_pic)
                                                    ? $record->lead->companyDetail->additional_pic
                                                    : json_decode($record->lead->companyDetail->additional_pic, true);

                                                if (is_array($additionalPics)) {
                                                    foreach ($additionalPics as $pic) {
                                                        // Skip entries with "Resign" status
                                                        if (isset($pic['status']) && strtolower($pic['status']) === 'resign') {
                                                            continue;
                                                        }

                                                        // Extract email field
                                                        if (isset($pic['email']) &&
                                                            !empty($pic['email']) &&
                                                            filter_var($pic['email'], FILTER_VALIDATE_EMAIL)) {

                                                            // Check for duplicate emails
                                                            $emailExists = false;
                                                            foreach ($recipients as $recipient) {
                                                                if ($recipient['email'] === $pic['email']) {
                                                                    $emailExists = true;
                                                                    break;
                                                                }
                                                            }

                                                            // Only add if not a duplicate
                                                            if (!$emailExists) {
                                                                $recipients[] = ['email' => $pic['email']];
                                                            }
                                                        }
                                                    }
                                                }
                                            } catch (\Exception $e) {
                                                // Log the error but continue
                                                \Illuminate\Support\Facades\Log::error("Error parsing additional_pic: " . $e->getMessage());
                                            }
                                        }

                                        return empty($recipients) ? [['email' => '']] : $recipients;
                                    })
                            ]),
                        ])
                        ->modalHeading("Create License Duration")
                        ->modalSubmitActionLabel('Submit')
                        ->modalCancelActionLabel('Cancel')
                        ->action(function (array $data, SoftwareHandover $record): void {
                            // Get the implementer info
                            $implementer = \App\Models\User::where('name', $record->implementer)->first();
                            $implementerEmail = $implementer?->email ?? null;
                            $implementerName = $implementer?->name ?? $record->implementer ?? 'Unknown';

                            // Get the salesperson info
                            $salespersonId = $record->lead->salesperson ?? null;
                            $salesperson = \App\Models\User::find($salespersonId);
                            $salespersonEmail = $salesperson?->email ?? null;
                            $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                            // Get the company name
                            $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';

                            // Calculate license dates
                            $kickOffDate = $data['confirmed_kickoff_date'] ?? now();

                            // Ensure kickOffDate is a Carbon object before cloning
                            if (!$kickOffDate instanceof Carbon) {
                                $kickOffDate = Carbon::parse($kickOffDate);
                            }

                            // Handle buffer license duration
                            $bufferMonths = (int) $data['buffer_months'];
                            $bufferYears = 0;

                            // Handle paid license duration - now supporting both years and months
                            $paidLicenseYears = (int) ($data['paid_license_years'] ?? 0);
                            $paidLicenseMonths = (int) ($data['paid_license_months'] ?? 0);

                            // Validate that at least some paid license duration is specified
                            if ($paidLicenseYears === 0 && $paidLicenseMonths === 0) {
                                throw new \Exception('Please specify at least some paid license duration (years or months).');
                            }

                            // Calculate buffer duration in months for display
                            $totalBufferMonths = ($bufferYears * 12) + $bufferMonths;

                            // Calculate total paid duration in months
                            $totalPaidMonths = ($paidLicenseYears * 12) + $paidLicenseMonths;

                            // Calculate dates
                            $bufferEndDate = (clone $kickOffDate)->addMonths($totalBufferMonths);
                            $paidStartDate = (clone $bufferEndDate)->addDay();
                            $paidEndDate = (clone $paidStartDate)
                                ->addYears($paidLicenseYears)
                                ->addMonths($paidLicenseMonths)
                                ->subDay();
                            $nextRenewalDate = (clone $paidEndDate)->addDay();

                            // Format durations for display
                            $bufferDuration = $this->formatDuration($bufferYears, $bufferMonths);
                            $paidDuration = $this->formatDuration($paidLicenseYears, $paidLicenseMonths);

                            // Create a new license certificate record
                            $certificate = \App\Models\LicenseCertificate::create([
                                'company_name' => $companyName,
                                'software_handover_id' => $record->id,
                                'kick_off_date' => $kickOffDate ?? $record->kick_off_meeting ?? now(),
                                'buffer_license_start' => $kickOffDate,
                                'buffer_license_end' => $bufferEndDate,
                                'buffer_months' => $totalBufferMonths,
                                'paid_license_start' => $paidStartDate,
                                'paid_license_end' => $paidEndDate,
                                'paid_months' => $totalPaidMonths, // Store total paid months
                                'next_renewal_date' => $nextRenewalDate,
                                'license_years' => $paidLicenseYears + ($paidLicenseMonths / 12), // Store license years with decimal for months
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id(),
                            ]);

                            // Update the software handover record with license information
                            $record->update([
                                'license_certification_id' => $certificate->id,
                                'kick_off_meeting' => $data['confirmed_kickoff_date'] ?? $record->kick_off_meeting,
                            ]);

                            // Format the handover ID properly
                            $handoverId = 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                            $certificateId = 'LC_' . str_pad($certificate->id, 4, '0', STR_PAD_LEFT);

                            // Get the handover PDF URL
                            $handoverFormUrl = $record->handover_pdf ? url('storage/' . $record->handover_pdf) : null;

                            // Send email notification
                            try {
                                $viewName = 'emails.implementer_license_notification';

                                // Create email content structure
                                $emailContent = [
                                    'company' => [
                                        'name' => $companyName,
                                    ],
                                    'salesperson' => [
                                        'name' => $salespersonName,
                                    ],
                                    'implementer' => [
                                        'name' => $implementerName,
                                    ],
                                    'handover_id' => $handoverId,
                                    'certificate_id' => $certificateId,
                                    'activatedAt' => now()->format('d M Y'),
                                    'licenses' => [
                                        'kickOffDate' => $record->kick_off_meeting ? $record->kick_off_meeting->format('d M Y') : now()->format('d M Y'),
                                        'bufferLicense' => [
                                            'start' => $kickOffDate->format('d M Y'),
                                            'end' => $bufferEndDate->format('d M Y'),
                                            'duration' => $bufferDuration
                                        ],
                                        'paidLicense' => [
                                            'start' => $paidStartDate->format('d M Y'),
                                            'end' => $paidEndDate->format('d M Y'),
                                            'duration' => $paidDuration
                                        ],
                                        'nextRenewal' => $nextRenewalDate->format('d M Y')
                                    ],
                                ];

                                // Initialize recipients array
                                $recipients = [];

                                // Process additional recipients from the form data
                                if (isset($data['additional_recipients']) && is_array($data['additional_recipients'])) {
                                    foreach ($data['additional_recipients'] as $recipient) {
                                        if (isset($recipient['email']) && filter_var($recipient['email'], FILTER_VALIDATE_EMAIL)) {
                                            $recipients[] = $recipient['email'];
                                        }
                                    }
                                }

                                // Always add implementer email if valid
                                if ($implementerEmail && filter_var($implementerEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $implementerEmail;
                                }

                                // Always add salesperson email if valid
                                if ($salespersonEmail && filter_var($salespersonEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $salespersonEmail;
                                }

                                // Get authenticated user's email for sender
                                $authUser = auth()->user();
                                $senderEmail = $authUser->email;
                                $senderName = $authUser->name;

                                // Send email with template and custom subject format
                                if (count($recipients) > 0) {
                                    \Illuminate\Support\Facades\Mail::send($viewName, ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $certificateId, $companyName) {
                                        $message->from($senderEmail, $senderName)
                                            ->to($recipients)
                                            ->subject("LICENSE CERTIFICATE | TIMETEC HR | {$companyName}");
                                    });

                                    \Illuminate\Support\Facades\Log::info("Data migration completion & license certification email sent successfully from {$senderEmail} to: " . implode(', ', $recipients));
                                }
                            } catch (\Exception $e) {
                                // Log error but don't stop the process
                                \Illuminate\Support\Facades\Log::error("Email sending failed for software handover #{$record->id}: {$e->getMessage()}");
                            }

                            Notification::make()
                                ->title('License Duration Created')
                                ->success()
                                ->body("License certificate duration generated successfully and email has been sent.")
                                ->send();
                        }),
                    Action::make('activate_license_v2')
                        ->label('Activate License (V2)')
                        ->icon('heroicon-o-key')
                        ->color('success')
                        ->visible(fn(SoftwareHandover $record): bool =>
                            $record->hr_version == 2 &&
                            $record->status === 'Completed' &&
                            is_null($record->license_certification_id)
                        )
                        ->form([
                            \Filament\Forms\Components\Section::make('Module Selection')
                                ->description('Modules are automatically selected based on quotation')
                                ->schema([
                                    \Filament\Forms\Components\Grid::make(2)
                                        ->schema([
                                            \Filament\Forms\Components\Checkbox::make('ta')
                                                ->label('Time Attendance (TA)')
                                                ->inline()
                                                ->disabled()
                                                ->dehydrated()
                                                ->helperText(function (SoftwareHandover $record) {
                                                    return $this->getModulePeriodInfo($record, ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW']);
                                                })
                                                ->default(function (SoftwareHandover $record) {
                                                    return $this->shouldModuleBeChecked($record, ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW']);
                                                }),

                                            \Filament\Forms\Components\Checkbox::make('tapp')
                                                ->label('TimeTec Appraisal (T-APP)')
                                                ->inline()
                                                ->disabled()
                                                ->dehydrated()
                                                ->helperText(function (SoftwareHandover $record) {
                                                    return $this->getModulePeriodInfo($record, ['TCL_APPRAISAL USER-NEW']);
                                                })
                                                ->default(function (SoftwareHandover $record) {
                                                    return $this->shouldModuleBeChecked($record, ['TCL_APPRAISAL USER-NEW']);
                                                }),

                                            \Filament\Forms\Components\Checkbox::make('tl')
                                                ->label('TimeTec Leave (TL)')
                                                ->inline()
                                                ->disabled()
                                                ->dehydrated()
                                                ->helperText(function (SoftwareHandover $record) {
                                                    return $this->getModulePeriodInfo($record, ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW']);
                                                })
                                                ->default(function (SoftwareHandover $record) {
                                                    return $this->shouldModuleBeChecked($record, ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW']);
                                                }),

                                            \Filament\Forms\Components\Checkbox::make('thire')
                                                ->label('TimeTec Hire (T-HIRE)')
                                                ->inline()
                                                ->disabled()
                                                ->dehydrated()
                                                ->helperText(function (SoftwareHandover $record) {
                                                    return $this->getModulePeriodInfo($record, ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL']);
                                                })
                                                ->default(function (SoftwareHandover $record) {
                                                    return $this->shouldModuleBeChecked($record, ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL']);
                                                }),

                                            \Filament\Forms\Components\Checkbox::make('tc')
                                                ->label('TimeTec Claim (TC)')
                                                ->inline()
                                                ->disabled()
                                                ->dehydrated()
                                                ->helperText(function (SoftwareHandover $record) {
                                                    return $this->getModulePeriodInfo($record, ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW']);
                                                })
                                                ->default(function (SoftwareHandover $record) {
                                                    return $this->shouldModuleBeChecked($record, ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW']);
                                                }),

                                            \Filament\Forms\Components\Checkbox::make('tacc')
                                                ->label('TimeTec Access (T-ACC)')
                                                ->inline()
                                                ->disabled()
                                                ->dehydrated()
                                                ->helperText(function (SoftwareHandover $record) {
                                                    return $this->getModulePeriodInfo($record, ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL']);
                                                })
                                                ->default(function (SoftwareHandover $record) {
                                                    return $this->shouldModuleBeChecked($record, ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL']);
                                                }),

                                            \Filament\Forms\Components\Checkbox::make('tp')
                                                ->label('TimeTec Payroll (TP)')
                                                ->inline()
                                                ->disabled()
                                                ->dehydrated()
                                                ->helperText(function (SoftwareHandover $record) {
                                                    return $this->getModulePeriodInfo($record, ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW']);
                                                })
                                                ->default(function (SoftwareHandover $record) {
                                                    return $this->shouldModuleBeChecked($record, ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW']);
                                                }),

                                            \Filament\Forms\Components\Checkbox::make('tpbi')
                                                ->label('TimeTec Power BI (T-PBI)')
                                                ->inline()
                                                ->disabled()
                                                ->dehydrated()
                                                ->helperText(function (SoftwareHandover $record) {
                                                    return $this->getModulePeriodInfo($record, ['TCL_POWER BI']);
                                                })
                                                ->default(function (SoftwareHandover $record) {
                                                    return $this->shouldModuleBeChecked($record, ['TCL_POWER BI']);
                                                }),
                                        ])
                                ]),

                            \Filament\Forms\Components\Section::make('License Duration Summary')
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('buffer_period')
                                        ->label('Buffer License Period')
                                        ->content(function () {
                                            $start = now()->format('d M Y');
                                            $end = now()->addMonth()->subDay()->format('d M Y');
                                            return "**{$start}** to **{$end}** (1 month)";
                                        }),

                                    \Filament\Forms\Components\Placeholder::make('paid_period')
                                        ->label('Paid License Period')
                                        ->content(function (SoftwareHandover $record) {
                                            $start = now()->addMonth()->format('d M Y');

                                            // Get total paid months
                                            $allPiIds = [];
                                            if (!empty($record->proforma_invoice_product)) {
                                                $productPis = is_string($record->proforma_invoice_product)
                                                    ? json_decode($record->proforma_invoice_product, true)
                                                    : $record->proforma_invoice_product;
                                                if (is_array($productPis)) {
                                                    $allPiIds = array_merge($allPiIds, $productPis);
                                                }
                                            }
                                            if (!empty($record->proforma_invoice_hrdf)) {
                                                $hrdfPis = is_string($record->proforma_invoice_hrdf)
                                                    ? json_decode($record->proforma_invoice_hrdf, true)
                                                    : $record->proforma_invoice_hrdf;
                                                if (is_array($hrdfPis)) {
                                                    $allPiIds = array_merge($allPiIds, $hrdfPis);
                                                }
                                            }

                                            $totalPaidMonths = 0;
                                            if (!empty($allPiIds)) {
                                                $licensePeriods = $this->getLicensePeriodsFromQuotations($allPiIds, $record->project_code);
                                                foreach ($licensePeriods as $period) {
                                                    if ($period['subscription_period'] > $totalPaidMonths) {
                                                        $totalPaidMonths = $period['subscription_period'];
                                                    }
                                                }
                                            }

                                            if ($totalPaidMonths === 0) {
                                                $totalPaidMonths = 12;
                                            }

                                            $end = now()->addMonth()->addMonths($totalPaidMonths)->subDay()->format('d M Y');
                                            $duration = $this->formatDuration(floor($totalPaidMonths / 12), $totalPaidMonths % 12);

                                            return "**{$start}** to **{$end}** ({$duration})";
                                        }),
                                ])
                                ->columns(2),
                        ])
                        ->modalHeading('Activate V2 License')
                        ->modalWidth('4xl')
                        ->action(function (SoftwareHandover $record, array $data): void {
                            // ... existing action code remains the same ...
                            $handoverId = $record->project_code;
                            $accountId = $record->hr_account_id;
                            $companyId = $record->hr_company_id;

                            if (!$accountId || !$companyId) {
                                Notification::make()
                                    ->title('License Activation Failed')
                                    ->danger()
                                    ->body('CRM account not found. Please complete the handover first.')
                                    ->send();
                                return;
                            }

                            // Extract module selections (from disabled but dehydrated checkboxes)
                            $moduleSelections = [
                                'ta' => $data['ta'] ?? false,
                                'tl' => $data['tl'] ?? false,
                                'tc' => $data['tc'] ?? false,
                                'tp' => $data['tp'] ?? false,
                                'tapp' => $data['tapp'] ?? false,
                                'thire' => $data['thire'] ?? false,
                                'tacc' => $data['tacc'] ?? false,
                                'tpbi' => $data['tpbi'] ?? false,
                            ];

                            \Illuminate\Support\Facades\Log::info("Starting V2 license activation", [
                                'handover_id' => $handoverId,
                                'account_id' => $accountId,
                                'company_id' => $companyId,
                                'modules' => $moduleSelections
                            ]);

                            // Get company name
                            $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';

                            // Calculate license dates for V2
                            $bufferStartDate = now();
                            $bufferEndDate = now()->copy()->addMonth()->subDay();
                            $paidStartDate = now()->copy()->addMonth();

                            // Get total paid months from quotations
                            $allPiIds = [];
                            if (!empty($record->proforma_invoice_product)) {
                                $productPis = is_string($record->proforma_invoice_product)
                                    ? json_decode($record->proforma_invoice_product, true)
                                    : $record->proforma_invoice_product;
                                if (is_array($productPis)) {
                                    $allPiIds = array_merge($allPiIds, $productPis);
                                }
                            }
                            if (!empty($record->proforma_invoice_hrdf)) {
                                $hrdfPis = is_string($record->proforma_invoice_hrdf)
                                    ? json_decode($record->proforma_invoice_hrdf, true)
                                    : $record->proforma_invoice_hrdf;
                                if (is_array($hrdfPis)) {
                                    $allPiIds = array_merge($allPiIds, $hrdfPis);
                                }
                            }

                            // Calculate total paid months
                            $totalPaidMonths = 0;
                            if (!empty($allPiIds)) {
                                $licensePeriods = $this->getLicensePeriodsFromQuotations($allPiIds, $handoverId);
                                foreach ($licensePeriods as $period) {
                                    if ($period['subscription_period'] > $totalPaidMonths) {
                                        $totalPaidMonths = $period['subscription_period'];
                                    }
                                }
                            }

                            if ($totalPaidMonths === 0) {
                                $totalPaidMonths = 12;
                            }

                            $paidEndDate = $paidStartDate->copy()->addMonths($totalPaidMonths)->subDay();
                            $nextRenewalDate = $paidEndDate->copy()->addDay();

                            $paidLicenseYears = floor($totalPaidMonths / 12);
                            $paidLicenseMonths = $totalPaidMonths % 12;
                            $licenseYears = $paidLicenseYears + ($paidLicenseMonths / 12);

                            // Create license certificate
                            $certificate = \App\Models\LicenseCertificate::create([
                                'company_name' => $companyName,
                                'software_handover_id' => $record->id,
                                'kick_off_date' => $record->kick_off_meeting ?? now(),
                                'buffer_license_start' => $bufferStartDate,
                                'buffer_license_end' => $bufferEndDate,
                                'buffer_months' => 1,
                                'paid_license_start' => $paidStartDate,
                                'paid_license_end' => $paidEndDate,
                                'paid_months' => $totalPaidMonths,
                                'next_renewal_date' => $nextRenewalDate,
                                'license_years' => $licenseYears,
                                'created_by' => auth()->id(),
                                'updated_by' => auth()->id(),
                            ]);

                            // Add buffer and paid licenses
                            $bufferResult = $this->addBufferLicenses($record, $accountId, $companyId, $moduleSelections, $handoverId);
                            $paidResult = $this->addPaidApplicationLicenses($record, $accountId, $companyId, $moduleSelections, $handoverId);

                            // Update software handover
                            $record->update([
                                'license_certification_id' => $certificate->id,
                                'license_activated' => true,
                                'license_activated_at' => now(),
                                'ta' => $moduleSelections['ta'],
                                'tl' => $moduleSelections['tl'],
                                'tc' => $moduleSelections['tc'],
                                'tp' => $moduleSelections['tp'],
                                'tapp' => $moduleSelections['tapp'],
                                'thire' => $moduleSelections['thire'],
                                'tacc' => $moduleSelections['tacc'],
                                'tpbi' => $moduleSelections['tpbi'],
                            ]);

                            $certificateId = 'LC_' . str_pad($certificate->id, 4, '0', STR_PAD_LEFT);

                            if ($bufferResult['success'] && $paidResult['success']) {
                                \Illuminate\Support\Facades\Log::info("V2 License certificate created", [
                                    'handover_id' => $handoverId,
                                    'certificate_id' => $certificateId,
                                    'buffer_period' => "{$bufferStartDate->format('d M Y')} to {$bufferEndDate->format('d M Y')}",
                                    'paid_period' => "{$paidStartDate->format('d M Y')} to {$paidEndDate->format('d M Y')}",
                                    'total_paid_months' => $totalPaidMonths
                                ]);

                                Notification::make()
                                    ->title('V2 License Activated')
                                    ->success()
                                    ->body("License certificate {$certificateId} created. Buffer and paid licenses added successfully.")
                                    ->send();
                            } else {
                                $errors = [];
                                if (!$bufferResult['success']) {
                                    $errors[] = "Buffer: " . ($bufferResult['error'] ?? 'Unknown error');
                                }
                                if (!$paidResult['success']) {
                                    $errors[] = "Paid: " . ($paidResult['error'] ?? 'Unknown error');
                                }

                                Notification::make()
                                    ->title('License Activation Completed with Errors')
                                    ->warning()
                                    ->body("Certificate created but some licenses failed: " . implode('; ', $errors))
                                    ->send();
                            }
                        }),
                ])
                ->button()
                ->color('warning')
                ->label('Actions')
            ]);
    }

    /**
     * Get module period info for display
     */
    protected function getModulePeriodInfo(SoftwareHandover $record, array $productCodes): ?string
    {
        // Get all PI IDs
        $allPiIds = [];
        if (!empty($record->proforma_invoice_product)) {
            $productPis = is_string($record->proforma_invoice_product)
                ? json_decode($record->proforma_invoice_product, true)
                : $record->proforma_invoice_product;
            if (is_array($productPis)) {
                $allPiIds = array_merge($allPiIds, $productPis);
            }
        }
        if (!empty($record->proforma_invoice_hrdf)) {
            $hrdfPis = is_string($record->proforma_invoice_hrdf)
                ? json_decode($record->proforma_invoice_hrdf, true)
                : $record->proforma_invoice_hrdf;
            if (is_array($hrdfPis)) {
                $allPiIds = array_merge($allPiIds, $hrdfPis);
            }
        }

        if (empty($allPiIds)) {
            return null;
        }

        // Get license periods
        $licensePeriods = $this->getLicensePeriodsFromQuotations($allPiIds, $record->project_code);

        // Find matching period for this module
        foreach ($licensePeriods as $period) {
            $intersection = array_intersect($productCodes, $period['product_codes']);
            if (!empty($intersection)) {
                $totalMonths = $period['subscription_period'];
                $years = floor($totalMonths / 12);
                $months = $totalMonths % 12;
                $duration = $this->formatDuration($years, $months);

                $startDate = now()->addMonth()->format('d M Y');
                $endDate = $period['end_date'];

                return "📅 {$startDate} to {$endDate} ({$duration})";
            }
        }

        return null;
    }

    /**
     * Check if module should be checked based on quotation products
     */
    protected function shouldModuleBeChecked(SoftwareHandover $record, array $productCodes): bool
    {
        // Get all PI IDs from proforma_invoice_product and proforma_invoice_hrdf
        $allPiIds = [];

        if (!empty($record->proforma_invoice_product)) {
            $productPis = is_string($record->proforma_invoice_product)
                ? json_decode($record->proforma_invoice_product, true)
                : $record->proforma_invoice_product;
            if (is_array($productPis)) {
                $allPiIds = array_merge($allPiIds, $productPis);
            }
        }

        if (!empty($record->proforma_invoice_hrdf)) {
            $hrdfPis = is_string($record->proforma_invoice_hrdf)
                ? json_decode($record->proforma_invoice_hrdf, true)
                : $record->proforma_invoice_hrdf;
            if (is_array($hrdfPis)) {
                $allPiIds = array_merge($allPiIds, $hrdfPis);
            }
        }

        if (empty($allPiIds)) {
            return false;
        }

        // Get quotation details for these PIs
        $quotations = \App\Models\Quotation::whereIn('id', $allPiIds)->get();

        foreach ($quotations as $quotation) {
            $details = \App\Models\QuotationDetail::where('quotation_id', $quotation->id)
                ->with('product')
                ->get();

            foreach ($details as $detail) {
                if (!$detail->product) {
                    continue;
                }

                // Check if this product code matches any of the module's product codes
                if (in_array($detail->product->code, $productCodes)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Add buffer licenses for all selected modules (1 month)
     */
    protected function addBufferLicenses(SoftwareHandover $record, int $accountId, int $companyId, array $modules, string $handoverId): array
    {
        try {
            $crmService = app(\App\Services\CRMApiService::class);

            // Buffer license: 1 month starting from today
            $bufferStartDate = now()->format('Y-m-d');
            $bufferEndDate = now()->addMonth()->subDay()->format('Y-m-d');

            \Illuminate\Support\Facades\Log::info("Adding buffer licenses", [
                'handover_id' => $handoverId,
                'buffer_start' => $bufferStartDate,
                'buffer_end' => $bufferEndDate,
                'modules' => $modules
            ]);

            // Map module checkboxes to application names
            $moduleMapping = [
                'ta' => 'Attendance',
                'tl' => 'Leave',
                'tc' => 'Claim',
                'tp' => 'Payroll',
                'tapp' => 'Appraisal',
                'thire' => 'Hire',
                'tacc' => 'Access',
                'tpbi' => 'PowerBI',
            ];

            $results = [];
            $successCount = 0;
            $failCount = 0;

            foreach ($moduleMapping as $moduleKey => $appName) {
                if (!empty($modules[$moduleKey])) {
                    $licenseData = [
                        'application' => $appName,
                        'startDate' => $bufferStartDate,
                        'endDate' => $bufferEndDate,
                        'userId' => auth()->id(),
                    ];

                    \Illuminate\Support\Facades\Log::info("Adding buffer license for module", [
                        'handover_id' => $handoverId,
                        'application' => $appName,
                        'license_data' => $licenseData
                    ]);

                    $result = $crmService->addBufferLicense($accountId, $companyId, $licenseData);

                    $results[$appName] = $result;

                    if ($result['success']) {
                        $successCount++;
                        \Illuminate\Support\Facades\Log::info("Buffer license added successfully", [
                            'handover_id' => $handoverId,
                            'application' => $appName,
                            'period_id' => $result['data']['periodId'] ?? null
                        ]);
                    } else {
                        $failCount++;
                        \Illuminate\Support\Facades\Log::error("Failed to add buffer license", [
                            'handover_id' => $handoverId,
                            'application' => $appName,
                            'error' => $result['error'] ?? 'Unknown error'
                        ]);
                    }
                }
            }

            return [
                'success' => $successCount > 0,
                'results' => $results,
                'success_count' => $successCount,
                'fail_count' => $failCount
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to add buffer licenses", [
                'handover_id' => $handoverId,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Add paid application licenses based on selected modules and quotation details
     */
    protected function addPaidApplicationLicenses(SoftwareHandover $record, int $accountId, int $companyId, array $modules, string $handoverId): array
    {
        try {
            $crmService = app(\App\Services\CRMApiService::class);

            // Get all PI IDs from proforma_invoice_product and proforma_invoice_hrdf
            $allPiIds = [];

            if (!empty($record->proforma_invoice_product)) {
                $productPis = is_string($record->proforma_invoice_product)
                    ? json_decode($record->proforma_invoice_product, true)
                    : $record->proforma_invoice_product;
                if (is_array($productPis)) {
                    $allPiIds = array_merge($allPiIds, $productPis);
                }
            }

            if (!empty($record->proforma_invoice_hrdf)) {
                $hrdfPis = is_string($record->proforma_invoice_hrdf)
                    ? json_decode($record->proforma_invoice_hrdf, true)
                    : $record->proforma_invoice_hrdf;
                if (is_array($hrdfPis)) {
                    $allPiIds = array_merge($allPiIds, $hrdfPis);
                }
            }

            if (empty($allPiIds)) {
                \Illuminate\Support\Facades\Log::warning("No proforma invoices found", [
                    'handover_id' => $handoverId
                ]);
                return ['success' => false, 'error' => 'No proforma invoices found'];
            }

            \Illuminate\Support\Facades\Log::info("Processing paid licenses for PIs", [
                'handover_id' => $handoverId,
                'pi_ids' => $allPiIds
            ]);

            // Get license periods from quotations
            $licensePeriods = $this->getLicensePeriodsFromQuotations($allPiIds, $handoverId);

            if (empty($licensePeriods)) {
                \Illuminate\Support\Facades\Log::warning("No valid license periods found", [
                    'handover_id' => $handoverId,
                    'pi_ids' => $allPiIds
                ]);
                return ['success' => false, 'error' => 'No valid license periods found in quotations'];
            }

            // Map module checkboxes to application names and product codes
            $moduleMapping = [
                'ta' => ['app' => 'Attendance', 'codes' => ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW']],
                'tl' => ['app' => 'Leave', 'codes' => ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW']],
                'tc' => ['app' => 'Claim', 'codes' => ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW']],
                'tp' => ['app' => 'Payroll', 'codes' => ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW']],
                'tapp' => ['app' => 'Appraisal', 'codes' => ['TCL_APPRAISAL USER-NEW']],
                'thire' => ['app' => 'Hire', 'codes' => ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL']],
                'tacc' => ['app' => 'Access', 'codes' => ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL']],
                'tpbi' => ['app' => 'PowerBI', 'codes' => ['TCL_POWER BI']],
            ];

            $results = [];

            // Paid license: starts the day after buffer ends (1 month from now)
            $paidStartDate = now()->addMonth()->format('Y-m-d');

            \Illuminate\Support\Facades\Log::info("Paid license start date", [
                'handover_id' => $handoverId,
                'paid_start' => $paidStartDate
            ]);

            $successCount = 0;
            $failCount = 0;

            foreach ($moduleMapping as $moduleKey => $moduleInfo) {
                if (!empty($modules[$moduleKey])) {
                    $appName = $moduleInfo['app'];
                    $productCodes = $moduleInfo['codes'];

                    // Find matching license period and calculate paid end date
                    $paidEndDate = $this->findEndDateForModule($productCodes, $licensePeriods, $paidStartDate, $handoverId);

                    if (!$paidEndDate) {
                        \Illuminate\Support\Facades\Log::warning("No end date found for module", [
                            'handover_id' => $handoverId,
                            'application' => $appName,
                            'product_codes' => $productCodes
                        ]);
                        continue;
                    }

                    $licenseData = [
                        'application' => $appName,
                        'startDate' => $paidStartDate,
                        'endDate' => $paidEndDate,
                        'userId' => auth()->id(),
                    ];

                    \Illuminate\Support\Facades\Log::info("Adding paid application license", [
                        'handover_id' => $handoverId,
                        'application' => $appName,
                        'license_data' => $licenseData
                    ]);

                    $result = $crmService->addPaidApplicationLicense($accountId, $companyId, $licenseData);

                    $results[$appName] = $result;

                    if ($result['success']) {
                        $successCount++;
                        \Illuminate\Support\Facades\Log::info("Paid application license added", [
                            'handover_id' => $handoverId,
                            'application' => $appName,
                            'period_id' => $result['data']['periodId'] ?? null,
                            'start_date' => $paidStartDate,
                            'end_date' => $paidEndDate
                        ]);
                    } else {
                        $failCount++;
                        \Illuminate\Support\Facades\Log::error("Failed to add paid application license", [
                            'handover_id' => $handoverId,
                            'application' => $appName,
                            'error' => $result['error'] ?? 'Unknown error'
                        ]);
                    }
                }
            }

            return [
                'success' => $successCount > 0,
                'results' => $results,
                'success_count' => $successCount,
                'fail_count' => $failCount
            ];

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to add paid application licenses", [
                'handover_id' => $handoverId,
                'error' => $e->getMessage()
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get license periods from quotations - groups by module type and sums periods
     */
    protected function getLicensePeriodsFromQuotations(array $piIds, string $handoverId): array
    {
        $licensePeriods = [];
        $quotations = \App\Models\Quotation::whereIn('id', $piIds)->get();

        $moduleGroups = [
            'Attendance' => ['TCL_TA USER-NEW', 'TCL_TA USER-ADDON', 'TCL_TA USER-ADDON(R)', 'TCL_TA USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Leave' => ['TCL_LEAVE USER-NEW', 'TCL_LEAVE USER-ADDON', 'TCL_LEAVE USER-ADDON(R)', 'TCL_LEAVE USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Claim' => ['TCL_CLAIM USER-NEW', 'TCL_CLAIM USER-ADDON', 'TCL_CLAIM USER-ADDON(R)', 'TCL_CLAIM USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Payroll' => ['TCL_PAYROLL USER-NEW', 'TCL_PAYROLL USER-ADDON', 'TCL_PAYROLL USER-ADDON(R)', 'TCL_PAYROLL USER-RENEWAL', 'TCL_FULL USER-NEW'],
            'Appraisal' => ['TCL_APPRAISAL USER-NEW'],
            'Hire' => ['TCL_HIRE-NEW', 'TCL_HIRE-RENEWAL'],
            'Access' => ['TCL_ACCESS-NEW', 'TCL_ACCESS-RENEWAL'],
            'PowerBI' => ['TCL_POWER BI'],
        ];

        $periodsByModule = [];

        foreach ($quotations as $quotation) {
            $details = \App\Models\QuotationDetail::where('quotation_id', $quotation->id)->with('product')->get();

            foreach ($details as $detail) {
                if (!$detail->product) continue;

                $productCode = $detail->product->code;
                $subscriptionPeriod = $detail->subscription_period ?? $detail->product->subscription_period ?? 12;

                $moduleName = null;
                foreach ($moduleGroups as $module => $codes) {
                    if (in_array($productCode, $codes)) {
                        $moduleName = $module;
                        break;
                    }
                }

                if (!$moduleName) continue;

                if (!isset($periodsByModule[$moduleName])) {
                    $periodsByModule[$moduleName] = [
                        'module_name' => $moduleName,
                        'total_months' => 0,
                        'product_codes' => [],
                    ];
                }

                $periodsByModule[$moduleName]['total_months'] += (int)$subscriptionPeriod;
                $periodsByModule[$moduleName]['product_codes'][] = $productCode;
            }
        }

        $paidStartDate = now()->addMonth();

        foreach ($periodsByModule as $moduleName => $data) {
            $totalMonths = $data['total_months'];
            $endDate = $paidStartDate->copy()->addMonths($totalMonths)->subDay()->format('Y-m-d');

            $licensePeriods[] = [
                'module_name' => $moduleName,
                'product_codes' => array_unique($data['product_codes']),
                'subscription_period' => $totalMonths,
                'end_date' => $endDate,
            ];
        }

        return $licensePeriods;
    }

    /**
     * Find end date for a specific module based on product codes
     */
    protected function findEndDateForModule(array $productCodes, array $licensePeriods, string $startDate, string $handoverId): ?string
    {
        foreach ($licensePeriods as $period) {
            $intersection = array_intersect($productCodes, $period['product_codes']);

            if (!empty($intersection)) {
                return $period['end_date'];
            }
        }

        return null;
    }

    private function formatDuration(int $years, int $months): string
    {
        $parts = [];

        if ($years > 0) {
            $parts[] = $years . ' year' . ($years > 1 ? 's' : '');
        }

        if ($months > 0) {
            $parts[] = $months . ' month' . ($months > 1 ? 's' : '');
        }

        if (empty($parts)) {
            return '0 months';
        }

        return implode(' and ', $parts);
    }

    public function render()
    {
        return view('livewire.implementer_dashboard.implementer-license');
    }
}
