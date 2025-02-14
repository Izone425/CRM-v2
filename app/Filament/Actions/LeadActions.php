<?php

namespace App\Filament\Actions;

use App\Classes\Encryptor;
use App\Enums\LeadCategoriesEnum;
use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Mail\DemoNotification;
use App\Mail\FollowUpNotification;
use App\Mail\SalespersonNotification;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Support\Enums\ActionSize;
use App\Models\Lead;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\InvalidLeadReason;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use Beta\Microsoft\Graph\Model\Event;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Microsoft\Graph\Graph;

class LeadActions
{
    public static function getViewAction(): Action
    {
        return Action::make('view_lead')
            ->label('View Details')
            ->icon('heroicon-o-eye')
            ->color('primary')
            ->requiresConfirmation()
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalHeading('Lead Details')
            ->modalDescription('Here are the details for this lead.')
            ->modalContent(fn (Lead $record) => view('filament.modals.lead-details', [
                'lead' => $record,
                'pending_days' => $record->pending_days, // Pass pending_days to the view
            ]))
            ->extraModalFooterActions([
                Action::make('view_lead')
                    ->label('Edit Details')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Lead Details')
                    ->modalDescription('Edit the lead details below.')
                    ->form(fn (Lead $record) => [
                        TextInput::make('company_name')
                            ->label('Company Name')
                            ->default($record->companyDetail->company_name ?? 'N/A')
                            ->extraAlpineAttributes(['@input' => ' $el.value = $el.value.toUpperCase()']),

                        TextInput::make('name')
                            ->label('PIC Name')
                            ->default($record->companyDetail->name ?? $record->companyDetail->company_name)
                            ->extraAlpineAttributes(['@input' => ' $el.value = $el.value.toUpperCase()']),

                        TextInput::make('contact_no')
                            ->label('PIC Contact No')
                            ->default($record->companyDetail->contact_no ?? $record->phone),

                        TextInput::make('email')
                            ->label('PIC Email Address')
                            ->default($record->companyDetail->email ?? $record->email),

                        Select::make('company_size')
                            ->label('Company Size')
                            ->options([
                                '1-24' => 'Small (1-24)',
                                '25-99' => 'Medium (25-99)',
                                '100-500' => 'Large (100-500)',
                                '501 and Above' => 'Enterprise (501+)',
                            ])
                            ->default($record->company_size),
                    ])
                    ->action(function (array $data, Lead $record) {
                        // Update the lead with the new values
                        $record->companyDetail->update([
                            'company_name' => $data['company_name'],
                            'name' => $data['name'],
                            'contact_no' => $data['contact_no'],
                            'email' => $data['email'],
                        ]);

                        $record->update([
                            'company_size' => $data['company_size'],
                        ]);

                        Notification::make()
                            ->title('Lead Updated Successfully')
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function getDemoViewAction(): Action
    {
        return Action::make('view_lead')
            ->label('View Details')
            ->icon('heroicon-o-eye')
            ->color('primary')
            ->requiresConfirmation()
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalHeading('Lead Details')
            ->modalDescription('Here are the details for this lead.')
            ->modalContent(fn (Appointment $record) => view('filament.modals.lead-details', [
                'lead' => $record->lead,
                'pending_days' => $record->pending_days, // Pass pending_days to the view
            ]));
    }

    public static function getAssignToMeAction(): Action
    {
        return Action::make('updateLeadOwner')
            ->label(__('Assign to Me'))
            ->requiresConfirmation()
            ->modalDescription('')
            ->form(function (Lead $record) {
                $isDuplicate = Lead::query()
                    ->where('company_name', $record->companyDetail->company_name)
                    ->orWhere('email', $record->email)
                    ->where('id', '!=', $record->id) // Exclude the current lead
                    ->exists();

                $content = $isDuplicate
                    ? '⚠️⚠️⚠️ Warning: This lead is a duplicate based on company name or email. Do you want to assign this lead to yourself?'
                    : 'Do you want to assign this lead to yourself? Make sure to confirm assignment before contacting the lead to avoid duplicate efforts by other team members.';

                return [
                    Placeholder::make('warning')
                        ->content($content)
                        ->hiddenLabel()
                        ->extraAttributes([
                            'style' => $isDuplicate ? 'color: red; font-weight: bold;' : '',
                        ]),
                ];
            })
            ->color('success')
            ->icon('heroicon-o-pencil-square')
            ->visible(fn (Lead $record) => is_null($record->lead_owner)) // Show only if lead_owner is NULL
            ->action(function (Lead $record) {
                // Update the lead owner and related fields
                $record->update([
                    'lead_owner' => auth()->user()->name,
                    'categories' => 'Active',
                    'stage' => 'Transfer',
                    'lead_status' => 'New',
                ]);

                // Update the latest activity log
                $latestActivityLog = ActivityLog::where('subject_id', $record->id)
                    ->orderByDesc('created_at')
                    ->first();

                if ($latestActivityLog && $latestActivityLog->description !== 'Lead assigned to Lead Owner: ' . auth()->user()->name) {
                    $latestActivityLog->update([
                        'description' => 'Lead assigned to Lead Owner: ' . auth()->user()->name,
                    ]);

                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($record);
                }

                Notification::make()
                    ->title('Lead Owner Assigned Successfully')
                    ->success()
                    ->send();
            });
    }

    public static function getAssignLeadAction(): Action
    {
        return Action::make('assignLead')
            ->label(__('Assign Lead To Lead Owner'))
            ->modalHeading('Confirm Lead Assignment')
            ->modalDescription('Select a lead owner to handle this lead.')
            ->form(function (Lead $record) {
                return [
                    Select::make('selected_user')
                        ->label('Assign To')
                        ->options(User::where('role_id', 1)->pluck('name', 'id'))
                        ->required(),
                    Placeholder::make('warning')
                        ->content('Make sure to confirm assignment before proceeding.'),
                ];
            })
            ->color('warning')
            ->icon('heroicon-o-receipt-refund')
            ->visible(fn () => auth()->user()->role_id == 3) // Only for users with role_id = 3
            ->action(function (Lead $record, array $data) {
                $selectedUser = User::find($data['selected_user']);

                if (!$selectedUser) {
                    Notification::make()
                        ->title('User Not Found')
                        ->danger()
                        ->send();
                    return;
                }

                // Update the lead owner
                $record->update([
                    'lead_owner' => $selectedUser->name,
                    'categories' => 'Active',
                    'stage' => 'Transfer',
                    'lead_status' => 'New',
                ]);

                // Log the activity
                ActivityLog::create([
                    'description' => 'Lead assigned to Lead Owner: ' . $selectedUser->name,
                    'subject_id' => $record->id,
                    'causer_id' => auth()->id(),
                ]);

                Notification::make()
                    ->title('Lead successfully assigned to ' . $selectedUser->name)
                    ->success()
                    ->send();
            });
    }

    public static function getAddDemoAction(): Action
    {
        return
            Action::make('add_demo')
                ->icon('heroicon-o-pencil')
                ->color('success')
                ->label('Add Demo')
                ->modalHeading('Add Demo')
                ->hidden(fn (Lead $record) => is_null($record->lead_owner)) // Use $record instead of getOwnerRecord()
                ->form(fn (?Lead $record) => $record ? [ // Ensure record exists before running form logic
                    Grid::make(3) // 3 columns for 3 Select fields
                    ->schema([
                        Select::make('type')
                        ->options(function () use ($record) {
                            // Check if the lead has an appointment with 'new' or 'done' status
                                $leadHasNewAppointment = Appointment::where('lead_id', $record->id)
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
                            ->options(function () {
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
                            ->hidden(fn () => auth()->user()->role_id === 2)
                            ->placeholder('Select a salesperson'),
                        ]),
                    // Schedule
                    ToggleButtons::make('mode')
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

                    Grid::make(3) // 3 columns for Date, Start Time, End Time
                    ->schema([
                        DatePicker::make('date')
                            ->required()
                            ->label('DATE')
                            ->default(Carbon::today()->toDateString()),

                        TimePicker::make('start_time')
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

                        TimePicker::make('end_time')
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
                        ->reactive()
                        ->afterStateUpdated(fn ($state, callable $set) => $set('remarks', strtoupper($state))),

                    TextInput::make('required_attendees')
                        ->label('Required Attendees')
                        ->helperText('Separate each email and name pair with a semicolon (e.g., email1;email2;email3).'),
                        // ->rules([
                        //     'regex:/^([^;]+;[^;]+;)*([^;]+;[^;]+)$/', // Validates the email-name pairs separated by semicolons
                        // ]),
                ] : []) // Return empty form if no record is found
                ->action(function (array $data, Lead $lead) {
                    // Create a new Appointment and store the form data in the appointments table
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
                            $salespersonEmail = $salespersonUser->email ?? null; // Prevent null errors

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

                    // $phoneNumber = $lead->phone; // Recipient's WhatsApp number
                    // $contentTemplateSid = 'HXb472dfadcc08d3dcc012b694fff20f96'; // Your Content Template SID

                    // $variables = [
                    //     $lead->name,
                    //     $lead->companyDetail->company_name,
                    //     $contactNo,
                    //     $picName,
                    //     $email,
                    //     $appointment->appointment_type,
                    //     "{$formattedDate} {$startTime->format('h:iA')} - {$endTime->format('h:iA')}",
                    //     $onlineMeeting->getOnlineMeeting()->getJoinUrl()
                    // ];

                    // $whatsappController = new \App\Http\Controllers\WhatsAppController();
                    // $response = $whatsappController->sendWhatsAppTemplate($phoneNumber, $contentTemplateSid, $variables);

                    // return $response;
                });
    }

    public static function getAddRFQ(): Action
    {
        return Action::make('addRFQ')
            ->label(__('Add RFQ'))
            ->visible(function (Lead $record) { // Change from ActivityLog to Lead
                // Decode properties once and retrieve relevant attributes
                $leadStatus = $record->lead_status;
                $category = $record->categories;
                $stage = $record->stage;

                // Define invalid lead statuses and stages
                $invalidLeadStatuses = [
                    LeadStatusEnum::RFQ_TRANSFER->value,
                    LeadStatusEnum::DEMO_CANCELLED->value,
                    LeadStatusEnum::RFQ_FOLLOW_UP->value,
                    LeadStatusEnum::PENDING_DEMO->value,
                    LeadStatusEnum::HOT->value,
                    LeadStatusEnum::WARM->value,
                    LeadStatusEnum::COLD->value,
                ];

                $invalidStages = [
                    LeadStageEnum::DEMO->value,
                    LeadStageEnum::FOLLOW_UP->value,
                ];

                return !in_array($leadStatus, $invalidLeadStatuses) &&
                    $category !== LeadCategoriesEnum::INACTIVE->value &&
                    !in_array($stage, $invalidStages);
            })
            ->form([
                Placeholder::make('')
                    ->content(__('You are marking this lead as "RFQ" and assigning it to a salesperson. Confirm?')),

                Select::make('salesperson')
                    ->label('Salesperson')
                    ->options(function () {
                        if (auth()->user()->role_id == 3) {
                            return User::query()
                                ->whereIn('role_id', [2, 3])
                                ->pluck('name', 'id')
                                ->toArray();
                        } else {
                            return User::query()
                                ->where('role_id', 2)
                                ->pluck('name', 'id')
                                ->toArray();
                        }
                    })
                    ->required()
                    ->placeholder('Select a salesperson'),

                Textarea::make('remark')
                    ->label('Remarks')
                    ->rows(4)
                    ->autosize()
                    ->required()
                    ->placeholder('Enter remarks here...')
                    ->afterStateUpdated(fn ($state, callable $set) => $set('remark', strtoupper($state))),
            ])
            ->color('success')
            ->icon('heroicon-o-pencil-square')
            ->action(function (Lead $record, array $data) { // Change from ActivityLog to Lead
                // Update the Lead model
                $record->update([
                    'stage' => 'Transfer',
                    'lead_status' => 'RFQ-Transfer',
                    'remark' => $data['remark'],
                    'salesperson' => $data['salesperson'],
                    'follow_up_date' => today(),
                    'rfq_transfer_at' => now(),
                ]);

                // Fetch the salesperson's name
                $salespersonName = User::find($data['salesperson'])?->name ?? 'Unknown Salesperson';

                $latestActivityLog = ActivityLog::where('subject_id', $record->id)
                ->orderByDesc('created_at')
                ->first();

                if ($latestActivityLog) {
                    // Fetch the salesperson's name based on $data['salesperson']
                    $salespersonName = \App\Models\User::find($data['salesperson'])?->name ?? 'Unknown Salesperson';

                    // Check if the latest activity log description needs updating
                    if ($latestActivityLog->description !== 'Lead assigned to Salesperson: ' . $salespersonName . '. RFQ only') {
                        $latestActivityLog->update([
                            'description' => 'Lead assigned to Salesperson: ' . $salespersonName . '. RFQ only', // New description
                        ]);

                        // Log the activity for auditing
                        activity()
                            ->causedBy(auth()->user())
                            ->performedOn($record);
                    }
                }

                // Fetch lead owner details
                $leadOwner = User::where('name', $record->lead_owner)->first();
                $salespersonUser = User::find($data['salesperson']);
                info($salespersonUser);
                if ($salespersonUser && filter_var($salespersonUser->email, FILTER_VALIDATE_EMAIL)) {
                    try {
                        // Get logged-in user details
                        $currentUser = auth()->user();
                        if (!$currentUser) {
                            throw new Exception('User not logged in');
                        }

                        // Set email sender details
                        $fromEmail = $currentUser->email;
                        $fromName = $currentUser->name ?? 'CRM User';

                        $emailContent = [
                            'salespersonName' => $salespersonUser->name,
                            'leadOwnerName' => $record->lead_owner ?? 'Unknown Manager',
                            'lead' => [
                                'lead_code' => isset($record->lead_code) ? 'https://crm.timeteccloud.com:8082/demo-request/' . $record->lead_code : 'N/A',
                                'lastName' => $record->name ?? 'N/A',
                                'company' => $record->companyDetail->company_name ?? 'N/A',
                                'companySize' => $record->company_size ?? 'N/A',
                                'phone' => $record->phone ?? 'N/A',
                                'email' => $record->email ?? 'N/A',
                                'country' => $record->country ?? 'N/A',
                                'products' => $record->products ?? 'N/A',
                            ],
                            'remark' => $data['remark'] ?? 'No remarks provided',
                            'formatted_products' => $record->formatted_products,
                        ];

                        // Send email notification
                        Mail::mailer('smtp')
                            ->to([$salespersonUser->email, $leadOwner->email])
                            ->send(new SalespersonNotification($emailContent, $fromEmail, $fromName, 'emails.salesperson_notification2'));

                        // Success notification
                        Notification::make()
                            ->title('RFQ Added Successfully')
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Log::error("Email sending failed for salesperson: {$data['salesperson']}, Error: {$e->getMessage()}");

                        Notification::make()
                            ->title('Error: Failed to send email')
                            ->danger()
                            ->send();
                    }
                }
            });
    }

    public static function getAddFollowUp(): Action
    {
        return Action::make('addFollowUp')
        ->label(__('Add Follow Up'))
        ->form([
            Placeholder::make('')
                ->content(__('Fill out the following section to add a follow-up for this lead.
                            Select a follow-up date if the lead requests to be contacted on a specific date.
                            Otherwise, the system will default to sending the follow-up on the next Tuesday.')),

            Textarea::make('remark')
                ->label('Remarks')
                ->rows(3)
                ->autosize()
                ->required()
                ->placeholder('Enter remarks here...')
                ->maxLength(500)
                ->afterStateUpdated(fn ($state, callable $set) => $set('remark', strtoupper($state))),

            // Forms\Components\Checkbox::make('follow_up_needed')
            //     ->label('Enable automatic follow-up (4 times)')
            //     ->default(false),

            DatePicker::make('follow_up_date')
            ->label('Next Follow Up Date')
            ->required()
            ->placeholder('Select a follow-up date')
            ->default(fn ($record) => $record->lead->follow_up_date ?? now())
            ->reactive()
            // ->minDate(fn ($record) => $record->lead->follow_up_date ? Carbon::parse($record->lead->follow_up_date)->startOfDay() : now()->startOfDay()) // Ensure it gets from DB
            ->visible(fn (Get $get) => !$get('follow_up_needed')) // Hide when follow_up_needed is checked
            ->afterStateUpdated(function (Set $set, Get $get) {
                if ($get('follow_up_needed')) {
                    $nextTuesday = Carbon::now()->next(Carbon::TUESDAY);
                    $set('follow_up_date', $nextTuesday); // Set to next Tuesday if checked
                }
            }),
        ])
        ->color('success')
        ->icon('heroicon-o-pencil-square')
        ->action(function (Lead $record, array $data) {
            // Retrieve the related Lead model from ActivityLog
            $lead = $record;

            // Check if follow_up_date exists in the $data array; if not, set it to next Tuesday
            $followUpDate = $data['follow_up_date'] ?? now()->next(Carbon::TUESDAY);
            if($lead->lead_status === 'New' || $lead->lead_status === 'Under Review'){

                $lead->update([
                    'follow_up_date' => $followUpDate,
                    'remark' => $data['remark'],
                    'follow_up_count' => $lead->follow_up_count + 1,
                    'lead_status' => 'Under Review',
                ]);

                // Increment the follow-up count for the new description
                $followUpDescription = ($lead->follow_up_count) . 'st Lead Owner Follow Up';
                $viewName = 'emails.email_blasting_1st';
                $contentTemplateSid = 'HX2d4adbe7d011693a90af7a09c866100f'; // Your Content Template SID

                if ($lead->follow_up_count == 2) {
                    $followUpDescription = '2nd Lead Owner Follow Up';
                    $viewName = 'emails.email_blasting_2nd';
                    $contentTemplateSid = 'HX72acd0ab4ffec49493288f9c0b53a17a';
                } elseif ($lead->follow_up_count == 3) {
                    $followUpDescription = '3rd Lead Owner Follow Up';
                    $viewName = 'emails.email_blasting_3rd';
                    $contentTemplateSid = 'HX9ed8a4589f03d9563e94d47c529aaa0a';
                } elseif ($lead->follow_up_count >= 4) {
                    $followUpDescription = $lead->follow_up_count . 'th Lead Owner Follow Up';
                    $viewName = 'emails.email_blasting_4th';
                    $contentTemplateSid = 'HXa18012edd80d072d54b60b93765dd3af';
                }
                // Update or create the latest activity log description
                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                    ->orderByDesc('created_at')
                    ->first();

                if ($latestActivityLog) {
                    $latestActivityLog->update([
                        'description' => $followUpDescription,
                    ]);
                } else {
                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($lead)
                        ->withProperties(['description' => $followUpDescription]);
                }

                // Send a notification
                Notification::make()
                    ->title('Follow Up Added Successfully')
                    ->success()
                    ->send();

                $leadowner = User::where('name', $lead->lead_owner)->first();
                try {
                    // Get the currently logged-in user
                    $currentUser = Auth::user();
                    if (!$currentUser) {
                        throw new Exception('User not logged in');
                    }

                    $emailContent = [
                        'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager', // Lead Owner/Manager Name
                        'lead' => [
                            'lastName' => $lead->name ?? 'N/A', // Lead's Last Name
                            'company' => $lead->companyDetail->company_name ?? 'N/A', // Lead's Company
                            'companySize' => $lead->company_size ?? 'N/A', // Company Size
                            'phone' => $lead->phone ?? 'N/A', // Lead's Phone
                            'email' => $lead->email ?? 'N/A', // Lead's Email
                            'country' => $lead->country ?? 'N/A', // Lead's Country
                            'products' => $lead->products ?? 'N/A', // Products
                            'position' => $leadowner->position ?? 'N/A', // position
                            'companyName' => $lead->companyDetail->company_name ?? 'Unknown Company',
                            'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                            // 'solutions' => $lead->solutions ?? 'N/A', // Solutions
                        ],
                    ];
                    Log::info('Company Name:', ['companyName' => $lead->companyDetail->company_name ?? 'N/A']);

                    // Mail::mailer('secondary')
                    //     ->to($lead->companyDetail->email ?? $lead->email)
                    //     ->send(new FollowUpNotification($emailContent, $viewName));
                } catch (\Exception $e) {
                    // Handle email sending failure
                    Log::error("Error: {$e->getMessage()}");
                }
            }else if($lead->lead_status === 'Transfer' || $lead->lead_status === 'Pending Demo'){

                $lead->update([
                    'follow_up_date' => $followUpDate,
                    'remark' => $data['remark'],
                    'demo_follow_up_count' => $lead->demo_follow_up_count + 1,
                ]);

                // Fetch the number of previous follow-ups for this lead
                $followUpCount = ActivityLog::where('subject_id', $lead->id)
                    ->whereJsonContains('properties->attributes->lead_status', 'Pending Demo') // Filter by lead_status in properties
                    ->count();

                $followUpCount = max(0, $followUpCount - 1); // Ensure count does not go below 0

                $viewName = 'emails.email_blasting_1st';
                $contentTemplateSid = 'HX2d4adbe7d011693a90af7a09c866100f'; // Your Content Template SID

                // Increment the follow-up count for the new description
                $followUpDescription = ($followUpCount) . 'st Salesperson Transfer Follow Up';
                if ($followUpCount == 2) {
                    $followUpDescription = '2nd Salesperson Transfer Follow Up';
                } elseif ($followUpCount == 3) {
                    $followUpDescription = '3rd Salesperson Transfer Follow Up';
                } elseif ($followUpCount >= 4) {
                    $followUpDescription = $followUpCount . 'th Salesperson Transfer Follow Up';
                }

                // Update or create the latest activity log description
                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                    ->orderByDesc('created_at')
                    ->first();

                if ($latestActivityLog) {
                    $latestActivityLog->update([
                        'description' => $followUpDescription,
                    ]);
                } else {
                    activity()
                        ->causedBy(auth()->user())
                        ->performedOn($lead)
                        ->withProperties(['description' => $followUpDescription]);
                }

                // Send a notification
                Notification::make()
                    ->title('Follow Up Added Successfully')
                    ->success()
                    ->send();

                $leadowner = User::where('name', $lead->lead_owner)->first();
                try {
                    // Get the currently logged-in user
                    $currentUser = Auth::user();
                    if (!$currentUser) {
                        throw new Exception('User not logged in');
                    }

                    $emailContent = [
                        'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager', // Lead Owner/Manager Name
                        'lead' => [
                            'lastName' => $lead->name ?? 'N/A', // Lead's Last Name
                            'company' => $lead->companyDetail->company_name ?? 'N/A', // Lead's Company
                            'companySize' => $lead->company_size ?? 'N/A', // Company Size
                            'phone' => $lead->phone ?? 'N/A', // Lead's Phone
                            'email' => $lead->email ?? 'N/A', // Lead's Email
                            'country' => $lead->country ?? 'N/A', // Lead's Country
                            'products' => $lead->products ?? 'N/A', // Products
                            'position' => $leadowner->position ?? 'N/A', // position
                            'companyName' => $lead->companyDetail->company_name ?? 'Unknown Company',
                            'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                            // 'solutions' => $lead->solutions ?? 'N/A', // Solutions
                        ],
                    ];
                    Log::info('Company Name:', ['companyName' => $lead->companyDetail->company_name ?? 'N/A']);

                    Mail::mailer('secondary')
                        ->to($lead->companyDetail->email ?? $lead->email)
                        ->send(new FollowUpNotification($emailContent, $viewName));
                } catch (\Exception $e) {
                    // Handle email sending failure
                    Log::error("Error: {$e->getMessage()}");
                }
            }else{
                // Retrieve the related Lead model from ActivityLog
                $lead = $record; // Assuming the 'activityLogs' relation in Lead is named 'lead'
                // Update the Lead model
                $lead->update([
                    'lead_status' => 'Demo Cancelled',
                    'remark' => $data['remark'],
                    'follow_up_date' => $followUpDate,
                    'follow_up_needed' => $data['follow_up_needed'] ?? false,
                    'follow_up_count' => $lead->demo_follow_up_count + 1,
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

                Notification::make()
                    ->title('You had follow up a cancelled demo')
                    ->success()
                    ->send();
            }
        });
    }

    public static function getAddAutomation(): Action
    {
        return Action::make('addAutomation')
        ->label(__('Add Automation'))
        ->color('primary')
        ->icon('heroicon-o-cog-8-tooth')
        ->form([
            Placeholder::make('confirmation')
                ->label('Are you sure you want to start the automation to follow up the lead by sending automation email to lead?'),
        ])
        ->modalHeading('Confirm Automation Action')
        ->modalSubmitActionLabel('Confirm')
        ->modalCancelActionLabel('Cancel')
        ->action(function (Lead $record, array $data) {
            $lead = $record;

            $lead->update([
                'follow_up_count' => 1,
                'follow_up_needed' => 1,
                'lead_status' => 'Under Review',
                'remark' => null,
                'follow_up_date' => null
            ]);
            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
            ->orderByDesc('created_at')
            ->first();

            if ($latestActivityLog) {
                $latestActivityLog->update([
                    'description' => 'Automation Enabled',
                ]);
            }
            $viewName = 'emails.email_blasting_1st';
            $followUpDescription = '1st Automation Follow Up';
            try {
                $emailContent = [
                    'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager', // Lead Owner/Manager Name
                    'lead' => [
                        'lastName' => $lead->name ?? 'N/A', // Lead's Last Name
                        'company' => $lead->companyDetail->company_name ?? 'N/A', // Lead's Company
                        'companySize' => $lead->company_size ?? 'N/A', // Company Size
                        'phone' => $lead->phone ?? 'N/A', // Lead's Phone
                        'email' => $lead->email ?? 'N/A', // Lead's Email
                        'country' => $lead->country ?? 'N/A', // Lead's Country
                        'products' => $lead->products ?? 'N/A', // Products
                        'position' => $leadowner->position ?? 'N/A', // position
                        'companyName' => $lead->companyDetail->company_name ?? 'Unknown Company',
                        'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                        // 'solutions' => $lead->solutions ?? 'N/A', // Solutions
                    ],
                ];

                Mail::mailer('secondary')
                    ->to($lead->companyDetail->email ?? $lead->email)
                    ->send(new FollowUpNotification($emailContent, $viewName));
            } catch (\Exception $e) {
                // Handle email sending failure
                Log::error("Error: {$e->getMessage()}");
            }
            $lead->updateQuietly([
                'follow_up_date' => now()->next('Tuesday'),
            ]);
            ActivityLog::create([
                'description' => $followUpDescription,
                'subject_id' => $lead->id,
                'causer_id' => auth()->id(),
            ]);
            Notification::make()
                ->title('Automation Applied')
                ->success()
                ->body('Will auto send email to lead every Tuesday 10am in 3 times')
                ->send();
        });
    }

    public static function getArchiveAction(): Action
    {
        return Action::make('archive')
        ->label(__('Archive'))
        ->modalHeading('Mark Lead as Inactive')
        ->color('warning')
        ->icon('heroicon-o-pencil-square')
        ->form([
            Placeholder::make('')
                ->content(__('Please select the reason to mark this lead as inactive and add any relevant remarks.')),

                Select::make('status')
                ->label('INACTIVE STATUS')
                ->options([
                    'On Hold' => 'On Hold',
                    'Junk' => 'Junk',
                    'Lost' => 'Lost',
                ])
                ->default('On Hold')
                ->required()
                ->reactive(), // Make status field reactive

            Select::make('reason')
                ->label('Select a Reason')
                ->options(fn (callable $get) =>
                    InvalidLeadReason::where('lead_stage', $get('status')) // Filter based on selected status
                        ->pluck('reason', 'id')
                        ->toArray()
                )
                ->required()
                ->reactive() // Make reason field update dynamically
                ->createOptionForm([
                    Select::make('lead_stage')
                        ->options([
                            'On Hold' => 'On Hold',
                            'Junk' => 'Junk',
                            'Lost' => 'Lost',
                        ])
                        ->default(fn (callable $get) => $get('status')) // Default lead_stage based on selected status
                        ->required(),
                    TextInput::make('reason')
                        ->label('New Reason')
                        ->required(),
                ])
                ->createOptionUsing(function (array $data) {
                    $newReason = InvalidLeadReason::create([
                        'lead_stage' => $data['lead_stage'],
                        'reason' => $data['reason'],
                    ]);

                    return $newReason->id; // Return the newly created reason ID
                }),

            TextInput::make('remark')
                ->label('Remarks')
                ->required()
                ->placeholder('Enter remarks here...')
                ->maxLength(500),
        ])
        ->action(function (Lead $record, array $data) {
            $statusLabels = [
                'on_hold' => 'On Hold',
                'junk' => 'Junk',
                'lost' => 'Lost',
            ];

            $statusLabel = $statusLabels[$data['status']] ?? $data['status'];

            $lead = $record;

            $lead->update([
                'categories' => 'Inactive',
                'lead_status' => $statusLabel,
                'remark' => $data['remark'],
                'stage' => null,
                'follow_up_date' => null,
            ]);

            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                ->orderByDesc('created_at')
                ->first();
            $reasonText = InvalidLeadReason::find($data['reason'])?->reason ?? 'Unknown Reason';

            if ($latestActivityLog) {
                $latestActivityLog->update([
                    'description' => 'Marked as ' . $statusLabel . ': ' . $reasonText, // New description
                ]);

                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($lead)
                    ->log('Lead marked as inactive.');
            }

            Notification::make()
                ->title('Lead Archived')
                ->success()
                ->body('You have successfully marked the lead as inactive.')
                ->send();
        });
    }

    public static function getViewRemark(){
        return Action::make('view_remark')
            ->label('View Remark')
            ->icon('heroicon-o-eye')
            ->modalHeading('Lead Remark')
            ->requiresConfirmation()
            ->modalSubmitAction(false)
            ->modalCancelAction(false)
            ->modalHeading('Lead Remarks')
            ->modalDescription('Here are the remark for this lead.')
            ->modalContent(function (Lead $record) {
                // Extract the remark, fallback to '-'
                $remark = $record->remark;

                // Preserve line breaks and return as HTML-safe string
                return new HtmlString(nl2br(e($remark)));
            })
            ->color('primary');
    }

    public static function getTransferCallAttempt()
    {
        return Action::make('transfer_call_attempt')
            ->label('Transfer to Call Attempt')
            ->requiresConfirmation()
            ->icon('heroicon-o-paper-airplane')
            ->modalHeading('Transfer to Call Attempt')
            ->modalDescription('Do you want to transfer this lead to Call Attempt Section? Make sure you have contact the lead before you transfer')
            ->color('primary')
            ->action(function (Lead $record) {
                // Increment the call attempt count
                $record->increment('call_attempt');
                $record->update(['done_call'=>1]);

                // Display a success notification
                Notification::make()
                    ->title('Call Attempt Recorded')
                    ->success()
                    ->body('The call attempt count has been increased.')
                    ->send();
            });
    }

    public static function getLeadDetailAction(): Action
    {
        return Action::make('view_lead_detail')
            ->label('Lead Detail')
            ->icon('heroicon-o-arrow-top-right-on-square')
            ->color('warning') // Orange color
            ->url(fn (Lead $record) => url('admin/leads/' . Encryptor::encrypt($record->id)))
            ->openUrlInNewTab(); // Opens in a new tab
    }
}
