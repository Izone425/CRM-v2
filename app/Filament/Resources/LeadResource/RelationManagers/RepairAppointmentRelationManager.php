<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Mail\CancelRepairAppointmentNotification;
use App\Mail\RepairAppointmentNotification;
use App\Models\ActivityLog;
use App\Models\Appointment;
use App\Models\RepairAppointment;
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

class RepairAppointmentRelationManager extends RelationManager
{
    protected static string $relationship = 'repairAppointment';

    #[On('refresh-repair-appointments')]
    #[On('refresh')] // General refresh event
    public function refresh()
    {
        $this->resetTable();
    }

    protected function getTableHeading(): string
    {
        return __('Repair Appointments');
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
                TextColumn::make('technician')
                    ->label('TECHNICIAN')
                    ->sortable(),
                TextColumn::make('type')
                    ->label('REPAIR TYPE')
                    ->sortable(),
                TextColumn::make('appointment_type')
                    ->label('APPOINTMENT TYPE')
                    ->sortable(),
                TextColumn::make('date')
                    ->label('DATE & TIME')
                    ->sortable()
                    ->formatStateUsing(function ($record) {
                        if (!$record->date || !$record->start_time || !$record->end_time) {
                            return 'No Data Available';
                        }

                        // Format the date
                        $date = \Carbon\Carbon::createFromFormat('Y-m-d', $record->date)->format('d M Y');

                        // Format the start and end times
                        $startTime = \Carbon\Carbon::createFromFormat('H:i:s', $record->start_time)->format('h:i A');
                        $endTime = \Carbon\Carbon::createFromFormat('H:i:s', $record->end_time)->format('h:i A');

                        return "{$date} | {$startTime} - {$endTime}";
                    }),
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
                            ->modalDescription('Here are the remarks for this specific repair appointment.')
                            ->modalContent(function (Appointment $record) {
                                // Retrieve activity logs that match the lead of this appointment
                                $activityLogs = \App\Models\ActivityLog::where('subject_id', $record->lead->id)
                                    ->where('subject_type', 'App\Models\Lead')
                                    ->orderBy('created_at', 'asc')
                                    ->get();

                                if ($activityLogs->isEmpty()) {
                                    return new HtmlString('<p>No remarks available for this appointment.</p>');
                                }

                                // Filter logs based on `repair_appointment` value matching the current appointment ID
                                $filteredLogs = $activityLogs->filter(function ($log) use ($record) {
                                    $properties = json_decode($log->properties, true);
                                    return isset($properties['attributes']['repair_appointment']) &&
                                        $properties['attributes']['repair_appointment'] == $record->id;
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
                        ->modalHeading('Repair Appointment Details')
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
                                            ->label('Repair Type')
                                            ->default(strtoupper($record->type))
                                            ->disabled(),

                                        TextInput::make('appointment_type')
                                            ->label('Appointment Type')
                                            ->default($record->appointment_type)
                                            ->disabled(),

                                        TextInput::make('technician')
                                            ->label('Technician')
                                            ->default(fn ($record) => \App\Models\User::find($record->technician)?->name ?? 'N/A')
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
                    Tables\Actions\Action::make('appointment_cancel')
                        ->visible(fn (RepairAppointment $appointment) =>
                            now()->lte(Carbon::parse($appointment->appointment_date)->addDays(7))
                        )
                        ->label(__('Cancel Appointment'))
                        ->modalHeading('Cancel Repair Appointment')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->action(function (array $data, $record) {
                            $appointment = $record;

                            // Get event details
                            $eventId = $appointment->event_id;
                            $technician = User::find($appointment->technician);

                            if (!$technician || !$technician->email) {
                                Notification::make()
                                    ->title('Technician Not Found')
                                    ->danger()
                                    ->body('The technician assigned to this appointment could not be found or does not have an email address.')
                                    ->send();
                                return;
                            }


                            // Update the Appointment status
                            $appointment->update([
                                'status' => 'Cancelled',
                            ]);

                            Notification::make()
                                ->title('You have cancelled a repair appointment')
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
                ->modalHeading('Add Repair Appointment')
                ->hidden(function() {
                    $user = auth()->user();
                    // Only allow admin, technicians, and resellers to schedule appointments
                    return !in_array($user->role_id, [3, 9]) && is_null($this->getOwnerRecord()->lead_owner);
                })
                ->form([
                    // Schedule
                    ToggleButtons::make('mode')
                        ->label('')
                        ->options([
                            'auto' => 'Auto',
                            'custom' => 'Custom',
                        ])
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

                    Grid::make(3)
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

                                    if ($user && in_array($user->role_id, [9]) && $date) {
                                        // Fetch all booked appointments as full models
                                        $appointments = RepairAppointment::where('technician', $user->id)
                                            ->whereDate('date', $date)
                                            ->whereIn('status', ['New', 'Completed'])
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
                                        return [];
                                    }

                                    $times = [];
                                    $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30));

                                    if ($user && in_array($user->role_id, [9]) && $date) {
                                        // Fetch booked time slots for this technician on the selected date
                                        $bookedAppointments = RepairAppointment::where('technician', $user->id)
                                            ->whereDate('date', $date)
                                            ->pluck('end_time', 'start_time')
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
                            Grid::make(3)
                            ->schema([
                                Select::make('type')
                                    ->options([
                                        'NEW INSTALLATION' => 'NEW INSTALLATION',
                                        'REPAIR' => 'REPAIR',
                                        'MAINTENANCE SERVICE' => 'MAINTENANCE SERVICE',
                                    ])
                                    ->default('NEW INSTALLATION')
                                    ->required()
                                    ->label('DEMO TYPE')
                                    ->reactive(),

                                Select::make('appointment_type')
                                    ->options([
                                        'ONSITE' => 'ONSITE',
                                    ])
                                    ->required()
                                    ->default('ONSITE')
                                    ->label('APPOINTMENT TYPE'),

                                Select::make('technician')
                                    ->label('TECHNICIAN')
                                    ->options(function () {
                                        // Get technicians (role_id 9) with their names as both keys and values
                                        $technicians = \App\Models\User::where('role_id', 9)
                                            ->orderBy('name')
                                            ->get()
                                            ->mapWithKeys(function ($tech) {
                                                return [$tech->name => $tech->name];
                                            })
                                            ->toArray();

                                        // Get resellers from reseller table with their names as both keys and values
                                        $resellers = \App\Models\Reseller::orderBy('company_name')
                                            ->get()
                                            ->mapWithKeys(function ($reseller) {
                                                return [$reseller->company_name => $reseller->company_name];
                                            })
                                            ->toArray();

                                        // Return as option groups
                                        return [
                                            'Internal Technicians' => $technicians,
                                            'Reseller Partners' => $resellers,
                                        ];
                                    })
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Select a technician')
                                ]),
                    Textarea::make('remarks')
                        ->label('REMARKS')
                        ->rows(3)
                        ->autosize()
                        ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

                    TextInput::make('required_attendees')
                        ->label('Required Attendees')
                        ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),
                ])
                ->action(function (RepairAppointment $appointment, array $data) {
                    // Create a new Appointment and store the form data in the appointments table
                    $lead = $this->ownerRecord;
                    $appointment = new \App\Models\RepairAppointment();
                    $appointment->fill([
                        'repair_handover_id' => $lead->repairHandover()->latest()->first()?->id ?? null,
                        'lead_id' => $lead->id,
                        'type' => $data['type'],
                        'appointment_type' => $data['appointment_type'],
                        'date' => $data['date'],
                        'start_time' => $data['start_time'],
                        'end_time' => $data['end_time'],
                        'technician' => $data['technician'],
                        'causer_id' => auth()->user()->id,
                        'technician_assigned_date' => now(),
                        'remarks' => $data['remarks'],
                        'title' => $data['type']. ' | '. $data['appointment_type']. ' | TIMETEC REPAIR | ' . $lead->companyDetail->company_name,
                        'required_attendees' => json_encode($data['required_attendees']),
                    ]);
                    $appointment->save();

                    $requiredAttendees = $data['required_attendees'] ?? null;
                    $attendeeEmails = [];
                    if (!empty($requiredAttendees)) {
                        $attendeeEmails = array_filter(array_map('trim', explode(';', $requiredAttendees)));
                    }

                    // Set up email recipients
                    // $recipients = ['admin.timetec.hr@timeteccloud.com']; // Always include admin
                    $recipients = ['zilih020906@gmail.com']; // Always include admin

                    // Add required attendees if they have valid emails
                    foreach ($attendeeEmails as $email) {
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $recipients[] = $email;
                        }
                    }

                    // Prepare email content
                    $viewName = 'emails.repair_appointment_notification';
                    $leadowner = User::where('name', $lead->lead_owner)->first();

                    $emailContent = [
                        'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager',
                        'lead' => [
                            'lastName' => $lead->companyDetail->name ?? $lead->name,
                            'company' => $lead->companyDetail->company_name ?? 'N/A',
                            'technicianName' => $data['technician'] ?? 'N/A',
                            'phone' => optional($lead->companyDetail)->contact_no ?? $lead->phone ?? 'N/A',
                            'pic' => optional($lead->companyDetail)->name ?? $lead->name ?? 'N/A',
                            'email' => optional($lead->companyDetail)->email ?? $lead->email ?? 'N/A',
                            'date' => Carbon::parse($data['date'])->format('d/m/Y') ?? 'N/A',
                            'startTime' => Carbon::parse($data['start_time'])->format('h:i A') ?? 'N/A',
                            'endTime' => Carbon::parse($data['end_time'])->format('h:i A') ?? 'N/A',
                            'leadOwnerMobileNumber' => $leadowner->mobile_number ?? 'N/A',
                            'repair_type' => $data['type'],
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
                                    ->subject("TIMETEC REPAIR APPOINTMENT | {$data['type']} | {$lead->companyDetail->company_name} | " . Carbon::parse($data['date'])->format('d/m/Y'));
                            });

                            Notification::make()
                                ->title('Repair appointment notification sent')
                                ->success()
                                ->body('Email notification sent to administrator and required attendees')
                                ->send();
                        }
                    } catch (\Exception $e) {
                        // Handle email sending failure
                        Log::error("Email sending failed for repair appointment: Error: {$e->getMessage()}");

                        Notification::make()
                            ->title('Email Notification Failed')
                            ->danger()
                            ->body('Could not send email notification: ' . $e->getMessage())
                            ->send();
                    }

                    $appointment = $lead->repairAppointment()->latest()->first();
                    if ($appointment) {
                        $appointment->update([
                            'status' => 'New',
                        ]);
                    }

                    $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                            ->orderByDesc('created_at')
                            ->first();

                    if ($latestActivityLog) {
                        $technicianName = \App\Models\User::find($data['technician'] ?? auth()->user()->id)?->name ?? 'Unknown Technician';

                        $latestActivityLog->update([
                            'description' => 'Repair appointment created. ' . $data['type'] . ' - ' . $data['appointment_type'] . ' - ' . $data['date'] . ' - ' . $technicianName
                        ]);
                        activity()
                            ->causedBy(auth()->user())
                            ->performedOn($lead);
                    }

                    Notification::make()
                        ->title('Repair Appointment Added Successfully')
                        ->success()
                        ->send();
                }),
        ];
    }
}
