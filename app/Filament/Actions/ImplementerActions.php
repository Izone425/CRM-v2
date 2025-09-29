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
use App\Models\EmailTemplate;
use App\Models\ImplementerAppointment;
use App\Models\ImplementerLogs;
use App\Models\SoftwareHandover;
use App\Models\InvalidLeadReason;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use App\Services\QuotationService;
use Beta\Microsoft\Graph\Model\Event;
use Exception;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Mail\Message;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
                                            'SESSION 3' => 'SESSION 3 (1500 - 1600)',
                                            'SESSION 4' => 'SESSION 4 (1630 - 1730)',
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

                                    // If either condition is true, allow REVIEW SESSIONs
                                    if ($hasKickoffAppointment || $hasKickoffMeeting) {
                                        return [
                                            'REVIEW SESSION' => 'REVIEW SESSION',
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
                                        ? 'REVIEW SESSION'
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
                                ->cc($senderEmail)
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

                if (method_exists($livewire, 'refreshData')) {
                    $livewire->refreshData();
                }

                $livewire->dispatch('refresh-salesperson-tables');
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
                Log::info('Teams meeting updated successfully', [
                    'event_id' => $eventId,
                    'join_url' => $joinUrl,
                    'implementer' => $organizerEmail,
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
            if ($appointment->softwareHandover) {
                $companyName = $appointment->softwareHandover->company_name ?? 'N/A';
            } elseif ($appointment->lead && $appointment->lead->companyDetail) {
                $companyName = $appointment->lead->companyDetail->company_name;
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
                        ->cc($senderEmail)
                        ->subject("CANCELLED: TIMETEC IMPLEMENTATION APPOINTMENT | {$appointment->type} | {$companyName} | {$appointmentDate}");
                }
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send cancellation email: ' . $e->getMessage());
            return false;
        }
    }

    public static function addImplementerFollowUp(): Action
    {
        return Action::make('add_follow_up')
            ->label('Add Follow-up')
            ->color('primary')
            ->icon('heroicon-o-plus')
            ->modalWidth('6xl')
            ->form([
                Grid::make(4)
                    ->schema([
                        DatePicker::make('follow_up_date')
                            ->label('Next Follow-up Date')
                            ->default(function() {
                                $today = now();
                                $daysUntilNextTuesday = (9 - $today->dayOfWeek) % 7; // 2 is Tuesday, but we add 7 to ensure positive
                                if ($daysUntilNextTuesday === 0) {
                                    $daysUntilNextTuesday = 7; // If today is Tuesday, we want next Tuesday
                                }
                                return $today->addDays($daysUntilNextTuesday);
                            })
                            ->minDate(now()->subDay())
                            ->required(),

                        Select::make('manual_follow_up_count')
                            ->label('Follow Up Count')
                            ->required()
                            ->options([
                                0 => '0',
                                1 => '1',
                                2 => '2',
                                3 => '3',
                                4 => '4',
                            ])
                            ->default(function (SoftwareHandover $record = null) {
                                if (!$record) return 0;

                                // Get current follow-up count from database
                                $currentCount = $record->manual_follow_up_count ?? 0;

                                // Increment by 1, but loop back to 0 if it's already at 4
                                $nextCount = ($currentCount >= 4) ? 0 : $currentCount + 1;

                                return $nextCount;
                            }),

                        Toggle::make('send_email')
                            ->label('Send Email to Customer?')
                            ->onIcon('heroicon-o-bell-alert')
                            ->offIcon('heroicon-o-bell-slash')
                            ->onColor('primary')
                            ->inline(false)
                            ->offColor('gray')
                            ->default(false)
                            ->live(onBlur: true),

                        // Scheduler Type options
                        Select::make('scheduler_type')
                            ->label('Scheduler Type')
                            ->options([
                                'instant' => 'Instant',
                                'scheduled' => 'Next Follow Up Date at 8am',
                                'both' => 'Both'
                            ])
                            ->visible(fn ($get) => $get('send_email'))
                            ->required(),
                    ]),

                Fieldset::make('Email Details')
                    ->schema([
                        TextInput::make('required_attendees')
                            ->label('Required Attendees')
                            ->default(function (SoftwareHandover $record = null) {
                                if (!$record) return null;

                                // Initialize emails array to store all collected emails
                                $emails = [];

                                // 1. Get emails from SoftwareHandover implementation_pics
                                if (!empty($record->implementation_pics) && is_string($record->implementation_pics)) {
                                    try {
                                        $contacts = json_decode($record->implementation_pics, true);

                                        // If it's valid JSON array, extract emails
                                        if (is_array($contacts)) {
                                            foreach ($contacts as $contact) {
                                                // Check if email exists and is valid
                                                if (!empty($contact['pic_email_impl'])) {
                                                    $email = trim($contact['pic_email_impl']);
                                                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                                        // Only include PICs with "Available" status IF status field exists
                                                        // If no status field, include all valid emails
                                                        if (!isset($contact['status']) || $contact['status'] === 'Available') {
                                                            $emails[] = $email;
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    } catch (\Exception $e) {
                                        \Illuminate\Support\Facades\Log::error('Error parsing implementation_pics JSON: ' . $e->getMessage());
                                    }
                                }

                                // 2. Get emails from company_detail->additional_pic
                                if ($record->lead_id) {
                                    $lead = \App\Models\Lead::find($record->lead_id);
                                    if ($lead && $lead->companyDetail && !empty($lead->companyDetail->additional_pic)) {
                                        try {
                                            $additionalPics = json_decode($lead->companyDetail->additional_pic, true);

                                            if (is_array($additionalPics)) {
                                                foreach ($additionalPics as $pic) {
                                                    // Only include contacts with "Available" status (not "Resign")
                                                    if (
                                                        !empty($pic['email']) &&
                                                        isset($pic['status']) &&
                                                        $pic['status'] !== 'Resign'
                                                    ) {
                                                        $email = trim($pic['email']);
                                                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                                            $emails[] = $email;
                                                        }
                                                    }
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            \Illuminate\Support\Facades\Log::error('Error parsing additional_pic JSON: ' . $e->getMessage());
                                        }
                                    }
                                }

                                // 3. Get salesperson email
                                if ($record->lead_id) {
                                    $lead = \App\Models\Lead::find($record->lead_id);
                                    if ($lead && !empty($lead->salesperson)) {
                                        // Find the user with this salesperson ID
                                        $salesperson = \App\Models\User::where('id', $lead->salesperson)->first();

                                        if ($salesperson && !empty($salesperson->email)) {
                                            $email = trim($salesperson->email);
                                            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                                $emails[] = $email;
                                            }
                                        }
                                    }
                                }

                                // Remove duplicates and return as semicolon-separated string
                                $uniqueEmails = array_unique($emails);
                                return !empty($uniqueEmails) ? implode(';', $uniqueEmails) : null;
                            })
                            ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),

                        Select::make('email_template')
                            ->label('Email Template')
                            ->options(function () {
                                return EmailTemplate::whereIn('type', ['implementer'])
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->preload()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $template = EmailTemplate::find($state);
                                    if ($template) {
                                        $set('email_subject', $template->subject);
                                        $set('email_content', $template->content);
                                    }
                                }
                            })
                            ->required(),

                        TextInput::make('email_subject')
                            ->label('Email Subject')
                            ->required(),

                        RichEditor::make('email_content')
                            ->label('Email Content')
                            ->disableToolbarButtons([
                                'attachFiles',
                            ])
                            ->required(),
                    ])
                    ->visible(fn ($get) => $get('send_email')),

                Hidden::make('implementer_name')
                    ->label('NAME')
                    ->default(auth()->user()->name ?? '')
                    ->required(),

                Hidden::make('implementer_designation')
                    ->label('DESIGNATION')
                    ->default('Implementer')
                    ->required(),

                Hidden::make('implementer_company')
                    ->label('COMPANY NAME')
                    ->default('TimeTec Cloud Sdn Bhd')
                    ->required(),

                Hidden::make('implementer_phone')
                    ->label('PHONE NO')
                    ->default('03-80709933')
                    ->required(),

                Hidden::make('implementer_email')
                    ->label('EMAIL')
                    ->default(auth()->user()->email ?? '')
                    ->required(),

                RichEditor::make('notes')
                    ->label('Remarks')
                    ->disableToolbarButtons([
                        'attachFiles',
                        'blockquote',
                        'codeBlock',
                        'h2',
                        'h3',
                        'link',
                        'redo',
                        'strike',
                        'undo',
                    ])
                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                    ->afterStateHydrated(fn($state) => Str::upper($state))
                    ->afterStateUpdated(fn($state) => Str::upper($state))
                    ->placeholder('Add your follow-up details here...')
                    ->required()
            ])
            ->modalHeading('Add New Follow-up');
    }

    /**
     * Process the follow-up action with email functionality
     *
     * @param SoftwareHandover $record
     * @param array $data
     * @return ImplementerLogs|null
     */
    public static function processFollowUpWithEmail(SoftwareHandover $record, array $data): ?ImplementerLogs
    {
        if (!$record) {
            Notification::make()
                ->title('Error: Software Handover record not found')
                ->danger()
                ->send();
            return null;
        }

        try {
            // Update the SoftwareHandover record with follow-up information
            $record->update([
                'follow_up_date' => $data['follow_up_date'],
                'follow_up_counter' => true,
                'manual_follow_up_count' => $data['manual_follow_up_count'] ?? 0,
            ]);

            // Create description for the follow-up
            $followUpDescription = 'Implementer Follow Up By ' . auth()->user()->name;

            // Create a new implementer_logs entry with reference to SoftwareHandover
            $implementerLog = ImplementerLogs::create([
                'lead_id' => $record->lead_id,
                'description' => $followUpDescription,
                'causer_id' => auth()->id(),
                'remark' => $data['notes'],
                'subject_id' => $record->id,
                'follow_up_date' => $data['follow_up_date'],
            ]);

            if (isset($data['send_email']) && $data['send_email']) {
                try {
                    // Get recipient emails
                    $recipientStr = $data['required_attendees'] ?? '';

                    if (!empty($recipientStr)) {
                        // Get email template content
                        $subject = $data['email_subject'];
                        $content = $data['email_content'];

                        // Add signature to email content if provided
                        if (isset($data['implementer_name']) && !empty($data['implementer_name'])) {
                            $signature = "Regards,<br>";
                            $signature .= "{$data['implementer_name']}<br>";
                            $signature .= "{$data['implementer_designation']}<br>";
                            $signature .= "{$data['implementer_company']}<br>";
                            $signature .= "Phone: {$data['implementer_phone']}<br>";

                            if (!empty($data['implementer_email'])) {
                                $signature .= "Email: {$data['implementer_email']}<br>";
                            }

                            $content .= $signature;
                        }

                        // Replace placeholders with actual data
                        $lead = Lead::find($record->lead_id);
                        $placeholders = [
                            '{customer_name}' => $lead->contact_name ?? '',
                            '{company_name}' => $lead->companyDetail->company_name ?? '',
                            '{implementer_name}' => $data['implementer_name'] ?? auth()->user()->name ?? '',
                            '{follow_up_date}' => $data['follow_up_date'] ? date('d M Y', strtotime($data['follow_up_date'])) : '',
                        ];

                        $content = str_replace(array_keys($placeholders), array_values($placeholders), $content);
                        $subject = str_replace(array_keys($placeholders), array_values($placeholders), $subject);

                        // Collect valid email addresses
                        $validRecipients = [];
                        foreach (explode(';', $recipientStr) as $recipient) {
                            $recipient = trim($recipient);
                            if (filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                                $validRecipients[] = $recipient;
                            }
                        }

                        if (!empty($validRecipients)) {
                            // Get authenticated user's email for sender and BCC
                            $authUser = auth()->user();
                            $senderEmail = $data['implementer_email'] ?? $authUser->email;
                            $senderName = $data['implementer_name'] ?? $authUser->name;

                            $schedulerType = $data['scheduler_type'] ?? 'instant';

                            $template = EmailTemplate::find($data['email_template']);
                            $templateName = $template ? $template->name : 'Custom Email';

                            // Store email data for scheduling
                            $emailData = [
                                'content' => $content,
                                'subject' => $subject,
                                'recipients' => $validRecipients,
                                'sender_email' => $senderEmail,
                                'sender_name' => $senderName,
                                'lead_id' => $record->lead_id,
                                'implementer_log_id' => $implementerLog->id,
                                'template_name' => $templateName,
                                'scheduler_type' => $schedulerType,
                            ];

                            // Handle different scheduler types
                            if ($schedulerType === 'instant' || $schedulerType === 'both') {
                                // Send email immediately
                                self::sendEmail($emailData);

                                Notification::make()
                                    ->title('Email sent immediately to ' . count($validRecipients) . ' recipient(s)')
                                    ->success()
                                    ->send();
                            }

                            if ($schedulerType === 'scheduled' || $schedulerType === 'both') {
                                // Schedule email for follow-up date at 8am
                                $scheduledDate = date('Y-m-d 08:00:00', strtotime($data['follow_up_date']));

                                // Store scheduled email in database
                                DB::table('scheduled_emails')->insert([
                                    'email_data' => json_encode($emailData),
                                    'scheduled_date' => $scheduledDate,
                                    'status' => 'New',
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);

                                Notification::make()
                                    ->title('Email scheduled for ' . date('d M Y \a\t 8:00 AM', strtotime($scheduledDate)))
                                    ->success()
                                    ->send();
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Error sending follow-up email: ' . $e->getMessage());
                    Notification::make()
                        ->title('Error sending email')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            }

            Notification::make()
                ->title('Follow-up added successfully')
                ->success()
                ->send();

            return $implementerLog;
        } catch (\Exception $e) {
            Log::error('Error processing follow-up: ' . $e->getMessage());
            Notification::make()
                ->title('Error adding follow-up')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    /**
     * Send email using the provided data
     *
     * @param array $emailData
     * @return void
     */
    public static function sendEmail(array $emailData): void
    {
        try {
            // Get the implementer log record
            $implementerLog = ImplementerLogs::find($emailData['implementer_log_id']);

            if (!$implementerLog) {
                Log::error("Implementer log not found for ID: {$emailData['implementer_log_id']}");
                return;
            }

            // Find the software handover record using subject_id from implementer log
            $softwareHandover = SoftwareHandover::find($implementerLog->subject_id);

            if (!$softwareHandover) {
                Log::error("Software handover not found for subject_id: {$implementerLog->subject_id}");
                return;
            }

            // Initialize CC recipients array
            $ccRecipients = [];

            // Add implementer to CC if available and different from sender
            if ($softwareHandover->implementer) {
                // Look up user by name instead of ID
                $implementer = User::where('name', $softwareHandover->implementer)->first();
                if ($implementer && $implementer->email && $implementer->email !== $emailData['sender_email']) {
                    $ccRecipients[] = $implementer->email;
                    Log::info("Added implementer to CC: {$implementer->name} <{$implementer->email}>");
                } else {
                    Log::info("Implementer not found or no valid email for: {$softwareHandover->implementer}");
                }
            }

            // Add salesperson to CC if available and different from sender and implementer
            if ($softwareHandover->salesperson) {
                // Look up user by name instead of ID
                $salesperson = User::where('name', $softwareHandover->salesperson)->first();
                if ($salesperson && $salesperson->email &&
                    $salesperson->email !== $emailData['sender_email'] &&
                    !in_array($salesperson->email, $ccRecipients)) {
                    $ccRecipients[] = $salesperson->email;
                    Log::info("Added salesperson to CC: {$salesperson->name} <{$salesperson->email}>");
                } else {
                    Log::info("Salesperson not found or no valid email for: {$softwareHandover->salesperson}");
                }
            }

            // Send the email with CC recipients
            Mail::html($emailData['content'], function (Message $message) use ($emailData, $ccRecipients) {
                $message->to($emailData['recipients'])
                    ->subject($emailData['subject'])
                    ->from($emailData['sender_email'], $emailData['sender_name']);

                // Add CC recipients if we have any
                if (!empty($ccRecipients)) {
                    $message->cc($ccRecipients);
                }

                // BCC the sender as well
                $message->bcc($emailData['sender_email']);
            });

            // Log email sent successfully
            Log::info('Follow-up email sent successfully', [
                'to' => $emailData['recipients'],
                'cc' => $ccRecipients,
                'subject' => $emailData['subject'],
                'implementer_log_id' => $emailData['implementer_log_id'] ?? null,
                'template' => $emailData['template_name'] ?? 'Unknown'
            ]);
        } catch (\Exception $e) {
            Log::error('Error in sendEmail method: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'data' => $emailData
            ]);
        }
    }

    public static function stopImplementerFollowUp(): Action
    {
        return Action::make('stop_follow_up')
            ->label('Stop Follow Up')
            ->color('danger')
            ->icon('heroicon-o-x-circle')
            ->requiresConfirmation()
            ->modalHeading('Stop Follow Up Process')
            ->modalDescription('This will create a final follow-up entry and mark the follow-up process as completed. Are you sure you want to continue?')
            ->modalWidth('lg');
    }

    /**
     * Process the stop follow-up action
     *
     * @param SoftwareHandover $record
     * @return ImplementerLogs|null
     */
    public static function processStopFollowUp(SoftwareHandover $record): ?ImplementerLogs
    {
        if (!$record) {
            Notification::make()
                ->title('Error: Software Handover record not found')
                ->danger()
                ->send();
            return null;
        }

        try {
            // Create description for the final follow-up
            $followUpDescription = 'Implementer Stop Follow Up By ' . auth()->user()->name;

            // Create a new implementer_logs entry with reference to SoftwareHandover
            $implementerLog = ImplementerLogs::create([
                'lead_id' => $record->lead_id,
                'description' => $followUpDescription,
                'causer_id' => auth()->id(),
                'remark' => 'Implementer Stop the Follow Up Features',
                'subject_id' => $record->id,
                'follow_up_date' => now()->format('Y-m-d'), // Today
            ]);

            // Cancel all scheduled emails related to this software handover
            $cancelledEmailsCount = self::cancelScheduledEmails($record);

            // Update the SoftwareHandover record to indicate follow-up is done
            $record->update([
                'follow_up_date' => now()->format('Y-m-d'), // Today
                'follow_up_counter' => false, // Stop future follow-ups (changed from true to false)
            ]);

            $message = 'Follow-up process stopped successfully';
            if ($cancelledEmailsCount > 0) {
                $message .= " and {$cancelledEmailsCount} scheduled email(s) were cancelled";
            }

            Notification::make()
                ->title($message)
                ->success()
                ->send();

            return $implementerLog;
        } catch (\Exception $e) {
            Log::error('Error stopping follow-up: ' . $e->getMessage());
            Notification::make()
                ->title('Error stopping follow-up')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return null;
        }
    }

    /**
     * Cancel all scheduled emails related to a software handover
     *
     * @param SoftwareHandover $record
     * @return int Number of cancelled emails
     */
    private static function cancelScheduledEmails(SoftwareHandover $record): int
    {
        try {
            // Find all implementer logs related to this software handover
            $implementerLogIds = ImplementerLogs::where('subject_id', $record->id)
                ->pluck('id')
                ->toArray();

            if (empty($implementerLogIds)) {
                return 0;
            }

            // Cancel scheduled emails that contain any of these implementer log IDs
            $cancelledCount = 0;
            $scheduledEmails = DB::table('scheduled_emails')
                ->where('status', 'New')
                ->whereNotNull('scheduled_date')
                ->whereDate('scheduled_date', '>=', now())
                ->get();

            foreach ($scheduledEmails as $scheduledEmail) {
                try {
                    $emailData = json_decode($scheduledEmail->email_data, true);

                    // Check if this scheduled email is related to our software handover
                    if (isset($emailData['implementer_log_id']) &&
                        in_array($emailData['implementer_log_id'], $implementerLogIds)) {

                        // Cancel the scheduled email
                        DB::table('scheduled_emails')
                            ->where('id', $scheduledEmail->id)
                            ->update([
                                'status' => 'Stop',
                                'updated_at' => now(),
                            ]);

                        $cancelledCount++;

                        Log::info("Cancelled scheduled email for implementer log ID: {$emailData['implementer_log_id']}");
                    }
                } catch (\Exception $e) {
                    Log::error("Error processing scheduled email ID {$scheduledEmail->id}: " . $e->getMessage());
                }
            }

            return $cancelledCount;
        } catch (\Exception $e) {
            Log::error('Error cancelling scheduled emails: ' . $e->getMessage());
            return 0;
        }
    }
}
