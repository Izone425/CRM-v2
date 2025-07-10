<?php

namespace App\Filament\Resources\LeadResource\RelationManagers;

use App\Enums\LeadStageEnum;
use App\Enums\LeadStatusEnum;
use App\Mail\CancelRepairAppointmentNotification;
use App\Mail\RepairAppointmentNotification;
use App\Models\ActivityLog;
use App\Models\AdminRepair;
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

    public function defaultForm()
    {
        return [
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
                        ->default(function ($record = null) {
                            return $record ? $record->date : Carbon::today()->toDateString();
                        })
                        ->reactive(),

                    TimePicker::make('start_time')
                        ->label('START TIME')
                        ->required()
                        ->seconds(false)
                        ->reactive()
                        ->default(function ($record = null) {
                            if ($record) {
                                return $record->start_time;
                            }
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
                        ->default(function ($record = null, callable $get) {
                            if ($record) {
                                return $record->end_time;
                            }
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
                            ->default(function ($record = null) {
                                // If we have a record (for reschedule), use its type
                                if ($record) {
                                    return $record->type;
                                }
                                // For new appointments, default to NEW INSTALLATION
                                return 'NEW INSTALLATION';
                            })
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
                            ->default(function ($record = null) {
                                return $record ? $record->technician : null;
                            })
                            ->searchable()
                            ->required()
                            ->placeholder('Select a technician')
                        ]),
            Textarea::make('remarks')
                ->label('REMARKS')
                ->rows(3)
                ->autosize()
                ->default(function ($record = null) {
                    return $record ? $record->remarks : '';
                })
                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

            TextInput::make('required_attendees')
                ->label('Required Attendees')
                ->default(function ($record = null) {
                    if ($record && !empty($record->required_attendees)) {
                        // If it looks like JSON, decode it and format as semicolon-separated string
                        if (is_string($record->required_attendees) && $this->isJson($record->required_attendees)) {
                            $attendees = json_decode($record->required_attendees, true);
                            return is_array($attendees) ? implode(';', $attendees) : '';
                        }
                        return $record->required_attendees;
                    }
                    return null; // Return null for new appointments
                })
                ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),
        ];
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
                            ->modalContent(function (RepairAppointment $record) {
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
                                            ->default($record->technician)
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
                        ->action(function (array $data, RepairAppointment $record) {
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
                                    'technicianName' => $record->technician ?? 'N/A',
                                    'date' => Carbon::parse($record->date)->format('d/m/Y'),
                                    'startTime' => Carbon::parse($record->start_time)->format('h:i A'),
                                    'endTime' => Carbon::parse($record->end_time)->format('h:i A'),
                                    'pic' => optional($lead->companyDetail)->name ?? $lead->name ?? 'N/A',
                                    'phone' => optional($lead->companyDetail)->contact_no ?? $lead->phone ?? 'N/A',
                                    'email' => optional($lead->companyDetail)->email ?? $lead->email ?? 'N/A',
                                    'repair_type' => $record->type,
                                    'appointment_type' => $record->appointment_type,
                                    'cancelReason' => $data['cancel_reason'] ?? 'No reason provided',
                                ],
                            ];

                            $repairHandoverId = $lead->repairHandover()->latest()->first()?->id ?? null;

                            if ($repairHandoverId) {
                                $adminRepair = \App\Models\AdminRepair::find($repairHandoverId);
                                if ($adminRepair) {
                                    $adminRepair->update([
                                        'status' => 'Pending Onsite Repair',
                                    ]);
                                }
                            }

                            $viewName = 'emails.repair_appointment_cancel';

                            $authUser = auth()->user();
                            $senderEmail = $authUser->email;
                            $senderName = $authUser->name;

                            try {
                                // Send email with template and custom subject format
                                if (count($recipients) > 0) {
                                    \Illuminate\Support\Facades\Mail::send($viewName, ['content' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $lead, $record) {
                                        $message->from($senderEmail, $senderName)
                                            ->to($recipients)
                                            ->subject("CANCELLED: TIMETEC REPAIR APPOINTMENT | {$record->type} | {$lead->companyDetail->company_name} | " .
                                                    Carbon::parse($record->date)->format('d M Y'));
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

                            Notification::make()
                                ->title('You have cancelled a repair appointment')
                                ->danger()
                                ->send();
                        }),

                    Tables\Actions\Action::make('reschedule_appointment')
                        ->label('Reschedule')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->modalHeading('Reschedule Repair Appointment')
                        ->form($this->defaultForm())
                        ->visible(fn (RepairAppointment $record) =>
                            $record->status !== 'Cancelled' && $record->status !== 'Completed'
                        )
                        ->action(function (array $data, RepairAppointment $record) {
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
                                'technician' => $data['technician'] ?? $record->technician,
                                'required_attendees' => !empty($attendeeEmails) ? json_encode($attendeeEmails) : null,
                                'updated_at' => now(),
                            ]);

                            // Log the activity
                            ActivityLog::create([
                                'user_id' => auth()->id(),
                                'action' => 'Rescheduled Repair Appointment',
                                'description' => "Rescheduled repair appointment from {$oldDate} {$oldStartTime}-{$oldEndTime} to " .
                                                 Carbon::parse($data['date'])->format('d/m/Y') . " " .
                                                 Carbon::parse($data['start_time'])->format('h:i A') . "-" .
                                                 Carbon::parse($data['end_time'])->format('h:i A'),
                                'subject_type' => RepairAppointment::class,
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
                            $viewName = 'emails.repair_appointment_reschedule';

                            $recipients = array_unique($recipients);
                            $authUser = auth()->user();
                            $senderEmail = $authUser->email;
                            $senderName = $authUser->name;
                            // Prepare email content with reschedule reason
                            $emailContent = [
                                'leadOwnerName' => $lead->lead_owner ?? 'Unknown Manager',
                                'lead' => [
                                    'company' => $lead->companyDetail->company_name ?? 'N/A',
                                    'technicianName' => $record->technician ?? 'N/A',
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

                            Notification::make()
                                ->title('Repair Appointment Rescheduled Successfully')
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
                ->modalHeading('Add Repair Appointment')
                ->hidden(function() {
                    $user = auth()->user();
                    // Only allow admin, technicians, and resellers to schedule appointments
                    return !in_array($user->role_id, [3, 9]) && is_null($this->getOwnerRecord()->lead_owner);
                })
                ->form($this->defaultForm())
                ->action(function (RepairAppointment $appointment, array $data) {
                    // Create a new Appointment and store the form data in the appointments table
                    $lead = $this->ownerRecord;
                    $appointment = new \App\Models\RepairAppointment();
                    $repairHandoverId = $lead->repairHandover()->latest()->first()?->id ?? null;
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
                        'required_attendees' => !empty($attendeeEmails) ? json_encode($attendeeEmails) : null,
                    ]);
                    $appointment->save();

                    if ($repairHandoverId) {
                        $adminRepair = \App\Models\AdminRepair::find($repairHandoverId);
                        if ($adminRepair) {
                            $adminRepair->update([
                                'status' => 'Completed',
                                'completed_at' => now()
                            ]);
                        }
                    }

                    $requiredAttendees = $data['required_attendees'] ?? null;
                    $attendeeEmails = [];
                    if (!empty($requiredAttendees)) {
                        $attendeeEmails = array_filter(array_map('trim', explode(';', $requiredAttendees)));
                    }

                    // Set up email recipients
                    $recipients = [
                        'admin.timetec.hr@timeteccloud.com',
                        'izzuddin@timeteccloud.com'
                    ];

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
                                    ->subject("TIMETEC REPAIR APPOINTMENT | {$data['type']} | {$lead->companyDetail->company_name}");
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

                    // Log the activity
                    ActivityLog::create([
                        'user_id' => auth()->id(),
                        'action' => 'Added Repair Appointment',
                        'description' => "Added a new repair appointment for lead: {$lead->companyDetail->company_name} ({$lead->id})",
                        'subject_type' => RepairAppointment::class,
                        'subject_id' => $appointment->id,
                    ]);

                    Notification::make()
                        ->title('Repair Appointment Added Successfully')
                        ->success()
                        ->send();
                }),
        ];
    }

    private function isJson($string) {
        if (!is_string($string)) return false;
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
