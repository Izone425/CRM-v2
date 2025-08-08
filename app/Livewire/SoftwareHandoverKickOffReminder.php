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
use Filament\Forms\Components\Hidden;
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
use Illuminate\Database\Eloquent\Model;
use Livewire\Attributes\On;

class SoftwareHandoverKickOffReminder extends Component implements HasForms, HasTable
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
        $query->whereNull('kick_off_meeting');
        $query->orderBy('updated_at', 'desc');
        $query->where(function ($q) {
            $q->whereIn('id', [420, 520, 531, 539]) //4 Company included
                ->orWhere('id', '>=', 540);
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
                            ->whereNot('id', 15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
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
                            ->modalWidth('3xl')
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
                    Action::make('manual_kick_off_meeting')
                        ->label('Task Completed (Manual)')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->modalSubmitActionLabel('Submit')
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->modalHeading(fn(SoftwareHandover $record) => "Online Kick-Off Meeting for {$record->company_name}") // SlideOver title
                        ->form([
                            DatePicker::make('kick_off_meeting')
                                ->native(false)
                                ->displayFormat('d F Y')
                                ->placeholder("Choose date")
                                ->label("Online Kick Off Meeting Date"),
                            PlaceHolder::make('webinar_training')
                                ->label("Online Webinar Date")
                                ->content('N/A'),
                        ])
                        ->action(function (SoftwareHandover $record, array $data): void {
                            $record->update([
                                'kick_off_meeting' => $data['kick_off_meeting']
                            ]);

                            Notification::make()
                                ->title('Online Kick-Off Meeting Scheduled successfully')
                                ->success()
                                ->send();
                        }),
                    Action::make('edit_kick_off_meeting')
                        ->label('Task Completed')
                        ->icon('heroicon-o-pencil')
                        ->color('warning')
                        ->modalSubmitActionLabel('Submit')
                        ->modalWidth(MaxWidth::FourExtraLarge)
                        ->modalHeading(fn(SoftwareHandover $record) => "Online Kick-Off Meeting for {$record->company_name}") // SlideOver title
                        ->form([
                            Grid::make(3)
                            ->schema([
                                DatePicker::make('date')
                                    ->required()
                                    ->label('DATE (MONDAY-THURSDAY/FRIDAY)')
                                    ->default(function ($record = null) {
                                        return $record ? $record->date : Carbon::today()->toDateString();
                                    })
                                    ->reactive()
                                    ->columnSpan(1),

                                Select::make('session')
                                    ->label('SESSION')
                                    ->options(function (callable $get) {
                                        $date = $get('date');
                                        $implementer = $get('implementer'); // Get the selected implementer

                                        if (!$date || !$implementer) return [];

                                        $selectedDate = Carbon::parse($date);
                                        $dayOfWeek = $selectedDate->dayOfWeek;

                                        // Define all possible sessions based on day of week
                                        $allSessions = [];

                                        // Friday sessions (dayOfWeek = 5)
                                        if ($dayOfWeek === 5) {
                                            $allSessions = [
                                                'SESSION 1' => 'SESSION 1 (0930 - 1030)',
                                                'SESSION 2' => 'SESSION 2 (1100 - 1200)',
                                                'SESSION 4' => 'SESSION 4 (1530 - 1630)',
                                                'SESSION 5' => 'SESSION 5 (1700 - 1800)',
                                            ];
                                        }
                                        // Monday to Thursday sessions (dayOfWeek = 1-4)
                                        else if ($dayOfWeek >= 1 && $dayOfWeek <= 4) {
                                            $allSessions = [
                                                'SESSION 1' => 'SESSION 1 (0930 - 1030)',
                                                'SESSION 2' => 'SESSION 2 (1100 - 1200)',
                                                'SESSION 3' => 'SESSION 3 (1400 - 1500)',
                                                'SESSION 4' => 'SESSION 4 (1530 - 1630)',
                                                'SESSION 5' => 'SESSION 5 (1700 - 1800)',
                                            ];
                                        } else {
                                            // Weekend or invalid date
                                            return ['NO_SESSIONS' => 'No sessions available on weekends'];
                                        }

                                        // Check for conflicts with existing appointments
                                        $bookedSessions = [];

                                        // Find any appointments for the selected implementer on the selected date
                                        // with status New or Completed (exclude Cancelled)
                                        $existingAppointments = \App\Models\ImplementerAppointment::where('implementer', $implementer)
                                            ->whereDate('date', $selectedDate->toDateString())
                                            ->whereIn('status', ['New', 'Completed'])
                                            ->get();

                                        // Mark sessions as booked based on time conflicts
                                        foreach ($existingAppointments as $appointment) {
                                            $appointmentStart = Carbon::parse($appointment->start_time)->format('H:i');
                                            $appointmentEnd = Carbon::parse($appointment->end_time)->format('H:i');

                                            // Check which session this appointment conflicts with
                                            if ($appointmentStart === '09:30' || ($appointmentStart < '10:30' && $appointmentEnd > '09:30')) {
                                                $bookedSessions[] = 'SESSION 1';
                                            } else if ($appointmentStart === '11:00' || ($appointmentStart < '12:00' && $appointmentEnd > '11:00')) {
                                                $bookedSessions[] = 'SESSION 2';
                                            } else if ($appointmentStart === '14:00' || ($appointmentStart < '15:00' && $appointmentEnd > '14:00')) {
                                                $bookedSessions[] = 'SESSION 3';
                                            } else if (($dayOfWeek === 5 && $appointmentStart === '15:00') ||
                                                    ($dayOfWeek !== 5 && $appointmentStart === '15:30') ||
                                                    ($appointmentStart < ($dayOfWeek === 5 ? '16:00' : '16:30') && $appointmentEnd > ($dayOfWeek === 5 ? '15:00' : '15:30'))) {
                                                $bookedSessions[] = 'SESSION 4';
                                            } else if (($dayOfWeek === 5 && $appointmentStart === '16:30') ||
                                                    ($dayOfWeek !== 5 && $appointmentStart === '17:00') ||
                                                    ($appointmentStart < ($dayOfWeek === 5 ? '17:30' : '18:00') && $appointmentEnd > ($dayOfWeek === 5 ? '16:30' : '17:00'))) {
                                                $bookedSessions[] = 'SESSION 5';
                                            }
                                        }

                                        // Filter out booked sessions
                                        $availableSessions = array_diff_key($allSessions, array_flip($bookedSessions));

                                        return $availableSessions ?: ['NO_AVAILABLE_SESSIONS' => 'No available sessions for this implementer on selected date'];
                                    })
                                    ->default(function (callable $get, ?Model $record = null) {
                                        // If editing existing record, use its session value
                                        if ($record && $record->session) {
                                            return $record->session;
                                        }

                                        // For new records, don't set a default as it depends on implementer selection
                                        return null;
                                    })
                                    ->columnSpan(2)
                                    ->required()
                                    ->reactive()
                                    ->disabled(function (callable $get) {
                                        // Disable if no implementer is selected
                                        return empty($get('implementer'));
                                    })
                                    ->placeholder(function (callable $get) {
                                        return empty($get('implementer'))
                                            ? 'Please select an implementer first'
                                            : 'Select a session';
                                    })
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        // Set the start_time and end_time based on selected session
                                        $times = [
                                            'SESSION 1' => ['09:30', '10:30'],
                                            'SESSION 2' => ['11:00', '12:00'],
                                            'SESSION 3' => ['14:00', '15:00'],
                                            'SESSION 4' => ['15:30', '16:30'],
                                            'SESSION 5' => ['17:00', '18:00'],
                                        ];

                                        // Friday has different times for sessions 4 and 5
                                        $date = $get('date');
                                        if ($date) {
                                            $carbonDate = Carbon::parse($date);
                                            if ($carbonDate->dayOfWeek === 5) { // Friday
                                                $times['SESSION 4'] = ['15:00', '16:00'];
                                                $times['SESSION 5'] = ['16:30', '17:30'];
                                            }
                                        }

                                        if (isset($times[$state])) {
                                            $set('start_time', $times[$state][0]);
                                            $set('end_time', $times[$state][1]);
                                            $set('start_time_display', $times[$state][0]);
                                            $set('end_time_display', $times[$state][1]);
                                        }
                                    }),

                                // Display-only time fields (non-editable)
                                Hidden::make('start_time_display')
                                    ->label('START TIME')
                                    ->disabled()
                                    ->default(function (callable $get) {
                                        $session = $get('session');
                                        $date = $get('date');

                                        if (!$session || !$date) {
                                            return '09:30';  // Default to SESSION 1 start time
                                        }

                                        $times = [
                                            'SESSION 1' => '09:30',
                                            'SESSION 2' => '11:00',
                                            'SESSION 3' => '14:00',
                                            'SESSION 4' => '15:30',
                                            'SESSION 5' => '17:00',
                                        ];

                                        // Adjust for Friday
                                        $selectedDate = Carbon::parse($date);
                                        if ($selectedDate->dayOfWeek === 5) { // Friday
                                            $times['SESSION 4'] = '15:00';
                                            $times['SESSION 5'] = '16:30';
                                        }

                                        return $times[$session] ?? '09:30';
                                    }),

                                Hidden::make('end_time_display')
                                    ->label('END TIME')
                                    ->disabled()
                                    ->default(function (callable $get) {
                                        $session = $get('session');
                                        $date = $get('date');

                                        if (!$session || !$date) {
                                            return '10:30';  // Default to SESSION 1 end time
                                        }

                                        $times = [
                                            'SESSION 1' => '10:30',
                                            'SESSION 2' => '12:00',
                                            'SESSION 3' => '15:00',
                                            'SESSION 4' => '16:30',
                                            'SESSION 5' => '18:00',
                                        ];

                                        // Adjust for Friday
                                        $selectedDate = Carbon::parse($date);
                                        if ($selectedDate->dayOfWeek === 5) { // Friday
                                            $times['SESSION 4'] = '16:00';
                                            $times['SESSION 5'] = '17:30';
                                        }

                                        return $times[$session] ?? '10:30';
                                    }),

                                // These are hidden fields that will store the actual time values
                                Hidden::make('start_time')
                                    ->default('09:30'),

                                Hidden::make('end_time')
                                    ->default('10:30'),
                            ]),
                            Grid::make(3)
                            ->schema([
                                Select::make('type')
                                    ->options([
                                        'KICK OFF MEETING SESSION' => 'KICK OFF MEETING SESSION',
                                    ])
                                    ->default('KICK OFF MEETING SESSION')
                                    ->required()
                                    ->label('DEMO TYPE')
                                    ->reactive(),

                                Select::make('appointment_type')
                                    ->options([
                                        'ONLINE' => 'ONLINE',
                                        'ONSITE' => 'ONSITE',
                                        'INHOUSE' => 'INHOUSE',
                                    ])
                                    ->required()
                                    ->default('ONLINE')
                                    ->label('APPOINTMENT TYPE'),

                                Select::make('implementer')
                                    ->label('IMPLEMENTER')
                                    ->options(function () {
                                        // Get technicians (role_id 9) with their names as both keys and values
                                        $technicians = \App\Models\User::whereIn('role_id', [4,5])
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function ($tech) {
                                                return [$tech->name => $tech->name];
                                            })
                                            ->toArray();
                                        return $technicians;
                                    })
                                    ->default(function ($record = null) {
                                        // First try to get from existing record if editing
                                        if ($record && $record->implementer) {
                                            return $record->implementer;
                                        }
                                        return null;
                                    })
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Select a implementer'),
                                ]),

                            TextInput::make('required_attendees')
                                ->label('REQUIRED ATTENDEES')
                                ->default(function (SoftwareHandover $record = null) {
                                    if ($record && !empty($record->implementation_pics) && is_string($record->implementation_pics)) {
                                        try {
                                            $contacts = json_decode($record->implementation_pics, true);

                                            // If it's valid JSON array, extract emails
                                            if (is_array($contacts)) {
                                                $emails = [];
                                                foreach ($contacts as $contact) {
                                                    if (!empty($contact['pic_email_impl'])) {
                                                        $emails[] = $contact['pic_email_impl'];
                                                    }
                                                }

                                                return !empty($emails) ? implode(';', $emails) : null;
                                            }
                                        } catch (\Exception $e) {
                                            \Illuminate\Support\Facades\Log::error('Error parsing implementation_pics JSON: ' . $e->getMessage());
                                        }
                                    }
                                    return null;
                                })
                                ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),

                            Textarea::make('remarks')
                                ->label('REMARKS')
                                ->rows(3)
                                ->autosize()
                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
                        ])
                        ->action(function (SoftwareHandover $record, array $data): void {
                            // Get the lead record
                            $lead = $this->getOwnerRecord();

                            if (!$lead) {
                                Notification::make()
                                    ->title('Error')
                                    ->danger()
                                    ->body('Could not find associated lead record.')
                                    ->send();
                                return;
                            }

                            // Process required attendees from form data
                            $requiredAttendeesInput = $data['required_attendees'] ?? '';
                            $attendeeEmails = [];
                            if (!empty($requiredAttendeesInput)) {
                                $attendeeEmails = array_filter(array_map('trim', explode(';', $requiredAttendeesInput)));
                            }

                            // Create a new Appointment
                            $appointment = new \App\Models\ImplementerAppointment();
                            $softwareHandoverId = \App\Models\SoftwareHandover::where('lead_id', $lead->id)
                                ->orderBy('id', 'desc')
                                ->value('id');

                            $appointment->fill([
                                'lead_id' => $lead->id,
                                'type' => $data['type'],
                                'appointment_type' => $data['appointment_type'],
                                'date' => $data['date'],
                                'start_time' => $data['start_time'],
                                'end_time' => $data['end_time'],
                                'implementer' => $data['implementer'],
                                'causer_id' => auth()->user()->id,
                                'implementer_assigned_date' => now(),
                                'remarks' => $data['remarks'] ?? null,
                                'title' => $data['type'] . ' | ' . $data['appointment_type'] . ' | TIMETEC IMPLEMENTER | ' . ($lead->companyDetail->company_name ?? 'Client'),
                                'required_attendees' => !empty($attendeeEmails) ? json_encode($attendeeEmails) : null,
                                'status' => 'New',
                                'session' => $data['session'] ?? null,
                                'software_handover_id' => $softwareHandoverId,
                            ]);

                            // Save the appointment
                            $appointment->save();

                            // Also update the software handover record to mark it as scheduled
                            $record->update([
                                'kick_off_meeting' => Carbon::parse($data['date'] . ' ' . $data['start_time'])->toDateTimeString(),
                            ]);

                            // Add required attendees if they have valid emails
                            foreach ($attendeeEmails as $email) {
                                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $email;
                                }
                            }

                            $authUser = auth()->user();
                            $senderEmail = $authUser->email;
                            $senderName = $authUser->name;

                            // Format start and end times for Teams meeting
                            $startTime = Carbon::parse($data['date'] . ' ' . $data['start_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');
                            $endTime = Carbon::parse($data['date'] . ' ' . $data['end_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');

                            // Get the implementer as the organizer
                            $implementerName = $data['implementer'] ?? null;
                            $implementerUser = User::where('name', $implementerName)->first();

                            if (!$implementerUser || !$implementerUser->email) {
                                Notification::make()
                                    ->title('Implementer Not Found')
                                    ->danger()
                                    ->body('The implementer assigned to this appointment could not be found or does not have an email address.')
                                    ->send();
                                // Continue with the process as we can still create the appointment without the Teams meeting
                            } else {
                                $organizerEmail = $implementerUser->email;

                                // Initialize Microsoft Graph service
                                $accessToken = \App\Services\MicrosoftGraphService::getAccessToken();
                                $graph = new \Microsoft\Graph\Graph();
                                $graph->setAccessToken($accessToken);

                                if ($data['appointment_type'] !== 'WEBINAR') {
                                    $meetingPayload = [
                                        'start' => [
                                            'dateTime' => $startTime,
                                            'timeZone' => 'Asia/Kuala_Lumpur'
                                        ],
                                        'end' => [
                                            'dateTime' => $endTime,
                                            'timeZone' => 'Asia/Kuala_Lumpur'
                                        ],
                                        'subject' => 'TIMETEC HR | ' . $data['appointment_type'] . ' | ' . $data['type'] . ' | ' . ($lead->companyDetail->company_name ?? 'Client'),
                                        'isOnlineMeeting' => true,
                                        'onlineMeetingProvider' => 'teamsForBusiness',
                                        'allowNewTimeProposals' => false,
                                        'responseRequested' => true,
                                        'attendees' => []
                                    ];

                                    // Add required attendees to the meeting payload
                                    if (!empty($attendeeEmails)) {
                                        foreach ($attendeeEmails as $email) {
                                            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                                $meetingPayload['attendees'][] = [
                                                    'emailAddress' => [
                                                        'address' => $email,
                                                        'name' => $email // Using email as name since we don't have names
                                                    ],
                                                    'type' => 'required'
                                                ];
                                            }
                                        }
                                    }
                                } else {
                                    $meetingPayload = [
                                        'start' => [
                                            'dateTime' => $startTime,
                                            'timeZone' => 'Asia/Kuala_Lumpur'
                                        ],
                                        'end' => [
                                            'dateTime' => $endTime,
                                            'timeZone' => 'Asia/Kuala_Lumpur'
                                        ],
                                        'subject' => 'TIMETEC HR | ' . $data['appointment_type'] . ' | ' . $data['type'] . ' | ' . ($lead->companyDetail->company_name ?? 'Client'),
                                        'isOnlineMeeting' => true,
                                        'onlineMeetingProvider' => 'teamsForBusiness',
                                    ];
                                }
                                $meetingLink = 'Will be provided separately';

                                try {
                                    // Use the correct endpoint for app-only authentication
                                    $onlineMeeting = $graph->createRequest("POST", "/users/$organizerEmail/events")
                                        ->attachBody($meetingPayload)
                                        ->setReturnType(\Microsoft\Graph\Model\Event::class)
                                        ->execute();

                                    $meetingInfo = $onlineMeeting->getOnlineMeeting();

                                    // Only try to get the join URL if meetingInfo exists
                                    if ($meetingInfo) {
                                        $meetingLink = $meetingInfo->getJoinUrl() ?? 'Will be provided separately';
                                    }

                                    Notification::make()
                                        ->title('Teams Meeting Created Successfully')
                                        ->success()
                                        ->body('The meeting has been scheduled successfully.')
                                        ->send();
                                } catch (\Exception $e) {
                                    // Keep the default value for meetingLink if an exception occurs

                                    \Illuminate\Support\Facades\Log::error('Failed to create Teams meeting: ' . $e->getMessage(), [
                                        'request' => $meetingPayload,
                                        'user' => $organizerEmail,
                                    ]);

                                    Notification::make()
                                        ->title('Failed to Create Teams Meeting')
                                        ->danger()
                                        ->body('Error: ' . $e->getMessage())
                                        ->send();
                                }
                            }

                            // Prepare email content
                            $viewName = 'emails.implementer_appointment_notification';
                            $leadowner = User::where('name', $lead->lead_owner)->first();

                            $emailContent = [
                                'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager',
                                'lead' => [
                                    'lastName' => $lead->companyDetail->name ?? $lead->name ?? 'Client',
                                    'company' => $lead->companyDetail->company_name ?? 'N/A',
                                    'implementerName' => $data['implementer'] ?? 'N/A',
                                    'implementerEmail' => $implementerUser->email ?? $senderEmail ?? 'admin.timetec.hr@timeteccloud.com', // Add this line
                                    'phone' => optional($lead->companyDetail)->contact_no ?? $lead->phone ?? 'N/A',
                                    'pic' => optional($lead->companyDetail)->name ?? $lead->name ?? 'N/A',
                                    'email' => optional($lead->companyDetail)->email ?? $lead->email ?? 'N/A',
                                    'date' => Carbon::parse($data['date'])->format('Y-m-d'),
                                    'dateDisplay' => Carbon::parse($data['date'])->format('d/m/Y'),
                                    'startTime' => Carbon::parse($data['start_time'])->format('h:i A') ?? 'N/A',
                                    'endTime' => Carbon::parse($data['end_time'])->format('h:i A') ?? 'N/A',
                                    'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                                    'session' => $data['session'] ?? 'N/A',
                                    'demo_type' => $data['type'],
                                    'appointment_type' => $data['appointment_type'],
                                    'remarks' => $data['remarks'] ?? 'N/A',
                                    'meetingLink' => $meetingLink ?? 'Will be provided separately',
                                ],
                            ];

                            $implementerName = $data['implementer'] ?? null;
                            $implementerUser = User::where('name', $implementerName)->first();

                            // Default to authenticated user if implementer not found
                            if (!$implementerUser || !$implementerUser->email) {
                                $senderEmail = $authUser->email;
                                $senderName = $authUser->name;
                            } else {
                                $senderEmail = $implementerUser->email;
                                $senderName = $implementerUser->name;
                            }

                            try {
                                // Send email with template and custom subject format
                                if (count($recipients) > 0) {
                                    \Illuminate\Support\Facades\Mail::send($viewName, ['content' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $lead, $data) {
                                        $message->from($senderEmail, $senderName)
                                            ->to($recipients)
                                            ->bcc('admin.timetec.hr@timeteccloud.com')
                                            ->subject("TIMETEC HR | ONLINE | KICK-OFF MEETING SESSION | {$lead->companyDetail->company_name}");
                                    });

                                    Notification::make()
                                        ->title('Implementer appointment notification sent')
                                        ->success()
                                        ->body('Email notification sent to administrator and required attendees')
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                // Handle email sending failure
                                \Illuminate\Support\Facades\Log::error("Email sending failed for implementer appointment: Error: {$e->getMessage()}");

                                Notification::make()
                                    ->title('Email Notification Failed')
                                    ->danger()
                                    ->body('Could not send email notification: ' . $e->getMessage())
                                    ->send();
                            }

                            // Create activity log entry
                            \App\Models\ActivityLog::create([
                                'user_id' => auth()->id(),
                                'causer_id' => auth()->id(),
                                'action' => 'Created Kick-Off Meeting',
                                'description' => "Created kick-off meeting for {$lead->companyDetail->company_name} with {$data['implementer']}",
                                'subject_type' => get_class($appointment),
                                'subject_id' => $appointment->id,
                            ]);

                            Notification::make()
                                ->title('Implementer Appointment Added Successfully')
                                ->success()
                                ->send();

                            $this->dispatch('refresh');
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
                                ->rows(4)
                                ->placeholder('Enter your remark here...')
                                ->columnSpan(2),

                            // Show existing remarks if any
                            Placeholder::make('existing_remarks')
                                ->label('Existing Remarks')
                                ->content(function (SoftwareHandover $record) {
                                    if (!$record->admin_remarks) {
                                        return 'No remarks yet.';
                                    }

                                    $remarks = json_decode($record->admin_remarks, true) ?: [];
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
                                ->visible(fn(SoftwareHandover $record) => !empty($record->admin_remarks))
                        ])
                        ->action(function (SoftwareHandover $record, array $data): void {
                            // Get existing remarks or create new array
                            $remarks = json_decode($record->admin_remarks, true) ?: [];

                            // Add new remark
                            $remarks[] = [
                                'author' => auth()->user()->name,
                                'date' => now()->format('Y-m-d H:i:s'),
                                'content' => $data['remark_content']
                            ];

                            // Update record
                            $record->update([
                                'admin_remarks' => json_encode($remarks)
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
        return view('livewire.software-handover-kick-off-reminder');
    }

    private function getOwnerRecord()
    {
        // Since this is not a RelationManager, we need to determine which lead to use
        // In the action context, we need to get it from the record being acted upon
        if (isset($this->mountedTableActionRecord)) {
            $softwareHandover = $this->mountedTableActionRecord;

            // Make sure we have a SoftwareHandover model object
            if (is_numeric($softwareHandover)) {
                // If we just have an ID, fetch the model
                $softwareHandover = SoftwareHandover::find($softwareHandover);
            }

            // Now we can safely get the lead_id from the model
            return $softwareHandover ? Lead::find($softwareHandover->lead_id) : null;
        }

        // If it's from the options() method (not in an action context)
        if (isset($this->mountedTableActionData['record'])) {
            $softwareHandoverId = $this->mountedTableActionData['record'];

            // Make sure we have a model and not just an ID
            $softwareHandover = is_numeric($softwareHandoverId)
                ? SoftwareHandover::find($softwareHandoverId)
                : $softwareHandoverId;

            return $softwareHandover && isset($softwareHandover->lead_id)
                ? Lead::find($softwareHandover->lead_id)
                : null;
        }

        return null;
    }
}
