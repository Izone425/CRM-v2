<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Mail\CancelRepairAppointmentNotification;
use App\Mail\RepairAppointmentNotification;
use App\Models\ActivityLog;
use App\Models\AdminRepair;
use App\Models\Appointment;
use App\Models\ImplementerAppointment;
use App\Models\RepairAppointment;
use App\Models\User;
use App\Services\MicrosoftGraphService;
use App\Services\TemplateSelector;
use Carbon\Carbon;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
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

class ImplementerAppointmentRelationManager extends RelationManager
{
    protected static string $relationship = 'implementerAppointment';

    #[On('refresh-repair-appointments')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

    protected function getTableHeading(): string
    {
        return __('Implementer Appointments');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->user_id === auth()->id();
    }

    public function defaultForm()
    {
        return [
            DatePicker::make('date')
                ->required()
                ->label('DATE (MONDAY-THURSDAY/FRIDAY)')
                ->default(function ($record = null) {
                    return $record ? $record->date : Carbon::today()->toDateString();
                })
                ->reactive(),

            Grid::make(3)
            ->schema([
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
                                'SESSION 1' => 'SESSION 1',
                                'SESSION 2' => 'SESSION 2',
                                'SESSION 4' => 'SESSION 4',
                                'SESSION 5' => 'SESSION 5',
                            ];
                        }
                        // Monday to Thursday sessions (dayOfWeek = 1-4)
                        else if ($dayOfWeek >= 1 && $dayOfWeek <= 4) {
                            return [
                                'SESSION 1' => 'SESSION 1',
                                'SESSION 2' => 'SESSION 2',
                                'SESSION 3' => 'SESSION 3',
                                'SESSION 4' => 'SESSION 4',
                                'SESSION 5' => 'SESSION 5',
                            ];
                        }

                        // Weekend or invalid date
                        return ['NO_SESSIONS' => 'No sessions available on weekends'];
                    })
                    ->default(function (callable $get, ?Model $record = null) {
                        // If editing existing record, use its session value
                        if ($record && $record->session) {
                            return $record->session;
                        }

                        // For new records, select a default based on the day
                        $date = $get('date');
                        if (!$date) return null;

                        $selectedDate = Carbon::parse($date);
                        $dayOfWeek = $selectedDate->dayOfWeek;

                        // Default to SESSION 1 for all days
                        return 'SESSION 1';
                    })
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                        // Set the start_time and end_time based on selected session
                        $times = [
                            'SESSION 1' => ['09:30', '10:30'],
                            'SESSION 2' => ['11:00', '12:30'],
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
                TextInput::make('start_time_display')
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
                    })
                    ->placeholder('Will be set based on session'),

                TextInput::make('end_time_display')
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
                            'SESSION 2' => '12:30',
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
                    ->placeholder('Will be set based on session'),

                // These are hidden fields that will store the actual time values
                Hidden::make('start_time')
                    ->default('09:30'),

                Hidden::make('end_time')
                    ->default('10:30'),
            ]),
            Grid::make(3)
            ->schema([
                Select::make('type')
                    ->options(function () {
                        // Get the base lead
                        $lead = $this->getOwnerRecord();

                        // Count existing appointments
                        $existingAppointmentsCount = \App\Models\ImplementerAppointment::where('lead_id', $lead->id)
                            ->where('status', '!=', 'Cancelled')
                            ->count();

                        // Determine which appointment type should be selected by default
                        switch ($existingAppointmentsCount) {
                            case 0:
                                return [
                                    'KICK OFF MEETING SESSION' => 'KICK OFF MEETING SESSION',
                                    'IMPLEMENTATION SESSION 1' => 'IMPLEMENTATION SESSION 1',
                                    'IMPLEMENTATION SESSION 2' => 'IMPLEMENTATION SESSION 2',
                                    'IMPLEMENTATION SESSION 3' => 'IMPLEMENTATION SESSION 3',
                                    'IMPLEMENTATION SESSION 4' => 'IMPLEMENTATION SESSION 4',
                                    'IMPLEMENTATION SESSION 5' => 'IMPLEMENTATION SESSION 5',
                                ];
                            case 1:
                                return [
                                    'IMPLEMENTATION SESSION 1' => 'IMPLEMENTATION SESSION 1',
                                    'KICK OFF MEETING SESSION' => 'KICK OFF MEETING SESSION',
                                    'IMPLEMENTATION SESSION 2' => 'IMPLEMENTATION SESSION 2',
                                    'IMPLEMENTATION SESSION 3' => 'IMPLEMENTATION SESSION 3',
                                    'IMPLEMENTATION SESSION 4' => 'IMPLEMENTATION SESSION 4',
                                    'IMPLEMENTATION SESSION 5' => 'IMPLEMENTATION SESSION 5',
                                ];
                            case 2:
                                return [
                                    'IMPLEMENTATION SESSION 2' => 'IMPLEMENTATION SESSION 2',
                                    'KICK OFF MEETING SESSION' => 'KICK OFF MEETING SESSION',
                                    'IMPLEMENTATION SESSION 1' => 'IMPLEMENTATION SESSION 1',
                                    'IMPLEMENTATION SESSION 3' => 'IMPLEMENTATION SESSION 3',
                                    'IMPLEMENTATION SESSION 4' => 'IMPLEMENTATION SESSION 4',
                                    'IMPLEMENTATION SESSION 5' => 'IMPLEMENTATION SESSION 5',
                                ];
                            case 3:
                                return [
                                    'IMPLEMENTATION SESSION 3' => 'IMPLEMENTATION SESSION 3',
                                    'KICK OFF MEETING SESSION' => 'KICK OFF MEETING SESSION',
                                    'IMPLEMENTATION SESSION 1' => 'IMPLEMENTATION SESSION 1',
                                    'IMPLEMENTATION SESSION 2' => 'IMPLEMENTATION SESSION 2',
                                    'IMPLEMENTATION SESSION 4' => 'IMPLEMENTATION SESSION 4',
                                    'IMPLEMENTATION SESSION 5' => 'IMPLEMENTATION SESSION 5',
                                ];
                            case 4:
                                return [
                                    'IMPLEMENTATION SESSION 4' => 'IMPLEMENTATION SESSION 4',
                                    'KICK OFF MEETING SESSION' => 'KICK OFF MEETING SESSION',
                                    'IMPLEMENTATION SESSION 1' => 'IMPLEMENTATION SESSION 1',
                                    'IMPLEMENTATION SESSION 2' => 'IMPLEMENTATION SESSION 2',
                                    'IMPLEMENTATION SESSION 3' => 'IMPLEMENTATION SESSION 3',
                                    'IMPLEMENTATION SESSION 5' => 'IMPLEMENTATION SESSION 5',
                                ];
                            default:
                                return [
                                    'IMPLEMENTATION SESSION 5' => 'IMPLEMENTATION SESSION 5',
                                    'KICK OFF MEETING SESSION' => 'KICK OFF MEETING SESSION',
                                    'IMPLEMENTATION SESSION 1' => 'IMPLEMENTATION SESSION 1',
                                    'IMPLEMENTATION SESSION 2' => 'IMPLEMENTATION SESSION 2',
                                    'IMPLEMENTATION SESSION 3' => 'IMPLEMENTATION SESSION 3',
                                    'IMPLEMENTATION SESSION 4' => 'IMPLEMENTATION SESSION 4',
                                ];
                        }
                    })
                    ->default(function () {
                        // Get the base lead
                        $lead = $this->getOwnerRecord();

                        // Count existing appointments (excluding cancelled ones)
                        $existingAppointmentsCount = \App\Models\ImplementerAppointment::where('lead_id', $lead->id)
                            ->where('status', '!=', 'Cancelled')
                            ->count();

                        // Set default type based on number of existing appointments
                        switch ($existingAppointmentsCount) {
                            case 0:
                                return 'KICK OFF MEETING SESSION';
                            case 1:
                                return 'IMPLEMENTATION SESSION 1';
                            case 2:
                                return 'IMPLEMENTATION SESSION 2';
                            case 3:
                                return 'IMPLEMENTATION SESSION 3';
                            case 4:
                                return 'IMPLEMENTATION SESSION 4';
                            default:
                                return 'IMPLEMENTATION SESSION 5';
                        }
                    })
                    ->required()
                    ->disabled()
                    ->dehydrated(true)
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
                        $technicians = \App\Models\User::where('role_id', 4)
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(function ($tech) {
                                return [$tech->name => $tech->name];
                            })
                            ->toArray();
                        return $technicians;
                    })
                    ->default(function ($record = null) {
                        return $record ? $record->implementer : null;
                    })
                    ->searchable()
                    ->required()
                    ->placeholder('Select a implementer'),
                ]),

            TextInput::make('required_attendees')
                ->label('REQUIRED ATTENDEES')
                ->default(function ($record = null) {
                    if ($record && !empty($record->required_attendees)) {
                        if (is_string($record->required_attendees) && $this->isJson($record->required_attendees)) {
                            $attendees = json_decode($record->required_attendees, true);
                            return is_array($attendees) ? implode(';', $attendees) : '';
                        }
                        return $record->required_attendees;
                    }
                    return null;
                })
                ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),

            Textarea::make('remarks')
                ->label('REMARKS')
                ->rows(3)
                ->autosize()
                ->default(function ($record = null) {
                    return $record ? $record->remarks : '';
                })
                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->headerActions($this->headerActions())
            ->columns([
                TextColumn::make('implementer')
                    ->label('IMPLEMENTER')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('   IMPLEMENTATION TYPE')
                    ->sortable(),
                TextColumn::make('appointment_type')
                    ->label('APPOINTMENT TYPE')
                    ->sortable(),
                // TextColumn::make('date')
                //     ->label('DATE & TIME')
                //     ->sortable()
                //     ->formatStateUsing(function ($record) {
                //         if (!$record->date || !$record->start_time || !$record->end_time) {
                //             return 'No Data Available';
                //         }

                //         // Format the date
                //         $date = \Carbon\Carbon::createFromFormat('Y-m-d', $record->date)->format('d M Y');

                //         // Format the start and end times
                //         $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $record->start_time)->format('h:i A');
                //         $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $record->end_time)->format('h:i A');

                //         return "{$date} | {$startTime} - {$endTime}";
                //     }),
                IconColumn::make('view_remark')
                    ->label('View Remark')
                    ->alignCenter()
                    ->getStateUsing(fn() => true)
                    ->icon(fn () => 'heroicon-o-magnifying-glass-plus')
                    ->color(fn () => 'blue')
                    ->tooltip('View Remark')
                    ->extraAttributes(['class' => 'cursor-pointer'])
                    ->action(
                        Action::make('view_remarks')
                            ->label('View Remark')
                            ->modalHeading('Appointment Remarks')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalDescription('Here are the remarks for this specific implementation appointment.')
                            ->modalContent(function (ImplementerAppointment $record) {
                                // Check if the appointment has direct remarks
                                if (!empty($record->remarks)) {
                                    // Format the direct remarks
                                    $timestamp = $record->updated_at->format('Y-m-d H:i:s');
                                    $formattedRemark = nl2br(e($record->remarks));

                                    // Build the HTML for the remarks
                                    $remarksHtml = '<div class="p-4 rounded-lg bg-gray-50">';
                                    // $remarksHtml .= "<p class='mb-1 text-sm text-gray-500'>Last updated: <strong>{$timestamp}</strong></p>";
                                    $remarksHtml .= "<div class='text-gray-800 whitespace-pre-line'>{$formattedRemark}</div>";
                                    $remarksHtml .= '</div>';

                                    return new HtmlString($remarksHtml);
                                }

                                // If no direct remarks, show a message
                                return new HtmlString('<p class="p-4 text-center text-gray-500">No remarks available for this appointment.</p>');
                            }),
                        ),
                TextColumn::make('status')
                    ->label('STATUS')
                    ->sortable()
                    ->color(fn ($state) => match ($state) {
                        'Completed' => 'success',
                        'Cancelled' => 'danger',
                        'New' => 'warning',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'Completed' => 'heroicon-o-check-circle',
                        'Cancelled' => 'heroicon-o-x-circle',
                        'New' => 'heroicon-o-clock',
                        default => 'heroicon-o-question-mark-circle',
                    }),

            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\Action::make('View Appointment')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->modalHeading('Implementation Appointment Details')
                        ->modalSubmitAction(false)
                        ->form(function ($record) {
                            if (!$record) {
                                return [
                                    TextInput::make('error')->default('Appointment not found')->disabled(),
                                ];
                            }

                            return [
                                DatePicker::make('date')
                                    ->label('Date')
                                    ->default($record->date)
                                    ->disabled(),


                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('session')
                                            ->label('SESSION')
                                            ->default(strtoupper($record->session))
                                            ->disabled(),

                                        TimePicker::make('start_time')
                                            ->label('START TIME')
                                            ->default($record->start_time)
                                            ->disabled(),

                                        TimePicker::make('end_time')
                                            ->label('END TIME')
                                            ->default($record->end_time)
                                            ->disabled(),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('type')
                                            ->label('DEMO TYPE')
                                            ->default(strtoupper($record->type))
                                            ->disabled(),

                                        TextInput::make('appointment_type')
                                            ->label('APPOINTMENT TYPE')
                                            ->default($record->appointment_type)
                                            ->disabled(),

                                        TextInput::make('implementer')
                                            ->label('IMPLEMENTER')
                                            ->default($record->implementer)
                                            ->disabled(),
                                    ]),

                                TextInput::make('required_attendees')
                                    ->label('REQUIERED ATTENDEES')
                                    ->default($record->required_attendees)
                                    ->disabled(),

                                Textarea::make('remarks')
                                    ->label('REMARKS')
                                    ->default($record->remarks)
                                    ->autosize()
                                    ->disabled()
                                    ->reactive()
                                    ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
                            ];
                        }),
                    Tables\Actions\Action::make('appointment_cancel')
                        ->visible(fn (ImplementerAppointment $appointment) =>
                            now()->lte(Carbon::parse($appointment->appointment_date)->addDays(7))
                        )
                        ->label(__('Cancel Appointment'))
                        ->modalHeading('Cancel Implementation Appointment')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->action(function (array $data, ImplementerAppointment $record) {
                            // Update the Appointment status
                            $record->update([
                                'status' => 'Cancelled',
                                // 'cancelled_at' => now(),
                                // 'cancelled_by' => auth()->id(),
                                // 'cancel_reason' => $data['cancel_reason'] ?? null,
                            ]);

                            // Send cancellation email notification
                            $lead = $this->ownerRecord;

                            // Set up email recipients
                            $recipients = ['admin.timetec.hr@timeteccloud.com']; // Admin email
                            // $recipients = ['zilih.ng@timeteccloud.com']; // Admin email

                            // Process required attendees from saved data
                            $requiredAttendees = null;
                            if (!empty($record->required_attendees)) {
                                if ($this->isJson($record->required_attendees)) {
                                    $requiredAttendees = json_decode($record->required_attendees, true);
                                } else {
                                    $requiredAttendees = array_filter(array_map('trim', explode(';', $record->required_attendees)));
                                }

                                // Add valid email addresses to recipients
                                if (is_array($requiredAttendees)) {
                                    foreach ($requiredAttendees as $email) {
                                        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $recipients)) {
                                            $recipients[] = $email;
                                        }
                                    }
                                }
                            }

                            // Ensure recipients are unique
                            $recipients = array_unique($recipients);

                            // Prepare email content
                            $emailContent = [
                                'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager',
                                'lead' => [
                                    'company' => $lead->companyDetail->company_name ?? 'N/A',
                                    'implementerName' => $record->implementer ?? 'N/A',
                                    'date' => Carbon::parse($record->date)->format('d/m/Y'),
                                    'startTime' => Carbon::parse($record->start_time)->format('h:i A'),
                                    'endTime' => Carbon::parse($record->end_time)->format('h:i A'),
                                    'pic' => optional($lead->companyDetail)->name ?? $lead->name ?? 'N/A',
                                    'phone' => optional($lead->companyDetail)->contact_no ?? $lead->phone ?? 'N/A',
                                    'email' => optional($lead->companyDetail)->email ?? $lead->email ?? 'N/A',
                                    'demo_type' => $record->type,
                                    'appointment_type' => $record->appointment_type,
                                    'cancelReason' => $data['cancel_reason'] ?? 'No reason provided',
                                ],
                            ];

                            $viewName = 'emails.implementer_appointment_cancel';

                            $authUser = auth()->user();
                            $senderEmail = $authUser->email;
                            $senderName = $authUser->name;

                            try {
                                // Send email with template and custom subject format
                                if (count($recipients) > 0) {
                                    \Illuminate\Support\Facades\Mail::send($viewName, ['content' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $lead, $record) {
                                        $message->from($senderEmail, $senderName)
                                            ->to($recipients)
                                            ->subject("CANCELLED: TIMETEC IMPLEMENTATION APPOINTMENT | {$record->type} | {$lead->companyDetail->company_name} | " .
                                                    Carbon::parse($record->date)->format('d M Y'));
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
                                ->title('You have cancelled a implementation appointment')
                                ->danger()
                                ->send();
                        }),

                    Tables\Actions\Action::make('reschedule_appointment')
                        ->label('Reschedule')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->modalHeading('Reschedule Implementation Appointment')
                        ->form($this->defaultForm())
                        ->visible(fn (ImplementerAppointment $record) =>
                            $record->status !== 'Cancelled' && $record->status !== 'Completed'
                        )
                        ->action(function (array $data, ImplementerAppointment $record) {
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

                            // Log the activity
                            ActivityLog::create([
                                'user_id' => auth()->id(),
                                'action' => 'Rescheduled Implementation Appointment',
                                'description' => "Rescheduled implementation appointment from {$oldDate} {$oldStartTime}-{$oldEndTime} to " .
                                                 Carbon::parse($data['date'])->format('d/m/Y') . " " .
                                                 Carbon::parse($data['start_time'])->format('h:i A') . "-" .
                                                 Carbon::parse($data['end_time'])->format('h:i A'),
                                'subject_type' => ImplementerAppointment::class,
                                'subject_id' => $record->id,
                            ]);

                            // Send email notification about the rescheduled appointment
                            $lead = $this->ownerRecord;

                            $recipients = ['admin.timetec.hr@timeteccloud.com']; // Always include admin
                            // $recipients = ['zilih.ng@timeteccloud.com']; // Admin email

                            // Add the lead owner's email if available
                            // $leadOwner = User::where('name', $lead->lead_owner)->first();
                            // if ($leadOwner && !empty($leadOwner->email)) {
                            //     $recipients[] = $leadOwner->email;
                            // }

                            // // Add company contact email if available
                            // if (!empty($lead->companyDetail->email)) {
                            //     $recipients[] = $lead->companyDetail->email;
                            // }

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
                                'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager',
                                'lead' => [
                                    'company' => $lead->companyDetail->company_name ?? 'N/A',
                                    'implementerName' => $record->implementer ?? 'N/A',
                                    'date' => Carbon::parse($data['date'])->format('d/m/Y'),
                                    'startTime' => Carbon::parse($data['start_time'])->format('h:i A'),
                                    'endTime' => Carbon::parse($data['end_time'])->format('h:i A'),
                                    'oldDate' => $oldDate,
                                    'oldStartTime' => $oldStartTime,
                                    'oldEndTime' => $oldEndTime,
                                    'pic' => optional($lead->companyDetail)->name ?? $lead->name ?? 'N/A',
                                    'phone' => optional($lead->companyDetail)->contact_no ?? $lead->phone ?? 'N/A',
                                    'email' => optional($lead->companyDetail)->email ?? $lead->email ?? 'N/A',
                                    'rescheduleReason' => $data['reschedule_reason'] ?? 'No reason provided',
                                ],
                            ];

                            try {
                                // Send email with template and custom subject format
                                if (count($recipients) > 0) {
                                    \Illuminate\Support\Facades\Mail::send($viewName, ['content' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $lead, $data) {
                                        $message->from($senderEmail, $senderName)
                                            ->to($recipients)
                                            ->subject("TIMETEC IMPLEMENTATION APPOINTMENT | {$data['type']} | {$lead->companyDetail->company_name} | " . Carbon::parse($data['date'])->format('d/m/Y'));
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
                ->modalHeading('Add Implementation Appointment')
                ->hidden(function() {
                    $user = auth()->user();
                    // Only allow admin, technicians, and resellers to schedule appointments
                    return !in_array($user->role_id, [3, 9]) && is_null($this->getOwnerRecord()->lead_owner);
                })
                ->form($this->defaultForm())
                ->action(function (array $data) {
                    // Get the lead record
                    $lead = $this->getOwnerRecord();

                    // Process required attendees from form data
                    $requiredAttendeesInput = $data['required_attendees'] ?? '';
                    $attendeeEmails = [];
                    if (!empty($requiredAttendeesInput)) {
                        $attendeeEmails = array_filter(array_map('trim', explode(';', $requiredAttendeesInput)));
                    }

                    // Create a new Appointment
                    $appointment = new \App\Models\ImplementerAppointment();
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
                        'title' => $data['type'] . ' | ' . $data['appointment_type'] . ' | TIMETEC IMPLEMENTER | ' . $lead->companyDetail->company_name ?? 'Client',
                        'required_attendees' => !empty($attendeeEmails) ? json_encode($attendeeEmails) : null,
                        'status' => 'New',
                        'session' => $data['session'] ?? null,
                    ]);

                    // Save the appointment
                    $appointment->save();

                    // Set up email recipients for notification
                    $recipients = ['admin.timetec.hr@timeteccloud.com']; // Always include admin
                    // $recipients = ['zilih.ng@timeteccloud.com']; // Always include admin

                    // Add required attendees if they have valid emails
                    foreach ($attendeeEmails as $email) {
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $recipients[] = $email;
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
                            'phone' => optional($lead->companyDetail)->contact_no ?? $lead->phone ?? 'N/A',
                            'pic' => optional($lead->companyDetail)->name ?? $lead->name ?? 'N/A',
                            'email' => optional($lead->companyDetail)->email ?? $lead->email ?? 'N/A',
                            'date' => Carbon::parse($data['date'])->format('d/m/Y') ?? 'N/A',
                            'startTime' => Carbon::parse($data['start_time'])->format('h:i A') ?? 'N/A',
                            'endTime' => Carbon::parse($data['end_time'])->format('h:i A') ?? 'N/A',
                            'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                            'session' => $data['session'] ?? 'N/A',
                            'demo_type' => $data['type'],
                            'appointment_type' => $data['appointment_type'],
                            'remarks' => $data['remarks'] ?? 'N/A',
                        ],
                    ];

                    // Get authenticated user's email for sender
                    $authUser = auth()->user();
                    $senderEmail = $authUser->email;
                    $senderName = $authUser->name;

                    try {
                        // Send email with template and custom subject format
                        if (count($recipients) > 0) {
                            \Illuminate\Support\Facades\Mail::send($viewName, ['content' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $lead, $data) {
                                $message->from($senderEmail, $senderName)
                                    ->to($recipients)
                                    ->subject("TIMETEC IMPLEMENTER APPOINTMENT | {$data['type']} | {$lead->companyDetail->company_name} | " . Carbon::parse($data['date'])->format('d/m/Y'));
                            });

                            Notification::make()
                                ->title('Implementer appointment notification sent')
                                ->success()
                                ->body('Email notification sent to administrator and required attendees')
                                ->send();
                        }
                    } catch (\Exception $e) {
                        // Handle email sending failure
                        Log::error("Email sending failed for implementer appointment: Error: {$e->getMessage()}");

                        Notification::make()
                            ->title('Email Notification Failed')
                            ->danger()
                            ->body('Could not send email notification: ' . $e->getMessage())
                            ->send();
                    }

                    // Log the activity
                    ActivityLog::create([
                        'user_id' => auth()->id(),
                        'causer_id' => auth()->id(),
                        'action' => 'Added Implementer Appointment',
                        'description' => "Added a new implementer appointment for lead: {$lead->companyDetail->company_name} ({$lead->id})",
                        'subject_type' => ImplementerAppointment::class,
                        'subject_id' => $appointment->id,
                    ]);

                    Notification::make()
                        ->title('Implementer Appointment Added Successfully')
                        ->success()
                        ->send();

                    $this->dispatch('refresh');
                }),
        ];
    }

    private function isJson($string) {
        if (!is_string($string)) return false;
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
