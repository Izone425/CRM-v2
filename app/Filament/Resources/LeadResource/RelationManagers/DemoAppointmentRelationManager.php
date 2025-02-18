<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Mail\DemoNotification;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard\Step;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\ActionSize;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Event;
use Spatie\Activitylog\Traits\LogsActivity;

class DemoAppointmentRelationManager extends RelationManager
{
    protected static string $relationship = 'demoAppointment';

    protected function getTableHeading(): string
    {
        return __('Appointments');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('1s')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->headerActions($this->headerActions())
            ->columns([
                TextColumn::make('salesperson')
                    ->label('SALESPERSON')
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        // Assuming $record->salesperson contains the user ID
                        $user = User::find($record->salesperson);

                        return $user?->name ?? 'No Salesperson'; // Return the user's name or 'No Salesperson' if not found
                    }),
                TextColumn::make('type')
                    ->label('DEMO TYPE')
                    ->sortable(),
                TextColumn::make('appointment_type')
                    ->label('APPOINTMENT TYPE')
                    ->sortable(),
                TextColumn::make('date')
                    ->label('DATE & TIME')
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        if (!$record->date || !$record->start_time || !$record->end_time) {
                            return 'No Data Available'; // Handle null values
                        }

                        // Format the date
                        $date = \Carbon\Carbon::createFromFormat('Y-m-d', $record->date)->format('d M Y');

                        // Format the start and end times
                        $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $record->start_time)->format('h:i A');
                        $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $record->end_time)->format('h:i A');

                        return "{$date} | {$startTime} - {$endTime}";
                    }),
                TextColumn::make('status')
                    ->label('STATUS')
                    ->sortable()
                    ->color(fn ($state) => match ($state) {
                        'Done' => 'success',    // Green
                        'Cancelled' => 'danger', // Red
                        'New' => 'warning',  // Yellow (Optional)
                        default => 'gray',       // Default color
                    })
                    ->icon(fn ($state) => match ($state) {
                        'Done' => 'heroicon-o-check-circle',
                        'Cancelled' => 'heroicon-o-x-circle',
                        'New' => 'heroicon-o-clock', // Optional icon for pending
                        default => 'heroicon-o-question-mark-circle',
                    }),

            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\Action::make('View Appointment')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->modalHeading('Appointment Details')
                        ->modalSubmitAction(false)
                        ->form(function ($record) {
                            if (!$record) {
                                return [
                                    TextInput::make('error')->default('Appointment not found')->disabled(),
                                ];
                            }

                            return [
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('type')
                                            ->label('Demo Type')
                                            ->default(strtoupper($record->type))
                                            ->disabled(),

                                        TextInput::make('appointment_type')
                                            ->label('Appointment Type')
                                            ->default($record->appointment_type)
                                            ->disabled(),

                                        TextInput::make('salesperson')
                                            ->label('Salesperson')
                                            ->default(fn ($record) => \App\Models\User::find($record->salesperson)?->name ?? 'N/A') // Get name from User table
                                            ->disabled(),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        DatePicker::make('date')
                                            ->label('Date')
                                            ->default($record->date)
                                            ->disabled(),

                                        TimePicker::make('start_time')
                                            ->label('Start Time')
                                            ->default($record->start_time)
                                            ->disabled(),

                                        TimePicker::make('end_time')
                                            ->label('End Time')
                                            ->default($record->end_time)
                                            ->disabled(),
                                    ]),

                                Textarea::make('remarks')
                                    ->label('Remarks')
                                    ->default($record->remarks)
                                    ->autosize()
                                    ->disabled()
                                    ->reactive()
                                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

                                TextInput::make('required_attendees')
                                    ->label('Required Attendees')
                                    ->default($record->required_attendees)
                                    ->disabled(),
                            ];
                        }),

                    Tables\Actions\Action::make('demo_cancel')
                        ->visible(fn (Appointment $appointment) => $appointment->status === 'New')
                        ->label(__('Cancel Demo'))
                        ->modalHeading('Cancel Demo')
                        ->form([
                            Forms\Components\Placeholder::make('')
                                ->content(__('You are cancelling this appointment. Confirm?')),

                            Forms\Components\TextInput::make('remark')
                                ->label('Remarks')
                                ->required()
                                ->placeholder('Enter remarks here...')
                                ->maxLength(500)
                                ->reactive()
                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
                            ])
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->action(function (array $data, $record) {
                            $appointment = $record;
                            $lead = $appointment->lead;

                            // Get event details
                            $eventId = $appointment->event_id;
                            $salesperson = User::find($appointment->salesperson);

                            if (!$salesperson || !$salesperson->email) {
                                Notification::make()
                                    ->title('Salesperson Not Found')
                                    ->danger()
                                    ->body('The salesperson assigned to this appointment could not be found or does not have an email address.')
                                    ->send();
                                return;
                            }

                            $organizerEmail = $salesperson->email;

                            try {
                                if ($eventId) {
                                    $accessToken = MicrosoftGraphService::getAccessToken();
                                    $graph = new Graph();
                                    $graph->setAccessToken($accessToken);

                                    // Cancel the Teams meeting
                                    $graph->createRequest("DELETE", "/users/$organizerEmail/events/$eventId")
                                          ->execute();

                                    Notification::make()
                                        ->title('Teams Meeting Cancelled Successfully')
                                        ->warning()
                                        ->body('The meeting has been cancelled successfully.')
                                        ->send();
                                } else {
                                    Log::warning('No event ID found for appointment', [
                                        'appointment_id' => $appointment->id,
                                    ]);

                                    Notification::make()
                                        ->title('No Meeting Found')
                                        ->danger()
                                        ->body('The appointment does not have an associated Teams meeting.')
                                        ->send();
                                }
                            } catch (\Exception $e) {
                                Log::error('Failed to cancel Teams meeting: ' . $e->getMessage(), [
                                    'event_id' => $eventId,
                                    'organizer' => $organizerEmail,
                                ]);

                                Notification::make()
                                    ->title('Failed to Cancel Teams Meeting')
                                    ->danger()
                                    ->body('Error: ' . $e->getMessage())
                                    ->send();
                            }

                            // Update Lead stage and status
                            $lead->update([
                                'salesperson' => null,
                                'stage' => 'Transfer',
                                'lead_status' => 'Demo Cancelled',
                                'remark' => $data['remark'],
                                'follow_up_date' => null,
                            ]);

                            $cancelfollowUpCount = ActivityLog::where('subject_id', $lead->id)
                                    ->whereJsonContains('properties->attributes->lead_status', 'Demo Cancelled') // Filter by lead_status in properties
                                    ->count();

                            // Increment the follow-up count for the new description
                            $cancelFollowUpDescription = ($cancelfollowUpCount) . 'st Demo Cancelled Follow Up';
                            if ($cancelfollowUpCount == 2) {
                                $cancelFollowUpDescription = '2nd Demo Cancelled Follow Up';
                            } elseif ($cancelfollowUpCount == 3) {
                                $cancelFollowUpDescription = '3rd Demo Cancelled Follow Up';
                            } elseif ($cancelfollowUpCount >= 4) {
                                $cancelFollowUpDescription = $cancelfollowUpCount . 'th Demo Cancelled Follow Up';
                            }

                            // Update or create the latest activity log description
                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('created_at')
                                ->first();

                            if ($latestActivityLog) {
                                $latestActivityLog->update([
                                    'description' => 'Demo Cancelled. ' . ($cancelFollowUpDescription),
                                ]);
                            } else {
                                activity()
                                    ->causedBy(auth()->user())
                                    ->performedOn($lead)
                                    ->withProperties(['description' => $cancelFollowUpDescription]);
                            }

                            $appointment->updateQuietly([
                                'status' => 'Cancelled',
                                'remarks' => $data['remark'],
                            ]);

                            Notification::make()
                                ->title('You have cancelled a demo')
                                ->warning()
                                ->send();
                        }),
                ])->icon('heroicon-m-list-bullet')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button(),
            ])->defaultSort('date', 'desc');
    }

    public function headerActions(): array
    {
        return [
            Tables\Actions\Action::make('Add Appointment')
                ->icon('heroicon-o-pencil')
                ->modalHeading('Add Appointment')
                ->hidden(is_null($this->getOwnerRecord()->lead_owner))
                ->form([
                    Grid::make(3) // 3 columns for 3 Select fields
                    ->schema([
                        Select::make('type')
                            ->options(function () {
                                // Check if the lead has an appointment with 'new' or 'done' status
                                $leadHasNewAppointment = Appointment::where('lead_id', $this->getOwnerRecord()->id)
                                    ->whereIn('status', ['New', 'Done'])
                                    ->exists();

                                // Dynamically set options
                                $options = [
                                    'NEW DEMO' => 'NEW DEMO',
                                    'WEBINAR DEMO' => 'WEBINAR DEMO',
                                ];

                                if ($leadHasNewAppointment) {
                                    $options = [
                                        'HRMS DEMO' => 'HRMS DEMO',
                                        'HRDF DISCUSSION' => 'HRDF DISCUSSION',
                                        'SYSTEM DISCUSSION' => 'SYSTEM DISCUSSION',
                                    ];
                                }

                                return $options;
                            })
                            ->default('NEW DEMO')
                            ->required()
                            ->label('DEMO TYPE'),

                        Select::make('appointment_type')
                            ->options([
                                'ONLINE' => 'ONLINE',
                                'ONSITE' => 'ONSITE',
                            ])
                            ->required()
                            ->default('ONLINE')
                            ->label('APPOINTMENT TYPE'),

                        Select::make('salesperson')
                            ->label('SALESPERSON')
                            ->options(function (ActivityLog $activityLog) {
                                $lead = $this->ownerRecord;
                                // if ($lead->salesperson) {
                                //     $salesperson = User::where('id', $lead->salesperson)->first();
                                //     return [
                                //         $lead->salesperson => $salesperson->name,
                                //     ];
                                // }

                                if (auth()->user()->role_id == 3) {
                                    return \App\Models\User::query()
                                        ->whereIn('role_id', [2, 3])
                                        ->pluck('name', 'id')
                                        ->toArray();
                                } else {
                                    return \App\Models\User::query()
                                        ->where('role_id', 2)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                }
                            })
                            ->disableOptionWhen(function ($value, $get) {
                                $date = $get('date');
                                $startTime = $get('start_time');
                                $endTime = $get('end_time');
                                $demo_type = $get('type');

                                // If the demo type is 'WEBINAR DEMO', do not disable any options
                                if ($demo_type === 'WEBINAR DEMO') {
                                    return false; // Allow selection without restrictions
                                }

                                // if ($date && $startTime && $endTime) {
                                    // Check for overlapping appointments
                                    $hasOverlap = Appointment::where('salesperson', $value)
                                        ->where('status', 'New')
                                        ->whereDate('date', $date)
                                        ->where(function ($query) use ($startTime, $endTime) {
                                            $query->whereBetween('start_time', [$startTime, $endTime])
                                                ->orWhereBetween('end_time', [$startTime, $endTime])
                                                ->orWhere(function ($query) use ($startTime, $endTime) {
                                                    $query->where('start_time', '<', $startTime)
                                                        ->where('end_time', '>', $endTime);
                                                });
                                        })
                                        ->exists();

                                    if ($hasOverlap) {
                                        return true;
                                    }

                                    // Morning or afternoon validation
                                    // $isMorning = strtotime($startTime) < strtotime('12:00:00');

                                    // if ($isMorning) {
                                    //     $morningCount = Appointment::where('salesperson', $value)
                                    //         ->whereNot('status', 'Cancelled')
                                    //         ->whereDate('date', $date)
                                    //         ->whereTime('start_time', '<', '12:00:00')
                                    //         ->count();

                                    //     if ($morningCount >= 1) {
                                    //         return true; // Morning slot already filled
                                    //     }
                                    // } else {
                                    //     $afternoonCount = Appointment::where('salesperson', $value)
                                    //         ->whereNot('status', 'Cancelled')
                                    //         ->whereDate('date', $date)
                                    //         ->whereTime('start_time', '>=', '12:00:00')
                                    //         ->count();

                                    //     if ($afternoonCount >= 1) {
                                    //         return true; // Afternoon slot already filled
                                    //     }
                                    // }
                                // }


                                // return false;
                            })
                            ->required()
                            ->hidden(fn () => auth()->user()->role_id === 2)
                            ->placeholder('Select a salesperson'),
                        ]),
                    // Schedule
                    Forms\Components\ToggleButtons::make('mode')
                        ->label('')
                        ->options([
                            'auto' => 'Auto',
                            'custom' => 'Custom',
                        ]) // Define custom options
                        ->reactive()
                        ->inline()
                        ->grouped()
                        ->default('auto')
                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                            if ($state === 'custom') {
                                $set('date', null);
                                $set('start_time', null);
                                $set('end_time', null);
                            }else{
                                $set('date', Carbon::today()->toDateString());
                                $set('start_time', Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30))->format('H:i'));
                                $set('end_time', Carbon::parse($get('start_time'))->addHour()->format('H:i'));
                            }
                        }),

                    Forms\Components\Grid::make(3) // 3 columns for Date, Start Time, End Time
                    ->schema([
                        DatePicker::make('date')
                            ->required()
                            ->label('DATE')
                            ->default(Carbon::today()->toDateString()),

                        Forms\Components\TimePicker::make('start_time')
                            ->label('START TIME')
                            ->required()
                            ->seconds(false)
                            ->reactive()
                            ->default(function () {
                                // Get the current time and round up to the next 30-minute interval
                                $now = Carbon::now();
                                return $now->addMinutes(30 - ($now->minute % 30))->format('H:i'); // Round up
                            })
                            ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                if ($get('mode') === 'auto' && $state) {
                                    $set('end_time', Carbon::parse($state)->addHour()->format('H:i'));
                                }
                            })
                            ->datalist(function (callable $get) {
                                if ($get('mode') === 'custom') {
                                    return []; // Return an empty list to disable the datalist
                                }

                                $times = [];
                                $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30)); // Round to next 30 min
                                for ($i = 0; $i < 48; $i++) { // Show next 5 available slots
                                    $times[] = $startTime->format('H:i');
                                    $startTime->addMinutes(30); // Increment by 30 minutes
                                }
                                return $times;
                            }),

                        Forms\Components\TimePicker::make('end_time')
                            ->label('END TIME')
                            ->required()
                            ->seconds(false)
                            ->reactive()
                            ->default(function (callable $get) {
                                // Default end_time to one hour after start_time
                                $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30));
                                return $startTime->addHour()->format('H:i');
                            })
                            ->datalist(function (callable $get) {
                                if ($get('mode') === 'custom') {
                                    return []; // Return an empty list to disable the datalist
                                }

                                $times = [];
                                $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30)); // Round to next 30 min
                                for ($i = 0; $i < 48; $i++) { // Show next 5 available slots
                                    $times[] = $startTime->format('H:i');
                                    $startTime->addMinutes(30); // Increment by 30 minutes
                                }
                                return $times;
                            }),
                    ]),

                    Textarea::make('remarks')
                        ->label('REMARKS')
                        ->rows(3)
                        ->autosize()
                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

                    TextInput::make('required_attendees')
                        ->label('Required Attendees')
                        ->helperText('Separate each email and name pair with a semicolon (e.g., email1;email2;email3).'),
                        // ->rules([
                        //     'regex:/^([^;]+;[^;]+;)*([^;]+;[^;]+)$/', // Validates the email-name pairs separated by semicolons
                        // ]),
                ])
                ->action(function (Appointment $appointment, array $data) {
                    // Create a new Appointment and store the form data in the appointments table
                    $lead = $this->ownerRecord;
                    $appointment = new \App\Models\Appointment();
                    $appointment->fill([
                        'lead_id' => $lead->id,
                        'type' => $data['type'],
                        'appointment_type' => $data['appointment_type'],
                        'date' => $data['date'],
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'salesperson' => $data['salesperson'] ?? auth()->user()->id,
                        'remarks' => $data['remarks'],
                        'title' => $data['type']. ' | '. $data['appointment_type']. ' | TIMETEC HR | ' . $lead->companyDetail->company_name,
                        'required_attendees' => json_encode($data['required_attendees']), // Serialize to JSON
                        // 'optional_attendees' => json_encode($data['optional_attendees']),
                        // 'location' => $data['location'] ?? null,
                        // 'details' => $data['details'],
                        // 'status' => 'New'
                    ]);
                    $appointment->save();
                    // Retrieve the related Lead model from ActivityLog
                    $accessToken = MicrosoftGraphService::getAccessToken(); // Implement your token generation method

                    $graph = new Graph();
                    $graph->setAccessToken($accessToken);

                    // $startTime = $data['date'] . 'T' . $data['start_time'] . 'Z'; // Format as ISO 8601
                    $startTime = Carbon::parse($data['date'] . ' ' . $data['start_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');
                    // $endTime = $data['date'] . 'T' . $data['end_time'] . 'Z';
                    $endTime = Carbon::parse($data['date'] . ' ' . $data['end_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');

                    // Retrieve the organizer's email dynamically
                    $salespersonId = $appointment->salesperson; // Assuming `salesperson` holds the user ID
                    $salesperson = User::find($salespersonId); // Find the user in the User table

                    if (!$salesperson || !$salesperson->email) {
                        Notification::make()
                            ->title('Salesperson Not Found')
                            ->danger()
                            ->body('The salesperson assigned to this appointment could not be found or does not have an email address.')
                            ->send();
                        return; // Exit if no valid email is found
                    }

                    $organizerEmail = $salesperson->email; // Get the salesperson's email

                    // $requiredAttendees = is_string($data['required_attendees'])
                    //     ? json_decode($data['required_attendees'], true)
                    //     : $data['required_attendees']; // Handle already-decoded data or string

                    // $optionalAttendees = is_string($data['optional_attendees'])
                    //     ? json_decode($data['optional_attendees'], true)
                    //     : $data['optional_attendees']; // Handle already-decoded data or string

                    $meetingPayload = [
                        'start' => [
                            'dateTime' => $startTime, // ISO 8601 format: "YYYY-MM-DDTHH:mm:ss"
                            'timeZone' => 'Asia/Kuala_Lumpur'
                        ],
                        'end' => [
                            'dateTime' => $endTime, // ISO 8601 format: "YYYY-MM-DDTHH:mm:ss"
                            'timeZone' => 'Asia/Kuala_Lumpur'
                        ],
                        // 'body'=> [
                        //     'contentType'=> 'HTML',
                        //     'content'=> $data['details']
                        // ],
                        'subject' => $lead->companyDetail->company_name, // Event title
                        // 'attendees' => array_merge(
                        //     array_map(function ($attendee) {
                        //         return [
                        //             'emailAddress' => [
                        //                 'address' => $attendee['email'],
                        //                 'name' => $attendee['name'],
                        //             ],
                        //             'type' => 'Required', // Set type as Required
                        //         ];
                        //     }, $requiredAttendees ?? []),
                        //     array_map(function ($attendee) {
                        //         return [
                        //             'emailAddress' => [
                        //                 'address' => $attendee['email'],
                        //                 'name' => $attendee['name'],
                        //             ],
                        //             'type' => 'Optional', // Set type as Optional
                        //         ];
                        //     }, $optionalAttendees ?? [])
                        // ),
                        'isOnlineMeeting' => true,
                        'onlineMeetingProvider' => 'teamsForBusiness',
                    ];

                    try {
                        // Use the correct endpoint for app-only authentication
                        $onlineMeeting = $graph->createRequest("POST", "/users/$organizerEmail/events")
                            ->attachBody($meetingPayload)
                            ->setReturnType(Event::class)
                            ->execute();

                        // Update the appointment with meeting details
                        if($data['appointment_type'] == 'Online Demo'){
                            $appointment->update([
                                'location' => $onlineMeeting->getOnlineMeeting()->getJoinUrl(), // Update location with meeting join URL
                                'event_id' => $onlineMeeting->getId(),
                            ]);
                        }else{
                            $appointment->update([
                                'event_id' => $onlineMeeting->getId(),
                            ]);
                        }

                        Notification::make()
                            ->title('Teams Meeting Created Successfully')
                            ->success()
                            ->body('The meeting has been scheduled successfully.')
                            ->send();
                    } catch (\Exception $e) {
                        Log::error('Failed to create Teams meeting: ' . $e->getMessage(), [
                            'request' => $meetingPayload, // Log the request payload for debugging
                            'user' => $organizerEmail, // Log the user's email or ID
                        ]);

                        Notification::make()
                            ->title('Failed to Create Teams Meeting')
                            ->danger()
                            ->body('Error: ' . $e->getMessage())
                            ->send();
                    }

                    $salespersonUser = \App\Models\User::find($data['salesperson'] ?? auth()->user()->id);
                    $demoAppointment = $lead->demoAppointment->first();
                    $startTime = Carbon::parse($demoAppointment->start_time);
                    $endTime = Carbon::parse($demoAppointment->end_time); // Assuming you have an end_time field
                    $formattedDate = Carbon::parse($demoAppointment->date)->format('d/m/Y');
                    $contactNo = isset($lead->companyDetail->contact_no) ? $lead->companyDetail->contact_no : $lead->phone;
                    $picName = isset($lead->companyDetail->name) ? $lead->companyDetail->name : $lead->name;
                    $email = isset($lead->companyDetail->email) ? $lead->companyDetail->email : $lead->email;

                    if ($salespersonUser && filter_var($salespersonUser->email, FILTER_VALIDATE_EMAIL)) {
                        try {
                            $viewName = 'emails.demo_notification';
                            $leadowner = User::where('name', $lead->lead_owner)->first();

                            $emailContent = [
                                'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager', // Lead Owner/Manager Name
                                'lead' => [
                                    'lastName' => $lead->name ?? 'N/A', // Lead's Last Name
                                    'company' => $lead->companyDetail->company_name ?? 'N/A', // Lead's Company
                                    'salespersonName' => $salespersonUser->name ?? 'N/A',
                                    'salespersonPhone' => $salespersonUser->mobile_number ?? 'N/A',
                                    'salespersonEmail' => $salespersonUser->email ?? 'N/A',
                                    'phone' =>$contactNo ?? 'N/A',
                                    'pic' => $picName ?? 'N/A',
                                    'email' => $email ?? 'N/A',
                                    'date' => $formattedDate ?? 'N/A',
                                    'startTime' => $startTime ?? 'N/A',
                                    'endTime' => $endTime ?? 'N/A',
                                    'meetingLink' => $onlineMeeting->getOnlineMeeting()->getJoinUrl() ?? 'N/A',
                                    'position' => $leadowner->position ?? 'N/A', // position
                                    'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                                    'demo_type' => $appointment->type,
                                    'appointment_type' => $appointment->appointment_type
                                ],
                            ];

                            $email = $lead->companyDetails->email ?? $lead->email;
                            $demoAppointment = $lead->demoAppointment()->latest()->first(); // Adjust based on your relationship type

                            $requiredAttendees = $demoAppointment->required_attendees ?? null;

                            // Parse attendees' emails if not null
                            $attendeeEmails = [];
                            if (!empty($requiredAttendees)) {
                                $cleanedAttendees = str_replace('"', '', $requiredAttendees);
                                $attendeeEmails = array_filter(array_map('trim', explode(';', $cleanedAttendees))); // Ensure no empty spaces
                            }

                            // Get Salesperson Email
                            $salespersonId = $lead->salesperson;
                            $salesperson = User::find($salespersonId);
                            $salespersonEmail = $salesperson->email ?? null; // Prevent null errors

                            // Get Lead Owner Email
                            $leadownerName = $lead->lead_owner;
                            $leadowner = User::where('name', $leadownerName)->first();
                            $leadOwnerEmail = $leadowner->email ?? null; // Prevent null errors

                            // Combine all recipients
                            $allEmails = array_unique(array_merge([$email], $attendeeEmails, [$salespersonEmail, $leadOwnerEmail]));

                            // Remove empty/null values and ensure valid emails
                            $allEmails = array_filter($allEmails, function ($email) {
                                return filter_var($email, FILTER_VALIDATE_EMAIL); // Validate email format
                            });

                            // Check if we have valid recipients before sending emails
                            if (!empty($allEmails)) {
                                foreach ($allEmails as $recipient) {
                                    Mail::mailer('secondary')->to($recipient)
                                        ->send(new DemoNotification($emailContent, $viewName));
                                }
                            } else {
                                Log::error("No valid email addresses found for sending DemoNotification.");
                            }
                        } catch (\Exception $e) {
                            // Handle email sending failure
                            Log::error("Email sending failed for salesperson: " . ($data['salesperson'] ?? auth()->user()->name) . ", Error: {$e->getMessage()}");
                        }
                    }

                    $lead->update([
                        'categories' => 'Active',
                        'stage' => 'Demo',
                        'lead_status' => 'Demo-Assigned',
                        'follow_up_date' => $data['date'],
                        'demo_appointment' => $appointment->id,
                        'remark' => $data['remarks'],
                        'salesperson' => $data['salesperson'] ?? auth()->user()->id
                    ]);

                    $appointment = $lead->demoAppointment()->latest()->first(); // Assuming a relation exists
                    if ($appointment) {
                        $appointment->update([
                            'status' => 'New',
                        ]);
                    }

                    $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                            ->orderByDesc('created_at')
                            ->first();

                    if ($latestActivityLog && $latestActivityLog->description !== 'Lead assigned to Salesperson: ' .($data['salesperson'] ?? auth()->user()->name).'. RFQ only') {
                        $salespersonName = \App\Models\User::find($data['salesperson'] ?? auth()->user()->id)?->name ?? 'Unknown Salesperson';

                        $latestActivityLog->update([
                            'description' => 'Demo created. New Demo Online - ' . $data['date'] . ' - ' . $salespersonName
                        ]);
                        activity()
                            ->causedBy(auth()->user())
                            ->performedOn($lead);
                    }

                    Notification::make()
                        ->title('Demo Added Successfully')
                        ->success()
                        ->send();

                    $phoneNumber = $lead->phone; // Recipient's WhatsApp number
                    $contentTemplateSid = 'HXb472dfadcc08d3dcc012b694fff20f96'; // Your Content Template SID

                    $variables = [
                        $lead->name,
                        $lead->companyDetail->company_name,
                        $contactNo,
                        $picName,
                        $email,
                        $appointment->appointment_type,
                        "{$formattedDate} {$startTime->format('h:iA')} - {$endTime->format('h:iA')}",
                        $onlineMeeting->getOnlineMeeting()->getJoinUrl()
                    ];

                    $whatsappController = new \App\Http\Controllers\WhatsAppController();
                    $response = $whatsappController->sendWhatsAppTemplate($phoneNumber, $contentTemplateSid, $variables);

                    return $response;
                }),
        ];
    }
}
