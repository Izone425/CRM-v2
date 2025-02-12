<?php
namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Classes\Encryptor;
use App\Enums\LeadCategoriesEnum;
use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\QuotationStatusEnum;
use App\Mail\DemoNotification;
use App\Mail\FollowUpNotification;
use App\Mail\SalespersonNotification;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\InvalidLeadReason;
use App\Models\Lead;
use App\Models\LeadSource;
use App\Models\Quotation;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use App\Services\QuotationService;
use Carbon\Carbon;
use Exception;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Livewire\Component;
use Twilio\Rest\Client;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model as MicrosoftGraph;
use Microsoft\Graph\Model\Event;

class ActivityLogRelationManager extends RelationManager
{
    public $companyName;
    public $lead_status;
    public $totalnum;
    public $categories;
    public $lead;
    public $stage;

    protected static string $relationship = 'activityLogs';

    protected $activityLog;

    protected $listeners = ['setActiveTab'];

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function mount(): void
    {
        // Fetch the ActivityLog data using the parent record
        $this->activityLog = ActivityLog::where('subject_id', $this->getOwnerRecord()->id)->latest()->first();

        if ($this->activityLog && $this->activityLog->lead) {
            $lead = $this->activityLog->lead; // Access related Lead data

            $companyDetail = $lead->companyDetail;

            $this->companyName = $companyDetail->company_name ?? 'Unknown Company';
            $this->lead_status = $lead->lead_status ?? 'Unknown status';
            $this->categories = $lead->categories ?? 'New';
            $this->stage = $lead->stage ?? 'New';
        } else {
            $this->companyName = 'Unknown Company';
            $this->lead_status = 'Unknown status';
            $this->categories = 'New';
            $this->stage = 'New';
        }
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function table(Table $table): Table
    {
        $this->totalnum = ActivityLog::where('subject_id', $this->getOwnerRecord()->id)->count();

        return $table
            ->poll('5s')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->recordTitleAttribute('subject_id')
            ->columns([
                // Define columns here
            ])
            ->modifyQueryUsing(function ($query) {
                return $query->orderBy('created_at', 'desc'); // Sort by created_at in descending order
            })
            ->columns([
                TextColumn::make('index')
                    ->label('NO.')
                    ->rowIndex(),
                TextColumn::make('updated_at')
                    ->label('DATE & TIME')
                    ->dateTime('j M Y, H:i:s'),
                TextColumn::make('description')->label('SUBJECT'),
                TextColumn::make('remark')->label('REMARK')
                    ->wrap()
                    ->getStateUsing(function ($record) {
                        $properties = json_decode($record->properties, true);
                        $remark = isset($properties['attributes']['remark']) ? $properties['attributes']['remark'] : '-';
                        return $remark;
                    }),
                // TextColumn::make('status')->label('LEAD STATUS')
                //     ->getStateUsing(function ($record) {
                //         // Decode the 'properties' JSON field first
                //         $properties = json_decode($record->properties, true); // If 'properties' is JSON, decode it

                //         // Retrieve both 'lead_status' and 'stage' from 'attributes' within 'properties'
                //         $leadStatus = isset($properties['attributes']['lead_status']) ? $properties['attributes']['lead_status'] : null;
                //         $stage = isset($properties['attributes']['stage']) ? $properties['attributes']['stage'] : null;

                //         // If either 'lead_status' or 'stage' is missing, return null
                //         if ($leadStatus === null || $stage === null) {
                //             $categories = isset($properties['attributes']['categories']) ? $properties['attributes']['categories'] : null;
                //             return "{$categories}: {$leadStatus}";
                //         }else{
                //             return "{$stage}: {$leadStatus}";
                //         }
                //     }),
                TextColumn::make('follow_up_date')->label('NEXT FOLLOW UP')
                    ->getStateUsing(function ($record) {
                        $properties = json_decode($record->properties, true);
                        $followUpDate = isset($properties['attributes']['follow_up_date']) ? $properties['attributes']['follow_up_date'] : '-';
                        return $followUpDate;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('updateLeadOwner')
                        ->label(__('Assign to Me'))
                        ->form([
                            Forms\Components\Placeholder::make('')
                            ->content(__('Do you want to assign this lead to yourself? Make sure to confirm assignment before contacting the lead to avoid duplicate efforts by other team members.'))
                        ])
                        ->color('success')
                        ->size(ActionSize::Small)
                        ->button()
                        ->icon('heroicon-o-pencil-square')
                        ->visible(function (ActivityLog $record) {
                            $lead = $record->lead;

                            // Get the latest activity log for the given lead
                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('created_at')
                                ->first();

                            if ($latestActivityLog) {
                                // Check if the latest activity log description needs updating
                                if (str_contains($latestActivityLog->description, 'New lead created')){
                                    return true; // Show button
                                }
                            }
                        })
                        ->action(function (ActivityLog $record, array $data) {
                            // $original = $record->getOriginal();

                            $record->lead->update([
                                    'lead_owner' => auth()->user()->name,
                                    'categories' => 'Active',
                                    'stage' => 'Transfer',
                                    'lead_status' => 'New',
                                ]);

                                $latestActivityLog = ActivityLog::where('subject_id', $record->lead->id)
                                    ->orderByDesc('created_at')
                                    ->first();
                                if ($latestActivityLog && $latestActivityLog->description !== 'Lead assigned to Lead Owner: ' . auth()->user()->name) {
                                    // Update the latest activity log description if it doesn't match
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
                        }),
                ActionGroup::make([
                    Tables\Actions\Action::make('addDemo')
                        ->label(__('Add Demo'))
                        ->modalHeading('Add Appointment')
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
                                    ->reactive()
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('remarks', strtoupper($state))),

                                TextInput::make('required_attendees')
                                    ->label('Required Attendees')
                                    ->helperText('Separate each email and name pair with a semicolon (e.g., email1;email2;email3).'),
                                    // ->rules([
                                    //     'regex:/^([^;]+;[^;]+;)*([^;]+;[^;]+)$/', // Validates the email-name pairs separated by semicolons
                                    // ]),
                            ])
                        ->visible(function (ActivityLog $record) {
                            // Decode properties once and retrieve relevant attributes
                            $attributes = json_decode($record->properties, true)['attributes'] ?? [];

                            $category = data_get($attributes, 'categories');
                            $stage = data_get($attributes, 'stage');
                            $leadStatus = data_get($attributes, 'lead_status');

                            // Define invalid categories, stages, and statuses
                            $invalidCategories = [LeadCategoriesEnum::INACTIVE->value];
                            $invalidStages = [LeadStageEnum::DEMO->value, LeadStageEnum::FOLLOW_UP->value];
                            $invalidLeadStatuses = [LeadStatusEnum::RFQ_FOLLOW_UP->value];

                            $lead = $record->lead;

                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('created_at')
                                ->first();

                            // Return visibility based on the conditions
                            return !(auth()->user()->role_id === 1 && !is_null($lead->salesperson)) &&
                                   !in_array($category, $invalidCategories) &&
                                   !in_array($stage, $invalidStages) &&
                                   !in_array($leadStatus, $invalidLeadStatuses) &&
                                   $latestActivityLog->description !== 'Demo Cancelled. 4th Demo Cancelled Follow Up';
                        })

                    ->color('success')
                    // ->modalSubmitAction(false)
                    ->modalCancelAction(false)
                    ->icon('heroicon-o-pencil-square')
                    ->action(function (ActivityLog $activityLog, array $data) {
                        // Create a new Appointment and store the form data in the appointments table
                        $lead = $activityLog->lead;
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
                            // 'subject' => $data['title'], // Event title
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
                            // 'location' => [
                            //     'displayName' => $data['location'] ?? null, // Specify the location
                            // ],
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

                        $appointment = $lead->demoAppointment()->latest()->first(); // Assuming a relation exists
                        if ($appointment) {
                            $appointment->update([
                                'status' => 'New',
                            ]);
                        }

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

                                // Collect required attendees' emails
                                $requiredAttendees = $demoAppointment->required_attendees ?? null;

                                // Parse attendees' emails if not null
                                $attendeeEmails = [];
                                if (!empty($requiredAttendees)) {
                                    $cleanedAttendees = str_replace('"', '', $requiredAttendees);
                                    $attendeeEmails = array_filter(array_map('trim', explode(';', $cleanedAttendees))); // Ensure no empty spaces
                                }

                                // Get Lead Owner Email
                                $leadownerName = $lead->lead_owner;
                                $leadowner = User::where('name', $leadownerName)->first();
                                $leadOwnerEmail = $leadowner->email ?? null; // Prevent null errors

                                // Combine all recipients
                                $allEmails = array_unique(array_merge([$email], $attendeeEmails, [$salespersonUser->email, $leadOwnerEmail]));

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

                        // $whatsappController = new \App\Http\Controllers\WhatsAppController();
                        // $response = $whatsappController->sendWhatsAppTemplate($phoneNumber, $contentTemplateSid, $variables);

                        // return $response;
                    }),
                    Tables\Actions\Action::make('addRFQ')
                        ->label(__('Add RFQ'))
                        ->visible(function (ActivityLog $record) {
                            // Decode properties once and retrieve relevant attributes
                            $attributes = json_decode($record->properties, true)['attributes'] ?? [];

                            $leadStatus = data_get($attributes, 'lead_status');
                            $category = data_get($attributes, 'categories');
                            $stage = data_get($attributes, 'stage');

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

                            // Return visibility based on the conditions
                            return !in_array($leadStatus, $invalidLeadStatuses) &&
                                $category !== LeadCategoriesEnum::INACTIVE->value &&
                                !in_array($stage, $invalidStages);
                        })
                        ->form([
                            Forms\Components\Placeholder::make('')
                            ->content(__('You are marking this lead as "RFQ" and assigning it to a salesperson. Confirm?')),

                            Forms\Components\Select::make('salesperson')
                                ->label('Salesperson')
                                ->options(function () {
                                    if (auth()->user()->role_id == 3) {
                                        return User::query()
                                        ->whereIn('role_id', [2, 3]) // Filter by role_id = 2 or 3
                                        ->pluck('name', 'id')
                                        ->toArray();
                                    }else{
                                        return User::query()
                                        ->where('role_id', 2)
                                        ->pluck('name', 'id')
                                        ->toArray();
                                    }
                                })
                                ->required() // Make it a required field if necessary
                                ->placeholder('Select a salesperson'),

                            Forms\Components\Textarea::make('remark')
                                ->label('Remarks')
                                ->rows(4)
                                ->autosize()
                                ->required()
                                ->placeholder('Enter remarks here...')
                                ->afterStateUpdated(fn ($state, callable $set) => $set('remark', strtoupper($state))),
                        ])
                        ->color('success')
                        ->icon('heroicon-o-pencil-square')
                        ->action(function (ActivityLog $activityLog, array $data) {
                            // Retrieve the related Lead model from ActivityLog
                            $lead = $activityLog->lead; // Assuming the 'activityLogs' relation in Lead is named 'lead'
                            // Update the Lead model
                            $lead->update([
                                'stage' => 'Transfer',
                                'lead_status' => 'RFQ-Transfer',
                                'remark' => $data['remark'],
                                'salesperson' => $data['salesperson'],
                                'follow_up_date' => today(),
                                'rfq_transfer_at' => now()
                            ]);

                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
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
                                            ->performedOn($lead);
                                    }
                                }
                                $leadownerName = $lead->lead_owner;
                                $leadowner = User::where('name', $leadownerName)->first();
                                $salespersonUser = \App\Models\User::find($data['salesperson']);
                            if ($salespersonUser && filter_var($salespersonUser->email, FILTER_VALIDATE_EMAIL)) {
                                try {
                                    // Get the currently logged-in user
                                    $currentUser = Auth::user();
                                    if (!$currentUser) {
                                        throw new Exception('User not logged in');
                                    }

                                    // Set "from" email and name from the logged-in user
                                    $fromEmail = $currentUser->email;
                                    $fromName = $currentUser->name ?? 'CRM User';

                                    $viewName = 'emails.salesperson_notification2'; // Replace with a valid default view

                                    $emailContent = [
                                        'salespersonName' => $salespersonUser, // Salesperson's Name
                                        'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager', // Lead Owner/Manager Name
                                        'lead' => [
                                            'lead_code' => isset($lead->lead_code) ? 'https://crm.timeteccloud.com:8082/demo-request/' . $lead->lead_code : 'N/A',
                                            'lastName' => $lead->name ?? 'N/A', // Lead's Last Name
                                            'company' => $lead->companyDetail->company_name ?? 'N/A', // Lead's Company
                                            'companySize' => $lead->company_size ?? 'N/A', // Company Size
                                            'phone' => $lead->phone ?? 'N/A', // Lead's Phone
                                            'email' => $lead->email ?? 'N/A', // Lead's Email
                                            'country' => $lead->country ?? 'N/A', // Lead's Country
                                            'products' => $lead->products ?? 'N/A', // Products
                                            // 'solutions' => $lead->solutions ?? 'N/A', // Solutions
                                        ],
                                        'remark' => $data['remark'] ?? 'No remarks provided', // Custom Remark
                                        'formatted_products' => $lead->formatted_products, // Add formatted products
                                    ];
                                    // Send the email with the appropriate template view
                                    Mail::mailer('smtp')
                                        ->to([$salespersonUser->email, $leadowner->email]) // Send to both
                                        ->send(new SalespersonNotification($emailContent, $fromEmail, $fromName, $viewName));

                                    // Success notification
                                    Notification::make()
                                        ->title('RFQ Added Successfully')
                                        ->success()
                                        ->send();
                                } catch (\Exception $e) {
                                    // Handle email sending failure
                                    Log::error("Email sending failed for salesperson: {$data['salesperson']}, Error: {$e->getMessage()}");

                                    Notification::make()
                                        ->title('Error: Failed to send email')
                                        ->danger()
                                        ->send();
                                }
                            }
                        }),
                    Tables\Actions\Action::make('addFollowUp')
                        ->label(__('Add Follow Up'))
                        ->form([
                            Forms\Components\Placeholder::make('')
                                ->content(__('Fill out the following section to add a follow-up for this lead.
                                            Select a follow-up date if the lead requests to be contacted on a specific date.
                                            Otherwise, the system will default to sending the follow-up on the next Tuesday.')),

                            Forms\Components\Textarea::make('remark')
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

                            Forms\Components\DatePicker::make('follow_up_date')
                            ->label('Next Follow Up Date')
                            ->required()
                            ->placeholder('Select a follow-up date')
                            ->default(fn ($record) => $record->lead->follow_up_date ?? now())
                            ->reactive()
                            // ->minDate(fn ($record) => $record->lead->follow_up_date ? Carbon::parse($record->lead->follow_up_date)->startOfDay() : now()->startOfDay()) // Ensure it gets from DB
                            ->visible(fn (Forms\Get $get) => !$get('follow_up_needed')) // Hide when follow_up_needed is checked
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                if ($get('follow_up_needed')) {
                                    $nextTuesday = Carbon::now()->next(Carbon::TUESDAY);
                                    $set('follow_up_date', $nextTuesday); // Set to next Tuesday if checked
                                }
                            }),
                        ])
                        ->color('success')
                        ->icon('heroicon-o-pencil-square')
                        ->action(function (ActivityLog $activityLog, array $data, Component $livewire) {
                            // Retrieve the related Lead model from ActivityLog
                            $lead = $activityLog->lead;

                            // Check if follow_up_date exists in the $data array; if not, set it to next Tuesday
                            $followUpDate = $data['follow_up_date'] ?? now()->next(Carbon::TUESDAY);
                            // if($lead->lead_status === 'New' || $lead->lead_status === 'Under Review'){

                                $lead->update([
                                    'follow_up_date' => $followUpDate,
                                    'remark' => $data['remark'],
                                    'follow_up_count' => $lead->follow_up_count + 1,
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
                            // }
                            // else if($lead->lead_status === 'Transfer' || $lead->lead_status === 'Pending Demo'){

                            //     $lead->update([
                            //         'follow_up_date' => $followUpDate,
                            //         'remark' => $data['remark'],
                            //         'demo_follow_up_count' => $lead->demo_follow_up_count + 1,
                            //     ]);

                            //     // Fetch the number of previous follow-ups for this lead
                            //     $followUpCount = ActivityLog::where('subject_id', $lead->id)
                            //         ->whereJsonContains('properties->attributes->lead_status', 'Pending Demo') // Filter by lead_status in properties
                            //         ->count();

                            //     $followUpCount = max(0, $followUpCount - 1); // Ensure count does not go below 0

                            //     $viewName = 'emails.email_blasting_1st';
                            //     $contentTemplateSid = 'HX2d4adbe7d011693a90af7a09c866100f'; // Your Content Template SID

                            //     // Increment the follow-up count for the new description
                            //     $followUpDescription = ($followUpCount) . 'st Salesperson Transfer Follow Up';
                            //     if ($followUpCount == 2) {
                            //         $followUpDescription = '2nd Salesperson Transfer Follow Up';
                            //     } elseif ($followUpCount == 3) {
                            //         $followUpDescription = '3rd Salesperson Transfer Follow Up';
                            //     } elseif ($followUpCount >= 4) {
                            //         $followUpDescription = $followUpCount . 'th Salesperson Transfer Follow Up';
                            //     }

                            //     // Update or create the latest activity log description
                            //     $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                            //         ->orderByDesc('created_at')
                            //         ->first();

                            //     if ($latestActivityLog) {
                            //         $latestActivityLog->update([
                            //             'description' => $followUpDescription,
                            //         ]);
                            //     } else {
                            //         activity()
                            //             ->causedBy(auth()->user())
                            //             ->performedOn($lead)
                            //             ->withProperties(['description' => $followUpDescription]);
                            //     }

                            //     // Send a notification
                            //     Notification::make()
                            //         ->title('Follow Up Added Successfully')
                            //         ->success()
                            //         ->send();

                            //     $leadowner = User::where('name', $lead->lead_owner)->first();
                            //     try {
                            //         // Get the currently logged-in user
                            //         $currentUser = Auth::user();
                            //         if (!$currentUser) {
                            //             throw new Exception('User not logged in');
                            //         }

                            //         $emailContent = [
                            //             'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager', // Lead Owner/Manager Name
                            //             'lead' => [
                            //                 'lastName' => $lead->name ?? 'N/A', // Lead's Last Name
                            //                 'company' => $lead->companyDetail->company_name ?? 'N/A', // Lead's Company
                            //                 'companySize' => $lead->company_size ?? 'N/A', // Company Size
                            //                 'phone' => $lead->phone ?? 'N/A', // Lead's Phone
                            //                 'email' => $lead->email ?? 'N/A', // Lead's Email
                            //                 'country' => $lead->country ?? 'N/A', // Lead's Country
                            //                 'products' => $lead->products ?? 'N/A', // Products
                            //                 'position' => $leadowner->position ?? 'N/A', // position
                            //                 'companyName' => $lead->companyDetail->company_name ?? 'Unknown Company',
                            //                 'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                            //                 // 'solutions' => $lead->solutions ?? 'N/A', // Solutions
                            //             ],
                            //         ];
                            //         Log::info('Company Name:', ['companyName' => $lead->companyDetail->company_name ?? 'N/A']);

                            //         Mail::mailer('secondary')
                            //             ->to($lead->companyDetail->email ?? $lead->email)
                            //             ->send(new FollowUpNotification($emailContent, $viewName));
                            //     } catch (\Exception $e) {
                            //         // Handle email sending failure
                            //         Log::error("Error: {$e->getMessage()}");
                            //     }
                            // }
                            // else{
                            //     // Retrieve the related Lead model from ActivityLog
                            //     $lead = $activityLog->lead; // Assuming the 'activityLogs' relation in Lead is named 'lead'
                            //     // Update the Lead model
                            //     $lead->update([
                            //         'lead_status' => 'Demo Cancelled',
                            //         'remark' => $data['remark'],
                            //         'follow_up_date' => $followUpDate,
                            //         'follow_up_needed' => $data['follow_up_needed'] ?? false,
                            //         'follow_up_count' => $lead->demo_follow_up_count + 1,
                            //     ]);

                            //     $cancelfollowUpCount = ActivityLog::where('subject_id', $lead->id)
                            //             ->whereJsonContains('properties->attributes->lead_status', 'Demo Cancelled') // Filter by lead_status in properties
                            //             ->count();

                            //         // Increment the follow-up count for the new description
                            //         $cancelFollowUpDescription = ($cancelfollowUpCount) . 'st Demo Cancelled Follow Up';
                            //         if ($cancelfollowUpCount == 2) {
                            //             $cancelFollowUpDescription = '2nd Demo Cancelled Follow Up';
                            //         } elseif ($cancelfollowUpCount == 3) {
                            //             $cancelFollowUpDescription = '3rd Demo Cancelled Follow Up';
                            //         } elseif ($cancelfollowUpCount >= 4) {
                            //             $cancelFollowUpDescription = $cancelfollowUpCount . 'th Demo Cancelled Follow Up';
                            //         }

                            //         // Update or create the latest activity log description
                            //         $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                            //             ->orderByDesc('created_at')
                            //             ->first();

                            //         if ($latestActivityLog) {
                            //             $latestActivityLog->update([
                            //                 'description' => 'Demo Cancelled. ' . ($cancelFollowUpDescription),
                            //             ]);
                            //         } else {
                            //             activity()
                            //                 ->causedBy(auth()->user())
                            //                 ->performedOn($lead)
                            //                 ->withProperties(['description' => $cancelFollowUpDescription]);
                            //         }

                            //     Notification::make()
                            //         ->title('You had follow up a cancelled demo')
                            //         ->success()
                            //         ->send();
                            // }
                        }),
                    Tables\Actions\Action::make('addAutomation')
                        ->label(__('Add Automation'))
                        ->color('primary')
                        ->icon('heroicon-o-cog-8-tooth')
                        ->visible(function (ActivityLog $record) {
                            // Decode properties once and retrieve relevant attributes
                            $attributes = json_decode($record->properties, true)['attributes'] ?? [];

                            $leadStatus = data_get($attributes, 'lead_status');
                            $category = data_get($attributes, 'categories');
                            $stage = data_get($attributes, 'stage');

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

                            // Return visibility based on the conditions
                            return $record->lead->follow_up_needed==0 &&!in_array($leadStatus, $invalidLeadStatuses) &&
                                $category !== LeadCategoriesEnum::INACTIVE->value &&
                                !in_array($stage, $invalidStages);
                        })
                        ->form([
                            Forms\Components\Placeholder::make('confirmation')
                                ->label('Are you sure you want to start the automation to follow up the lead by sending automation email to lead?'),
                        ])
                        ->modalHeading('Confirm Automation Action')
                        ->modalSubmitActionLabel('Confirm')
                        ->modalCancelActionLabel('Cancel')
                        ->action(function (ActivityLog $activityLog, array $data) {
                            $lead = $activityLog->lead;

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
                        }),
                    Tables\Actions\Action::make('archive')
                        ->label(__('Archive'))
                        ->modalHeading('Mark Lead as Inactive')
                        ->color('warning')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(function (ActivityLog $record) {
                            $attributes = json_decode($record->properties, true)['attributes'] ?? [];

                            $categories = data_get($attributes, 'categories');

                            $lead = $record->lead;

                            return  auth()->user()->role_id === 1 && is_null($lead->salesperson) &&
                                    $categories !== LeadCategoriesEnum::INACTIVE->value;
                        })
                        ->form([
                            Forms\Components\Placeholder::make('')
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
                        ->action(function (ActivityLog $activityLog, array $data) {
                            $statusLabels = [
                                'on_hold' => 'On Hold',
                                'junk' => 'Junk',
                                'lost' => 'Lost',
                            ];

                            $statusLabel = $statusLabels[$data['status']] ?? $data['status'];

                            $lead = Lead::find($activityLog->subject_id);

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
                        }),
                    Tables\Actions\Action::make('quotation')
                        ->label(__('Add Quotation'))
                        ->color('success')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(function (ActivityLog $record) {
                            $attributes = json_decode($record->properties, true)['attributes'] ?? [];

                            $leadStatus = data_get($attributes, 'lead_status');

                            $lead = $record->lead;

                            return !(auth()->user()->role_id === 1 && !is_null($lead->salesperson)) &&
                                ($leadStatus === LeadStatusEnum::RFQ_FOLLOW_UP->value
                                || $leadStatus === LeadStatusEnum::RFQ_TRANSFER->value);
                        })
                        ->url(fn (ActivityLog $record) => route('filament.admin.resources.quotations.create', [
                            'lead_id' => Encryptor::encrypt($record->subject_id),
                        ]), true),
                    Tables\Actions\Action::make('quotationFollowUp')
                        ->label(__('Add RFQ Follow Up'))
                        ->color('success')
                        ->icon('heroicon-o-pencil-square')
                        ->modalHeading('Determine Lead Status')
                        ->form([
                            Forms\Components\Placeholder::make('')
                                ->content(__('Fill out the following section to add a follow-up for this lead.
                                            Select a follow-up date if the lead requests to be contacted on a specific date.
                                            Otherwise, the system will default to sending the follow-up on the next Tuesday.')),

                            Forms\Components\TextInput::make('remark')
                                ->label('Remarks')
                                ->required()
                                ->placeholder('Enter remarks here...')
                                ->maxLength(500),

                            Forms\Components\Checkbox::make('follow_up_needed')
                                ->label('Enable automatic follow-up (4 times)')
                                ->default(false),

                            Forms\Components\Select::make('follow_up_choice')
                                ->label('NEXT FOLLOW UP DATE')
                                ->options(['custom' => 'Custom'])
                                ->required()
                                ->default('custom')
                                ->disabled(fn (Forms\Get $get) => $get('follow_up_needed')), // Disable if checkbox is checked

                            Forms\Components\DatePicker::make('follow_up_date')
                                ->label('')
                                ->required()
                                ->placeholder('Select a follow-up date')
                                ->default(now())
                                ->disabled(fn (Forms\Get $get) => $get('follow_up_needed'))
                                ->reactive()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                    if ($get('follow_up_needed')) {
                                        $set('follow_up_date', now()->next(Carbon::TUESDAY)); // Set to next Tuesday if checked
                                    }
                                }),
                            Forms\Components\Placeholder::make('')
                            ->content(__('What status do you feel for this lead at this moment?')),

                            Forms\Components\Select::make('status')
                            ->label('STATUS')
                            ->options(['hot' => 'Hot',
                                        'warm' => 'Warm',
                                        'cold' => 'Cold'])
                            ->default('hot')
                            ->required(),
                        ])
                        ->visible(function (ActivityLog $record) {
                            $attributes = json_decode($record->properties, true)['attributes'] ?? [];
                            $lead = $record->lead;

                            $leadStatus = data_get($attributes, 'lead_status');

                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('created_at')
                                ->first();

                            if($leadStatus == LeadStatusEnum::PENDING_DEMO->value){
                                return false;
                            }

                            if(str_contains($latestActivityLog->description, 'Quotation Sent.')){
                                return true;
                            }

                            return ($leadStatus === LeadStatusEnum::HOT->value ||
                                $leadStatus === LeadStatusEnum::WARM->value ||
                                $leadStatus === LeadStatusEnum::COLD->value) &&
                                $latestActivityLog->description !== '4th Quotation Transfer Follow Up' &&
                                $latestActivityLog->description !== 'Order Uploaded. Pending Approval to close lead.';
                        })
                        ->action(function (ActivityLog $activityLog, array $data, Component $livewire) {
                            // Retrieve the related Lead model from ActivityLog
                            $lead = $activityLog->lead;

                            // Check if follow_up_date exists in the $data array; if not, set it to next Tuesday
                            $followUpDate = $data['follow_up_date'] ?? now()->next(Carbon::TUESDAY);

                            $lead->update([
                                'lead_status' => $data['status'],
                                'follow_up_date' => $followUpDate,
                                'remark' => $data['remark'],
                                'follow_up_needed' => $data['follow_up_needed'] ?? false,
                                'follow_up_count' => $lead->follow_up_count + 1,
                            ]);

                            $followUpCount = max(1, ActivityLog::where('subject_id', $lead->id)
                                ->where(function ($query) {
                                    $query->whereJsonContains('properties->attributes->lead_status', 'Hot')
                                        ->orWhereJsonContains('properties->attributes->lead_status', 'Warm')
                                        ->orWhereJsonContains('properties->attributes->lead_status', 'Cold');
                                })
                                ->count() - 1);

                            // Increment the follow-up count for the new description
                            $followUpDescription = ($followUpCount) . 'st Quotation Transfer Follow Up';
                            if ($followUpCount == 2) {
                                $followUpDescription = '2nd Quotation Transfer Follow Up';
                            } elseif ($followUpCount == 3) {
                                $followUpDescription = '3rd Quotation Transfer Follow Up';
                            } elseif ($followUpCount >= 4) {
                                $followUpDescription = $followUpCount . 'th Quotation Transfer Follow Up';
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

                            $message = urlencode("Hello {$lead->name},\nYour follow-up is scheduled for: {$followUpDate}");
                            $phoneNumber = $lead->phone; // Ensure this includes the country code
                            // Redirect to WhatsApp Web/App
                            return $livewire->js("window.open('https://api.whatsapp.com/send?phone={$phoneNumber}&text={$message}', '_blank');");
                        })
                        ->icon('heroicon-o-pencil-square'),
                    Tables\Actions\Action::make('noResponse')
                        ->label(__('No Response'))
                        ->modalHeading('Mark Lead as No Response')
                        ->form([
                            Forms\Components\Placeholder::make('')
                            ->content(__('You are making this lead as No Response after multiple follow-ups. Confirm?')),

                            Forms\Components\TextInput::make('remark')
                            ->label('Remarks')
                            ->required()
                            ->placeholder('Enter remarks here...')
                            ->maxLength(500),
                        ])
                        ->color('danger')
                        ->icon('heroicon-o-pencil-square')
                        ->action(function (ActivityLog $activityLog, array $data) {
                            // Retrieve the related Lead model from ActivityLog
                            $lead = $activityLog->lead; // Assuming the 'activityLogs' relation in Lead is named 'lead'

                            // Update the Lead model for role_id = 1
                            $lead->update([
                                'categories' => 'Inactive',
                                'stage' => null,
                                'lead_status' => 'No Response',
                                'remark' => $data['remark'],
                                'follow_up_date' => null,
                            ]);

                            // Update the latest ActivityLog for role_id = 1
                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('created_at')
                                ->first();

                            $latestActivityLog->update([
                                'description' => 'Marked as No Response',
                            ]);

                            // Send notification for role_id = 1
                            Notification::make()
                                ->title('You have marked No Response to a lead')
                                ->success()
                                ->send();

                            // Log the activity (for both roles)
                            activity()
                                ->causedBy(auth()->user())
                                ->performedOn($lead);
                        })
                        ->visible(function (ActivityLog $record) {
                            $lead = $record->lead;

                            // Get the latest activity log for the given lead
                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('created_at')
                                ->first();

                            if ($latestActivityLog) {
                                // Check if the latest activity log description needs updating
                                if ($lead->call_attempt >= 4 || $latestActivityLog->description == '4th Lead Owner Follow Up (Auto Follow Up Stop)'|| $latestActivityLog->description == '4th Salesperson Transfer Follow Up'
                                    || $latestActivityLog->description == 'Demo Cancelled. 4th Demo Cancelled Follow Up' || $latestActivityLog->description == 'Demo Cancelled. 8th Demo Cancelled Follow Up'
                                    || $latestActivityLog->description == 'Demo Cancelled. 12th Demo Cancelled Follow Up' || $latestActivityLog->description == '4th Quotation Transfer Follow Up') {
                                    return true; // Show button
                                }
                            }

                            return false; // Default: Hide button
                        }),
                    Tables\Actions\Action::make('reactive')
                        ->label(__('Reactive'))
                        ->modalHeading('Reactive Lead')
                        ->form([
                            Forms\Components\Placeholder::make('')
                            ->content(__('Are you sure you want to reactive this lead? This action will move the lead back to active status for further follow-ups and actions.')),

                            Forms\Components\TextInput::make('remark')
                            ->label('Remarks')
                            ->required()
                            ->placeholder('Enter remarks here...')
                            ->maxLength(500),
                        ])
                        ->color('danger')
                        ->icon('heroicon-o-pencil-square')
                        ->action(function (ActivityLog $activityLog, array $data) {
                            // Retrieve the related Lead model from ActivityLog
                            $lead = $activityLog->lead; // Assuming the 'activityLogs' relation in Lead is named 'lead'

                            // Determine actions based on user role
                            if (auth()->user()->role_id == 2) {
                                // Update the Lead model for role_id = 2
                                $lead->update([
                                    'categories' => 'Active',
                                    'stage' => 'Transfer',
                                    'lead_status' => 'RFQ-Transfer',
                                    'remark' => $data['remark'],
                                    'follow_up_date' => null,
                                    'salesperson' => auth()->user()->name,
                                ]);

                                // Update the latest ActivityLog for role_id = 2
                                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                    ->orderByDesc('created_at')
                                    ->first();

                                $latestActivityLog->update([
                                    'description' => 'Lead assigned to Salesperson: ' . auth()->user()->name . '. RFQ only',
                                ]);

                                Notification::make()
                                    ->title('You have reactivated a lead')
                                    ->success()
                                    ->send();
                            } elseif (auth()->user()->role_id == 1) {
                                // Update the Lead model for role_id = 1
                                $lead->update([
                                    'categories' => 'Active',
                                    'stage' => 'Transfer',
                                    'lead_status' => 'New',
                                    'remark' => $data['remark'],
                                    'follow_up_date' => null,
                                ]);

                                // Update the latest ActivityLog for role_id = 1
                                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                    ->orderByDesc('created_at')
                                    ->first();

                                $latestActivityLog->update([
                                    'description' => 'Lead assigned to Lead Owner: ' . auth()->user()->name,
                                ]);

                                Notification::make()
                                    ->title('You have reactivated a lead')
                                    ->success()
                                    ->send();
                            }

                            activity()
                                ->causedBy(auth()->user())
                                ->performedOn($lead);
                        })
                        ->visible(function (ActivityLog $record) {

                            $attributes = json_decode($record->properties, true)['attributes'] ?? [];

                            $leadCategories = data_get($attributes, 'categories');

                            $leadStatus = data_get($attributes, 'lead_status');

                            return $leadCategories == 'Inactive' && $leadStatus == 'No Response';
                        }),

                    Tables\Actions\Action::make('rearchive')
                        ->visible(function (ActivityLog $record) {
                            return data_get(json_decode($record->properties, true), 'attributes.lead_status') == 'On Hold' ||
                            data_get(json_decode($record->properties, true), 'attributes.lead_status') == 'Lost' ||
                            data_get(json_decode($record->properties, true), 'attributes.lead_status') == 'Junk';
                        })
                        ->label(__('Rearchive'))
                        ->modalHeading('Reactive Lead')
                        ->form([
                            Forms\Components\Placeholder::make('')
                            ->content(__('Are you sure you want to reactive this lead? This action will move the lead back to active status for further follow-ups and actions.')),

                            Forms\Components\TextInput::make('remark')
                            ->label('Remarks')
                            ->required()
                            ->placeholder('Enter remarks here...')
                            ->maxLength(500),
                        ])
                        ->color('danger')
                        ->icon($icon = 'heroicon-o-pencil-square')
                        ->action(function (ActivityLog $activityLog, array $data) {
                            $lead = $activityLog->lead;

                            if (auth()->user()->role_id == 1) {
                                $lead->update([
                                    'categories' => 'Active',
                                    'stage' => 'Transfer',
                                    'lead_status' => 'RFQ-Transfer',
                                    'remark' => $data['remark'],
                                    'follow_up_date' => null,
                                    'salesperson' => auth()->user()->name,
                                ]);

                                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                    ->orderByDesc('created_at')
                                    ->first();

                                $latestActivityLog->update([
                                    'description' => 'Lead assigned to Salesperson: ' . auth()->user()->name . '. RFQ only',
                                ]);

                            } elseif (auth()->user()->role_id == 2) {
                                $lead->update([
                                    'lead_status' => 'New',
                                ]);

                                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                    ->orderByDesc('created_at')
                                    ->first();

                                $latestActivityLog->update([
                                    'description' => 'Lead assigned to Lead Owner: ' . auth()->user()->name,
                                ]);
                            }

                            activity()
                                ->causedBy(auth()->user())
                                ->performedOn($lead);

                            Notification::make()
                                ->title('You had rearchived a record')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('demo_done')
                        ->visible(function (ActivityLog $record) {
                            $attributes = json_decode($record->properties, true)['attributes'] ?? [];

                            return data_get($attributes, 'stage') === 'Demo';
                        })
                        ->label(__('Demo Done'))
                        ->modalHeading('Demo Completed Confirmation')
                        ->form([
                            Forms\Components\Placeholder::make('')
                                ->content(__('You are marking this demo as completed. Confirm?')),

                            Forms\Components\TextInput::make('remark')
                                ->label('Remarks')
                                ->required()
                                ->placeholder('Enter remarks here...')
                                ->maxLength(500),
                        ])
                        ->color('success')
                        ->icon($icon = 'heroicon-o-pencil-square')
                        ->action(function (ActivityLog $activityLog, array $data) {
                            // Retrieve the related Lead model from ActivityLog
                            $lead = $activityLog->lead; // Ensure this relation exists

                            // Retrieve the latest demo appointment for the lead
                            $latestDemoAppointment = $lead->demoAppointment() // Assuming 'demoAppointments' relation exists
                                ->latest('created_at') // Retrieve the most recent demo
                                ->first();

                            if ($latestDemoAppointment) {
                                $latestDemoAppointment->update([
                                    'status' => 'Done', // Or whatever status you need to set
                                ]);
                            }

                            // Update the Lead model
                            $lead->update([
                                'stage' => 'Follow Up',
                                'lead_status' => 'RFQ-Follow Up',
                                'remark' => $data['remark'],
                                'follow_up_date' => null,
                            ]);

                            // Update the latest ActivityLog related to the lead
                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('created_at')
                                ->first();

                            if ($latestActivityLog) {
                                $latestActivityLog->update([
                                    'description' => 'Demo Completed',
                                ]);
                            }

                            // Log activity
                            activity()
                                ->causedBy(auth()->user())
                                ->performedOn($lead);

                            // Send success notification
                            Notification::make()
                                ->title('Demo completed successfully')
                                ->success()
                                ->send();
                        }),

                    Tables\Actions\Action::make('demo_cancel')
                        ->visible(function (ActivityLog $record) {
                            // Decode the properties from the activity log
                            $attributes = json_decode($record->properties, true)['attributes'] ?? [];

                            // Extract lead status and stage
                            $leadStatus = data_get($attributes, 'lead_status');
                            $stage = data_get($attributes, 'stage');

                            // Check if the lead status is 'Demo-Assigned' or if the stage is 'Follow Up' and lead status is 'RFQ-Follow Up'
                            return $leadStatus === LeadStatusEnum::DEMO_ASSIGNED->value ||
                                ($stage === LeadStageEnum::FOLLOW_UP->value && $leadStatus === LeadStatusEnum::RFQ_FOLLOW_UP->value);
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
                        ->color('warning')
                        ->icon('heroicon-o-pencil-square')
                        ->action(function (ActivityLog $activityLog, array $data) {
                            // Retrieve the related Lead model from ActivityLog
                            $lead = $activityLog->lead;

                            $accessToken = MicrosoftGraphService::getAccessToken();

                            $graph = new Graph();
                            $graph->setAccessToken($accessToken);

                            $appointment = $lead->demoAppointment()->latest('created_at')->first();
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
                                    $graph->createRequest("DELETE", "/users/$organizerEmail/events/$eventId")
                                        ->execute();

                                    Log::info('Teams meeting cancelled successfully', [
                                        'event_id' => $eventId,
                                        'organizer' => $organizerEmail,
                                    ]);

                                    $appointment->update([
                                        'status' => 'Cancelled',
                                    ]);

                                    Notification::make()
                                        ->title('Teams Meeting Cancelled Successfully')
                                        ->warning()
                                        ->body('The meeting has been cancelled successfully.')
                                        ->send();
                                } else {
                                    // Log missing event ID
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

                            $lead->update([
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

                            $appointment = $lead->demoAppointment(); // Assuming a relation exists

                            if ($appointment) {
                                $appointment->update([
                                    'status' => 'Cancelled', // Or whatever status you need to set
                                ]);
                            }

                            Notification::make()
                                ->title('You had cancelled a demo')
                                ->warning()
                                ->send();
                        }),
                    Tables\Actions\Action::make('view_proof')
                        ->visible(function (ActivityLog $record) {
                            // Decode the properties from the activity log
                            $attributes = json_decode($record->properties, true)['attributes'] ?? [];

                            // Extract lead status
                            $leadStatus = data_get($attributes, 'lead_status');
                            $lead = $record->lead;

                            // Get the latest activity log for the given lead
                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('created_at')
                                ->first();

                            // Decode the properties from the activity log
                            $attributes = json_decode($record->properties, true)['attributes'] ?? [];

                            if($latestActivityLog && (str_contains($latestActivityLog->description, 'Quotation Sent.')
                                || str_contains($latestActivityLog->description, 'Quotation Transfer Follow Up'))){
                                return false;
                            }
                            // Show action only for specific lead statuses
                            return $leadStatus === LeadStatusEnum::HOT->value ||
                                $leadStatus === LeadStatusEnum::WARM->value ||
                                $leadStatus === LeadStatusEnum::COLD->value;
                        })
                        ->label(__('View Proof'))
                        ->color('warning')
                        ->icon('heroicon-o-document-text')
                        ->url(function (ActivityLog $record) {
                            $quotation = $record->lead->quotations()->latest('created_at')->first();

                            if ($quotation && $quotation->confirmation_order_document) {
                                // Generate the public URL using Storage::url
                                return Storage::url($quotation->confirmation_order_document);
                            }
                            return null; // No document URL
                        })
                        ->openUrlInNewTab()
                        ->action(function (ActivityLog $record) {
                            // Notify the user that no document is found
                            Notification::make()
                                ->title('Error')
                                ->body('No document found for this quotation.')
                                ->danger()
                                ->send();
                        }),
                    Tables\Actions\Action::make('view_pi')
                        ->visible(function (ActivityLog $record) {
                            $lead = $record->lead;

                            // Get the latest activity log for the given lead
                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('created_at')
                                ->first();

                            // Decode the properties from the activity log
                            $attributes = json_decode($record->properties, true)['attributes'] ?? [];

                            // Extract lead status
                            $leadStatus = data_get($attributes, 'lead_status');

                            if($latestActivityLog && (str_contains($latestActivityLog->description, 'Quotation Sent.')
                                || str_contains($latestActivityLog->description, 'Quotation Transfer Follow Up'))){
                                return false;
                            }
                            // Show action only for specific lead statuses
                            return $leadStatus === LeadStatusEnum::HOT->value ||
                                $leadStatus === LeadStatusEnum::WARM->value ||
                                $leadStatus === LeadStatusEnum::COLD->value;
                        })
                        ->label(__('View PI'))
                        ->color('warning')
                        ->icon('heroicon-o-document-text')
                        ->url(function (ActivityLog $record) {
                            $quotation = $record->lead->quotations()->latest('created_at')->first();

                            if ($quotation && $quotation->pi_reference_no) {
                                // Generate the PI URL using the pi_reference_no
                                $lastTwoDigits = substr($quotation->pi_reference_no, -2); // Get the last 2 characters

                                if (is_numeric($lastTwoDigits)) {
                                    return "https://crm.timeteccloud.com:8082/proforma-invoice-v2/{$lastTwoDigits}";
                                }
                            }

                            return null; // No valid PI reference number
                        })
                        ->openUrlInNewTab()
                        ->action(function (ActivityLog $record) {
                            Notification::make()
                                ->title('Error')
                                ->body('No valid PI reference number found for this quotation.')
                                ->danger()
                                ->send();
                        }),
                    Tables\Actions\Action::make('Reupload')
                        ->color('warning')
                        ->icon('heroicon-o-receipt-refund')
                        ->visible(function (ActivityLog $record) {
                            $description = $record->description;

                            return $description === 'Order Uploaded. Pending Approval to close lead.';
                        })
                        ->form([
                            FileUpload::make('attachment')
                                ->label('Upload Confirmation Order Document')
                                ->acceptedFileTypes(['application/pdf','image/jpg','image/jpeg'])
                                ->uploadingMessage('Uploading document...')
                                ->previewable(false)
                                ->preserveFilenames()
                                ->disk('public')
                                ->directory('confirmation_orders')
                        ])
                        ->action(
                            function (ActivityLog $record, array $data) {
                                $quotation = $record->lead->quotations()->latest('created_at')->first();

                                if (!$quotation) {
                                    // Notify user about missing quotations
                                    return Notification::make()
                                        ->title('No Quotation Found')
                                        ->body('No quotations are associated with this lead.')
                                        ->danger()
                                        ->send();
                                }

                                $quotation->confirmation_order_document = $data['attachment'];
                                $quotation->save();

                                Notification::make()
                                    ->title('Quotation Updated')
                                    ->body('The confirmation order document has been updated successfully.')
                                    ->success()
                                    ->send();
                            }
                        ),
                    Tables\Actions\Action::make('Confirm Order')
                        ->label('Confirm Order')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->visible(function (ActivityLog $record) {
                                $description = $record->description;
                                $attributes = json_decode($record->properties, true)['attributes'] ?? [];

                                // Extract lead status and stage
                                $leadStatus = data_get($attributes, 'lead_status');
                                return (str_contains($description, 'Quotation Sent.') && $leadStatus !== LeadStatusEnum::PENDING_DEMO->value) || str_contains($description, 'Quotation Transfer');
                            }
                        )
                        ->form([
                            FileUpload::make('attachment')
                                ->label('Upload Confirmation Order Document')
                                ->acceptedFileTypes(['application/pdf','image/jpg','image/jpeg'])
                                ->uploadingMessage('Uploading document...')
                                ->previewable(false)
                                ->preserveFilenames()
                                ->disk('public')
                                ->directory('confirmation_orders')
                        ])
                        ->action(function (ActivityLog $record, array $data) {
                            $quotation = $record->lead->quotations()->latest('created_at')->first();

                            if (!$quotation) {
                                Notification::make()
                                    ->title('Quotation Not Found')
                                    ->body('No quotation is associated with this lead.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $quotationService = app(QuotationService::class);
                            $quotation->confirmation_order_document = $data['attachment'];
                            $quotation->pi_reference_no = $quotationService->update_pi_reference_no($quotation);
                            $quotation->status = QuotationStatusEnum::accepted;
                            $quotation->save();

                            $notifyUsers = User::whereIn('role_id', ['2'])->get();
                            $currentUser = auth()->user();
                            $notifyUsers = $notifyUsers->push($currentUser);

                            $lead = $quotation->lead;

                            $lead->update([
                                'follow_up_date' => null,
                            ]);

                            ActivityLog::create([
                                'subject_id' => $lead->id,
                                'description' => 'Order Uploaded. Pending Approval to close lead.',
                                'causer_id' => auth()->id(),
                                'causer_type' => get_class(auth()->user()),
                                'properties' => json_encode([
                                    'attributes' => [
                                        'quotation_reference_no' => $quotation->quotation_reference_no,
                                        'lead_status' => $lead->lead_status,
                                        'stage' => $lead->stage,
                                    ],
                                ]),
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Confirmation Order Document Uploaded!')
                                ->body('Confirmation order document for quotation ' . $quotation->quotation_reference_no . ' has been uploaded successfully!')
                                ->sendToDatabase($notifyUsers)
                                ->send();
                            }
                        ),
                    Tables\Actions\Action::make('approve')
                        ->visible(function (ActivityLog $record) {
                            $user = auth()->user();

                            $description = $record->description;

                            return $user->role_id === 3 &&
                                $description === 'Order Uploaded. Pending Approval to close lead.';
                        })
                        ->label(__('Approve'))
                        ->color('success')
                        ->icon('heroicon-o-clipboard-document-check')
                        ->modalHeading(__('Approve Order Confirmation'))
                        ->modalDescription('You are approving the order confirmation for this sale. One approved, the lead status will change to closed.')
                        ->form([
                            Forms\Components\TextInput::make('remark')
                            ->label('Remarks')
                            ->required()
                            ->placeholder('Enter remarks here...')
                            ->maxLength(500),
                        ])
                        ->action(function (ActivityLog $record, array $data) {
                            $lead = $record->lead;

                            $record->lead->update([
                                'stage' => null,
                                'remark' => $data['remark'],
                                'lead_status' => 'Closed',
                                'categories' => 'Inactive',
                                'follow_up_date' => null,
                            ]);

                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('created_at')
                                ->first();

                            if ($latestActivityLog) {
                                $latestActivityLog->update([
                                    'description' => 'Order confirmed. Client profile created',
                                ]);
                            }

                            activity()
                                ->causedBy(auth()->user())
                                ->performedOn($lead);

                            Notification::make()
                                ->title('Approved')
                                ->body('The action has been successfully approved.')
                                ->success()
                                ->send();
                        }),
                        Tables\Actions\Action::make('view_remark')
                        ->label('View Remark')
                        ->icon('heroicon-o-eye')
                        ->modalHeading('Lead Remark')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (ActivityLog $record) {
                            // Decode JSON properties safely
                            $properties = json_decode($record->properties, true);

                            // Extract the remark, fallback to '-'
                            $remark = isset($properties['attributes']['remark']) ? $properties['attributes']['remark'] : '-';

                            // Preserve line breaks and return as HTML-safe string
                            return new HtmlString(nl2br(e($remark)));
                        })
                        ->color('primary')
                        ->visible(function (ActivityLog $record) {
                            $remark = $record->lead->remark;

                            return !empty($remark);
                        }),
                    Tables\Actions\Action::make('edit_remark')
                        ->label('Edit Remark')
                        ->icon('heroicon-o-pencil')
                        ->modalHeading('Edit Lead Remark')
                        ->form(function (ActivityLog $record) {
                            $properties = json_decode($record->properties, true);
                            $remark = data_get($properties, 'attributes.remark', '');

                            return [
                                Forms\Components\Textarea::make('remark')
                                    ->label('Remarks')
                                    ->default($remark)
                                    ->autosize() // Auto-expand based on content
                                    ->required()
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('remark', strtoupper($state)))
                                    ->columnSpanFull(),
                            ];
                        })
                        ->mutateFormDataUsing(function (array $data) {
                            $data['remark'] = strip_tags($data['remark']); // Remove HTML tags for security
                            return $data;
                        })
                        ->action(function (array $data, ActivityLog $record) {
                            $properties = json_decode($record->properties, true);
                            $properties['attributes']['remark'] = $data['remark'];

                            // Save back to the database
                            $record->update([
                                'properties' => json_encode($properties),
                            ]);

                            Notification::make()
                            ->title('Remark Updated')
                            ->success()
                            ->body('The remark has been successfully updated.')
                            ->send();
                        })
                        ->color('primary')
                        ->visible(function (ActivityLog $record) {
                            $updatedAt = $record->lead->rfq_transfer_at;
                            $remark = $record->lead->remark;

                            return !empty($remark);
                            // Check if the lead_status is RFQ_TRANSFER and within 48 hours
                            return auth()->user()->role_id == 1 && !empty($remark) &&
                                Carbon::parse($updatedAt)->diffInHours(Carbon::now()) <= 48;
                        }),
                ])
                ->icon('heroicon-m-list-bullet')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button()
                ->visible(function (ActivityLog $record) {
                    $lead = $record->lead;

                    if (!$lead) {
                        return false; // No lead associated, hide the ActionGroup
                    }

                    // Get the latest ActivityLog for the related Lead
                    $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                        ->orderByDesc('created_at')
                        ->first();

                    // Check if the current record is the latest activity log
                    if ($record->id !== $latestActivityLog->id) {
                        return false; // Not the latest record, hide the ActionGroup
                    }

                    // Apply the salesperson null check only for role_id = 1
                    // if ((auth()->user()->role_id === 1 && !is_null($lead->salesperson))) {
                    //     return false; // Hide for role_id = 1 if salesperson is not null
                    // }

                    if(is_null($lead->lead_owner)){
                        return false;
                    }

                    return true;
                })
            ]);
    }
}
