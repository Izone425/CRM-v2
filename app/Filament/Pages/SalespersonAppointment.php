<?php

namespace App\Filament\Pages;

use App\Models\Appointment;
use App\Models\User;
use Carbon\Carbon;
use com\zoho\crm\api\appointmentpreference\AppointmentPreferenceOperations;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class SalespersonAppointment extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $navigationLabel = 'Salesperson Requests';
    protected static string $view = 'filament.pages.salesperson-appointment';
    protected static ?int $navigationSort = 81;

    // Only show this page to managers (role_id 3)
    public static function canAccess(): bool
    {
        return auth()->user()->role_id === 3;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Appointment::query()
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
                    ->getStateUsing(function (Appointment $record): string {
                        $user = User::find($record->causer_id);
                        return $user ? $user->name : 'N/A';
                    }),
                TextColumn::make('salesperson')
                    ->label('Salesperson')
                    ->getStateUsing(function (Appointment $record): string {
                        $user = User::find($record->salesperson);
                        return $user ? $user->name : 'N/A';
                    })
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Demo Type'),
                TextColumn::make('appointment_type')
                    ->label('Appointment Type'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'New' => 'warning',
                        'Done' => 'success',
                        'Cancelled' => 'danger',
                        default => 'gray',
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
                            ->modalHeading('Request Remarks')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (Appointment $record) {
                                return new HtmlString('<div class="p-4 border border-gray-200 rounded-lg bg-gray-50">'
                                    . '<h3 class="mb-2 text-lg font-medium text-gray-900">Remarks</h3>'
                                    . '<p class="whitespace-pre-line">' . nl2br(e($record->remarks)) . '</p>'
                                    . '</div>');
                            }),
                    ),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Demo Type')
                    ->options([
                        'EXHIBITION' => 'EXHIBITION',
                        'INTERNAL MEETING' => 'INTERNAL MEETING',
                        'SALES MEETING' => 'SALES MEETING',
                        'PRODUCT MEETING' => 'PRODUCT MEETING',
                        'TOWNHALL SESSION' => 'TOWNHALL SESSION',
                        'FOLLOW UP SESSION' => 'FOLLOW UP SESSION',
                        'BUSINESS TRIP' => 'BUSINESS TRIP',
                    ])
                    ->placeholder('All Demo Types')
                    ->multiple(),

                SelectFilter::make('status')
                    ->options([
                        'New' => 'New',
                        'Done' => 'Done',
                        'Cancelled' => 'Cancelled',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),

                SelectFilter::make('salesperson')
                    ->label('Salesperson')
                    ->options(function () {
                        return User::query()
                            ->where('role_id', 2) // Salespeople
                            ->pluck('name', 'id') // Changed to use id as key
                            ->toArray();
                    })
                    ->searchable()
                    ->placeholder('All Salespeople')
                    ->multiple()
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('View')
                        ->icon('heroicon-o-eye')
                        ->color('success')
                        ->modalHeading('Salesperson Request Details')
                        ->modalSubmitAction(false)
                        ->form(function ($record) {
                            if (!$record) {
                                return [
                                    TextInput::make('error')->default('Request not found')->disabled(),
                                ];
                            }

                            return [
                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('type')
                                            ->label('Request Type')
                                            ->default(strtoupper($record->type))
                                            ->disabled(),

                                        TextInput::make('appointment_type')
                                            ->label('Appointment Type')
                                            ->default($record->appointment_type)
                                            ->disabled(),

                                        TextInput::make('salesperson')
                                            ->label('Salesperson')
                                            ->default(function ($record) {
                                                $user = User::find($record->salesperson);
                                                return $user ? $user->name : 'N/A';
                                            })
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
                                    ->disabled(),
                            ];
                        }),
                    Action::make('reschedule')
                        ->label('Reschedule')
                        ->color('warning')
                        ->icon('heroicon-o-pencil')
                        ->form(fn (Appointment $record) => $this->getFormSchema())
                        ->fillForm(fn (Appointment $record) => [
                            'date' => $record->date,
                            'start_time' => $record->start_time,
                            'end_time' => $record->end_time,
                            'type' => $record->type,
                            'appointment_type' => $record->appointment_type,
                            'salesperson' => $record->salesperson,
                            'remarks' => $record->remarks,
                            'mode' => 'auto',
                        ])
                        ->action(function (array $data, Appointment $record): void {
                            $record->update($data);

                            Notification::make()
                                ->success()
                                ->title('Request Updated')
                                ->body('The salesperson request has been successfully rescheduled.')
                                ->send();
                        })
                        ->visible(fn (Appointment $record): bool =>
                            $record->status !== 'Cancelled' && $record->status !== 'Done'
                        ),
                    Action::make('cancel')
                        ->label('Cancel Appointment')
                        ->color('danger')
                        ->icon('heroicon-o-x-circle')
                        ->requiresConfirmation()
                        ->modalHeading('Cancel Salesperson Appointment')
                        ->modalDescription('Are you sure you want to cancel this appointment? This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, cancel appointment')
                        ->visible(fn (Appointment $record) => $record->status !== 'Cancelled')
                        ->action(function (array $data, Appointment $record): void {
                            // Update the request status
                            $record->update([
                                'status' => 'Cancelled',
                            ]);

                            Notification::make()
                                ->success()
                                ->title('Appointment Cancelled')
                                ->body('The salesperson appointment has been successfully cancelled.')
                                ->send();
                        })
                ])->button()
            ])
            ->headerActions([
                Action::make('create')
                ->label('Add Salesperson Appointment')
                ->form($this->getFormSchema())
                ->action(function (array $data): void {
                    // Extract the salespeople array and remove it from data
                    $salespeople = $data['salespeople'];
                    unset($data['salespeople']);

                    $createdCount = 0;

                    // Create a separate appointment for each selected salesperson
                    foreach ($salespeople as $salespersonId) {
                        Appointment::create(array_merge($data, [
                            'causer_id' => Auth::id(),
                            'status' => 'New',
                            'salesperson' => $salespersonId, // Set the individual salesperson ID
                        ]));
                        $createdCount++;
                    }

                    Notification::make()
                        ->success()
                        ->title('Appointments Created')
                        ->body("Successfully created {$createdCount} salesperson appointment(s).")
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
                    } else {
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
                        }),
                ]),
                Grid::make(3)
                ->schema([
                    Select::make('type')
                        ->options([
                            'EXHIBITION' => 'EXHIBITION',
                            'INTERNAL MEETING' => 'INTERNAL MEETING',
                            'SALES MEETING' => 'SALES MEETING',
                            'PRODUCT MEETING' => 'PRODUCT MEETING',
                            'TOWNHALL SESSION' => 'TOWNHALL SESSION',
                            'FOLLOW UP SESSION' => 'FOLLOW UP SESSION',
                            'BUSINESS TRIP' => 'BUSINESS TRIP',
                        ])
                        ->default('BUSINESS TRIP')
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

                    Select::make('salespeople')
                        ->label('SALESPEOPLE')
                        ->options(function () {
                            return User::where('role_id', 2)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })
                        ->multiple()
                        ->searchable()
                        ->required()
                        ->placeholder('Select salespeople'),
                    ]),
            Textarea::make('remarks')
                ->label('REMARKS')
                ->rows(3)
                ->required()
                ->autosize()
                ->extraAttributes(['style' => 'text-transform: uppercase'])
                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),
        ];
    }
}
