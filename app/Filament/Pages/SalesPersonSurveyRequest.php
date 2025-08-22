<?php
namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\RepairAppointment;
use App\Models\User;
use App\Models\DeviceModel;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class SalesPersonSurveyRequest extends Page implements HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationLabel = 'Site Survey Request';
    protected static ?string $title = 'Site Survey Request';
    protected static ?string $slug = 'sales/site-survey-request';
    protected static ?string $navigationGroup = 'SalesPerson Request';
    protected static ?int $navigationSort = 10;
    protected $defaultTechnician = 'Khairul Izzudin';

    protected static string $view = 'filament.pages.sales-person-survey-request';

    public function getTableQuery(): Builder
    {
        return RepairAppointment::query()
            ->where('type', 'SITE SURVEY HANDOVER')
            ->where('causer_id', auth()->id())
            ->orderBy('created_at', 'desc');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (Builder $query) => $this->getTableQuery())
            ->columns([
                TextColumn::make('id')
                    ->label('Survey ID')
                    ->formatStateUsing(function ($state) {
                        return 'SS_250' . str_pad($state, 4, '0', STR_PAD_LEFT);
                    })
                    ->sortable(),

                TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable(),

                TextColumn::make('date')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('time_range')
                    ->label('Time')
                    ->formatStateUsing(function ($state, $record) {
                        $start = Carbon::parse($record->start_time)->format('h:i A');
                        $end = Carbon::parse($record->end_time)->format('h:i A');
                        return $start . ' - ' . $end;
                    }),

                TextColumn::make('technician')
                    ->label('Technician')
                    ->searchable(),

                TextColumn::make('device_model')
                    ->label('Device')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return implode(', ', $state);
                        }
                        return $state;
                    })
                    ->searchable(false) // Can't easily search in array
                    ->wrap(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Confirmed' => 'success',
                        'Completed' => 'success',
                        'Cancelled' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('created_at')
                    ->label('Date Requested')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),
            ])
            ->filters([
                Filter::make('date')
                    ->form([
                        DatePicker::make('from'),
                        DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->icon('heroicon-o-eye')
                        ->modalContent(fn (RepairAppointment $record) => view('components.survey-request-detail', [
                            'record' => $record,
                        ]))
                        ->modalWidth('md'),
                ])
            ])
            ->bulkActions([])
            ->headerActions([
                Action::make('create')
                    ->label('New Task')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->form([
                        Section::make('Site Survey Details')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Select::make('lead_id')
                                            ->label('Company Name')
                                            ->options(function () {
                                                return Lead::query()
                                                    ->where('salesperson', auth()->id())
                                                    ->whereHas('companyDetail')
                                                    ->get()
                                                    ->mapWithKeys(function ($lead) {
                                                        $companyName = $lead->companyDetail?->company_name ?? "Lead #{$lead->id}";
                                                        return [$lead->id => $companyName];
                                                    });
                                            })
                                            ->searchable()
                                            ->required()
                                            ->reactive()
                                            ->afterStateUpdated(function ($state, callable $set) {
                                                if ($state) {
                                                    $lead = Lead::with('companyDetail')->find($state);
                                                    if ($lead && $lead->companyDetail) {
                                                        $address = $lead->companyDetail->company_address1 ?? '';
                                                        if (!empty($lead->companyDetail->company_address2)) {
                                                            $address .= ", " . $lead->companyDetail->company_address2;
                                                        }
                                                        if (!empty($lead->companyDetail->postcode) || !empty($lead->companyDetail->state)) {
                                                            $address .= ", " . ($lead->companyDetail->postcode ?? '') . " " . ($lead->companyDetail->state ?? '');
                                                        }

                                                        $set('site_survey_address', $address);
                                                        $set('company_name', $lead->companyDetail->company_name ?? "Lead #{$lead->id}");
                                                        $set('pic_name', $lead->pic_name ?? $lead->name ?? '');
                                                        $set('pic_phone', $lead->pic_phone ?? $lead->phone ?? '');
                                                    }
                                                }
                                            }),

                                        Select::make('device_model')
                                            ->label('Device Model')
                                            ->options(function() {
                                                return DeviceModel::where('is_active', true)
                                                    ->orderBy('name')
                                                    ->pluck('name', 'name')
                                                    ->toArray();
                                            })
                                            ->multiple()
                                            ->required(),

                                        Grid::make(3)
                                            ->schema([
                                                DatePicker::make('date')
                                                    ->required()
                                                    ->label('DATE')
                                                    ->default(function ($record = null) {
                                                        return $record ? $record->date : Carbon::today()->toDateString();
                                                    })
                                                    ->minDate(Carbon::today()) // Can't book in the past
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
                                                        $date = $get('date');
                                                        if (!$date) return [];

                                                        $times = [];
                                                        $startTime = Carbon::parse($date)->hour(8)->minute(0)->second(0); // Start at 8 AM
                                                        $endTime = Carbon::parse($date)->hour(18)->minute(0)->second(0);  // End at 6 PM

                                                        // Block dates in the past
                                                        $today = Carbon::today();
                                                        $selectedDate = Carbon::parse($date);
                                                        if ($selectedDate->lt($today)) {
                                                            return $times; // Return empty array for past dates
                                                        }

                                                        // If today, start from current time (rounded to next 30 minutes)
                                                        if ($selectedDate->isToday()) {
                                                            $now = Carbon::now();
                                                            $startTime = $now->copy()->addMinutes(30 - ($now->minute % 30));
                                                            if ($startTime->hour < 8) {
                                                                $startTime = Carbon::today()->hour(8)->minute(0);
                                                            }
                                                        }

                                                        // Get technician's appointments for this date
                                                        $bookedAppointments = RepairAppointment::where('technician', $this->defaultTechnician)
                                                            ->whereDate('date', $date)
                                                            ->whereIn('status', ['New'])
                                                            ->get(['start_time', 'end_time']);

                                                        while ($startTime < $endTime) {
                                                            $slotStart = $startTime->copy();
                                                            $slotEnd = $startTime->copy()->addMinutes(30);
                                                            $formattedTime = $slotStart->format('H:i');

                                                            // Check if this time slot is available
                                                            $isBooked = false;
                                                            foreach ($bookedAppointments as $appointment) {
                                                                $apptStart = Carbon::parse($appointment->start_time);
                                                                $apptEnd = Carbon::parse($appointment->end_time);

                                                                // Check for overlap using time components only
                                                                $apptStartTime = Carbon::parse($date . ' ' . $apptStart->format('H:i:s'));
                                                                $apptEndTime = Carbon::parse($date . ' ' . $apptEnd->format('H:i:s'));

                                                                // If appointment overlaps with current slot
                                                                if ($slotStart->lt($apptEndTime) && $slotEnd->gt($apptStartTime)) {
                                                                    $isBooked = true;
                                                                    break;
                                                                }
                                                            }

                                                            if (!$isBooked) {
                                                                $times[] = $formattedTime;
                                                            }

                                                            $startTime->addMinutes(30); // 30-minute intervals
                                                        }

                                                        return $times;
                                                    })
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        if ($state) {
                                                            // Default end time to 1 hour after start time
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
                                                        $startTime = $get('start_time');
                                                        if ($startTime) {
                                                            return Carbon::parse($startTime)->addHour()->format('H:i');
                                                        }
                                                        return null;
                                                    })
                                                    ->datalist(function (callable $get) {
                                                        $date = $get('date');
                                                        $startTime = $get('start_time');

                                                        if (!$date || !$startTime) return [];

                                                        $times = [];
                                                        $currentTime = Carbon::parse("$date $startTime")->addMinutes(30); // Minimum 30 min appointment
                                                        $endTime = Carbon::parse($date)->hour(18)->minute(0)->second(0);  // End at 6 PM

                                                        // Don't allow appointments longer than 3 hours
                                                        $maxEndTime = Carbon::parse("$date $startTime")->addHours(3);
                                                        if ($maxEndTime < $endTime) {
                                                            $endTime = $maxEndTime;
                                                        }

                                                        // Get technician's appointments for this date
                                                        $bookedAppointments = RepairAppointment::where('technician', $this->defaultTechnician)
                                                            ->whereDate('date', $date)
                                                            ->whereIn('status', ['New'])
                                                            ->get(['start_time', 'end_time']);

                                                        $slotStart = Carbon::parse("$date $startTime");

                                                        while ($currentTime <= $endTime) {
                                                            $formattedTime = $currentTime->format('H:i');
                                                            $slotEnd = Carbon::parse("$date $formattedTime");

                                                            // Check if this end time works with the selected start time
                                                            $isBooked = false;
                                                            foreach ($bookedAppointments as $appointment) {
                                                                $apptStart = Carbon::parse($appointment->start_time);
                                                                $apptEnd = Carbon::parse($appointment->end_time);

                                                                // Check for overlap using time components only
                                                                $apptStartTime = Carbon::parse($date . ' ' . $apptStart->format('H:i:s'));
                                                                $apptEndTime = Carbon::parse($date . ' ' . $apptEnd->format('H:i:s'));

                                                                // If our appointment would overlap with an existing one
                                                                if ($slotStart->lt($apptEndTime) && $slotEnd->gt($apptStartTime)) {
                                                                    $isBooked = true;
                                                                    break;
                                                                }
                                                            }

                                                            if (!$isBooked) {
                                                                $times[] = $formattedTime;
                                                            }

                                                            $currentTime->addMinutes(30);
                                                        }

                                                        return $times;
                                                    }),
                                            ]),
                                    ]),

                                Grid::make(3)
                                    ->schema([
                                        TextInput::make('type')
                                            ->label('Type')
                                            ->default('SITE SURVEY HANDOVER')
                                            ->disabled()
                                            ->dehydrated(true),

                                        TextInput::make('appointment_type')
                                            ->label('Appointment Type')
                                            ->default('ONSITE')
                                            ->disabled()
                                            ->dehydrated(true),

                                        TextInput::make('technician')
                                            ->label('Technician')
                                            ->default('Khairul Izzudin')
                                            ->disabled()
                                            ->dehydrated(true),
                                    ]),

                                Hidden::make('status')
                                    ->default('New'),
                                Hidden::make('company_name'),

                                Textarea::make('remarks')
                                    ->label('SalesPerson Remark')
                                    ->required()
                                    ->rows(3),
                            ])
                    ])
                    ->action(function (array $data) {
                        $data['causer_id'] = auth()->id();
                        $data['created_at'] = now();

                        // Format times properly for database
                        if (is_string($data['start_time'])) {
                            $data['start_time'] = Carbon::parse($data['start_time'])->format('H:i:s');
                        }

                        if (is_string($data['end_time'])) {
                            $data['end_time'] = Carbon::parse($data['end_time'])->format('H:i:s');
                        }

                        // Create the appointment
                        $appointment = RepairAppointment::create($data);

                        // Generate survey ID
                        $surveyId = 'SS_250' . str_pad($appointment->id, 4, '0', STR_PAD_LEFT);

                        // Send email notification
                        $this->sendSurveyNotification($appointment, $surveyId);

                        Notification::make()
                            ->title('Site Survey Request Created')
                            ->body("Your site survey request has been created with ID: $surveyId")
                            ->success()
                            ->send();
                    }),
            ]);
    }

    private function sendSurveyNotification($appointment, $surveyId)
    {
        // Get salesperson name
        $salespersonName = auth()->user()->name;

        // Format date/times for email
        $surveyDate = Carbon::parse($appointment->date)->format('d M Y');
        $startTime = Carbon::parse($appointment->start_time)->format('h:i A');
        $endTime = Carbon::parse($appointment->end_time)->format('h:i A');
        $dateSubmitted = Carbon::now()->format('d M Y, h:i A');

        // Format device models for display
        $deviceModels = is_array($appointment->device_model)
            ? implode(', ', $appointment->device_model)
            : $appointment->device_model;

        // Prepare email data
        $emailData = [
            'surveyId' => $surveyId,
            'dateSubmitted' => $dateSubmitted,
            'salesperson' => $salespersonName,
            'companyName' => $appointment->lead->companyDetail->company_name ?? 'Unknown Company',
            'deviceModel' => $deviceModels,
            'date' => $surveyDate,
            'timeRange' => "$startTime - $endTime",
            'remark' => $appointment->remarks,
        ];

        // Recipients
        $recipients = [
            auth()->user()->email, // Salesperson
            'khairul.izzudin@timeteccloud.com', // Default technician
        ];

        // Send the email
        Mail::send('emails.site-survey-notification', $emailData, function ($message) use ($recipients, $surveyId, $appointment) {
            $message->to($recipients)
                ->subject("SITE SURVEY HANDOVER ID $surveyId | {$appointment->company_name}");
        });
    }
}
