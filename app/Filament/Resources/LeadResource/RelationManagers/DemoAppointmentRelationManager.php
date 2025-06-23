<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Mail\CancelDemoNotification;
use App\Mail\DemoNotification;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use App\Services\TemplateSelector;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Forms;
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
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\Event;
use Spatie\Activitylog\Traits\LogsActivity;
use Livewire\Attributes\On;

class DemoAppointmentRelationManager extends RelationManager
{
    protected static string $relationship = 'demoAppointment';

    #[On('refresh-demo-appointments')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

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
            ->poll('300s')
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
                IconColumn::make('view_remark')
                    ->label('View Remark') // Hide label
                    ->alignCenter()
                    ->getStateUsing(fn() => true) // the column requires a state to be passed to it
                    ->icon(fn () => 'heroicon-o-magnifying-glass-plus') // Ensure the icon is dynamically set
                    ->color(fn () => 'blue') // Set icon color
                    ->tooltip('View Remark') // Show tooltip
                    ->extraAttributes(['class' => 'cursor-pointer']) // Make it clickable
                    ->action(
                        Action::make('view_remarks')
                            ->label('View Remark')
                            ->modalHeading('Appointment Remarks')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalDescription('Here are the remarks for this specific appointment.')
                            ->modalContent(function (Appointment $record) {
                                // Retrieve activity logs that match the lead of this appointment
                                $activityLogs = \App\Models\ActivityLog::where('subject_id', $record->lead->id)
                                    ->where('subject_type', 'App\Models\Lead') // Ensure we are filtering only Lead-related logs
                                    ->orderBy('created_at', 'asc')
                                    ->get();

                                if ($activityLogs->isEmpty()) {
                                    return new HtmlString('<p>No remarks available for this appointment.</p>');
                                }

                                // Filter logs based on `demo_appointment` value matching the current appointment ID
                                $filteredLogs = $activityLogs->filter(function ($log) use ($record) {
                                    $properties = json_decode($log->properties, true);
                                    return isset($properties['attributes']['demo_appointment']) &&
                                        $properties['attributes']['demo_appointment'] == $record->id;
                                });

                                if ($filteredLogs->isEmpty()) {
                                    return new HtmlString('<p>No remarks found for this appointment.</p>');
                                }

                                // Format remarks for display, ensuring line breaks are preserved
                                $remarksHtml = '<ul class="mt-2">';
                                foreach ($filteredLogs as $log) {
                                    $properties = json_decode($log->properties, true);

                                    // Extract lead status and remark, with fallbacks
                                    $leadStatus = $properties['attributes']['lead_status'] ?? 'No status';
                                    $remark = $properties['attributes']['remark'] ?? 'No remark available';
                                    $timestamp = $log->created_at->format('Y-m-d H:i:s');

                                    // Preserve line breaks using nl2br() to convert new lines into <br>
                                    $formattedRemark = nl2br(e($remark));

                                    // Display Lead Status before the remark
                                    $remarksHtml .= "<li><strong>{$timestamp}</strong> - <span class='font-bold text-blue-600'>{$leadStatus}</span>: {$formattedRemark}</li>";
                                }
                                $remarksHtml .= '</ul>';

                                return new HtmlString($remarksHtml);
                            }),
                        ),
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
                        ->visible(fn (Appointment $appointment) =>
                            now()->lte(Carbon::parse($appointment->appointment_date)->addDays(7)) &&
                            !($appointment->type === 'NEW DEMO' && $this->hasOtherNewDemos($appointment->lead_id))
                        )
                        ->label(__('Cancel Demo'))
                        ->modalHeading('Cancel Demo')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->action(function (array $data, $record) {
                            $appointment = $record;
                            $lead = $appointment->lead;

                            $updateData = [
                                'stage' => 'Transfer',
                                'lead_status' => 'Demo Cancelled',
                                'remark' => $data['remark'] ?? null,
                                'follow_up_date' => null
                            ];

                            if (in_array(auth()->user()->role_id, [1, 3])) {
                                $updateData['salesperson'] = null;
                            }

                            $lead->update($updateData);

                            $lead->refresh();

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

                            // ✅ Get all recipients for cancellation email
                            $email = $lead->companyDetail->email ?? $lead->email;
                            $demoAppointment = $lead->demoAppointment()->latest()->first();

                            // Extract required attendees
                            $requiredAttendees = $demoAppointment->required_attendees ?? null;
                            $attendeeEmails = [];
                            if (!empty($requiredAttendees)) {
                                $cleanedAttendees = str_replace('"', '', $requiredAttendees);
                                $attendeeEmails = array_filter(array_map('trim', explode(';', $cleanedAttendees))); // Ensure no empty spaces
                            }

                            $salespersonUser = \App\Models\User::find($appointment->salesperson ?? auth()->user()->id);
                            $demoAppointment = $lead->demoAppointment->first();
                            $startTime = Carbon::parse($demoAppointment->start_time);
                            $endTime = Carbon::parse($demoAppointment->end_time); // Assuming you have an end_time field
                            $formattedDate = Carbon::parse($demoAppointment->date)->format('d/m/Y');
                            $contactNo = optional($lead->companyDetail)->contact_no ?? $lead->phone;
                            $picName = optional($lead->companyDetail)->name ?? $lead->name;
                            $email = optional($lead->companyDetail)->email ?? $lead->email;

                            try {
                                if ($eventId) {
                                    $accessToken = MicrosoftGraphService::getAccessToken();
                                    $graph = new Graph();
                                    $graph->setAccessToken($accessToken);

                                    // Cancel the Teams meeting
                                    $graph->createRequest("DELETE", "/users/$organizerEmail/events/$eventId")->execute();
                                    $leadowner = User::where('name', $lead->lead_owner)->first();

                                    $utmCampaign = $lead->utmDetail->utm_campaign ?? null;
                                    $templateSelector = new TemplateSelector();
                                    $template = $templateSelector->getTemplate($utmCampaign, 5);

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

                            // Count how many times Demo was Cancelled
                            $cancelFollowUpCount = ActivityLog::where('subject_id', $lead->id)
                                ->whereJsonContains('properties->attributes->lead_status', 'Demo Cancelled')
                                ->count();

                            // Generate Follow-up Description
                            $cancelFollowUpDescription = match ($cancelFollowUpCount) {
                                1 => '1st Demo Cancelled Follow Up',
                                2 => '2nd Demo Cancelled Follow Up',
                                3 => '3rd Demo Cancelled Follow Up',
                                default => "{$cancelFollowUpCount}th Demo Cancelled Follow Up",
                            };

                            // Update or Create the Latest Activity Log
                            $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                                ->orderByDesc('created_at')
                                ->first();

                            if ($latestActivityLog) {
                                $latestActivityLog->update([
                                    'description' => 'Demo Cancelled. ' . $cancelFollowUpDescription,
                                ]);
                            } else {
                                activity()
                                    ->causedBy(auth()->user())
                                    ->performedOn($lead)
                                    ->withProperties(['description' => $cancelFollowUpDescription])
                                    ->log('Demo Cancelled');
                            }

                            // Update the Appointment status
                            $appointment->update([
                                'status' => 'Cancelled',
                                // 'remarks' => $data['remark'],
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
                ->hidden(auth()->user()->role_id != 2 && is_null($this->getOwnerRecord()->lead_owner))
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
                                ->default(Carbon::today()->toDateString())
                                ->reactive(),

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
                                    ->required()
                                    ->label('DEMO TYPE')
                                    ->reactive(),

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

                    $organizerEmail = $salesperson->email;

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
                            'allowNewTimeProposals' => false,
                            'responseRequested' => true ,
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
                    info($email);
                    if ($salespersonUser && filter_var($salespersonUser->email, FILTER_VALIDATE_EMAIL)) {
                        try {
                            $viewName = 'emails.demo_notification';
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
                            $salespersonEmail = $salespersonUser->email ?? null; // Prevent null errors
                            info($salespersonEmail);

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
                }),
        ];
    }

    private function hasOtherNewDemos($leadId)
    {
        return Appointment::where('lead_id', $leadId)
            ->where('type', '!=', 'NEW DEMO') // Exclude "NEW DEMO"
            ->where('status', 'New') // Only check "New" status
            ->exists();
    }
}
