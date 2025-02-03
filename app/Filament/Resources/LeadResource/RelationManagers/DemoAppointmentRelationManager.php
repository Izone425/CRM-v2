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
            ->poll('5s')
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
                TextColumn::make('appointment_type')
                    ->label('TYPE')
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
                TextColumn::make('location')
                    ->label('LOCATION')
                    ->copyable()
                    ->limit(40),
                TextColumn::make('status')
                    ->label('STATUS')
                    ->alignCenter()
                    ->extraAttributes(function ($record) {
                        // Define the styles dynamically based on the status
                        $styles = [
                            'Done' => 'background-color: #00ff3e; color: white; border-radius: 25px; width: 80%; height: 27px; text-align: center;',
                            'New' => 'background-color: #FFA500; color: white; border-radius: 25px; width: 80%; height: 27px; text-align: center;',
                            'Cancelled' => 'background-color: #E5E4E2; color: black; border-radius: 25px; width: 80%; height: 27px; text-align: center;',
                        ];

                        // Apply the style based on the status
                        $status = $record->status ?? 'Unknown';
                        $style = $styles[$status] ?? 'background-color: #d3d3d3; color: black; border-radius: 25px; width: 60%; height: 27px; text-align: center;';

                        return [
                            'style' => $style,
                        ];
                    }),
                TextColumn::make('remarks')
                    ->label('REMARKS')
                    ->wrap(),
            ])
            ->actions([
                Tables\Actions\Action::make('Edit')
                    ->label('Edit Demo')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->size(ActionSize::Small)
                    ->button()
                    ->visible(function (Appointment $appointment) {
                        return $appointment->status === 'New';
                    })
                    // ->color('white')
                    // ->extraAttributes([
                    //     'style' => 'background-color: #431fa1; border-radius: 50%; padding: 5px; display: inline-flex; align-items: center; justify-content: center; width: 32px; height: 32px;',
                    // ])
                    ->modalHeading('Edit on Demo Appointment')
                    ->form([
                        // Appointment Details
                        Select::make('type')
                            ->options(function () {
                                // Check if the lead has an appointment with 'new' or 'done' status
                                $leadHasNewAppointment = Appointment::where('lead_id', $this->getOwnerRecord()->id)
                                    ->whereIn('status', ['New', 'Done'])
                                    ->exists();

                                // Dynamically set options
                                $options = [
                                    'New Private Demo' => 'New Private Demo',
                                    'New Webinar Demo' => 'New Webinar Demo',
                                ];

                                if ($leadHasNewAppointment) {
                                    $options = [
                                        'Second Demo' => 'Second Demo',
                                        'HRDF Discussion' => 'HRDF Discussion',
                                        'System Discussion' => 'System Discussion',
                                    ];
                                }

                                return $options;
                            })
                            ->required()
                            ->label('DEMO TYPE'),

                        Select::make('appointment_type')
                            ->options([
                                'Onsite Demo' => 'Onsite Demo',
                                'Online Demo' => 'Online Demo',
                            ])
                            ->required()
                            ->label('APPOINTMENT TYPE'),

                        // Schedule
                        Forms\Components\ToggleButtons::make('mode')
                            ->label('')
                            ->options([
                                'auto' => 'Auto',
                                'custom' => 'Custom',
                            ]) // Define custom options
                            ->reactive() // Make it reactive to trigger changes
                            ->inline()
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

                        DatePicker::make('date')
                            ->required()
                            ->label('DATE')
                            ->default(Carbon::today()->toDateString()),

                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\TimePicker::make('start_time')
                                    ->label('START TIME')
                                    ->required()
                                    ->seconds(false)
                                    ->columnSpan(1)
                                    ->reactive()
                                    ->default(function () {
                                        // Get the current time and round up to the next 30-minute interval
                                        $now = Carbon::now();
                                        $roundedTime = $now->addMinutes(30 - ($now->minute % 30))->format('H:i'); // Round up
                                        return $roundedTime;
                                    })
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($get('mode') === 'auto' && $state) {
                                            $set('end_time', Carbon::parse($state)->addHour()->format('H:i'));
                                        }
                                    })
                                    ->datalist(function (callable $get) {
                                        if ($get('mode') === 'auto') {
                                            $times = [];
                                            $startTime = Carbon::createFromTimeString('00:00'); // Start of the day
                                            $endTime = Carbon::createFromTimeString('23:30');  // End of the day
                                            while ($startTime->lte($endTime)) {
                                                $times[] = $startTime->format('H:i'); // Format as HH:mm
                                                $startTime->addMinutes(30); // Increment by 30 minutes
                                            }
                                            return $times;
                                        }
                                    }),

                                Forms\Components\TimePicker::make('end_time')
                                    ->label('END TIME')
                                    ->required()
                                    ->seconds(false)
                                    ->columnSpan(1)
                                    ->reactive()
                                    ->default(function (callable $get) {
                                        // Default end_time to one hour after start_time
                                        $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30));
                                        return $startTime->addHour()->format('H:i');
                                    })
                                    ->datalist(function (callable $get) {
                                        if ($get('mode') === 'auto') {
                                            $times = [];
                                            $startTime = Carbon::createFromTimeString('00:00'); // Start of the day
                                            $endTime = Carbon::createFromTimeString('23:30');  // End of the day
                                            while ($startTime->lte($endTime)) {
                                                $times[] = $startTime->format('H:i'); // Format as HH:mm
                                                $startTime->addMinutes(30); // Increment by 30 minutes
                                            }
                                            return $times;
                                        }
                                    }),
                            ])
                            ->columns(2),

                        // Salesperson
                        Select::make('salesperson')
                            ->label('SALESPERSON')
                            ->options(function (ActivityLog $activityLog) {
                                $lead = $this->ownerRecord;
                                if ($lead->salesperson) {
                                    $salesperson = User::where('id', $lead->salesperson)->first();
                                    return [
                                        $lead->salesperson => $salesperson->name,
                                    ];
                                }

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
                            ->required()
                            ->placeholder('Select a salesperson'),

                        Textarea::make('remarks')
                            ->label('REMARKS'),

                        // Additional Information
                        TextInput::make('title')
                            ->required()
                            ->label('TITLE'),

                        TextInput::make('required_attendees')
                            ->label('Required Attendees')
                            ->required()
                            ->helperText('Separate each email and name pair with a semicolon (e.g., email1;name1;email2;name2).')
                            ->rules([
                                'regex:/^([^;]+;[^;]+;)*([^;]+;[^;]+)$/', // Validates the email-name pairs separated by semicolons
                            ]),
                    ])
                    ->action(function (array $data, $record) {
                        // Update the appointment record in the database
                        $record->update([
                            'type' => $data['type'] ?? $record->type,
                            'appointment_type' => $data['appointment_type'] ?? $record->appointment_type,
                            'date' => $data['date'] ?? $record->date,
                            'start_time' => $data['start_time'] ?? $record->start_time,
                            'end_time' => $data['end_time'] ?? $record->end_time,
                            'salesperson' => $data['salesperson'] ?? $record->salesperson,
                            'remarks' => $data['remarks'] ?? $record->remarks,
                            'title' => $data['title'] ?? $record->title,
                            'required_attendees' => json_encode($data['required_attendees']) ?? $record->required_attendees,
                            // 'optional_attendees' => json_encode($data['optional_attendees']) ?? $record->optional_attendees,
                            // 'location' => $data['location'] ?? $record->location,
                            // 'details' => $data['details'] ?? $record->details,
                        ]);

                        $record->lead->withoutEvents(function () use ($record, $data) {
                            $record->lead->update([
                                'remarks' => $data['remarks'] ?? $record->remarks,
                                'follow_up_date' => $data['follow_up_date'] ?? $record->date,
                            ]);
                        });

                        $appointment = $record->latest('created_at')->first();

                        $accessToken = MicrosoftGraphService::getAccessToken(); // Implement your token generation method

                        $graph = new Graph();
                        $graph->setAccessToken($accessToken);

                        $appointment = $record->latest('created_at')->first();
                        $eventId = $appointment->event_id;

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
                            'subject' => $data['title'], // Event title
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
                            $onlineMeeting = $graph->createRequest("PATCH", "/users/$organizerEmail/events/$eventId")
                                ->attachBody($meetingPayload)
                                ->setReturnType(Event::class)
                                ->execute();

                            // Update the appointment with meeting details
                            if($data['appointment_type'] == 'Online Demo'){
                                $appointment->update([
                                    'event_id' => $onlineMeeting->getId(),
                                ]);
                            }else{
                                $appointment->update([
                                    'event_id' => $onlineMeeting->getId(),
                                ]);
                            }

                            Notification::make()
                                ->title('Teams Meeting Updated Successfully')
                                ->success()
                                ->body('The meeting has been updated successfully.')
                                ->send();
                        } catch (\Exception $e) {
                            Log::error('Failed to update Teams meeting: ' . $e->getMessage(), [
                                'request' => $meetingPayload, // Log the request payload for debugging
                                'user' => $organizerEmail, // Log the user's email or ID
                            ]);

                            Notification::make()
                                ->title('Failed to Create Teams Meeting')
                                ->danger()
                                ->body('Error: ' . $e->getMessage())
                                ->send();
                        }

                        $lead = $record->lead;
                        $leadFollowUpDate = $lead->follow_up_date;
                        $formattedDate = Carbon::parse($leadFollowUpDate)->toDateString();

                        ActivityLog::create([
                            'subject_id' => $lead->id, // Associate the log with the lead
                            'description' => 'Demo details updated. Please check the latest demo details',
                            'causer_id' => auth()->id(), // Log the current user's ID
                            'causer_type' => get_class(auth()->user()), // Log the user's model class
                            'properties' => json_encode([ // Serialize properties to JSON
                                'attributes' => [ // Store as a JSON array under "attributes"
                                    'lead_status' => $lead->lead_status,
                                    'stage' => $lead->stage,
                                    'remark' => $lead->remark,
                                    'follow_up_date' => $formattedDate,
                                ],
                            ]),
                        ]);

                        Notification::make()
                            ->title('Appointment updated successfully!')
                            ->success()
                            ->send();
                    }),
                    Tables\Actions\Action::make('demo_cancel')
                    ->visible(function (Appointment $appointment) {
                        return $appointment->status === 'New';
                    })
                    ->label(__('Cancel Demo'))
                    ->modalHeading('Cancel Demo')
                    ->form([
                        Forms\Components\Placeholder::make('')
                        ->content(__('You are cancelling this appointment. Confirm?')),

                        Forms\Components\TextInput::make('remark')
                        ->label('Remarks')
                        ->required()
                        ->placeholder('Enter remarks here...')
                        ->maxLength(500),
                    ])
                    ->color('danger')
                    ->icon('heroicon-o-x-circle')
                    ->size(ActionSize::Small)
                    ->button()
                    ->action(function (array $data, $record) {
                        // Retrieve the specific appointment related to the record
                        $appointment = $record; // Assuming $record is the current appointment being acted upon

                        // Retrieve the related Lead model from the appointment
                        $lead = $appointment->lead;

                        // Get the event details from the appointment
                        $eventId = $appointment->event_id;
                        $salespersonId = $appointment->salesperson;
                        $salesperson = User::find($salespersonId);

                        if (!$salesperson || !$salesperson->email) {
                            Notification::make()
                                ->title('Salesperson Not Found')
                                ->danger()
                                ->body('The salesperson assigned to this appointment could not be found or does not have an email address.')
                                ->send();
                            return; // Exit if no valid email is found
                        }

                        $organizerEmail = $salesperson->email;

                        try {
                            if ($eventId) {
                                $accessToken = MicrosoftGraphService::getAccessToken();

                                $graph = new Graph();
                                $graph->setAccessToken($accessToken);

                                // Cancel the Teams meeting for this specific event
                                $graph->createRequest("DELETE", "/users/$organizerEmail/events/$eventId")
                                      ->execute();

                                Log::info('Teams meeting cancelled successfully', [
                                    'event_id' => $eventId,
                                    'organizer' => $organizerEmail,
                                ]);

                                // Update this specific appointment's status
                                $appointment->update([
                                    'status' => 'Cancelled',
                                ]);

                                Notification::make()
                                    ->title('Teams Meeting Cancelled Successfully')
                                    ->warning()
                                    ->body('The meeting has been cancelled successfully.')
                                    ->send();
                            } else {
                                // Log missing event ID for this specific appointment
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

                        // Update the Lead stage and status
                        $lead->update([
                            'stage' => 'Transfer',
                            'lead_status' => 'Demo Cancelled',
                            'remark' => $data['remark'],
                            'follow_up_date' => null,
                        ]);

                        // Handle activity logs for this cancellation
                        $cancelfollowUpCount = ActivityLog::where('subject_id', $lead->id)
                            ->whereJsonContains('properties->attributes->lead_status', 'Demo Cancelled')
                            ->count();

                        $cancelFollowUpDescription = ($cancelfollowUpCount) . 'st Demo Cancelled Follow Up';
                        if ($cancelfollowUpCount == 2) {
                            $cancelFollowUpDescription = '2nd Demo Cancelled Follow Up';
                        } elseif ($cancelfollowUpCount == 3) {
                            $cancelFollowUpDescription = '3rd Demo Cancelled Follow Up';
                        } elseif ($cancelfollowUpCount >= 4) {
                            $cancelFollowUpDescription = $cancelfollowUpCount . 'th Demo Cancelled Follow Up';
                        }

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

                        // Update the status of this specific appointment (if the demoAppointment relation is relevant)
                        $appointment->update([
                            'status' => 'Cancelled', // Update status to 'Cancelled'
                        ]);

                        Notification::make()
                            ->title('You have cancelled a demo')
                            ->warning()
                            ->send();
                    })
            ])
            ->defaultSort('date', 'desc');
    }

    public function headerActions(): array
    {
        return [
            Tables\Actions\Action::make('Add Appointment')
                ->icon('heroicon-o-pencil')
                ->modalHeading('Add Appointment')
                ->hidden(is_null($this->getOwnerRecord()->lead_owner))
                ->form([
                    // Appointment Details
                    Select::make('type')
                        ->options(function () {
                            // Check if the lead has an appointment with 'new' or 'done' status
                            $leadHasNewAppointment = Appointment::where('lead_id', $this->getOwnerRecord()->id)
                                ->whereIn('status', ['New', 'Done'])
                                ->exists();

                            // Dynamically set options
                            $options = [
                                'New Private Demo' => 'New Private Demo',
                                'New Webinar Demo' => 'New Webinar Demo',
                            ];

                            if ($leadHasNewAppointment) {
                                $options = [
                                    'Second Demo' => 'Second Demo',
                                    'HRDF Discussion' => 'HRDF Discussion',
                                    'System Discussion' => 'System Discussion',
                                ];
                            }

                            return $options;
                        })
                        ->required()
                        ->label('DEMO TYPE'),

                    Select::make('appointment_type')
                        ->options([
                            'Onsite Demo' => 'Onsite Demo',
                            'Online Demo' => 'Online Demo',
                        ])
                        ->required()
                        ->label('APPOINTMENT TYPE'),

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

                    DatePicker::make('date')
                        ->required()
                        ->label('DATE')
                        ->default(Carbon::today()->toDateString()),

                    Forms\Components\Grid::make()
                        ->schema([
                            Forms\Components\TimePicker::make('start_time')
                                ->label('START TIME')
                                ->required()
                                ->seconds(false)
                                ->columnSpan(1)
                                ->reactive()
                                ->default(function () {
                                    // Get the current time and round up to the next 30-minute interval
                                    $now = Carbon::now();
                                    $roundedTime = $now->addMinutes(30 - ($now->minute % 30))->format('H:i'); // Round up
                                    return $roundedTime;
                                })
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    if ($get('mode') === 'auto' && $state) {
                                        $set('end_time', Carbon::parse($state)->addHour()->format('H:i'));
                                    }
                                })
                                ->datalist(function (callable $get) {
                                    if ($get('mode') === 'auto') {
                                        $times = [];
                                        $startTime = Carbon::createFromTimeString('00:00'); // Start of the day
                                        $endTime = Carbon::createFromTimeString('23:30');  // End of the day
                                        while ($startTime->lte($endTime)) {
                                            $times[] = $startTime->format('H:i'); // Format as HH:mm
                                            $startTime->addMinutes(30); // Increment by 30 minutes
                                        }
                                        return $times;
                                    }
                                }),

                            Forms\Components\TimePicker::make('end_time')
                                ->label('END TIME')
                                ->required()
                                ->seconds(false)
                                ->columnSpan(1)
                                ->reactive()
                                ->default(function (callable $get) {
                                    // Default end_time to one hour after start_time
                                    $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30));
                                    return $startTime->addHour()->format('H:i');
                                })
                                ->datalist(function (callable $get) {
                                    if ($get('mode') === 'auto') {
                                        $times = [];
                                        $startTime = Carbon::createFromTimeString('00:00'); // Start of the day
                                        $endTime = Carbon::createFromTimeString('23:30');  // End of the day
                                        while ($startTime->lte($endTime)) {
                                            $times[] = $startTime->format('H:i'); // Format as HH:mm
                                            $startTime->addMinutes(30); // Increment by 30 minutes
                                        }
                                        return $times;
                                    }
                                }),
                        ])
                        ->columns(2),

                    // Salesperson
                    Select::make('salesperson')
                        ->label('SALESPERSON')
                        ->options(function (ActivityLog $activityLog) {
                            $lead = $this->ownerRecord;
                            if ($lead->salesperson) {
                                $salesperson = User::where('id', $lead->salesperson)->first();
                                return [
                                    $lead->salesperson => $salesperson->name,
                                ];
                            }

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

                            if ($date && $startTime && $endTime) {
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
                                $isMorning = strtotime($startTime) < strtotime('12:00:00');

                                if ($isMorning) {
                                    $morningCount = Appointment::where('salesperson', $value)
                                        ->whereNot('status', 'Cancelled')
                                        ->whereDate('date', $date)
                                        ->whereTime('start_time', '<', '12:00:00')
                                        ->count();

                                    if ($morningCount >= 1) {
                                        return true; // Morning slot already filled
                                    }
                                } else {
                                    $afternoonCount = Appointment::where('salesperson', $value)
                                        ->whereNot('status', 'Cancelled')
                                        ->whereDate('date', $date)
                                        ->whereTime('start_time', '>=', '12:00:00')
                                        ->count();

                                    if ($afternoonCount >= 1) {
                                        return true; // Afternoon slot already filled
                                    }
                                }
                            }

                            return false;
                        })
                        ->required()
                        ->placeholder('Select a salesperson'),

                    Textarea::make('remarks')
                        ->label('REMARKS'),

                    // Additional Information
                    TextInput::make('title')
                        ->required()
                        ->label('TITLE'),

                    TextInput::make('required_attendees')
                        ->label('Required Attendees')
                        ->required()
                        ->helperText('Separate each email and name pair with a semicolon (e.g., email1;email2;email3).')
                        ->rules([
                            'regex:/^([^;]+;[^;]+;)*([^;]+;[^;]+)$/', // Validates the email-name pairs separated by semicolons
                        ]),
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
                        'salesperson' => $data['salesperson'],
                        'remarks' => $data['remarks'],
                        'title' => $data['title'],
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
                        'subject' => $data['title'], // Event title
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

                    $salespersonUser = \App\Models\User::find($data['salesperson']);
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
                                    'department' => $leadowner->department ?? 'N/A', // department
                                    'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                                    'demo_type' => $appointment->appointment_type
                                ],
                            ];

                            $email = $lead->companyDetails->email ?? $lead->email;
                            $demoAppointment = $lead->demoAppointment()->latest()->first(); // Adjust based on your relationship type

                            // Collect required attendees' emails
                            $requiredAttendees = $demoAppointment->required_attendees ?? null;

                            // Parse attendees' emails if not null
                            $attendeeEmails = [];
                            if (!empty($requiredAttendees)) {
                                $cleanedAttendees = str_replace('"', '', $requiredAttendees);
                                $attendeeEmails = explode(';', $cleanedAttendees);
                                $attendeeEmails = array_filter($attendeeEmails);
                            }
                            // Combine primary email and attendee emails
                            $allEmails = array_unique(array_merge([$email], $attendeeEmails));

                            // Send emails to all recipients
                            foreach ($allEmails as $recipient) {
                                Mail::mailer('secondary')->to($recipient)
                                    ->send(new DemoNotification($emailContent, $viewName));
                            }
                        } catch (\Exception $e) {
                            // Handle email sending failure
                            Log::error("Email sending failed for salesperson: {$data['salesperson']}, Error: {$e->getMessage()}");
                        }
                    }

                    $lead->update([
                        'categories' => 'Active',
                        'stage' => 'Demo',
                        'lead_status' => 'Demo-Assigned',
                        'follow_up_date' => $data['date'],
                        'demo_appointment' => $appointment->id,
                        'remark' => $data['remarks'],
                        'salesperson' => $data['salesperson']
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

                    if ($latestActivityLog && $latestActivityLog->description !== 'Lead assigned to Salesperson: ' .$data['salesperson'].'. RFQ only') {
                        $salespersonName = \App\Models\User::find($data['salesperson'])?->name ?? 'Unknown Salesperson';

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
