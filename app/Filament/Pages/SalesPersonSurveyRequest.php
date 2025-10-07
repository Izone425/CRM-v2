<?php
namespace App\Filament\Pages;

use App\Models\Lead;
use App\Models\RepairAppointment;
use App\Models\User;
use App\Models\DeviceModel;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
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
    protected $defaultTechnician = 'Khairul Izzuddin';

    protected static string $view = 'filament.pages.sales-person-survey-request';

    public function getTableQuery(): Builder
    {
        $query = RepairAppointment::query()
            ->where('type', 'SITE SURVEY HANDOVER');

        // Only filter by causer_id for non-admin users
        if (auth()->user()->role_id !== 3) {
            $query->where('causer_id', auth()->id());
        }

        return $query->orderBy('created_at', 'desc');
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
                                                $query = Lead::query();

                                                // Exclude leads with unwanted statuses
                                                $excludedStatuses = ['Lost', 'On Hold', 'No Response', 'Junk'];
                                                $query->whereNotIn('lead_status', $excludedStatuses);

                                                // Show all leads for admin users (role_id 3), otherwise filter by salesperson
                                                if (auth()->user()->role_id === 3) {
                                                    // Admin can see all leads with company details
                                                    $query->whereHas('companyDetail');
                                                } else {
                                                    // Regular users only see their own leads
                                                    $query->where('salesperson', auth()->id())
                                                        ->whereHas('companyDetail');
                                                }

                                                // Order by lead ID in descending order
                                                $query->orderBy('id', 'desc');

                                                return $query->get()
                                                    ->mapWithKeys(function ($lead) {
                                                        $companyName = $lead->companyDetail?->company_name ?? "Unknown Company";
                                                        // Format as "LEAD ID 12001 | COMPANY NAME"
                                                        $displayText = 'ID ' . $lead->id . ' | ' . $companyName;
                                                        return [$lead->id => $displayText];
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
                                                        // Use the formatted company name with lead ID for consistency
                                                        $set('company_name', 'LEAD ID ' . $lead->id . ' | ' . ($lead->companyDetail->company_name ?? "Unknown Company"));
                                                        $set('pic_name', $lead->pic_name ?? $lead->name ?? '');
                                                        $set('pic_phone', $lead->pic_phone ?? $lead->phone ?? '');
                                                    }
                                                }
                                            }),

                                        Select::make('device_model')
                                            ->label('Device Model')
                                            ->options(function() {
                                                return DeviceModel::where('is_active', true)
                                                    ->orderBy('id')
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
                                                    ->default(function () {
                                                        // Get current time
                                                        $now = Carbon::now();

                                                        // Define business hours
                                                        $businessStart = Carbon::today()->setHour(9)->setMinute(0)->setSecond(0);
                                                        $businessEnd = Carbon::today()->setHour(18)->setMinute(0)->setSecond(0);

                                                        // If before business hours, return 9am
                                                        if ($now->lt($businessStart)) {
                                                            return '08:00';
                                                        }

                                                        // If after business hours, return 8am
                                                        if ($now->gt($businessEnd)) {
                                                            return '08:00';
                                                        }

                                                        // Otherwise round to next 30 min interval within business hours
                                                        $rounded = $now->copy()->addMinutes(30 - ($now->minute % 30))->setSecond(0);

                                                        // If rounded time is after business hours, return 8am next day
                                                        if ($rounded->gt($businessEnd)) {
                                                            return '08:00';
                                                        }

                                                        return $rounded->format('H:i');
                                                    })
                                                    ->datalist(function (callable $get) {
                                                        $date = $get('date');
                                                        if (!$date) return [];

                                                        // Get current time for reference
                                                        $currentTime = Carbon::now();
                                                        $currentTimeString = $currentTime->format('H:i');

                                                        // Generate all possible time slots in business hours (8am-6pm)
                                                        $allTimes = [];

                                                        $selectedDate = Carbon::parse($date);

                                                        // For any date (including past dates), generate all time slots
                                                        $startTime = Carbon::createFromTime(8, 0, 0);
                                                        $endTime = Carbon::createFromTime(18, 0, 0);

                                                        // Fetch all booked appointments for this technician on the selected date
                                                        $bookedAppointments = RepairAppointment::where('technician', $this->defaultTechnician)
                                                            ->whereDate('date', $date)
                                                            ->whereIn('status', ['New'])
                                                            ->get(['start_time', 'end_time']);

                                                        while ($startTime < $endTime) {
                                                            $slotStart = $startTime->copy();
                                                            $slotEnd = $startTime->copy()->addMinutes(30);
                                                            $formattedTime = $slotStart->format('H:i');

                                                            // Check if slot is already booked
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
                                                                $allTimes[] = $formattedTime;
                                                            }

                                                            $startTime->addMinutes(30);
                                                        }

                                                        // Sort times based on proximity to current time
                                                        usort($allTimes, function($a, $b) use ($currentTimeString) {
                                                            $aTime = Carbon::createFromFormat('H:i', $a);
                                                            $bTime = Carbon::createFromFormat('H:i', $b);
                                                            $currentTime = Carbon::createFromFormat('H:i', $currentTimeString);

                                                            // If current time is after business hours, sort by normal time order
                                                            if ($currentTime->format('H') >= 18) {
                                                                return $aTime <=> $bTime;
                                                            }

                                                            // For times after current time, sort by proximity to current
                                                            if ($aTime >= $currentTime && $bTime >= $currentTime) {
                                                                return $aTime <=> $bTime;
                                                            }

                                                            // For times before current time, sort normally
                                                            if ($aTime < $currentTime && $bTime < $currentTime) {
                                                                return $aTime <=> $bTime;
                                                            }

                                                            // If one is after and one is before current time, the after one comes first
                                                            return $bTime >= $currentTime ? 1 : -1;
                                                        });

                                                        return $allTimes;
                                                    })
                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                        if ($state) {
                                                            // Default end time to 1 hour after start time
                                                            $endTime = Carbon::parse($state)->addHour();

                                                            // Cap end time at 6:30pm
                                                            $maxEndTime = Carbon::createFromTime(18, 30, 0);
                                                            if ($endTime->gt($maxEndTime)) {
                                                                $endTime = $maxEndTime;
                                                            }

                                                            $set('end_time', $endTime->format('H:i'));
                                                        }
                                                    }),

                                                TimePicker::make('end_time')
                                                    ->label('END TIME')
                                                    ->required()
                                                    ->seconds(false)
                                                    ->reactive()
                                                    ->default(function () {
                                                        $startTime = Carbon::now()->addMinutes(30 - (Carbon::now()->minute % 30));
                                                        return $startTime->addHour()->format('H:i');
                                                    })
                                                    ->datalist(function (callable $get) {
                                                        $date = $get('date');
                                                        $startTimeString = $get('start_time');

                                                        if (!$date || !$startTimeString) return [];

                                                        $times = [];
                                                        $startTime = Carbon::parse("$date $startTimeString");

                                                        // End time must be at least 30 minutes after start time
                                                        $currentTime = $startTime->copy()->addMinutes(30);
                                                        // End time can't be later than 6:30pm
                                                        $endTime = Carbon::parse($date)->setHour(18)->setMinute(30)->setSecond(0);

                                                        // Don't allow appointments longer than 3 hours
                                                        $maxEndTime = $startTime->copy()->addHours(3);
                                                        if ($maxEndTime->lt($endTime)) {
                                                            $endTime = $maxEndTime;
                                                        }

                                                        // Get technician's appointments for this date
                                                        $bookedAppointments = RepairAppointment::where('technician', $this->defaultTechnician)
                                                            ->whereDate('date', $date)
                                                            ->whereIn('status', ['New'])
                                                            ->get(['start_time', 'end_time']);

                                                        while ($currentTime <= $endTime) {
                                                            $slotEnd = $currentTime->copy();
                                                            $formattedTime = $slotEnd->format('H:i');

                                                            // Check if this end time works with the selected start time
                                                            $isBooked = false;
                                                            foreach ($bookedAppointments as $appointment) {
                                                                $apptStart = Carbon::parse($appointment->start_time);
                                                                $apptEnd = Carbon::parse($appointment->end_time);

                                                                // Check for overlap using time components only
                                                                $apptStartTime = Carbon::parse($date . ' ' . $apptStart->format('H:i:s'));
                                                                $apptEndTime = Carbon::parse($date . ' ' . $apptEnd->format('H:i:s'));

                                                                // If our appointment would overlap with an existing one
                                                                if ($startTime->lt($apptEndTime) && $slotEnd->gt($apptStartTime)) {
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
                                            ->default('SITE SURVEY')
                                            ->disabled()
                                            ->dehydrated(true),

                                        TextInput::make('appointment_type')
                                            ->label('Appointment Type')
                                            ->default('ONSITE')
                                            ->disabled()
                                            ->dehydrated(true),

                                        TextInput::make('technician')
                                            ->label('Technician')
                                            ->default('Khairul Izzuddin')
                                            ->disabled()
                                            ->dehydrated(true),
                                    ]),

                                TextInput::make('pic_name')
                                    ->label('PIC Name')
                                    ->required()
                                    ->placeholder('Contact Person Name'),

                                // FIELD 2: PIC NO HP - Mandatory
                                TextInput::make('pic_phone')
                                    ->label('PIC Phone Number')
                                    ->tel()
                                    ->required()
                                    ->placeholder('Contact Person Phone Number'),

                                // FIELD 3: SITE SURVEY ADDRESS - Mandatory
                                Textarea::make('site_survey_address')
                                    ->label('Site Survey Address')
                                    ->required()
                                    ->placeholder('Full address for the site survey')
                                    ->rows(2),

                                Textarea::make('salesperson_remark')
                                    ->label('SalesPerson Remark (Optional)')
                                    ->placeholder('Enter any additional information here')
                                    ->extraAlpineAttributes([
                                        'x-on:input' => '
                                            const start = $el.selectionStart;
                                            const end = $el.selectionEnd;
                                            const value = $el.value;
                                            $el.value = value.toUpperCase();
                                            $el.setSelectionRange(start, end);
                                        '
                                    ])
                                    ->dehydrateStateUsing(fn ($state) => strtoupper($state))
                                    ->rows(2),

                                // FIELD 5: ATTACHMENT - Optional
                                FileUpload::make('attachment')
                                    ->label('Attachment (Optional)')
                                    ->disk('public')
                                    ->directory('survey-attachments')
                                    ->visibility('public')
                                    ->multiple()
                                    ->reorderable()
                                    ->appendFiles()
                                    ->maxFiles(5)
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'image/jpeg',
                                        'image/png',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                                    ])
                                    ->maxSize(5120) // 5MB per file
                                    ->downloadable(),

                                Hidden::make('status')
                                    ->default('New'),
                                Hidden::make('company_name'),
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

                        // Format remarks with proper line breaks
                        $formattedRemarks = "PIC NAME: " . $data['pic_name'] . "\n";
                        $formattedRemarks .= "PIC NO HP: " . $data['pic_phone'] . "\n";
                        $formattedRemarks .= "SITE SURVEY ADDRESS: " . $data['site_survey_address'] . "\n";

                        // Add salesperson remark if provided (convert to uppercase)
                        if (!empty($data['salesperson_remark'])) {
                            $formattedRemarks .= "REMARK: " . strtoupper($data['salesperson_remark']) . "\n";
                        }

                        // Store formatted remarks in the remarks field
                        $data['remarks'] = $formattedRemarks;

                        // Remove individual fields that shouldn't be stored directly
                        unset($data['pic_name']);
                        unset($data['pic_phone']);
                        unset($data['site_survey_address']);
                        unset($data['salesperson_remark']);
                        // Keep 'attachment' to store it in the database if your model supports it

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
                    })
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

        // Process attachments
        $attachments = [];
        if (!empty($appointment->attachment)) {
            $files = is_array($appointment->attachment) ? $appointment->attachment : [$appointment->attachment];

            foreach ($files as $file) {
                $fileUrl = asset('storage/' . $file);
                $fileName = basename($file);
                $attachments[] = [
                    'name' => $fileName,
                    'url' => $fileUrl
                ];
            }
        }

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
            'attachments' => $attachments,
        ];

        // Recipients
        $recipients = [
            auth()->user()->email, // Salesperson
            'izzuddin@timeteccloud.com', // Default technician
        ];

        // Send the email
        Mail::send('emails.site-survey-notification', $emailData, function ($message) use ($recipients, $surveyId, $appointment) {
            $message->to($recipients)
                ->subject("SITE SURVEY HANDOVER ID $surveyId | {$appointment->company_name}");
        });
    }
}
