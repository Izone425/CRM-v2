<?php

namespace App\Filament\Pages;

use App\Models\RepairAppointment;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class TechnicianAppointment extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Technician Appointments';
    protected static string $view = 'filament.pages.technician-appointment';
    protected static ?int $navigationSort = 80;

    public bool $openCreateModal = false;

    public function mount(): void
    {
        // Check if we should auto-open the create modal
        if (request()->has('open_create_modal')) {
            $this->openCreateModal = true;
        }
    }

    // Add the View return type hint
    public function render(): View
    {
        return parent::render()
            ->with([
                'openCreateModal' => $this->openCreateModal,
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                RepairAppointment::query()
                    ->orderBy('date', 'desc')
                    ->orderBy('start_time', 'desc')
            )
            ->columns([
                TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('start_time')
                    ->time('h:i A')
                    ->sortable(),
                TextColumn::make('end_time')
                    ->time('h:i A'),
                TextColumn::make('causer_id')
                    ->label('Created By')
                    ->getStateUsing(function (RepairAppointment $record): string {
                        // Find the user directly from the users table
                        $user = \App\Models\User::find($record->causer_id);
                        return $user ? $user->name : 'N/A';
                    }),
                TextColumn::make('technician')
                    ->searchable(),
                TextColumn::make('lead.companyDetail.company_name')
                    ->searchable()
                    ->label('Company')
                    ->getStateUsing(function (RepairAppointment $record): string {
                        // Fallback mechanism if relation is not available
                        if ($record->lead && $record->lead->companyDetail) {
                            return $record->lead->companyDetail->company_name ?? 'N/A';
                        } elseif ($record->company_name) {
                            return $record->company_name;
                        }
                        return 'N/A';
                    }),
                TextColumn::make('type')
                    ->label('Demo Type'),
                TextColumn::make('status')
                    ->label('Status'),
            ])
            ->filters([
                Filter::make('type')
                    ->label('Demo Type')
                    ->form([
                        Select::make('type')
                            ->label('Demo Type')
                            ->options([
                                'FINGERTEC TASK' => 'FingerTec Task',
                                'TIMETEC HR TASK' => 'TimeTec HR Task',
                                'TIMETEC PARKING TASK' => 'TimeTec Parking Task',
                                'TIMETEC PROPERTY TASK' => 'TimeTec Property Task',
                            ])
                            ->placeholder('All Demo Types')
                            ->multiple()
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when(
                            $data['type'],
                            fn (Builder $query, $types): Builder => $query->whereIn('type', $types)
                        );
                    })
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('View Appointment')
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
                    Action::make('edit')
                        ->label('Reschedule')
                        ->color('warning')
                        ->icon('heroicon-o-pencil')
                        ->form(fn (RepairAppointment $record) => $this->getFormSchema())
                        ->fillForm(fn (RepairAppointment $record) => [
                            'company_name' => $record->company_name,
                            'date' => $record->date,
                            'start_time' => $record->start_time,
                            'end_time' => $record->end_time,
                            'type' => $record->type,
                            'appointment_type' => $record->appointment_type,
                            'technician' => $record->technician,
                            'remarks' => $record->remarks,
                            'required_attendees' => $record->required_attendees,
                            'mode' => 'auto',
                        ])
                        ->visible(function (RepairAppointment $record) {
                            // First check if appointment is already Cancelled or Done
                            if (in_array($record->status, ['Cancelled', 'Done'])) {
                                return false;
                            }

                            $user = Auth::user();

                            // If user has role_id 9 (technician), they can only reschedule their own new appointments
                            if ($user && $user->role_id === 9) {
                                return $record->causer_id === $user->id && $record->status === 'New';
                            }

                            // For other roles, show the reschedule button for appointments with status = New
                            return $record->status === 'New';
                        })
                        ->action(function (array $data, RepairAppointment $record): void {
                            $record->update($data);

                            $this->notify('success', 'Appointment updated successfully');
                        }),
                    Action::make('cancel')
                        ->label('Cancel Appointment')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Repair Appointment')
                        ->modalDescription('Are you sure you want to cancel this appointment? Please provide a reason for cancellation.')
                        ->form([
                            Textarea::make('cancellation_remarks')
                                ->label('Cancellation Reason')
                                ->required()
                                ->rows(3)
                                ->autosize()
                                ->extraAttributes(['style' => 'text-transform: uppercase'])
                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()'])
                                ->placeholder('Please provide a reason for cancellation'),
                        ])
                        ->modalSubmitActionLabel('Yes, cancel appointment')
                        ->visible(function (RepairAppointment $record) {
                            // First check if appointment is already cancelled
                            if (in_array($record->status, ['Cancelled', 'Done'])) {
                                return false;
                            }

                            $user = Auth::user();

                            if ($user && $user->role_id === 9) {
                                return $record->causer_id === $user->id && in_array($record->type, [
                                    'FINGERTEC TASK',
                                    'TIMETEC HR TASK',
                                    'TIMETEC PARKING TASK',
                                    'TIMETEC PROPERTY TASK'
                                ]);
                            }

                            // For all other roles, show the cancel button
                            return true;
                        })
                        ->action(function (array $data, RepairAppointment $record): void {
                            // Update the appointment status and add cancellation remarks
                            $record->update([
                                'status' => 'Cancelled',
                                'cancellation_remarks' => $data['cancellation_remarks'] ?? null,
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Appointment Cancelled')
                                ->body('The appointment has been successfully cancelled with remarks.')
                                ->send();
                        }),
                    Action::make('viewRemarks')
                        ->label('View Remarks')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->color('info')
                        ->modalHeading('Appointment Remarks')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalFooterActions(fn (Action $action) => [
                            $action->makeModalSubmitAction('Close', ['wire:click' => 'closeModal']),
                        ])
                        ->form(function ($record) {
                            return [
                                Grid::make(1)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                TextInput::make('date')
                                                    ->label('Date')
                                                    ->default(Carbon::parse($record->date)->format('d M Y'))
                                                    ->disabled(),

                                                TextInput::make('time')
                                                    ->label('Time')
                                                    ->default($record->start_time . ' - ' . $record->end_time)
                                                    ->disabled(),
                                            ]),

                                        // Regular Remarks Section
                                        Textarea::make('remarks')
                                            ->label('Appointment Remarks')
                                            ->default($record->remarks ?? 'No remarks available')
                                            ->disabled()
                                            ->rows(4)
                                            ->columnSpanFull(),

                                        // Only show cancellation remarks if status is Cancelled
                                        Textarea::make('cancellation_remarks')
                                            ->label('Cancellation Reason')
                                            ->default($record->cancellation_remarks ?? 'No cancellation reason available')
                                            ->disabled()
                                            ->rows(4)
                                            ->columnSpanFull()
                                            ->hidden(fn() => $record->status !== 'Cancelled'),
                                    ]),
                            ];
                        }),
                ])->button()
            ])
            ->bulkActions([
                // Add bulk actions if needed
            ])
            ->headerActions([
                Action::make('create')
                    ->label('Add Technician Appointment')
                    ->form($this->getFormSchema())
                    ->closeModalByClickingAway(false)
                    ->action(function (array $data): void {
                        RepairAppointment::create(array_merge($data, [
                            'causer_id' => Auth::id(),
                        ]));

                        Notification::make()
                            ->success()
                            ->title('Appointment Created')
                            ->body('The appointment has been successfully created.')
                            ->send();
                    })
            ]);
    }

    protected function getFormSchema(): array
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
                            'FINGERTEC TASK' => 'FINGERTEC TASK',
                            'TIMETEC HR TASK' => 'TIMETEC HR TASK',
                            'TIMETEC PARKING TASK' => 'TIMETEC PARKING TASK',
                            'TIMETEC PROPERTY TASK' => 'TIMETEC PROPERTY TASK',
                        ])
                        ->default('FINGERTEC TASK')
                        ->required()
                        ->label('DEMO TYPE')
                        ->reactive(),

                    Select::make('appointment_type')
                        ->options([
                            'ONSITE' => 'ONSITE',
                            'ONLINE' => 'ONLINE',
                            'INHOUSE' => 'INHOUSE',
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

                            // Return only internal technicians
                            return $technicians;
                        })
                        ->disableOptionWhen(function ($value, $get) {
                            $date = $get('date');
                            $startTime = $get('start_time');
                            $endTime = $get('end_time');

                            // If any of the required fields is not filled, don't disable options
                            if (!$date || !$startTime || !$endTime) {
                                return false;
                            }

                            $parsedDate = Carbon::parse($date)->format('Y-m-d');
                            $parsedStartTime = Carbon::parse($startTime)->format('H:i:s');
                            $parsedEndTime = Carbon::parse($endTime)->format('H:i:s');

                            // Check if the technician has any overlapping appointments
                            $hasOverlap = RepairAppointment::query()
                                ->where('technician', $value)
                                ->whereIn('status', ['New', 'Done']) // Only check active appointments
                                ->whereDate('date', $parsedDate)
                                ->where(function ($query) use ($parsedStartTime, $parsedEndTime) {
                                    $query->where(function ($q) use ($parsedStartTime, $parsedEndTime) {
                                        // Start time falls within existing appointment
                                        $q->where('start_time', '<=', $parsedStartTime)
                                        ->where('end_time', '>', $parsedStartTime);
                                    })
                                    ->orWhere(function ($q) use ($parsedStartTime, $parsedEndTime) {
                                        // End time falls within existing appointment
                                        $q->where('start_time', '<', $parsedEndTime)
                                        ->where('end_time', '>=', $parsedEndTime);
                                    })
                                    ->orWhere(function ($q) use ($parsedStartTime, $parsedEndTime) {
                                        // Existing appointment falls completely within selected time
                                        $q->where('start_time', '>=', $parsedStartTime)
                                        ->where('end_time', '<=', $parsedEndTime);
                                    });
                                })
                                ->exists();

                            return $hasOverlap;
                        })
                        ->searchable()
                        ->required()
                        ->placeholder('Select a technician'),
                    ]),
            Textarea::make('remarks')
                ->label('REMARKS')
                ->required()
                ->rows(3)
                ->autosize()
                ->extraAttributes(['style' => 'text-transform: uppercase'])
                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

            TextInput::make('required_attendees')
                ->label('Required Attendees')
                ->helperText('Separate each email with a semicolon (e.g., email1;email2;email3).'),
        ];
    }

    protected function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
