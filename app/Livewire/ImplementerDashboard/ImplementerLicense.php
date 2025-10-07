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
            ->where('id', '>=', 561)
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
                            ->modalHeading(' ')
                            ->modalWidth('md')
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
                        ->modalHeading(' ')
                        ->modalWidth('md')
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
                        ->form([
                            \Filament\Forms\Components\Grid::make(3)
                            ->schema([
                                \Filament\Forms\Components\DatePicker::make('confirmed_kickoff_date')
                                    ->label('Confirmed Kick-off Meeting Date')
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
                                    ->default('1'),

                                \Filament\Forms\Components\Select::make('paid_license_years')
                                    ->label('Paid License Duration')
                                    ->options([
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
                                    ->default('1'),
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

                            // Handle buffer license duration based on selection type
                            $bufferMonths = (int) $data['buffer_months'];
                            $bufferYears = 0;

                            // Handle paid license duration based on selection type
                            $paidLicenseYears = (int) $data['paid_license_years'];
                            $paidLicenseMonths = 0;

                            // Calculate buffer duration in months for display
                            $totalBufferMonths = ($bufferYears * 12) + $bufferMonths;

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
                                'software_handover_id' => $record->id, // Fixed from is_dir to id
                                'kick_off_date' => $kickOffDate ?? $record->kick_off_meeting ?? now(),
                                'buffer_license_start' => $kickOffDate,
                                'buffer_license_end' => $bufferEndDate,
                                'buffer_months' => $totalBufferMonths, // Store total buffer months
                                'paid_license_start' => $paidStartDate,
                                'paid_license_end' => $paidEndDate,
                                'paid_months' => ($paidLicenseYears * 12) + $paidLicenseMonths, // Store total paid months
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
                                            'duration' => $bufferDuration  // Use the formatted duration that includes both years and months
                                        ],
                                        'paidLicense' => [
                                            'start' => $paidStartDate->format('d M Y'),
                                            'end' => $paidEndDate->format('d M Y'),
                                            'duration' => $paidDuration  // Use the formatted duration that includes both years and months
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

                                // Always add implementer email if valid (since checkbox fields are not present in the form)
                                if ($implementerEmail && filter_var($implementerEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $implementerEmail;
                                }

                                // Always add salesperson email if valid
                                if ($salespersonEmail && filter_var($salespersonEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $salespersonEmail;
                                }

                                // // Always include adminx
                                // $recipients[] = 'admin.timetec.hr@timeteccloud.com';

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
                        })
                ])
                ->button()
                ->color('warning')
                ->label('Actions')
            ]);
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
