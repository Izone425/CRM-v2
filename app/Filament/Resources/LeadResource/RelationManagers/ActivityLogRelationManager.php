<?php
namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Services\TemplateSelector;
use App\Classes\Encryptor;
use App\Enums\LeadCategoriesEnum;
use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Enums\QuotationStatusEnum;
use App\Mail\CancelDemoNotification;
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
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Wizard\Step;
use Filament\Notifications\Notification;
use Filament\Support\Enums\ActionSize;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\View;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Illuminate\Database\Eloquent\Builder;
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
use Livewire\Attributes\On;

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

    #[On('refresh-activity-logs')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

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
            ->poll('300s')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->recordTitleAttribute('subject_id')
            ->modifyQueryUsing(function ($query) {
                return $query->orderBy('updated_at', 'desc'); // Sort by created_at in descending order
            })
            ->filters([
                // Add the salesperson filter
                Tables\Filters\SelectFilter::make('salesperson_activities')
                ->label('Filter by Role')
                ->options([
                    'salesperson' => 'Salesperson Activities',
                    'lead_owner' => 'Lead Owner Activities',
                    'manager' => 'Manager Activities',
                    'system' => 'System Actions',
                    'all' => 'All Activities',
                ])
                ->placeholder('All Activities')
                ->default('salesperson')
                ->query(function (Builder $query, array $data) {
                    // If no filter is selected, return the original query
                    if (empty($data['value'])) {
                        return $query;
                    }

                    // Filter based on the selected option
                    return match ($data['value']) {
                        'salesperson' => $query->whereIn('causer_id', function($subQuery) {
                            $subQuery->select('id')
                                    ->from('users')
                                    ->where('role_id', 2);
                        }),
                        'lead_owner' => $query->whereIn('causer_id', function($subQuery) {
                            $subQuery->select('id')
                                    ->from('users')
                                    ->where('role_id', 1);
                        }),
                        'manager' => $query->whereIn('causer_id', function($subQuery) {
                            $subQuery->select('id')
                                    ->from('users')
                                    ->where('role_id', 3);
                        }),
                        'system' => $query->where(function ($q) {
                            $q->whereNull('causer_id')
                            ->orWhere('causer_id', 0);
                        }),
                        'all' => $query,
                        default => $query,
                    };
                })
                ->indicateUsing(function (array $data): ?string {
                    if (empty($data['value'])) {
                        return null;
                    }

                    $labels = [
                        'salesperson' => 'Salesperson Activities',
                        'lead_owner' => 'Lead Owner Activities',
                        'manager' => 'Manager Activities',
                        'system' => 'System Actions',
                        'all' => 'All Activities',
                    ];

                    return 'Role: ' . ($labels[$data['value']] ?? 'Unknown');
                })
            ])
            ->columns([
                TextColumn::make('index')
                    ->label('NO.')
                    ->rowIndex(),
                TextColumn::make('updated_at')
                    ->label('DATE & TIME')
                    ->dateTime('j M Y, H:i:s'),
                TextColumn::make('description')->label('SUBJECT'),
                // TextColumn::make('remark')->label('REMARK')
                //     ->wrap()
                //     ->limit(30)
                //     ->getStateUsing(function ($record) {
                //         $properties = json_decode($record->properties, true);
                //         $remark = isset($properties['attributes']['remark']) ? $properties['attributes']['remark'] : '-';
                //         return $remark;
                //     }),

                IconColumn::make('view_remark')
                    ->label('View Remark') // Hide label
                    ->alignCenter()
                    ->getStateUsing(fn() => true) // the column requires a state to be passed to it
                    ->icon('heroicon-o-magnifying-glass-plus') // Ensure the icon is dynamically set
                    ->color(fn () => 'blue') // Set icon color
                    ->tooltip('View Remark') // Show tooltip
                    ->extraAttributes(['class' => 'cursor-pointer']) // Make it clickable
                    ->action(
                        Action::make('view_remark_action')
                            ->label('View Remark')
                            ->modalHeading('Lead Remarks')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalDescription('Here is the remark for this lead.')
                            ->modalContent(function (\App\Models\ActivityLog $record) {
                                // Decode properties
                                $properties = is_array($record->properties)
                                    ? $record->properties
                                    : json_decode($record->properties, true);

                                $leadStatus = $properties['attributes']['lead_status'] ?? 'No status';
                                $rawRemark = $properties['attributes']['remark'] ?? null;
                                $remark = trim($rawRemark) !== '' ? $rawRemark : 'No remark available';
                                $timestamp = $record->created_at->format('d F Y');
                                $time = $record->created_at->format('H:i:s');
                                $formattedRemark = nl2br(e($remark));

                                // Get the related Lead (based on subject_id/type)
                                $lead = $record->subject_type === \App\Models\Lead::class
                                    ? \App\Models\Lead::find($record->subject_id)
                                    : null;

                                $html = "<div class='p-4 bg-gray-100 rounded'>";

                                // Header with follow up count, date and time
                                $html .= "<h3 class='text-lg'><strong>{$record->description}</strong> | Date: {$timestamp} | Time: {$time}</h3><br>";

                                // Status
                                $html .= "<p><strong>Status:</strong> {$leadStatus}</p>";

                                // Deal Amount
                                if ($lead && !is_null($lead->deal_amount)) {
                                    $formattedAmount = number_format($lead->deal_amount, 2);
                                    $html .= "<p><strong>Sales Amount:</strong> RM {$formattedAmount}</p>";
                                }

                                // Follow Up Date
                                if ($lead && !is_null($lead->follow_up_date)) {
                                    $formattedDate = \Carbon\Carbon::parse($lead->follow_up_date)->format('d F Y');
                                    $html .= "<p><strong>Next Follow Up Date:</strong> {$formattedDate}</p><br>";
                                }

                                // Remarks section
                                $html .= "<div class='mt-4'>";
                                $html .= "<h4 class='font-bold'>Follow Up Remark:</h4>";
                                $html .= "<p class='mt-2'>{$formattedRemark}</p>";
                                $html .= "</div>";

                                $html .= "</div>";

                                $html .= "</div>";

                                return new \Illuminate\Support\HtmlString($html);
                            })
                        ),

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
                // TextColumn::make('follow_up_date')->label('NEXT FOLLOW UP')
                //     ->getStateUsing(function ($record) {
                //         $properties = json_decode($record->properties, true);
                //         $followUpDate = isset($properties['attributes']['follow_up_date']) ? $properties['attributes']['follow_up_date'] : '-';
                //         return $followUpDate;
                //     }),
            ])
            ->actions([
                Action::make('updateLeadOwner')
                    ->label(__('Assign to Me'))
                    ->form([
                        Placeholder::make('')
                        ->content(__('Do you want to assign this lead to yourself? Make sure to confirm assignment before contacting the lead to avoid duplicate efforts by other team members.'))
                    ])
                    ->color('success')
                    ->size(ActionSize::Small)
                    ->button()
                    ->icon('heroicon-o-pencil-square')
                    ->visible(function (ActivityLog $record) {
                        $lead = $record->lead;

                        if (!is_null($lead->salesperson)) {
                            return false;
                        }

                        // Get the latest activity log for the given lead
                        $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                            ->orderByDesc('created_at')
                            ->first();

                        if ($latestActivityLog) {
                            // Check if the latest activity log description needs updating
                            if (str_contains($latestActivityLog->description, 'New lead created') && auth()->user()->role_id !== 2 && is_null($record->lead_owner)){
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
                                'pickup_date' => now(),
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
                                        // Round up to the next 30-minute interval
                                        $now = Carbon::now();
                                        return $now->addMinutes(30 - ($now->minute % 30))->format('H:i');
                                    })
                                    ->datalist(function (callable $get) {
                                        $user = Auth::user();
                                        $date = $get('date');

                                        if ($get('mode') === 'custom') {
                                            return [];
                                        }

                                        $times = [];
                                        $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30))->setSeconds(0);

                                        if ($user && $user->role_id == 2 && $date) {
                                            // Fetch all booked appointments as full models
                                            $appointments = Appointment::where('salesperson', $user->id)
                                                ->whereDate('date', $date)
                                                ->whereIn('status', ['New', 'Done'])
                                                ->get(['start_time', 'end_time']);

                                            for ($i = 0; $i < 48; $i++) {
                                                $slotStart = $startTime->copy();
                                                $slotEnd = $startTime->copy()->addMinutes(30);
                                                $formattedTime = $slotStart->format('H:i');

                                                $isBooked = $appointments->contains(function ($appointment) use ($slotStart, $slotEnd) {
                                                    $apptStart = Carbon::createFromFormat('H:i:s', $appointment->start_time);
                                                    $apptEnd = Carbon::createFromFormat('H:i:s', $appointment->end_time);

                                                    // Check if the slot overlaps with the appointment
                                                    return $slotStart->lt($apptEnd) && $slotEnd->gt($apptStart);
                                                });

                                                if (!$isBooked) {
                                                    $times[] = $formattedTime;
                                                }

                                                $startTime->addMinutes(30);
                                            }
                                        } else {
                                            for ($i = 0; $i < 48; $i++) {
                                                $times[] = $startTime->format('H:i');
                                                $startTime->addMinutes(30);
                                            }
                                        }

                                        return $times;
                                    })
                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                        if ($get('mode') === 'auto' && $state) {
                                            $set('end_time', Carbon::parse($state)->addHour()->format('H:i'));
                                        }
                                    }),

                                TimePicker::make('end_time')
                                    ->label('END TIME')
                                    ->required()
                                    ->seconds(false)
                                    ->reactive()
                                    ->default(function (callable $get) {
                                        $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30));
                                        return $startTime->addHour()->format('H:i');
                                    })
                                    ->datalist(function (callable $get) {
                                        $user = Auth::user();
                                        $date = $get('date');

                                        if ($get('mode') === 'custom') {
                                            return []; // Custom mode: empty list
                                        }

                                        $times = [];
                                        $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30));

                                        if ($user && $user->role_id == 2 && $date) {
                                            // Fetch booked time slots for this salesperson on the selected date
                                            $bookedAppointments = Appointment::where('salesperson', $user->id)
                                                ->whereDate('date', $date)
                                                ->pluck('end_time', 'start_time') // Start as key, End as value
                                                ->toArray();

                                            for ($i = 0; $i < 48; $i++) {
                                                $formattedTime = $startTime->format('H:i');

                                                // Check if time is booked
                                                $isBooked = collect($bookedAppointments)->contains(function ($end, $start) use ($formattedTime) {
                                                    return $formattedTime >= $start && $formattedTime <= $end;
                                                });

                                                if (!$isBooked) {
                                                    $times[] = $formattedTime;
                                                }

                                                $startTime->addMinutes(30);
                                            }
                                        } else {
                                            // Default available slots
                                            for ($i = 0; $i < 48; $i++) {
                                                $times[] = $startTime->format('H:i');
                                                $startTime->addMinutes(30);
                                            }
                                        }

                                        return $times;
                                    }),
                                ]),
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
                                        ->live()
                                        ->required()
                                        ->label('DEMO TYPE'),

                                    Select::make('appointment_type')
                                        ->options([
                                            'ONLINE' => 'ONLINE',
                                            'ONSITE' => 'ONSITE',
                                            'INHOUSE' => 'INHOUSE'
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
                                                    ->where('role_id', 2)
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

                                            $parsedDate = Carbon::parse($date)->format('Y-m-d'); // Ensure it's properly formatted
                                            $parsedStartTime = Carbon::parse($startTime)->format('H:i:s'); // Ensure proper time format
                                            $parsedEndTime = Carbon::parse($endTime)->format('H:i:s');

                                            $hasOverlap = Appointment::where('salesperson', $value)
                                                ->where('status', 'New')
                                                ->whereDate('date', $parsedDate) // Ensure date is formatted correctly
                                                ->where(function ($query) use ($parsedStartTime, $parsedEndTime) {
                                                    $query->whereBetween('start_time', [$parsedStartTime, $parsedEndTime])
                                                        ->orWhereBetween('end_time', [$parsedStartTime, $parsedEndTime])
                                                        ->orWhere(function ($query) use ($parsedStartTime, $parsedEndTime) {
                                                            $query->where('start_time', '<', $parsedStartTime)
                                                                    ->where('end_time', '>', $parsedEndTime);
                                                        });
                                                })
                                                ->exists();

                                                if ($hasOverlap) {
                                                    return true;
                                                }
                                        })
                                        ->required()
                                        ->hidden(fn () => auth()->user()->role_id === 2)
                                        ->placeholder('Select a salesperson'),
                                    ]),

                                Toggle::make('skip_notifications')
                                    ->label('Skip Email & Teams Meeting')
                                    ->helperText('Check this to create appointment without sending emails or creating a Teams meeting')
                                    ->default(false)
                                    ->inline(false),

                                Textarea::make('remarks')
                                    ->label('REMARKS')
                                    ->rows(3)
                                    ->autosize()
                                    ->reactive()
                                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

                                TextInput::make('required_attendees')
                                    ->label('Required Attendees')
                                    ->helperText('Separate each email and name pair with a semicolon (e.g., email1;email2;email3).'),
                                    // ->rules([
                                    //     'regex:/^([^;]+;[^;]+;)*([^;]+;[^;]+)$/', // Validates the email-name pairs separated by semicolons
                                    // ]),
                            ])
                            ->visible(function (ActivityLog $record) {
                                // Get the current lead data directly from the relationship
                                $lead = $record->lead;

                                // Check core conditions directly from the lead model
                                if (!$lead) {
                                    return false;
                                }

                                // First check if the category is Inactive - if so, hide the action
                                if ($lead->categories === LeadCategoriesEnum::INACTIVE->value) {
                                    return false;
                                }

                                // Check if user role is lead owner with assigned salesperson - hide if true
                                if (auth()->user()->role_id === 1 && !is_null($lead->salesperson)) {
                                    return false;
                                }

                                // Check for invalid stages - hide if found
                                if (in_array($lead->stage, [
                                    LeadStageEnum::DEMO->value,
                                    LeadStageEnum::FOLLOW_UP->value
                                ])) {
                                    return false;
                                }

                                // Check for invalid lead statuses - hide if found
                                if (in_array($lead->lead_status, [
                                    LeadStatusEnum::RFQ_FOLLOW_UP->value
                                ])) {
                                    return false;
                                }

                                // Check for specific latest activity log description
                                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                    ->orderByDesc('created_at')
                                    ->first();

                                if ($latestActivityLog &&
                                    $latestActivityLog->description === 'Demo Cancelled. 4th Demo Cancelled Follow Up') {
                                    return false;
                                }

                                // If all checks pass, show the action
                                return true;
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
                            'causer_id' => auth()->user()->id,
                            'salesperson_assigned_date' => now(),
                            'remarks' => $data['remarks'],
                            'title' => $data['type']. ' | '. $data['appointment_type']. ' | TIMETEC HR | ' . $lead->companyDetail->company_name,
                            'required_attendees' => json_encode($data['required_attendees']), // Serialize to JSON
                            // 'optional_attendees' => json_encode($data['optional_attendees']),
                            // 'location' => $data['location'] ?? null,
                            // 'details' => $data['details'],
                            // 'status' => 'New'
                        ]);
                        $appointment->save();

                        if (!($data['skip_notifications'] ?? false)) {
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

                            if ($appointment->type !== 'WEBINAR DEMO') {
                                $meetingPayload = [
                                    'start' => [
                                        'dateTime' => $startTime,
                                        'timeZone' => 'Asia/Kuala_Lumpur'
                                    ],
                                    'end' => [
                                        'dateTime' => $endTime,
                                        'timeZone' => 'Asia/Kuala_Lumpur'
                                    ],
                                    'subject' => 'TIMETEC HRMS | ' . $lead->companyDetail->company_name,
                                    'isOnlineMeeting' => true,
                                    'onlineMeetingProvider' => 'teamsForBusiness',

                                    // ✅ Add attendees only if it's NOT a WEBINAR DEMO
                                    'attendees' => [
                                        [
                                            'emailAddress' => [
                                                'address' => $lead->email, // Lead's email as required attendee
                                                'name' => $lead->name ?? 'Lead Attendee' // Fallback in case name is null
                                            ],
                                            'type' => 'required' // Required attendee
                                        ]
                                    ]
                                ];
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
                                    'subject' => 'TIMETEC HRMS | ' . $lead->companyDetail->company_name,
                                    'isOnlineMeeting' => true,
                                    'onlineMeetingProvider' => 'teamsForBusiness',
                                ];
                            }


                            try {
                                // Use the correct endpoint for app-only authentication
                                $onlineMeeting = $graph->createRequest("POST", "/users/$organizerEmail/events")
                                    ->attachBody($meetingPayload)
                                    ->setReturnType(Event::class)
                                    ->execute();

                                $appointment->update([
                                    'location' => $onlineMeeting->getOnlineMeeting()->getJoinUrl(), // Update location with meeting join URL
                                    'event_id' => $onlineMeeting->getId(),
                                ]);

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
                            $demoAppointment = $lead->demoAppointment()->latest('created_at')->first();
                            $startTime = Carbon::parse($demoAppointment->start_time);
                            $endTime = Carbon::parse($demoAppointment->end_time); // Assuming you have an end_time field
                            $formattedDate = Carbon::parse($demoAppointment->date)->format('d/m/Y');
                            $contactNo = optional($lead->companyDetail)->contact_no ?? $lead->phone;
                            $picName = optional($lead->companyDetail)->name ?? $lead->name;
                            $email = optional($lead->companyDetail)->email ?? $lead->email;

                            $appointment = $lead->demoAppointment()->latest()->first(); // Assuming a relation exists
                            if ($appointment) {
                                $appointment->update([
                                    'status' => 'New',
                                ]);
                            }

                            if ($salespersonUser && filter_var($salespersonUser->email, FILTER_VALIDATE_EMAIL)) {
                                try {
                                    $utmCampaign = $lead->utmDetail->utm_campaign ?? null;
                                    $templateSelector = new TemplateSelector();

                                    if ($lead->lead_code && (
                                        str_contains($lead->lead_code, '(CN)') ||
                                        str_contains($lead->lead_code, 'CN')
                                    )) {
                                        // Use CN templates
                                        $template = $templateSelector->getTemplateByLeadSource('CN', 0);
                                    } else {
                                        // Use regular templates based on UTM campaign
                                        $template = $templateSelector->getTemplate($utmCampaign, 0); // first follow-up
                                    }

                                    $viewName = $template['email'] ?? 'emails.demo_notification'; // fallback
                                    $leadowner = User::where('name', $lead->lead_owner)->first();

                                    $emailContent = [
                                        'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager', // Lead Owner/Manager Name
                                        'lead' => [
                                            'lastName' => $lead->companyDetail->name ?? $lead->name, // Lead's Last Name
                                            'company' => $lead->companyDetail->company_name ?? 'N/A', // Lead's Company
                                            'salespersonName' => $salespersonUser->name ?? 'N/A',
                                            'salespersonPhone' => $salespersonUser->mobile_number ?? 'N/A',
                                            'salespersonEmail' => $salespersonUser->email ?? 'N/A',
                                            'salespersonMeetingLink' => $salespersonUser->msteam_link ?? 'N/A',
                                            'phone' =>$contactNo ?? 'N/A',
                                            'pic' => $picName ?? 'N/A',
                                            'email' => $email ?? 'N/A',
                                            'date' => $formattedDate ?? 'N/A',
                                            'startTime' => $startTime ?? 'N/A',
                                            'endTime' => $endTime ?? 'N/A',
                                            'meetingLink' => $onlineMeeting->getOnlineMeeting()->getJoinUrl() ?? 'N/A',
                                            'position' => $salespersonUser->position ?? 'N/A', // position
                                            'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                                            'demo_type' => $appointment->type,
                                            'appointment_type' => $appointment->appointment_type
                                        ],
                                    ];

                                    $email = $lead->companyDetail->email ?? $lead->email;
                                    $demoAppointment = $lead->demoAppointment()->latest()->first(); // Adjust based on your relationship type

                                    $requiredAttendees = $demoAppointment->required_attendees ?? null;

                                    // Parse attendees' emails if not null
                                    $attendeeEmails = [];
                                    if (!empty($requiredAttendees)) {
                                        $cleanedAttendees = str_replace('"', '', $requiredAttendees);
                                        $attendeeEmails = array_filter(array_map('trim', explode(';', $cleanedAttendees))); // Ensure no empty spaces
                                    }

                                    // Get Lead's Email (Primary recipient)
                                    $leadEmail = $lead->companyDetail->email ?? $lead->email;

                                    // Get Salesperson Email
                                    $salespersonId = $lead->salesperson;
                                    $salesperson = User::find($salespersonId);
                                    $salespersonEmail = $salesperson->email ?? null; // Prevent null errors

                                    // Get Lead Owner Email
                                    $leadownerName = $lead->lead_owner;
                                    $leadowner = User::where('name', $leadownerName)->first();
                                    $leadOwnerEmail = $leadowner->email ?? null; // Prevent null errors

                                    // Combine CC recipients
                                    $ccEmails = array_filter(array_merge([$salespersonEmail, $leadOwnerEmail], $attendeeEmails), function ($email) {
                                        return filter_var($email, FILTER_VALIDATE_EMAIL); // Validate email format
                                    });

                                    // Send email only if valid
                                    if (!empty($leadEmail)) {
                                        $mail = Mail::to($leadEmail); // ✅ Lead is the main recipient

                                        if (!empty($ccEmails)) {
                                            $mail->cc($ccEmails); // ✅ Others are in CC, so they can see each other
                                        }

                                        $mail->send(new DemoNotification($emailContent, $viewName));

                                        info("Email sent successfully to: " . $leadEmail . " and CC to: " . implode(', ', $ccEmails));
                                    } else {
                                        Log::error("No valid lead email found for sending DemoNotification.");
                                    }
                                } catch (\Exception $e) {
                                    // Handle email sending failure
                                    Log::error("Email sending failed for salesperson: " . ($data['salesperson'] ?? auth()->user()->name) . ", Error: {$e->getMessage()}");
                                }
                            }
                        }

                        if ($lead->stage !== 'Follow Up') {
                            $lead->update([
                                'categories' => 'Active',
                                'stage' => 'Demo',
                                'lead_status' => 'Demo-Assigned',
                                'follow_up_date' => $data['date'],
                                'demo_appointment' => $appointment->id,
                                'remark' => $data['remarks'],
                                'salesperson' => $data['salesperson'] ?? auth()->user()->id,
                                'salesperson_assigned_date' => now(),
                                'follow_up_counter' => true,
                            ]);
                        } else {
                            $lead->update([
                                'follow_up_date' => $data['date'],
                                'demo_appointment' => $appointment->id,
                                'remark' => $data['remarks'],
                                'salesperson' => $data['salesperson'] ?? auth()->user()->id,
                                'salesperson_assigned_date' => now(),
                                'follow_up_counter' => true,
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
                    }),
                    Tables\Actions\Action::make('addRFQ')
                        ->label(__('Add RFQ'))
                        ->visible(function (ActivityLog $record) {
                            // Get the current lead data directly from the relationship
                            $lead = $record->lead;

                            // Check core conditions directly from the lead model
                            if (!$lead) {
                                return false;
                            }

                            // First check if the category is Inactive - if so, hide the action
                            if ($lead->categories === LeadCategoriesEnum::INACTIVE->value) {
                                return false;
                            }

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

                            // Check all conditions against the current lead data
                            return !in_array($lead->lead_status, $invalidLeadStatuses) &&
                                !in_array($lead->stage, $invalidStages) &&
                                $lead->salesperson == null;
                        })
                        ->form([
                            Placeholder::make('')
                            ->content(__('You are marking this lead as "RFQ" and assigning it to a salesperson. Confirm?')),

                            Select::make('salesperson')
                                ->label('Salesperson')
                                ->options(function ($record) {
                                    $user = auth()->user();

                                    // Ensure we have a record and get the assigned salesperson
                                    $leadSalespersonId = $record->lead?->salesperson;

                                    // If the lead has an assigned salesperson, show only that salesperson
                                    if ($leadSalespersonId) {
                                        return User::where('id', $leadSalespersonId)
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    }

                                    // If no salesperson is assigned, return options based on the user's role
                                    if ($user->role_id == 3) {
                                        return User::where('role_id', 2)
                                            ->pluck('name', 'id')
                                            ->toArray();
                                    } else {
                                        return User::where('role_id', 2)
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
                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
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
                                'rfq_transfer_at' => now(),
                                'follow_up_counter' => true,
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
                                        'salespersonName' => $salespersonUser->name, // Salesperson's Name
                                        'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager', // Lead Owner/Manager Name
                                        'lead' => [
                                            'lead_code' => isset($lead->lead_code) ? 'https://crm.timeteccloud.com:8082/demo-request/' . $lead->lead_code : 'N/A',
                                            'lastName' => $lead->companyDetail->name ?? $lead->name,
                                            'company' => $lead->companyDetail->company_name ?? 'N/A',
                                            'companySize' => $lead->company_size ?? 'N/A', // Company Size
                                            'phone' => $lead->companyDetail->phone ?? $lead->phone,
                                            'email' => $lead->companyDetail->email ?? $lead->email,
                                            'country' => $lead->country ?? 'N/A', // Lead's Country
                                            'products' => $lead->products ?? 'N/A', // Products
                                            // 'solutions' => $lead->solutions ?? 'N/A', // Solutions
                                        ],
                                        'remark' => $data['remark'] ?? 'No remarks provided', // Custom Remark
                                        'formatted_products' => $lead->formatted_products, // Add formatted products
                                    ];
                                    // Send the email with the appropriate template view
                                    Mail::to([$salespersonUser->email, $leadowner->email]) // Send to both
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

                            Grid::make(3) // 2 columns grid
                                ->schema([
                                    DatePicker::make('follow_up_date')
                                        ->label('Next Follow Up Date')
                                        ->required()
                                        ->placeholder('Select a follow-up date')
                                        ->default(fn ($record) =>
                                            optional(optional($record)->lead?->follow_up_date)->addDays(7) ?? now()->addDays(7)
                                        )
                                        ->reactive(),
                                        // ->minDate(fn ($record) => $record->lead->follow_up_date ? Carbon::parse($record->lead->follow_up_date)->startOfDay() : now()->startOfDay()) // Ensure it gets from DB

                                    Select::make('status')
                                        ->label('STATUS')
                                        ->options([
                                            'Hot' => 'Hot',
                                            'Warm' => 'Warm',
                                            'Cold' => 'Cold'
                                        ])
                                        ->default('hot')
                                        ->required()
                                        ->visible(fn (ActivityLog $record) => in_array(Auth::user()->role_id, [2, 3]) && $record->lead->stage === 'Follow Up'),
                                ])
                            ])
                        ->color('success')
                        ->icon('heroicon-o-pencil-square')
                        ->action(function (ActivityLog $activityLog, array $data, Component $livewire) {
                            // Retrieve the related Lead model from ActivityLog
                            $lead = $activityLog->lead;

                            // Check if follow_up_date exists in the $data array; if not, set it to next Tuesday
                            $followUpDate = $data['follow_up_date'] ?? now()->next(Carbon::TUESDAY);
                            // if($lead->lead_status === 'New' || $lead->lead_status === 'Under Review'){

                                $updateData = [
                                    'follow_up_date' => $followUpDate,
                                    'remark' => $data['remark'],
                                    'follow_up_needed' => 0,
                                    'follow_up_counter' => true,
                                    'manual_follow_up_count' => $lead->manual_follow_up_count + 1
                                ];

                                // Only update 'status' if it exists in $data
                                if (isset($data['status'])) {
                                    $updateData['lead_status'] = $data['status'];
                                }

                                $lead->update($updateData);

                                if(auth()->user()->role_id == 1){
                                    $role = 'Lead Owner';
                                }else if(auth()->user()->role_id == 2){
                                    $role = 'Salesperson';
                                }else{
                                    $role = 'Manager';
                                }

                                // Increment the follow-up count for the new description
                                $followUpDescription = $role .' Follow Up '. $lead->manual_follow_up_count;

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
                        }),
                    Tables\Actions\Action::make('addAutomation')
                        ->label(__('Add Automation'))
                        ->color('primary')
                        ->icon('heroicon-o-cog-8-tooth')
                        ->visible(function (ActivityLog $record) {
                            // Get the current lead data directly from the relationship
                            $lead = $record->lead;

                            // Check core conditions directly from the lead model
                            if (!$lead) {
                                return false;
                            }

                            // First check if category is Inactive - if so, hide the action
                            if ($lead->categories === LeadCategoriesEnum::INACTIVE->value) {
                                return false;
                            }

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

                            // Check conditions against current lead data
                            return auth()->user()->role_id !== 2 &&
                                $lead->follow_up_needed == 0 &&
                                !in_array($lead->lead_status, $invalidLeadStatuses) &&
                                !in_array($lead->stage, $invalidStages);
                        })
                        ->form([
                            Placeholder::make('confirmation')
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

                            $utmCampaign = $lead->utmDetail->utm_campaign ?? null;
                            $templateSelector = new TemplateSelector();

                            if ($lead->lead_code && (
                                str_contains($lead->lead_code, '(CN)') ||
                                str_contains($lead->lead_code, 'CN')
                            )) {
                                // Use CN templates
                                $template = $templateSelector->getTemplateByLeadSource('CN', 1);
                            } else {
                                // Use regular templates based on UTM campaign
                                $template = $templateSelector->getTemplate($utmCampaign, 1); // first follow-up
                            }

                            $viewName = $template['email'] ?? 'emails.email_blasting_1st';
                            $contentTemplateSid = $template['sid'];
                            $followUpDescription = '1st Automation Follow Up';
                            try {
                                $leadowner = User::where('name', $lead->lead_owner)->first();

                                $emailContent = [
                                    'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager',
                                    'leadOwnerEmail' => $leadowner->email ?? 'N/A',
                                    'lead' => [
                                        'lastName' => $lead->name ?? 'N/A',
                                        'company' => $lead->companyDetail->company_name ?? 'N/A',
                                        'companySize' => $lead->company_size ?? 'N/A',
                                        'phone' => $lead->phone ?? 'N/A',
                                        'email' => $lead->email ?? 'N/A',
                                        'country' => $lead->country ?? 'N/A',
                                        'products' => $lead->products ?? 'N/A',
                                        'position' => $leadowner->position ?? 'N/A',
                                        'companyName' => $lead->companyDetail->company_name ?? 'Unknown Company',
                                        'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                                    ],
                                ];

                                Mail::to($lead->companyDetail->email ?? $lead->email)
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

                            try {
                                $phoneNumber = $lead->companyDetail->contact_no ?? $lead->phone;

                                $isChinese = $lead->lead_code && (
                                    str_contains($lead->lead_code, '(CN)') ||
                                    str_contains($lead->lead_code, 'CN')
                                );

                                // Set variables based on language
                                if ($isChinese) {
                                    // Chinese templates only need one variable for the salesperson's name
                                    $variables = [$lead->lead_owner]; // Only the lead's name for Chinese template
                                } else {
                                    // Regular templates need both lead name and lead owner
                                    $variables = [$lead->name, $lead->lead_owner];
                                }

                                $whatsappController = new \App\Http\Controllers\WhatsAppController();
                                $whatsappController->sendWhatsAppTemplate($phoneNumber, $contentTemplateSid, $variables);
                            } catch (\Exception $e) {
                                Log::error("WhatsApp Error: {$e->getMessage()}");
                            }
                        }),
                    Tables\Actions\Action::make('send_quotation_template')
                        ->label('Send Quotation Request')
                        ->color('info')
                        ->icon('heroicon-o-paper-airplane')
                        ->modalHeading('Send Request Quotation Message')
                        ->modalDescription('This will send a WhatsApp message to request additional details for quotation from the lead.')
                        ->requiresConfirmation()
                        ->action(function (ActivityLog $activityLog) {
                            $lead = $activityLog->lead;

                            // Get phone number from lead
                            $phoneNumber = $lead->companyDetail->contact_no ?? $lead->phone;

                            if (empty($phoneNumber)) {
                                Notification::make()
                                    ->title('Missing Phone Number')
                                    ->body('No valid phone number found for this lead.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Get recipient name
                            $recipientName = $lead->companyDetail->name ?? $lead->name;

                            $isChinese = $lead->lead_code && (
                                str_contains($lead->lead_code, '(CN)') ||
                                str_contains($lead->lead_code, 'CN')
                            );

                            // Set template SID based on lead type
                            $contentTemplateSid = $isChinese
                                ? 'HXf040c1812afbe8b0de069b4301bd31f8'  // Chinese template
                                : 'HX7f5ccf2114ed839e0291c70aa25c9df8'; // Default template

                            // Set up variables for the template
                            $variables = [$recipientName];

                            try {
                                $whatsappController = new \App\Http\Controllers\WhatsAppController();
                                $whatsappController->sendWhatsAppTemplate($phoneNumber, $contentTemplateSid, $variables);

                                Notification::make()
                                    ->title('Message Sent')
                                    ->body('Request details WhatsApp message has been sent successfully.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('WhatsApp Request Details Error: ' . $e->getMessage());

                                Notification::make()
                                    ->title('Failed to Send Message')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(function (ActivityLog $record) {
                            // First check user role - only show for Lead Owners (1) and Managers (3)
                            if (!in_array(auth()->user()->role_id, [1, 3])) {
                                return false;
                            }

                            $lead = $record->lead;

                            // Check if lead exists and has a valid phone number
                            if (!$lead || empty($lead->companyDetail->contact_no ?? $lead->phone)) {
                                return false;
                            }

                            // Get the latest activity log for the lead
                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('updated_at')
                                ->first();

                            // Only show on the latest activity log entry
                            if ($record->id !== $latestActivityLog->id) {
                                return false;
                            }

                            // Show for active leads that aren't in demo or follow-up stages
                            return $lead->categories !== 'Inactive' &&
                                !in_array($lead->stage, ['Demo', 'Follow Up']);
                        }),
                    Tables\Actions\Action::make('send_request_details')
                        ->label('Request Company Details')
                        ->color('info')
                        ->icon('heroicon-o-paper-airplane')
                        ->modalHeading('Send Request Details WhatsApp Message')
                        ->modalDescription('This will send a WhatsApp message to request additional details from the lead.')
                        ->requiresConfirmation()
                        ->action(function (ActivityLog $activityLog) {
                            $lead = $activityLog->lead;

                            // Get phone number from lead
                            $phoneNumber = $lead->companyDetail->contact_no ?? $lead->phone;

                            if (empty($phoneNumber)) {
                                Notification::make()
                                    ->title('Missing Phone Number')
                                    ->body('No valid phone number found for this lead.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Get recipient name
                            $recipientName = $lead->companyDetail->name ?? $lead->name;

                            $isChinese = $lead->lead_code && (
                                str_contains($lead->lead_code, '(CN)') ||
                                str_contains($lead->lead_code, 'CN')
                            );

                            // Set template SID based on lead type
                            $contentTemplateSid = $isChinese
                                ? 'HXfa73af7acacd48a0d09ffc3ded7c6e43'  // Chinese template
                                : 'HX04b20de70eaa3c6754846d1d5d94658e'; // Default template

                            // Set up variables for the template
                            $variables = [$recipientName];

                            try {
                                $whatsappController = new \App\Http\Controllers\WhatsAppController();
                                $whatsappController->sendWhatsAppTemplate($phoneNumber, $contentTemplateSid, $variables);

                                Notification::make()
                                    ->title('Message Sent')
                                    ->body('Request details WhatsApp message has been sent successfully.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('WhatsApp Request Details Error: ' . $e->getMessage());

                                Notification::make()
                                    ->title('Failed to Send Message')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(function (ActivityLog $record) {
                            // First check user role - only show for Lead Owners (1) and Managers (3)
                            if (!in_array(auth()->user()->role_id, [1, 3])) {
                                return false;
                            }

                            $lead = $record->lead;

                            // Check if lead exists and has a valid phone number
                            if (!$lead || empty($lead->companyDetail->contact_no ?? $lead->phone)) {
                                return false;
                            }

                            // Get the latest activity log for the lead
                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('updated_at')
                                ->first();

                            // Only show on the latest activity log entry
                            if ($record->id !== $latestActivityLog->id) {
                                return false;
                            }

                            // Show for active leads that aren't in demo or follow-up stages
                            return $lead->categories !== 'Inactive' &&
                                !in_array($lead->stage, ['Demo', 'Follow Up']);
                        }),
                    Tables\Actions\Action::make('send_demo_selection')
                        ->label('Demo Selection')
                        ->color('info')
                        ->icon('heroicon-o-paper-airplane')
                        ->modalHeading('Send Demo Selection WhatsApp Message')
                        ->modalDescription('This will send available demo time slots to the lead.')
                        ->visible(function (ActivityLog $record) {
                            // First check user role - only show for Lead Owners (1) and Managers (3)
                            if (!in_array(auth()->user()->role_id, [1, 3])) {
                                return false;
                            }

                            $lead = $record->lead;

                            // Check if lead exists and has a valid phone number
                            if (!$lead || empty($lead->companyDetail->contact_no ?? $lead->phone)) {
                                return false;
                            }

                            // Get the latest activity log for the lead
                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('updated_at')
                                ->first();

                            // Only show on the latest activity log entry
                            if ($record->id !== $latestActivityLog->id) {
                                return false;
                            }

                            // Show for active leads that aren't in demo or follow-up stages
                            return $lead->categories !== 'Inactive' &&
                                !in_array($lead->stage, ['Demo', 'Follow Up']);
                        })
                        ->form([
                            Select::make('demo_type')
                                ->label('Demo Type')
                                ->options([
                                    'online' => 'Online',
                                    'onsite' => 'Onsite',
                                ])
                                ->required()
                                ->default('online'),

                            Select::make('num_days')
                                ->label('Number of Days to Offer')
                                ->options([
                                    '1' => '1 Day',
                                    '2' => '2 Days',
                                    '3' => '3 Days',
                                ])
                                ->required()
                                ->default('2')
                                ->reactive(),

                            Grid::make(4)
                                ->schema([
                                    DatePicker::make('date_1')
                                        ->label('First Day')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d M Y')
                                        ->weekStartsOnMonday()
                                        ->minDate(now())
                                        ->default(fn() => now()->nextWeekday())
                                        ->columnSpan(1),

                                    Repeater::make('slots_1')
                                        ->label('Time Slots - First Day')
                                        ->grid(3)
                                        ->columnSpan(3)
                                        ->schema([
                                            TimePicker::make('time')
                                                ->hiddenLabel()
                                                ->seconds(false)
                                                ->required()
                                                ->datalist(function (callable $get) {
                                                    $date = $get('../date_1'); // Get selected date
                                                    if (!$date) {
                                                        return [];
                                                    }

                                                    $times = [];
                                                    $startTime = Carbon::parse($date)->setHour(9)->setMinute(0);
                                                    $endTime = Carbon::parse($date)->setHour(17)->setMinute(30);

                                                    $user = Auth::user();

                                                    // If user is a salesperson, check availability
                                                    if ($user && $user->role_id == 2) {
                                                        // Fetch booked appointments for this date
                                                        $appointments = Appointment::where('salesperson', $user->id)
                                                            ->whereDate('date', $date)
                                                            ->whereIn('status', ['New', 'Done'])
                                                            ->get(['start_time', 'end_time']);

                                                        // Generate available time slots
                                                        while ($startTime <= $endTime) {
                                                            $slotStart = $startTime->copy();
                                                            $slotEnd = $startTime->copy()->addMinutes(30);
                                                            $formattedTime = $slotStart->format('H:i');

                                                            $isBooked = $appointments->contains(function ($appointment) use ($slotStart, $slotEnd) {
                                                                $apptStart = Carbon::createFromFormat('H:i:s', $appointment->start_time);
                                                                $apptEnd = Carbon::createFromFormat('H:i:s', $appointment->end_time);

                                                                return $slotStart->lt($apptEnd) && $slotEnd->gt($apptStart);
                                                            });

                                                            if (!$isBooked) {
                                                                $times[] = $formattedTime;
                                                            }

                                                            $startTime->addMinutes(30);
                                                        }
                                                    } else {
                                                        // Standard time slots for non-salespersons
                                                        while ($startTime <= $endTime) {
                                                            $times[] = $startTime->format('H:i');
                                                            $startTime->addMinutes(30);
                                                        }
                                                    }

                                                    return $times;
                                                })
                                        ])
                                        ->minItems(1)
                                        ->maxItems(3)
                                        ->defaultItems(3)
                                        ->columns(1)
                                        ->reorderable(false)
                                        ->default([
                                            ['time' => '10:00'],
                                            ['time' => '14:30'],
                                            ['time' => '16:00']
                                        ])
                                ]),

                            Grid::make(4)
                                ->schema([
                                    DatePicker::make('date_2')
                                        ->label('Second Day')
                                        ->required()
                                        ->native(false)
                                        ->displayFormat('d M Y')
                                        ->weekStartsOnMonday()
                                        ->minDate(now()->addDay())
                                        ->columnSpan(1)
                                        ->default(fn() => now()->nextWeekday()->addDay()),

                                    Repeater::make('slots_2')
                                        ->label('Time Slots - Second Day')
                                        ->grid(3)
                                        ->columnSpan(3)
                                        ->schema([
                                            TimePicker::make('time')
                                                ->hiddenLabel()
                                                ->seconds(false)
                                                ->required()
                                                ->datalist(function (callable $get) {
                                                    $date = $get('../date_2');
                                                    if (!$date) {
                                                        return [];
                                                    }

                                                    $times = [];
                                                    $startTime = Carbon::parse($date)->setHour(9)->setMinute(0);
                                                    $endTime = Carbon::parse($date)->setHour(17)->setMinute(30);

                                                    $user = Auth::user();

                                                    if ($user && $user->role_id == 2) {
                                                        $appointments = Appointment::where('salesperson', $user->id)
                                                            ->whereDate('date', $date)
                                                            ->whereIn('status', ['New', 'Done'])
                                                            ->get(['start_time', 'end_time']);

                                                        while ($startTime <= $endTime) {
                                                            $slotStart = $startTime->copy();
                                                            $slotEnd = $startTime->copy()->addMinutes(30);
                                                            $formattedTime = $slotStart->format('H:i');

                                                            $isBooked = $appointments->contains(function ($appointment) use ($slotStart, $slotEnd) {
                                                                $apptStart = Carbon::createFromFormat('H:i:s', $appointment->start_time);
                                                                $apptEnd = Carbon::createFromFormat('H:i:s', $appointment->end_time);

                                                                return $slotStart->lt($apptEnd) && $slotEnd->gt($apptStart);
                                                            });

                                                            if (!$isBooked) {
                                                                $times[] = $formattedTime;
                                                            }

                                                            $startTime->addMinutes(30);
                                                        }
                                                    } else {
                                                        while ($startTime <= $endTime) {
                                                            $times[] = $startTime->format('H:i');
                                                            $startTime->addMinutes(30);
                                                        }
                                                    }

                                                    return $times;
                                                })
                                        ])
                                        ->minItems(1)
                                        ->maxItems(3)
                                        ->defaultItems(3)
                                        ->columns(1)
                                        ->reorderable(false)
                                        ->default([
                                            ['time' => '10:00'],
                                            ['time' => '14:30'],
                                            ['time' => '16:00']
                                        ]),
                                ])
                                ->visible(fn (callable $get) => $get('num_days') >= 2),

                            Grid::make(4)
                                ->schema([
                                    DatePicker::make('date_3')
                                        ->label('Third Day')
                                        ->native(false)
                                        ->displayFormat('d M Y')
                                        ->required()
                                        ->weekStartsOnMonday()
                                        ->minDate(now()->addDays(2))
                                        ->columnSpan(1)
                                        ->default(fn() => now()->nextWeekday()->addDays(2)),

                                    Repeater::make('slots_3')
                                        ->label('Time Slots - Third Day')
                                        ->schema([
                                            TimePicker::make('time')
                                                ->hiddenLabel()
                                                ->seconds(false)
                                                ->required()
                                                ->datalist(function (callable $get) {
                                                    $date = $get('../date_3');
                                                    if (!$date) {
                                                        return [];
                                                    }

                                                    $times = [];
                                                    $startTime = Carbon::parse($date)->setHour(9)->setMinute(0);
                                                    $endTime = Carbon::parse($date)->setHour(17)->setMinute(30);

                                                    $user = Auth::user();

                                                    if ($user && $user->role_id == 2) {
                                                        $appointments = Appointment::where('salesperson', $user->id)
                                                            ->whereDate('date', $date)
                                                            ->whereIn('status', ['New', 'Done'])
                                                            ->get(['start_time', 'end_time']);

                                                        while ($startTime <= $endTime) {
                                                            $slotStart = $startTime->copy();
                                                            $slotEnd = $startTime->copy()->addMinutes(30);
                                                            $formattedTime = $slotStart->format('H:i');

                                                            $isBooked = $appointments->contains(function ($appointment) use ($slotStart, $slotEnd) {
                                                                $apptStart = Carbon::createFromFormat('H:i:s', $appointment->start_time);
                                                                $apptEnd = Carbon::createFromFormat('H:i:s', $appointment->end_time);

                                                                return $slotStart->lt($apptEnd) && $slotEnd->gt($apptStart);
                                                            });

                                                            if (!$isBooked) {
                                                                $times[] = $formattedTime;
                                                            }

                                                            $startTime->addMinutes(30);
                                                        }
                                                    } else {
                                                        while ($startTime <= $endTime) {
                                                            $times[] = $startTime->format('H:i');
                                                            $startTime->addMinutes(30);
                                                        }
                                                    }

                                                    return $times;
                                                })
                                        ])
                                        ->minItems(1)
                                        ->grid(3)
                                        ->columnSpan(3)
                                        ->maxItems(3)
                                        ->defaultItems(3)
                                        ->columns(1)
                                        ->reorderable(false)
                                        ->default([
                                            ['time' => '10:00'],
                                            ['time' => '14:30'],
                                            ['time' => '16:00']
                                        ]),
                                ])
                                ->visible(fn (callable $get) => $get('num_days') >= 3),
                        ])
                        ->action(function (ActivityLog $activityLog, array $data) {
                            $lead = $activityLog->lead;

                            // Get phone number from lead
                            $phoneNumber = $lead->companyDetail->contact_no ?? $lead->phone;

                            if (empty($phoneNumber)) {
                                Notification::make()
                                    ->title('Missing Phone Number')
                                    ->body('No valid phone number found for this lead.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Get recipient name
                            $recipientName = $lead->companyDetail->name ?? $lead->name;

                            // Format slots for each day
                            // First day (always present)
                            $date1 = \Carbon\Carbon::parse($data['date_1'])->format('d/m (l)');

                            // Format time slots with AM/PM
                            $slots1 = collect($data['slots_1'])->pluck('time')->map(function($time) {
                                return \Carbon\Carbon::parse($time)->format('g:i A');
                            })->toArray();
                            $formattedSlots1 = implode(' / ', $slots1);
                            $day1 = "$date1 - $formattedSlots1";

                            // Second day (only if num_days >= 2)
                            $day2 = null;
                            if ((int)$data['num_days'] >= 2) {
                                $date2 = \Carbon\Carbon::parse($data['date_2'])->format('d/m (l)');
                                $slots2 = collect($data['slots_2'])->pluck('time')->map(function($time) {
                                    return \Carbon\Carbon::parse($time)->format('g:i A');
                                })->toArray();
                                $formattedSlots2 = implode(' / ', $slots2);
                                $day2 = "$date2 - $formattedSlots2";
                            }

                            // Third day (only if num_days >= 3)
                            $day3 = null;
                            if ((int)$data['num_days'] >= 3) {
                                $date3 = \Carbon\Carbon::parse($data['date_3'])->format('d/m (l)');
                                $slots3 = collect($data['slots_3'])->pluck('time')->map(function($time) {
                                    return \Carbon\Carbon::parse($time)->format('g:i A');
                                })->toArray();
                                $formattedSlots3 = implode(' / ', $slots3);
                                $day3 = "$date3 - $formattedSlots3";
                            }

                            $isChinese = $lead->lead_code && (
                                str_contains($lead->lead_code, '(CN)') ||
                                str_contains($lead->lead_code, 'CN')
                            );

                            if ($isChinese) {
                                // Chinese template
                                $contentTemplateSid = 'HX99cd275a009cf38322ede220d81be784';

                                // For Chinese templates, we exclude the recipient name
                                // and only include the day slots
                                $variables = [
                                    $day1,
                                    $day2 ?? ' ',  // Send empty string if day2 is null
                                    $day3 ?? ' '   // Send empty string if day3 is null
                                ];
                            } else {
                                // Regular template
                                $contentTemplateSid = 'HX8ffc6fd8b995859aa28fa59ba9712529';

                                // For regular templates, we include the recipient name
                                $variables = [
                                    $recipientName,
                                    $day1,
                                    $day2 ?? ' ',  // Send empty string if day2 is null
                                    $day3 ?? ' '   // Send empty string if day3 is null
                                ];
                            }

                            try {
                                $whatsappController = new \App\Http\Controllers\WhatsAppController();
                                $response = $whatsappController->sendWhatsAppTemplate($phoneNumber, $contentTemplateSid, $variables);

                                Notification::make()
                                    ->title('Message Sent')
                                    ->body('Demo selection options have been sent successfully.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error('WhatsApp Demo Selection Error', [
                                    'error' => $e->getMessage(),
                                    'phone' => $phoneNumber,
                                    'templateId' => $contentTemplateSid,
                                    'variables' => $variables
                                ]);

                                Notification::make()
                                    ->title('Failed to Send Message')
                                    ->body('Error: ' . $e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Tables\Actions\Action::make('archive')
                        ->label(__('Archive'))
                        ->modalHeading('Mark Lead as Inactive')
                        ->color('warning')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(function (ActivityLog $record) {
                            // Get the lead directly from the relationship
                            $lead = $record->lead;

                            // Return false if lead doesn't exist
                            if (!$lead) {
                                return false;
                            }

                            return auth()->user()->role_id === 1 &&
                                   is_null($lead->salesperson) &&
                                   $lead->categories !== LeadCategoriesEnum::INACTIVE->value;
                        })
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
                                            'Closed' => 'Closed'
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
                                ->maxLength(500)
                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
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
                                activity()
                                    ->causedBy(auth()->user())
                                    ->performedOn($lead)
                                    ->log('Lead marked as inactive.');

                                sleep(1);

                                $latestActivityLog->update([
                                    'description' => 'Marked as ' . $statusLabel . ': ' . $reasonText, // New description
                                ]);
                            }

                            Notification::make()
                                ->title('Lead Archived')
                                ->success()
                                ->body('You have successfully marked the lead as inactive.')
                                ->send();
                        }),
                    // Tables\Actions\Action::make('quotation')
                    //     ->label(__('Add Quotation'))
                    //     ->color('success')
                    //     ->icon('heroicon-o-pencil-square')
                    //     ->visible(function (ActivityLog $record) {
                    //         $lead = $record->lead;

                    //         return (auth()->user()->role_id !== 1 && !is_null($lead->salesperson)) &&
                    //             ($lead->lead_status === LeadStatusEnum::RFQ_FOLLOW_UP->value
                    //             || $lead->lead_status === LeadStatusEnum::RFQ_TRANSFER->value);
                    //     })
                    //     ->requiresConfirmation()
                    //     ->modalDescription('Did you create the quotation under Mr Wee Quotation System?')
                    //     ->action(function (ActivityLog $record) {
                    //         $lead = $record->lead;
                    //         if ($lead) {
                    //             if ($lead->lead_status === 'RFQ-Transfer') {
                    //                 $lead->update([
                    //                     'lead_status' => 'Pending Demo',
                    //                     'remark' => null,
                    //                     'follow_up_date' => today(),
                    //                 ]);
                    //             }else if($lead->lead_status === 'RFQ-Follow Up'){
                    //                 $lead->update([
                    //                     'lead_status' => 'Hot',
                    //                     'remark' => null,
                    //                     'follow_up_date' => today(),
                    //                 ]);
                    //             }
                    //         }

                    //         $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                    //             ->orderByDesc('created_at')
                    //             ->first();

                    //         $latestActivityLog->update([
                    //             'description' => 'Quotation Added',
                    //         ]);

                    //         Notification::make()
                    //             ->title('Quotation Added')
                    //             ->success()
                    //             ->send();
                    //     }),
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
                    Tables\Actions\Action::make('noResponse')
                        ->label(__('No Response'))
                        ->modalHeading('Mark Lead as No Response')
                        ->form([
                            Placeholder::make('')
                            ->content(__('You are making this lead as No Response after multiple follow-ups. Confirm?')),

                            TextInput::make('remark')
                            ->label('Remarks')
                            ->required()
                            ->placeholder('Enter remarks here...')
                            ->maxLength(500)
                            ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
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
                            Placeholder::make('')
                            ->content(__('Are you sure you want to reactive this lead? This action will move the lead back to active status for further follow-ups and actions.')),

                            TextInput::make('remark')
                            ->label('Remarks')
                            ->required()
                            ->placeholder('Enter remarks here...')
                            ->maxLength(500)
                            ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
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
                                    'salesperson' => auth()->user()->id,
                                    'salesperson_assigned_date' => now(),
                                ]);

                                // Update the latest ActivityLog for role_id = 2
                                $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                    ->orderByDesc('created_at')
                                    ->first();

                                $latestActivityLog->update([
                                    'description' => 'Reactive. Lead assigned to Salesperson: ' . auth()->user()->name . '. RFQ only',
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
                                    'description' => 'Reactive. Lead assigned to Lead Owner: ' . auth()->user()->name,
                                ]);

                                Notification::make()
                                    ->title('You have reactivated a lead')
                                    ->success()
                                    ->send();
                            }
                        })
                        ->visible(function (ActivityLog $record) {
                            $lead = $record->lead;

                            return $lead
                                && $lead->categories === 'Inactive'
                                && $lead->lead_status === 'No Response';
                        }),

                    Tables\Actions\Action::make('rearchive')
                        ->visible(function (ActivityLog $record) {
                            $status = optional($record->lead)->lead_status;

                            return in_array($status, ['On Hold', 'Lost', 'Junk', 'Closed'])
                                && optional($record->lead)->categories === 'Inactive';
                        })
                        ->label(__('Rearchive'))
                        ->modalHeading('Reactivate Lead')
                        ->form([
                            Placeholder::make('')
                                ->content(__('Are you sure you want to reactivate this lead? This action will move the lead back to its previous status before being archived.')),

                            TextInput::make('remark')
                                ->label('Remarks')
                                ->required()
                                ->placeholder('Enter remarks here...')
                                ->maxLength(500)
                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
                        ])
                        ->color('danger')
                        ->icon('heroicon-o-pencil-square')
                        ->action(function (ActivityLog $activityLog, array $data) {
                            $lead = $activityLog->lead;

                            $previousData = data_get(json_decode($activityLog->properties, true), 'old');

                            if (!$previousData) {
                                // Instead of showing an error, use default reactivation values
                                $lead->updateQuietly([
                                    'categories' => 'Active',
                                    'lead_status' => 'New',
                                    'stage' => 'Transfer',
                                    'remark' => $data['remark']
                                ]);

                                activity()
                                    ->causedBy(auth()->user())
                                    ->performedOn($lead)
                                    ->log('Lead reactivated with default active status.');

                                Notification::make()
                                    ->title('Lead has been reactivated')
                                    ->success()
                                    ->send();
                                return;
                            }

                            // Update lead with old data if available
                            $lead->updateQuietly(array_merge($previousData, [
                                'remark' => $data['remark'], // override remark with new input
                            ]));

                            activity()
                                ->causedBy(auth()->user())
                                ->performedOn($lead)
                                ->log('Lead reactivated and restored to previous state.');

                            Notification::make()
                                ->title('Lead has been reactivated and restored')
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
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
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
                            $salespersonUser = \App\Models\User::find($appointment->salesperson ?? auth()->user()->id);
                            $demoAppointment = $lead->demoAppointment->first();
                            $startTime = Carbon::parse($demoAppointment->start_time);
                            $endTime = Carbon::parse($demoAppointment->end_time); // Assuming you have an end_time field
                            $formattedDate = Carbon::parse($demoAppointment->date)->format('d/m/Y');
                            $contactNo = isset($lead->companyDetail->contact_no) ? $lead->companyDetail->contact_no : $lead->phone;
                            $picName = isset($lead->companyDetail->name) ? $lead->companyDetail->name : $lead->name;
                            $email = isset($lead->companyDetail->email) ? $lead->companyDetail->email : $lead->email;

                            try {
                                if ($eventId) {
                                    $graph->createRequest("DELETE", "/users/$organizerEmail/events/$eventId")
                                        ->execute();
                                    $leadowner = User::where('name', $lead->lead_owner)->first();

                                    $appointment->update([
                                        'status' => 'Cancelled',
                                    ]);
                                    $utmCampaign = $lead->utmDetail->utm_campaign ?? null;
                                    $templateSelector = new TemplateSelector();

                                    if ($lead->lead_code && (
                                        str_contains($lead->lead_code, '(CN)') ||
                                        str_contains($lead->lead_code, 'CN')
                                    )) {
                                        // Use CN templates
                                        $template = $templateSelector->getTemplateByLeadSource('CN', 5);
                                    } else {
                                        // Use regular templates based on UTM campaign
                                        $template = $templateSelector->getTemplate($utmCampaign, 5); // first follow-up
                                    }

                                    $viewName = $template['email'] ?? 'emails.cancel_demo_notification';
                                    $emailContent = [
                                        'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager', // Lead Owner/Manager Name
                                        'lead' => [
                                            'lastName' => $lead->companyDetail->name ?? $lead->name, // Lead's Last Name
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
                                            'position' => $salespersonUser->position ?? 'N/A', // position
                                            'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                                            'demo_type' => $appointment->type,
                                            'appointment_type' => $appointment->appointment_type
                                        ],
                                    ];

                                    $requiredAttendees = $demoAppointment->required_attendees ?? null;

                                    // Parse attendees' emails if not null
                                    $attendeeEmails = [];
                                    if (!empty($requiredAttendees)) {
                                        $cleanedAttendees = str_replace('"', '', $requiredAttendees);
                                        $attendeeEmails = array_filter(array_map('trim', explode(';', $cleanedAttendees))); // Ensure no empty spaces
                                    }

                                    // ✅ Extract CC Recipients
                                    $ccEmails = array_filter(array_merge([$salespersonUser->email, $leadowner->email], $attendeeEmails), function ($email) {
                                        return filter_var($email, FILTER_VALIDATE_EMAIL); // Validate email format
                                    });

                                    // ✅ Send email with CC recipients
                                    if (!empty($email)) {
                                        $mail = Mail::to($email); // Send to Lead

                                        if (!empty($ccEmails)) {
                                            $mail->cc($ccEmails); // Add CC recipients
                                        }

                                        $mail->send(new CancelDemoNotification($emailContent, $viewName));

                                        info("Email sent successfully to: " . $email . " and CC to: " . implode(', ', $ccEmails));
                                    } else {
                                        Log::error("No valid lead email found for sending CancelDemoNotification.");
                                    }

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
                                // 'remark' => $data['remark'],
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

                    Tables\Actions\Action::make('demo_done')
                        ->visible(function (ActivityLog $record) {
                            $attributes = json_decode($record->properties, true)['attributes'] ?? [];

                            return data_get($attributes, 'stage') === 'Demo';
                        })
                        ->label(__('Demo Done'))
                        ->requiresConfirmation()
                        ->modalHeading('Demo Completed Confirmation')
                        // ->form([
                        //     Placeholder::make('')
                        //         ->content(__('You are marking this demo as completed. Confirm?')),

                        //     TextInput::make('remark')
                        //         ->label('Remarks')
                        //         ->required()
                        //         ->placeholder('Enter remarks here...')
                        //         ->maxLength(500),
                        // ])
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
                                // 'remark' => $data['remark'],
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
                            TextInput::make('remark')
                            ->label('Remarks')
                            ->required()
                            ->placeholder('Enter remarks here...')
                            ->maxLength(500)
                            ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
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
                    Tables\Actions\Action::make('edit_remark')
                        ->label('Edit Remark')
                        ->icon('heroicon-o-pencil')
                        ->modalHeading('Edit Lead Remark')
                        ->form(function (ActivityLog $record) {
                            $properties = json_decode($record->properties, true);
                            $remark = data_get($properties, 'attributes.remark', '');

                            return [
                                Textarea::make('remark')
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

                            return auth()->user()->role_id == 1
                                && !empty($remark)
                                && $updatedAt
                                && \Carbon\Carbon::parse($updatedAt)->diffInHours(now()) <= 48;
                        }),
                ])
                ->icon('heroicon-m-list-bullet')
                ->size(ActionSize::Small)
                ->color('primary')
                ->button()
                ->visible(function (ActivityLog $record) {
                    $lead = $record->lead;

                    // Get the latest ActivityLog for the related Lead
                    $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                        ->orderByDesc('updated_at')
                        ->first();

                    // Check if the current record is the latest activity log
                    if ($record->id !== $latestActivityLog->id) {
                        return false; // Not the latest record, hide the ActionGroup
                    }

                    // Apply the salesperson null check only for role_id = 1
                    // if ((auth()->user()->role_id === 1 && !is_null($lead->salesperson))) {
                    //     return false; // Hide for role_id = 1 if salesperson is not null
                    // }

                    if(is_null($lead->lead_owner) && $lead->lead_status == 'RFQ-Transfer' && !is_null($lead->salesperson)){
                        return true;
                    }

                    if(auth()->user()->role_id == '1' && is_null($lead->lead_owner)){
                        return false;
                    }

                    return true;
                })
            ]);
    }
}
