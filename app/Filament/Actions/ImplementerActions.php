<?php
namespace App\Filament\Actions;

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
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Placeholder;
use Filament\Support\Enums\ActionSize;
use App\Models\Lead;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\ImplementerAppointment;
use App\Models\SoftwareHandover;
use App\Models\InvalidLeadReason;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use App\Services\QuotationService;
use Beta\Microsoft\Graph\Model\Event;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event as FacadesEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\HtmlString;
use Microsoft\Graph\Graph;
use Illuminate\Support\Str;
use Livewire\Component;

class ImplementerActions
{
    /**
     * Get the reschedule implementation appointment action
     *
     * @return \Filament\Tables\Actions\Action
     */
    public static function rescheduleAppointmentAction()
    {
        return Action::make('reschedule_appointment')
            ->label('Reschedule')
            ->icon('heroicon-o-clock')
            ->color('warning')
            ->modalHeading('Reschedule Implementation Appointment')
            ->form(function (?ImplementerAppointment $record = null) {
                if (!$record) {
                    return [
                        TextInput::make('error')
                            ->label('Error')
                            ->default('No appointment record found.')
                            ->disabled(),
                    ];
                }

                return [
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
                                    if (!$date) return [];

                                    $selectedDate = Carbon::parse($date);
                                    $dayOfWeek = $selectedDate->dayOfWeek;

                                    // Friday sessions (dayOfWeek = 5)
                                    if ($dayOfWeek === 5) {
                                        return [
                                            'SESSION 1' => 'SESSION 1 (0930 - 1030)',
                                            'SESSION 2' => 'SESSION 2 (1100 - 1200)',
                                            'SESSION 4' => 'SESSION 4 (1530 - 1630)',
                                            'SESSION 5' => 'SESSION 5 (1700 - 1800)',
                                        ];
                                    }
                                    // Monday to Thursday sessions (dayOfWeek = 1-4)
                                    else if ($dayOfWeek >= 1 && $dayOfWeek <= 4) {
                                        return [
                                            'SESSION 1' => 'SESSION 1 (0930 - 1030)',
                                            'SESSION 2' => 'SESSION 2 (1100 - 1200)',
                                            'SESSION 3' => 'SESSION 3 (1400 - 1500)',
                                            'SESSION 4' => 'SESSION 4 (1530 - 1630)',
                                            'SESSION 5' => 'SESSION 5 (1700 - 1800)',
                                        ];
                                    }

                                    // Weekend or invalid date
                                    return ['NO_SESSIONS' => 'No sessions available on weekends'];
                                })
                                ->default(function (callable $get, $record = null) {
                                    // If editing existing record, use its session value
                                    if ($record && $record->session) {
                                        return $record->session;
                                    }

                                    // For new records, select a default based on the day
                                    $date = $get('date');
                                    if (!$date) return null;

                                    return 'SESSION 1';
                                })
                                ->columnSpan(2)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                    // Set the start_time and end_time based on selected session
                                    $times = [
                                        'SESSION 1' => ['09:30', '10:30'],
                                        'SESSION 2' => ['11:00', '12:00'],
                                        'SESSION 3' => ['14:00', '15:00'],
                                        'SESSION 4' => ['15:30', '16:30'], // Friday has different time
                                        'SESSION 5' => ['17:00', '18:00'], // Friday has different time
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
                                })
                                ->reactive()
                                ->afterStateHydrated(function (callable $set, callable $get, $state) {
                                    // Set initial state based on session when form first loads
                                    $session = $get('session');
                                    $date = $get('date');

                                    if ($session && $date) {
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

                                        $set('start_time', $times[$session] ?? $state);
                                    }
                                }),

                            Hidden::make('end_time')
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
                                })
                                ->reactive()
                                ->afterStateHydrated(function (callable $set, callable $get, $state) {
                                    // Set initial state based on session when form first loads
                                    $session = $get('session');
                                    $date = $get('date');

                                    if ($session && $date) {
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

                                        $set('end_time', $times[$session] ?? $state);
                                    }
                                }),
                        ]),

                    Grid::make(3)
                        ->schema([
                            Select::make('type')
                                ->options(function ($record = null) {
                                    if (!$record) return ['KICK OFF MEETING SESSION' => 'KICK OFF MEETING SESSION'];

                                    // Retrieve software handover information first
                                    $softwareHandover = null;
                                    if ($record->software_handover_id) {
                                        $softwareHandover = SoftwareHandover::find($record->software_handover_id);
                                    }

                                    // Check if there are any existing kick-off meetings that are completed or scheduled
                                    $hasKickoffAppointment = ImplementerAppointment::where('lead_id', $record->lead_id)
                                        ->where('software_handover_id', $record->software_handover_id ?? 0)
                                        ->where('type', 'KICK OFF MEETING SESSION')
                                        ->whereIn('status', ['Done', 'New']) // Check for completed or scheduled kick-offs
                                        ->exists();

                                    // Also check if kick_off_meeting exists in the software handover record as a backup
                                    $hasKickoffMeeting = $softwareHandover && !empty($softwareHandover->kick_off_meeting);

                                    // If either condition is true, allow implementation review sessions
                                    if ($hasKickoffAppointment || $hasKickoffMeeting) {
                                        return [
                                            'IMPLEMENTATION REVIEW SESSION' => 'IMPLEMENTATION REVIEW SESSION',
                                        ];
                                    } else {
                                        return [
                                            'KICK OFF MEETING SESSION' => 'KICK OFF MEETING SESSION',
                                        ];
                                    }
                                })
                                ->default(function ($record = null) {
                                    if (!$record) return 'KICK OFF MEETING SESSION';

                                    // Retrieve software handover information first
                                    $softwareHandover = null;
                                    if ($record->software_handover_id) {
                                        $softwareHandover = SoftwareHandover::find($record->software_handover_id);
                                    }

                                    // Check if there are any existing kick-off meetings that are completed or scheduled
                                    $hasKickoffAppointment = ImplementerAppointment::where('lead_id', $record->lead_id)
                                        ->where('software_handover_id', $record->software_handover_id ?? 0)
                                        ->where('type', 'KICK OFF MEETING SESSION')
                                        ->whereIn('status', ['Completed', 'New'])
                                        ->exists();

                                    // Also check if kick_off_meeting exists in the software handover record as a backup
                                    $hasKickoffMeeting = $softwareHandover && !empty($softwareHandover->kick_off_meeting);

                                    // Set default based on whether any kick-off meeting exists
                                    return ($hasKickoffAppointment || $hasKickoffMeeting)
                                        ? 'IMPLEMENTATION REVIEW SESSION'
                                        : 'KICK OFF MEETING SESSION';
                                })
                                ->required()
                                ->label('SESSION TYPE')
                                ->disabled() // Disable the field
                                ->reactive()
                                ->dehydrated(true),

                            Select::make('appointment_type')
                                ->options([
                                    'ONLINE' => 'ONLINE',
                                    'ONSITE' => 'ONSITE',
                                    'INHOUSE' => 'INHOUSE',
                                ])
                                ->disabled()
                                ->required()
                                ->dehydrated(true)
                                ->default('ONLINE')
                                ->label('APPOINTMENT TYPE'),

                            Select::make('implementer')
                                ->label('IMPLEMENTER')
                                ->options(function ($record = null) {
                                    if (!$record) {
                                        return User::whereIn('role_id', [4, 5])
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function ($tech) {
                                                return [$tech->name => $tech->name];
                                            })
                                            ->toArray();
                                    }

                                    // If we found a software handover with an implementer, only show that implementer
                                    if ($record->implementer) {
                                        return [$record->implementer => $record->implementer];
                                    }

                                    // Fallback: if no software handover or no implementer assigned,
                                    // show all implementers (role_id 4 or 5)
                                    return User::whereIn('role_id', [4, 5])
                                        ->orderBy('name')
                                        ->get()
                                        ->mapWithKeys(function ($tech) {
                                            return [$tech->name => $tech->name];
                                        })
                                        ->toArray();
                                })
                                ->default(function ($record = null) {
                                    // First try to get from existing record if editing
                                    if ($record && $record->implementer) {
                                        return $record->implementer;
                                    }

                                    // Default to null if nothing found
                                    return null;
                                })
                                ->searchable()
                                ->required()
                                ->disabled(function ($record = null) {
                                    // Disable the field if there's a software handover with an implementer
                                    if (!$record) return false;

                                    $softwareHandover = SoftwareHandover::where('lead_id', $record->lead_id)
                                        ->latest()
                                        ->first();

                                    return $softwareHandover && $softwareHandover->implementer;
                                })
                                ->dehydrated(true)
                                ->placeholder('Select a implementer'),
                        ]),

                    TextInput::make('required_attendees')
                        ->label('REQUIRED ATTENDEES')
                        ->default(function() use ($record) {
                            if (!$record) return '';

                            // Try to decode JSON if it exists
                            if (!empty($record->required_attendees)) {
                                try {
                                    $attendees = json_decode($record->required_attendees, true);
                                    if (is_array($attendees)) {
                                        return implode(';', $attendees);
                                    }
                                    return $record->required_attendees;
                                } catch (\Exception $e) {
                                    return $record->required_attendees;
                                }
                            }
                            return '';
                        })
                        ->disabled()
                        ->dehydrated(true)
                        ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),

                    Textarea::make('remarks')
                        ->label('REMARKS')
                        ->rows(3)
                        ->default($record->remarks ?? '')
                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

                    Hidden::make('type')
                        ->default($record->type ?? 'KICK OFF MEETING SESSION'),
                ];
            })
            ->visible(fn (ImplementerAppointment $record) =>
                $record->status !== 'Cancelled' && $record->status !== 'Completed'
            )
            ->action(function (array $data, ImplementerAppointment $record, Component $livewire) {
                // Store the previous appointment details for the notification
                $oldDate = Carbon::parse($record->date)->format('d/m/Y');
                $oldStartTime = Carbon::parse($record->start_time)->format('h:i A');
                $oldEndTime = Carbon::parse($record->end_time)->format('h:i A');

                // Process required attendees from form data
                $requiredAttendeesInput = $data['required_attendees'] ?? '';
                $attendeeEmails = [];
                if (!empty($requiredAttendeesInput)) {
                    $attendeeEmails = array_filter(array_map('trim', explode(';', $requiredAttendeesInput)));
                }

                // Update the appointment with new schedule
                $record->update([
                    'date' => $data['date'],
                    'start_time' => $data['start_time'],
                    'end_time' => $data['end_time'],
                    'remarks' => $data['remarks'],
                    'type' => $data['type'] ?? $record->type,
                    'appointment_type' => $data['appointment_type'] ?? $record->appointment_type,
                    'implementer' => $data['implementer'] ?? $record->implementer,
                    'session' => $data['session'] ?? $record->session,
                    'required_attendees' => !empty($attendeeEmails) ? json_encode($attendeeEmails) : null,
                    'updated_at' => now(),
                ]);

                // Get company name with fallback
                $companyName = 'N/A';
                if ($record->lead && $record->lead->companyDetail) {
                    $companyName = $record->lead->companyDetail->company_name;
                } elseif ($record->softwareHandover) {
                    $companyName = $record->softwareHandover->company_name ?? 'N/A';
                }

                $recipients = ['fazuliana.mohdarsad@timeteccloud.com']; // Always include admin

                // Add required attendees from the form input
                if (!empty($attendeeEmails)) {
                    foreach ($attendeeEmails as $email) {
                        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $recipients)) {
                            $recipients[] = $email;
                        }
                    }
                }

                // Ensure recipients are unique
                $viewName = 'emails.implementer_appointment_reschedule';

                $recipients = array_unique($recipients);
                $authUser = auth()->user();
                $senderEmail = $authUser->email;
                $senderName = $authUser->name;

                // Prepare email content with reschedule reason
                $emailContent = [
                    'lead' => [
                        'company' => $companyName,
                        'implementerName' => $record->implementer ?? 'N/A',
                        'date' => Carbon::parse($data['date'])->format('d/m/Y'),
                        'startTime' => Carbon::parse($data['start_time'])->format('h:i A'),
                        'endTime' => Carbon::parse($data['end_time'])->format('h:i A'),
                        'oldDate' => $oldDate,
                        'oldStartTime' => $oldStartTime,
                        'oldEndTime' => $oldEndTime,
                        'rescheduleReason' => $data['reschedule_reason'] ?? 'No reason provided',
                    ],
                ];

                // Update Teams meeting
                self::updateTeamsMeeting($record, $data, $companyName);

                try {
                    // Send email with template and custom subject format
                    if (count($recipients) > 0) {
                        Mail::send($viewName, ['content' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $data, $companyName) {
                            $message->from($senderEmail, $senderName)
                                ->to($recipients)
                                ->subject("TIMETEC IMPLEMENTATION APPOINTMENT | {$data['type']} | {$companyName} | " . Carbon::parse($data['date'])->format('d/m/Y'));
                        });

                        Notification::make()
                            ->title('Implementation appointment notification sent')
                            ->success()
                            ->body('Email notification sent to administrator and required attendees')
                            ->send();
                    }
                } catch (\Exception $e) {
                    // Handle email sending failure
                    Log::error("Email sending failed for implementation appointment: Error: {$e->getMessage()}");

                    Notification::make()
                        ->title('Email Notification Failed')
                        ->danger()
                        ->body('Could not send email notification: ' . $e->getMessage())
                        ->send();
                }

                Notification::make()
                    ->title('Implementation Appointment Rescheduled Successfully')
                    ->success()
                    ->send();

                // Refresh the Livewire component if it has a refreshData method
                if (method_exists($livewire, 'refreshData')) {
                    $livewire->refreshData();
                }
            });
    }

    /**
     * Get the cancel implementation appointment action
     *
     * @return \Filament\Tables\Actions\Action
     */
    public static function cancelAppointmentAction()
    {
        return Action::make('cancel_appointment')
            ->label('Cancel')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Cancel Implementation Appointment')
            ->modalDescription('Are you sure you want to cancel this appointment? This will also cancel any associated Teams meetings.')
            ->modalSubmitActionLabel('Yes, Cancel Appointment')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->visible(fn (ImplementerAppointment $record) =>
                $record->status !== 'Cancelled' && $record->status !== 'Completed')
            ->action(function (ImplementerAppointment $record, Component $livewire) {
                if (!$record) {
                    Notification::make()
                        ->title('Appointment not found')
                        ->danger()
                        ->send();
                    return;
                }

                try {
                    // Update status to Cancelled
                    $record->status = 'Cancelled';
                    $record->request_status = 'CANCELLED';

                    // Cancel Teams meeting if exists
                    if ($record->event_id) {
                        $eventId = $record->event_id;

                        // Get implementer's email instead of using organizer_email
                        $implementer = User::where('name', $record->implementer)->first();

                        if ($implementer && $implementer->email) {
                            $implementerEmail = $implementer->email;

                            try {
                                $accessToken = MicrosoftGraphService::getAccessToken();
                                $graph = new Graph();
                                $graph->setAccessToken($accessToken);

                                // Cancel the Teams meeting using implementer's email
                                $graph->createRequest("DELETE", "/users/$implementerEmail/events/$eventId")->execute();

                                Notification::make()
                                    ->title('Teams Meeting Cancelled Successfully')
                                    ->warning()
                                    ->body('The meeting has been cancelled in Microsoft Teams.')
                                    ->send();

                            } catch (\Exception $e) {
                                Log::error('Failed to cancel Teams meeting: ' . $e->getMessage(), [
                                    'event_id' => $eventId,
                                    'implementer' => $implementerEmail,
                                    'trace' => $e->getTraceAsString()
                                ]);

                                Notification::make()
                                    ->title('Failed to Cancel Teams Meeting')
                                    ->warning()
                                    ->body('The appointment was cancelled, but there was an error cancelling the Teams meeting: ' . $e->getMessage())
                                    ->send();
                            }
                        } else {
                            Log::error('Failed to cancel Teams meeting: Implementer email not found', [
                                'event_id' => $eventId,
                                'implementer_name' => $record->implementer
                            ]);

                            Notification::make()
                                ->title('Failed to Cancel Teams Meeting')
                                ->warning()
                                ->body('The appointment was cancelled, but the implementer email was not found.')
                                ->send();
                        }
                    }

                    $record->save();

                    // Send email notification about cancellation
                    self::sendCancellationEmail($record);

                    Notification::make()
                        ->title('Appointment cancelled successfully')
                        ->success()
                        ->send();

                    // Refresh the Livewire component if it has a refreshData method
                    if (method_exists($livewire, 'refreshData')) {
                        $livewire->refreshData();
                    }
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Error cancelling appointment')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Update Teams meeting for an implementer appointment
     *
     * @param ImplementerAppointment $record
     * @param array $data
     * @param string $companyName
     * @return void
     */
    private static function updateTeamsMeeting(ImplementerAppointment $record, array $data, string $companyName)
    {
        try {
            $accessToken = MicrosoftGraphService::getAccessToken();
            $graph = new Graph();
            $graph->setAccessToken($accessToken);

            $startTime = Carbon::parse($data['date'] . ' ' . $data['start_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');
            $endTime = Carbon::parse($data['date'] . ' ' . $data['end_time'])->timezone('UTC')->format('Y-m-d\TH:i:s\Z');

            $implementer = User::where('name', $record->implementer)->first();
            $organizerEmail = $implementer->email ?? null;

            if (!$organizerEmail) {
                Notification::make()
                    ->title('Missing Organizer Email')
                    ->danger()
                    ->body('Implementer email is not available.')
                    ->send();
                return;
            }

            if ($record->event_id) {
                $meetingUpdatePayload = [
                    'start' => ['dateTime' => $startTime, 'timeZone' => 'Asia/Kuala_Lumpur'],
                    'end' => ['dateTime' => $endTime, 'timeZone' => 'Asia/Kuala_Lumpur'],
                    'subject' => 'TIMETEC | ' . $companyName,
                ];

                $response = $graph->createRequest("PATCH", "/users/$organizerEmail/events/{$record->event_id}")
                    ->attachBody($meetingUpdatePayload)
                    ->execute();

                $eventData = $response->getBody(); // associative array

                // Extract the meeting details
                $joinUrl = $eventData['onlineMeeting']['joinUrl'] ?? null;
                $eventId = $eventData['id'] ?? $record->event_id;

                // Update the record with the meeting details
                $record->update([
                    'event_id' => $eventId,
                    'meeting_link' => $joinUrl,
                ]);

                Notification::make()
                    ->title('Meeting Updated')
                    ->success()
                    ->body('The implementation appointment and Teams meeting have been updated.')
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Teams Meeting Reschedule Failed: ' . $e->getMessage());
            Notification::make()
                ->title('Rescheduling Failed')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
    }

    /**
     * Send cancellation email for an implementer appointment
     *
     * @param ImplementerAppointment $appointment
     * @return bool
     */
    private static function sendCancellationEmail($appointment)
    {
        try {
            $recipients = ['fazuliana.mohdarsad@timeteccloud.com']; // Default recipient

            // Add required attendees from appointment if available
            if (!empty($appointment->required_attendees)) {
                try {
                    $attendees = json_decode($appointment->required_attendees, true);
                    if (is_array($attendees)) {
                        foreach ($attendees as $email) {
                            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $recipients)) {
                                $recipients[] = $email;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error processing attendees: ' . $e->getMessage());
                }
            }

            // Get company name with fallback
            $companyName = 'N/A';
            if ($appointment->lead && $appointment->lead->companyDetail) {
                $companyName = $appointment->lead->companyDetail->company_name;
            } elseif ($appointment->softwareHandover) {
                $companyName = $appointment->softwareHandover->company_name ?? 'N/A';
            }

            // Format dates for email
            $appointmentDate = Carbon::parse($appointment->date)->format('d/m/Y');
            $startTime = Carbon::parse($appointment->start_time)->format('h:i A');
            $endTime = Carbon::parse($appointment->end_time)->format('h:i A');

            // Prepare email content
            $emailContent = [
                'companyName' => $companyName,
                'implementer' => $appointment->implementer ?? 'N/A',
                'date' => $appointmentDate,
                'time' => $startTime . ' - ' . $endTime,
                'appointmentType' => $appointment->type ?? 'N/A',
                'reason' => 'Appointment has been cancelled by ' . auth()->user()->name,
            ];

            $authUser = auth()->user();
            $senderEmail = $authUser->email;
            $senderName = $authUser->name;

            // Send email
            Mail::send(
                'emails.implementer_appointment_cancel',
                ['content' => $emailContent],
                function ($message) use ($recipients, $senderEmail, $senderName, $appointment, $companyName, $appointmentDate) {
                    $message->from($senderEmail, $senderName)
                        ->to($recipients)
                        ->subject("CANCELLED: TIMETEC IMPLEMENTATION APPOINTMENT | {$appointment->type} | {$companyName} | {$appointmentDate}");
                }
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send cancellation email: ' . $e->getMessage());
            return false;
        }
    }
}
