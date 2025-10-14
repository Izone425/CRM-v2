<?php
// filepath: /var/www/html/timeteccrm/app/Livewire/AdminHardwareV2Dashboard/HardwareV2NewTable.php

namespace App\Livewire\AdminHardwareV2Dashboard;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateHardwareHandoverPdfController;
use App\Models\HardwareHandoverV2;
use App\Models\Lead;
use App\Models\RepairAppointment;
use App\Models\User;
use App\Services\CategoryService;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Tables\Actions\Action;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\On;

class HardwareV2PendingInternalInstallationTable extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;
    protected static ?int $indexRepeater3 = 0;
    protected static ?int $indexRepeater4 = 0;

    public $selectedUser;
    public $lastRefreshTime;
    public $currentDashboard;

    public function mount($currentDashboard = null)
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
        $this->currentDashboard = $currentDashboard ?? 'HardwareAdminV2';
    }

    public function refreshTable()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');

        Notification::make()
            ->title('Table refreshed')
            ->success()
            ->send();
    }

    #[On('refresh-HardwareHandoverV2-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    #[On('updateTablesForUser')]
    public function updateTablesForUser($selectedUser)
    {
        $this->selectedUser = $selectedUser;
        session(['selectedUser' => $selectedUser]);
        $this->resetTable();
    }

    public function getNewHardwareHandovers()
    {
        return HardwareHandoverV2::query()
            ->whereIn('status', ['Pending: Internal Installation'])
            // ->where('created_at', '<', Carbon::today()) // Only those created before today
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);
    }

    public function getHardwareHandoverCount()
    {
        $query = HardwareHandoverV2::query()
            ->whereIn('status', ['Pending: Internal Installation'])
            // ->where('created_at', '<', Carbon::today()) // Only those created before today
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);

        return $query->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getNewHardwareHandovers())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'New' => 'New',
                        'Rejected' => 'Rejected',
                        'Pending Stock' => 'Pending Stock',
                        'Pending Migration' => 'Pending Migration',
                        'Pending Payment' => 'Pending Payment',
                        'Pending: Courier' => 'Pending: Courier',
                        'Completed: Courier' => 'Completed: Courier',
                        'Pending Admin: Self Pick-Up' => 'Pending Admin: Self Pick-Up',
                        'Pending Customer: Self Pick-Up' => 'Pending Customer: Self Pick-Up',
                        'Completed: Self Pick-Up' => 'Completed: Self Pick-Up',
                        'Pending: External Installation' => 'Pending: External Installation',
                        'Completed: External Installation' => 'Completed: External Installation',
                        'Pending: Internal Installation' => 'Pending: Internal Installation',
                        'Completed: Internal Installation' => 'Completed: Internal Installation',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),

                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id', 15) // Exclude Testing Account
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Salesperson')
                    ->multiple(),

                SelectFilter::make('implementer')
                    ->label('Filter by Implementer')
                    ->options(function () {
                        return User::where('role_id', '4')
                            ->pluck('name', 'name')
                            ->toArray();
                    })
                    ->placeholder('All Implementers')
                    ->multiple(),

                SortFilter::make("sort_by"),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, HardwareHandoverV2 $record) {
                        if (!$state) {
                            return 'Unknown';
                        }

                        if ($record->handover_pdf) {
                            $filename = basename($record->handover_pdf, '.pdf');
                            return $filename;
                        }

                        return '250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(false)
                            ->modalWidth('6xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandoverV2 $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareHandoverV2 $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    }),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 30, '...'));
                        $encryptedId = Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $shortened . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('installation_type')
                    ->label('Category 1')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'external_installation' => 'External Installation',
                        'internal_installation' => 'Internal Installation',
                        'self_pick_up' => 'Self Pick-Up',
                        'courier' => 'Courier',
                        default => ucfirst($state ?? 'Unknown')
                    }),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Pending Stock' => new HtmlString('<span style="color: orange;">Pending Stock</span>'),
                        'Pending Migration' => new HtmlString('<span style="color: purple;">Pending Migration</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),

                TextColumn::make('created_at')
                    ->label('Created Date')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View Details')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(false)
                        ->modalWidth('6xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HardwareHandoverV2 $record): View {
                            return view('components.hardware-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),
                    Action::make('book_installation_appointment')
                        ->label('OnSite Installation')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->modalHeading('Book Installation Appointment')
                        ->modalWidth('7xl')
                        ->form(function (HardwareHandoverV2 $record) {
                            // Get device quantities from the record
                            $deviceQuantities = [
                                'tc10' => $record->tc10_quantity ?? 0,
                                'face_id5' => $record->face_id5_quantity ?? 0,
                                'tc20' => $record->tc20_quantity ?? 0,
                                'face_id6' => $record->face_id6_quantity ?? 0,
                            ];

                            // Get existing appointments if any
                            $existingCategory2 = $record->category2 ? json_decode($record->category2, true) : [];
                            $existingAppointments = $existingCategory2['installation_appointments'] ?? [];

                            // Calculate remaining units
                            $totalAllocated = [
                                'tc10' => 0,
                                'face_id5' => 0,
                                'tc20' => 0,
                                'face_id6' => 0,
                            ];

                            foreach ($existingAppointments as $appointment) {
                                $totalAllocated['tc10'] += (int)($appointment['device_allocation']['tc10_units'] ?? 0);
                                $totalAllocated['face_id5'] += (int)($appointment['device_allocation']['face_id5_units'] ?? 0);
                                $totalAllocated['tc20'] += (int)($appointment['device_allocation']['tc20_units'] ?? 0);
                                $totalAllocated['face_id6'] += (int)($appointment['device_allocation']['face_id6_units'] ?? 0);
                            }

                            return [
                                Section::make('Device Installation Overview')
                                    ->schema([
                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('tc10_total')
                                                    ->label('TC10 Total')
                                                    ->default($deviceQuantities['tc10'])
                                                    ->disabled()
                                                    ->suffix('units'),

                                                TextInput::make('face_id5_total')
                                                    ->label('Face ID 5 Total')
                                                    ->default($deviceQuantities['face_id5'])
                                                    ->disabled()
                                                    ->suffix('units'),

                                                TextInput::make('tc20_total')
                                                    ->label('TC20 Total')
                                                    ->default($deviceQuantities['tc20'] ?: 'N/A')
                                                    ->disabled()
                                                    ->suffix($deviceQuantities['tc20'] ? 'units' : ''),

                                                TextInput::make('face_id6_total')
                                                    ->label('Face ID 6 Total')
                                                    ->default($deviceQuantities['face_id6'] ?: 'N/A')
                                                    ->disabled()
                                                    ->suffix($deviceQuantities['face_id6'] ? 'units' : ''),
                                            ]),

                                        Grid::make(4)
                                            ->schema([
                                                TextInput::make('tc10_remaining')
                                                    ->label('TC10 Remaining')
                                                    ->default($deviceQuantities['tc10'] ? ($deviceQuantities['tc10'] - $totalAllocated['tc10']) : 'N/A')
                                                    ->disabled()
                                                    ->suffix('units')
                                                    ->extraAttributes(['style' => 'background-color: #22c55e; color: white; font-weight: bold;']),

                                                TextInput::make('face_id5_remaining')
                                                    ->label('Face ID 5 Remaining')
                                                    ->default($deviceQuantities['face_id5'] ? ($deviceQuantities['face_id5'] - $totalAllocated['face_id5']) : 'N/A')
                                                    ->disabled()
                                                    ->suffix('units')
                                                    ->extraAttributes(['style' => 'background-color: #22c55e; color: white; font-weight: bold;']),

                                                TextInput::make('tc20_remaining')
                                                    ->label('TC20 Remaining')
                                                    ->default($deviceQuantities['tc20'] ? ($deviceQuantities['tc20'] - $totalAllocated['tc20']) : 'N/A')
                                                    ->disabled()
                                                    ->suffix($deviceQuantities['tc20'] ? 'units' : '')
                                                    ->extraAttributes(['style' => 'background-color: #22c55e; color: white; font-weight: bold;']),

                                                TextInput::make('face_id6_remaining')
                                                    ->label('Face ID 6 Remaining')
                                                    ->default($deviceQuantities['face_id6'] ? ($deviceQuantities['face_id6'] - $totalAllocated['face_id6']) : 'N/A')
                                                    ->disabled()
                                                    ->suffix($deviceQuantities['face_id6'] ? 'units' : '')
                                                    ->extraAttributes(['style' => 'background-color: #22c55e; color: white; font-weight: bold;']),
                                            ]),
                                    ])
                                    ->collapsible(),

                                Section::make('Device Allocation & Appointments')
                                    ->schema([
                                        Repeater::make('installations')
                                            ->label('Installation Appointments')
                                            ->schema([
                                                Section::make('Device Allocation')
                                                    ->schema([
                                                        Grid::make(4)
                                                            ->schema([
                                                                TextInput::make('tc10_units')
                                                                    ->label('TC10 Units')
                                                                    ->numeric()
                                                                    ->default(0)
                                                                    ->minValue(0)
                                                                    ->live()
                                                                    ->afterStateUpdated(function ($state, $get, $set) use ($deviceQuantities, $totalAllocated) {
                                                                        $this->validateAllocation($get, $set, $deviceQuantities, $totalAllocated);
                                                                    }),

                                                                TextInput::make('face_id5_units')
                                                                    ->label('Face ID 5 Units')
                                                                    ->numeric()
                                                                    ->default(0)
                                                                    ->minValue(0)
                                                                    ->live()
                                                                    ->afterStateUpdated(function ($state, $get, $set) use ($deviceQuantities, $totalAllocated) {
                                                                        $this->validateAllocation($get, $set, $deviceQuantities, $totalAllocated);
                                                                    }),

                                                                TextInput::make('tc20_units')
                                                                    ->label('TC20 Units')
                                                                    ->numeric()
                                                                    ->default(0)
                                                                    ->minValue(0)
                                                                    ->disabled($deviceQuantities['tc20'] == 0)
                                                                    ->live()
                                                                    ->afterStateUpdated(function ($state, $get, $set) use ($deviceQuantities, $totalAllocated) {
                                                                        $this->validateAllocation($get, $set, $deviceQuantities, $totalAllocated);
                                                                    }),

                                                                TextInput::make('face_id6_units')
                                                                    ->label('Face ID 6 Units')
                                                                    ->numeric()
                                                                    ->default(0)
                                                                    ->minValue(0)
                                                                    ->disabled($deviceQuantities['face_id6'] == 0)
                                                                    ->live()
                                                                    ->afterStateUpdated(function ($state, $get, $set) use ($deviceQuantities, $totalAllocated) {
                                                                        $this->validateAllocation($get, $set, $deviceQuantities, $totalAllocated);
                                                                    }),
                                                            ]),
                                                    ])
                                                    ->collapsible(),

                                                Section::make('Appointment Details')
                                                    ->schema([
                                                        Grid::make(3)
                                                            ->schema([
                                                                Select::make('demo_type')
                                                                    ->label('Demo Type')
                                                                    ->options([
                                                                        'NEW INSTALLATION' => 'NEW INSTALLATION',
                                                                    ])
                                                                    ->default('NEW INSTALLATION')
                                                                    ->disabled()
                                                                    ->required(),

                                                                Select::make('appointment_type')
                                                                    ->label('Appointment Type')
                                                                    ->options([
                                                                        'ONSITE' => 'ONSITE',
                                                                    ])
                                                                    ->default('ONSITE')
                                                                    ->disabled()
                                                                    ->required(),

                                                                Select::make('technician')
                                                                    ->label('Technician')
                                                                    ->options([
                                                                        'KHAIRUL IZZUDIN' => 'KHAIRUL IZZUDIN',
                                                                    ])
                                                                    ->default('KHAIRUL IZZUDIN')
                                                                    ->disabled()
                                                                    ->required(),
                                                            ]),

                                                        Grid::make(3)
                                                            ->schema([
                                                                DatePicker::make('appointment_date')
                                                                    ->label('Date')
                                                                    ->required()
                                                                    ->native(false)
                                                                    ->displayFormat('d/m/Y')
                                                                    ->minDate(now()),

                                                                TimePicker::make('start_time')
                                                                    ->label('START TIME')
                                                                    ->required()
                                                                    ->seconds(false)
                                                                    ->live()
                                                                    ->default(function () {
                                                                        // Get current time
                                                                        $now = Carbon::now();

                                                                        // Define business hours
                                                                        $businessStart = Carbon::today()->setHour(9)->setMinute(0)->setSecond(0);
                                                                        $businessEnd = Carbon::today()->setHour(18)->setMinute(0)->setSecond(0);

                                                                        // If before business hours, return 9am
                                                                        if ($now->lt($businessStart)) {
                                                                            return '09:00';
                                                                        }

                                                                        // If after business hours, return 9am next day
                                                                        if ($now->gt($businessEnd)) {
                                                                            return '09:00';
                                                                        }

                                                                        // Otherwise round to next 30 min interval within business hours
                                                                        $rounded = $now->copy()->addMinutes(30 - ($now->minute % 30))->setSecond(0);

                                                                        // If rounded time is after business hours, return 9am next day
                                                                        if ($rounded->gt($businessEnd)) {
                                                                            return '09:00';
                                                                        }

                                                                        return $rounded->format('H:i');
                                                                    })
                                                                    ->datalist(function (callable $get) {
                                                                        $user = Auth::user();
                                                                        $date = $get('appointment_date');

                                                                        // Get current time for reference
                                                                        $currentTime = Carbon::now();
                                                                        $currentTimeString = $currentTime->format('H:i');

                                                                        // Generate all possible time slots in business hours (9am-6pm)
                                                                        $allTimes = [];

                                                                        if ($user && in_array($user->role_id, [9]) && $date) {
                                                                            // Fetch all booked appointments
                                                                            $appointments = RepairAppointment::where('technician', $user->id)
                                                                                ->whereDate('date', $date)
                                                                                ->whereIn('status', ['New', 'Completed'])
                                                                                ->get(['start_time', 'end_time']);

                                                                            // Generate all possible time slots
                                                                            $startTime = Carbon::createFromTime(9, 0, 0);
                                                                            $endTime = Carbon::createFromTime(18, 0, 0);

                                                                            // Generate time slots from 9am to 6pm
                                                                            while ($startTime < $endTime) {
                                                                                $slotStart = $startTime->copy();
                                                                                $slotEnd = $startTime->copy()->addMinutes(30);
                                                                                $formattedTime = $slotStart->format('H:i');

                                                                                // Check if slot is already booked
                                                                                $isBooked = $appointments->contains(function ($appointment) use ($slotStart, $slotEnd) {
                                                                                    $apptStart = Carbon::createFromFormat('H:i:s', $appointment->start_time);
                                                                                    $apptEnd = Carbon::createFromFormat('H:i:s', $appointment->end_time);

                                                                                    return $slotStart->lt($apptEnd) && $slotEnd->gt($apptStart);
                                                                                });

                                                                                if (!$isBooked) {
                                                                                    $allTimes[] = $formattedTime;
                                                                                }

                                                                                $startTime->addMinutes(30);
                                                                            }
                                                                        } else {
                                                                            // Generate all possible time slots without checking for booked slots
                                                                            $startTime = Carbon::createFromTime(8, 0, 0);
                                                                            $endTime = Carbon::createFromTime(18, 30, 0);

                                                                            while ($startTime < $endTime) {
                                                                                $allTimes[] = $startTime->format('H:i');
                                                                                $startTime->addMinutes(30);
                                                                            }
                                                                        }

                                                                        // Sort times based on proximity to current time in a circular manner
                                                                        usort($allTimes, function($a, $b) use ($currentTimeString) {
                                                                            $aTime = Carbon::createFromFormat('H:i', $a);
                                                                            $bTime = Carbon::createFromFormat('H:i', $b);
                                                                            $currentTime = Carbon::createFromFormat('H:i', $currentTimeString);

                                                                            // If current time is after business hours, consider 9am as the reference
                                                                            if ($currentTime->format('H') >= 18) {
                                                                                return $aTime <=> $bTime; // Just sort by normal time order starting from 9am
                                                                            }

                                                                            // For times after current time, they come first and are sorted by proximity to current
                                                                            if ($aTime >= $currentTime && $bTime >= $currentTime) {
                                                                                return $aTime <=> $bTime;
                                                                            }

                                                                            // For times before current time, they come after times that are after current
                                                                            if ($aTime < $currentTime && $bTime < $currentTime) {
                                                                                return $aTime <=> $bTime;
                                                                            }

                                                                            // If one is after and one is before current time, the after one comes first
                                                                            return $bTime >= $currentTime ? 1 : -1;
                                                                        });

                                                                        return $allTimes;
                                                                    })
                                                                    ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                                                        // Automatically set end time to start time + 1 hour
                                                                        if ($state) {
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
                                                                    ->live()
                                                                    ->default(function (callable $get) {
                                                                        // Get start time from form first
                                                                        $startTime = $get('start_time');
                                                                        if ($startTime) {
                                                                            $endTime = Carbon::parse($startTime)->addHour();

                                                                            // Cap end time at 6:30pm
                                                                            $maxEndTime = Carbon::createFromTime(18, 30, 0);
                                                                            if ($endTime->gt($maxEndTime)) {
                                                                                $endTime = $maxEndTime;
                                                                            }

                                                                            return $endTime->format('H:i');
                                                                        }

                                                                        // Fallback: current time + 1 hour with business hour constraints
                                                                        $now = Carbon::now();
                                                                        $businessStart = Carbon::today()->setHour(9)->setMinute(0);
                                                                        $businessEnd = Carbon::today()->setHour(18)->setMinute(30);

                                                                        if ($now->lt($businessStart)) {
                                                                            return '10:00'; // 9am + 1 hour
                                                                        }

                                                                        if ($now->gt($businessEnd->copy()->subHour())) {
                                                                            return '10:00'; // Next day 9am + 1 hour
                                                                        }

                                                                        $defaultStart = $now->copy()->addMinutes(30 - ($now->minute % 30));
                                                                        $defaultEnd = $defaultStart->copy()->addHour();

                                                                        if ($defaultEnd->gt($businessEnd)) {
                                                                            $defaultEnd = $businessEnd;
                                                                        }

                                                                        return $defaultEnd->format('H:i');
                                                                    })
                                                                    ->datalist(function (callable $get) {
                                                                        $user = Auth::user();
                                                                        $date = $get('appointment_date');

                                                                        $times = [];

                                                                        if ($user && in_array($user->role_id, [9]) && $date) {
                                                                            // Fetch booked time slots for this technician on the selected date
                                                                            $bookedAppointments = RepairAppointment::where('technician', $user->id)
                                                                                ->whereDate('date', $date)
                                                                                ->whereIn('status', ['New', 'Completed'])
                                                                                ->pluck('end_time', 'start_time')
                                                                                ->toArray();

                                                                            // Generate time slots from 9:30am to 6:30pm (end times)
                                                                            $startTime = Carbon::createFromTime(9, 30, 0);
                                                                            $endTime = Carbon::createFromTime(18, 30, 0);

                                                                            while ($startTime <= $endTime) {
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
                                                                            // Default available slots (9:30am to 6:30pm for end times)
                                                                            $startTime = Carbon::createFromTime(9, 30, 0);
                                                                            $endTime = Carbon::createFromTime(18, 30, 0);

                                                                            while ($startTime <= $endTime) {
                                                                                $times[] = $startTime->format('H:i');
                                                                                $startTime->addMinutes(30);
                                                                            }
                                                                        }

                                                                        return $times;
                                                                    })
                                                            ]),

                                                        Grid::make(2)
                                                            ->schema([
                                                                TextInput::make('pic_name')
                                                                    ->label('PIC Name')
                                                                    ->required()
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
                                                                    ->maxLength(255),

                                                                TextInput::make('pic_phone')
                                                                    ->label('PIC HP Number')
                                                                    ->required()
                                                                    ->tel()
                                                                    ->maxLength(255),
                                                            ]),

                                                        TextInput::make('pic_email')
                                                            ->label('PIC Email')
                                                            ->required()
                                                            ->email()
                                                            ->maxLength(255),

                                                        Textarea::make('installation_address')
                                                            ->label('Installation Address')
                                                            ->required()
                                                            ->rows(3)
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
                                                            ->columnSpanFull(),

                                                        Textarea::make('installation_remark')
                                                            ->label('Installation Remark')
                                                            ->placeholder('Optional remarks about the installation')
                                                            ->maxLength(500)
                                                            ->rows(3)
                                                            ->columnSpanFull()
                                                            ->extraAlpineAttributes([
                                                                'x-on:input' => '
                                                                    const start = $el.selectionStart;
                                                                    const end = $el.selectionEnd;
                                                                    const value = $el.value;
                                                                    $el.value = value.toUpperCase();
                                                                    $el.setSelectionRange(start, end);
                                                                '
                                                            ])
                                                            ->dehydrateStateUsing(fn ($state) => strtoupper($state)),
                                                    ])
                                                    ->collapsible(),
                                            ])
                                            ->defaultItems(1)
                                            ->addActionLabel('Add Another Installation')
                                            ->columnSpanFull()
                                            ->reorderable(false)
                                            ->collapsible()
                                            ->cloneable(),
                                    ]),

                                Section::make('Existing Appointments')
                                    ->schema([
                                        Repeater::make('existing_appointments')
                                            ->label('Created Appointments')
                                            ->schema([
                                                Grid::make(6)
                                                    ->schema([
                                                        TextInput::make('tc10_allocated')
                                                            ->label('TC10')
                                                            ->disabled()
                                                            ->suffix('units'),

                                                        TextInput::make('face_id5_allocated')
                                                            ->label('Face ID 5')
                                                            ->disabled()
                                                            ->suffix('units'),

                                                        TextInput::make('tc20_allocated')
                                                            ->label('TC20')
                                                            ->disabled()
                                                            ->suffix('units'),

                                                        TextInput::make('face_id6_allocated')
                                                            ->label('Face ID 6')
                                                            ->disabled()
                                                            ->suffix('units'),

                                                        TextInput::make('status')
                                                            ->label('Status')
                                                            ->disabled(),
                                                    ]),
                                            ])
                                            ->default(array_map(function($appointment) {
                                                return [
                                                    'appointment_name' => $appointment['appointment_name'] ?? 'Unknown',
                                                    'tc10_allocated' => $appointment['device_allocation']['tc10_units'] ?? 0,
                                                    'face_id5_allocated' => $appointment['device_allocation']['face_id5_units'] ?? 0,
                                                    'tc20_allocated' => $appointment['device_allocation']['tc20_units'] ?? 0,
                                                    'face_id6_allocated' => $appointment['device_allocation']['face_id6_units'] ?? 0,
                                                    'status' => $appointment['appointment_status'] ?? 'Scheduled',
                                                ];
                                            }, $existingAppointments))
                                            ->addable(false)
                                            ->deletable(false)
                                            ->reorderable(false)
                                            ->columnSpanFull()
                                            ->visible(fn () => !empty($existingAppointments)),
                                    ])
                                    ->visible(fn () => !empty($existingAppointments)),
                            ];
                        })
                        ->action(function (HardwareHandoverV2 $record, array $data): void {
                            try {
                                $installations = $data['installations'] ?? [];

                                if (empty($installations)) {
                                    Notification::make()
                                        ->title('No Installations')
                                        ->body('Please add at least one installation appointment.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Get the lead from handover record
                                $lead = $record->lead;

                                if (!$lead) {
                                    Notification::make()
                                        ->title('Error')
                                        ->body('Lead information not found for this handover.')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Get existing appointments
                                $existingCategory2 = $record->category2 ? json_decode($record->category2, true) : [];
                                if (!is_array($existingCategory2)) {
                                    $existingCategory2 = [];
                                }

                                if (!isset($existingCategory2['installation_appointments'])) {
                                    $existingCategory2['installation_appointments'] = [];
                                }

                                // Process each installation
                                foreach ($installations as $installation) {
                                    // Validate that at least one device is allocated
                                    $totalUnits = ($installation['tc10_units'] ?? 0) + ($installation['face_id5_units'] ?? 0) +
                                                ($installation['tc20_units'] ?? 0) + ($installation['face_id6_units'] ?? 0);

                                    if ($totalUnits == 0) {
                                        Notification::make()
                                            ->title('Device Allocation Required')
                                            ->body('Please allocate at least one device for installation' ?? 'Unknown')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    // Create repair appointment
                                    $appointment = new \App\Models\RepairAppointment();
                                    $appointment->fill([
                                        'lead_id' => $lead->id,
                                        'type' => 'NEW INSTALLATION',
                                        'appointment_type' => 'ONSITE',
                                        'date' => $installation['appointment_date'],
                                        'start_time' => $installation['start_time'],
                                        'end_time' => $installation['end_time'],
                                        'technician' => 'KHAIRUL IZZUDIN',
                                        'causer_id' => auth()->user()->id,
                                        'technician_assigned_date' => now(),
                                        'remarks' => $installation['installation_remark'] ?? '',
                                        'title' => 'NEW INSTALLATION | ONSITE | TIMETEC REPAIR | ' . ($lead->companyDetail->company_name ?? 'N/A'),
                                        'status' => 'New',
                                    ]);
                                    $appointment->save();

                                    // Store appointment data in category2
                                    $existingCategory2['installation_appointments'][] = [
                                        'appointment_id' => $appointment->id,
                                        'device_allocation' => [
                                            'tc10_units' => $installation['tc10_units'] ?? 0,
                                            'face_id5_units' => $installation['face_id5_units'] ?? 0,
                                            'tc20_units' => $installation['tc20_units'] ?? 0,
                                            'face_id6_units' => $installation['face_id6_units'] ?? 0,
                                        ],
                                        'appointment_details' => [
                                            'demo_type' => 'NEW INSTALLATION',
                                            'appointment_type' => 'ONSITE',
                                            'technician' => 'KHAIRUL IZZUDIN',
                                            'date' => $installation['appointment_date'],
                                            'start_time' => $installation['start_time'],
                                            'end_time' => $installation['end_time'],
                                            'pic_name' => $installation['pic_name'],
                                            'pic_phone' => $installation['pic_phone'],
                                            'pic_email' => $installation['pic_email'],
                                            'installation_address' => $installation['installation_address'],
                                            'installation_remark' => strtoupper($installation['installation_remark'] ?? ''),
                                        ],
                                        'appointment_status' => 'Scheduled',
                                        'created_at' => now(),
                                        'created_by' => auth()->id(),
                                    ];

                                    // Send email for this appointment
                                    $this->sendInstallationEmail($record, $installation, $lead);
                                }

                                $record->update([
                                    'category2' => json_encode($existingCategory2),
                                ]);

                                // Check if all devices are allocated
                                $deviceQuantities = [
                                    'tc10' => $record->tc10_quantity ?? 0,
                                    'face_id5' => $record->face_id5_quantity ?? 0,
                                    'tc20' => $record->tc20_quantity ?? 0,
                                    'face_id6' => $record->face_id6_quantity ?? 0,
                                ];

                                $totalAllocated = [
                                    'tc10' => 0,
                                    'face_id5' => 0,
                                    'tc20' => 0,
                                    'face_id6' => 0,
                                ];

                                foreach ($existingCategory2['installation_appointments'] as $appt) {
                                    $totalAllocated['tc10'] += (int)($appt['device_allocation']['tc10_units'] ?? 0);
                                    $totalAllocated['face_id5'] += (int)($appt['device_allocation']['face_id5_units'] ?? 0);
                                    $totalAllocated['tc20'] += (int)($appt['device_allocation']['tc20_units'] ?? 0);
                                    $totalAllocated['face_id6'] += (int)($appt['device_allocation']['face_id6_units'] ?? 0);
                                }

                                $allDevicesAllocated =
                                    $totalAllocated['tc10'] >= $deviceQuantities['tc10'] &&
                                    $totalAllocated['face_id5'] >= $deviceQuantities['face_id5'] &&
                                    $totalAllocated['tc20'] >= $deviceQuantities['tc20'] &&
                                    $totalAllocated['face_id6'] >= $deviceQuantities['face_id6'];

                                if ($allDevicesAllocated) {
                                    $existingCategory2['all_devices_allocated'] = true;
                                    $existingCategory2['all_appointments_scheduled'] = true;
                                    $existingCategory2['completion_date'] = now();

                                    $record->update([
                                        'category2' => json_encode($existingCategory2),
                                        'status' => 'Completed: Internal Installation',
                                        'completed_at' => now(),
                                    ]);

                                    Notification::make()
                                        ->title('Installation Complete!')
                                        ->body('All devices have been allocated to appointments. Internal installation process completed.')
                                        ->success()
                                        ->send();
                                } else {
                                    $remaining = [
                                        'tc10' => max(0, $deviceQuantities['tc10'] - $totalAllocated['tc10']),
                                        'face_id5' => max(0, $deviceQuantities['face_id5'] - $totalAllocated['face_id5']),
                                        'tc20' => max(0, $deviceQuantities['tc20'] - $totalAllocated['tc20']),
                                        'face_id6' => max(0, $deviceQuantities['face_id6'] - $totalAllocated['face_id6']),
                                    ];

                                    $remainingText = array_filter([
                                        $remaining['tc10'] > 0 ? "TC10: {$remaining['tc10']}" : null,
                                        $remaining['face_id5'] > 0 ? "Face ID 5: {$remaining['face_id5']}" : null,
                                        $remaining['tc20'] > 0 ? "TC20: {$remaining['tc20']}" : null,
                                        $remaining['face_id6'] > 0 ? "Face ID 6: {$remaining['face_id6']}" : null,
                                    ]);

                                    $appointmentCount = count($installations);
                                    Notification::make()
                                        ->title('Appointments Created Successfully!')
                                        ->body("Created {$appointmentCount} appointments. Remaining devices: " . implode(', ', $remainingText) . '. You can create more appointments if needed.')
                                        ->success()
                                        ->send();
                                }

                                Log::info("Installation appointments created for handover {$record->id}", [
                                    'appointments_count' => count($installations),
                                    'user_id' => auth()->id(),
                                ]);

                            } catch (\Exception $e) {
                                Log::error("Error creating installation appointments for handover {$record->id}: " . $e->getMessage());

                                Notification::make()
                                    ->title('Error')
                                    ->body('Failed to create appointments. Please try again.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->modalSubmitActionLabel('Create All Appointments')
                        ->visible(fn (HardwareHandoverV2 $record): bool =>
                            $record->status === 'Pending: Internal Installation' && auth()->user()->role_id !== 2
                        ),
                ])->button()
            ]);
    }

    private function sendInstallationEmail(HardwareHandoverV2 $record, array $installation, Lead $lead)
    {
        try {
            // Generate Hardware ID
            $hardwareId = $record->handover_pdf ?
                basename($record->handover_pdf, '.pdf') :
                'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

            // Format date and time
            $appointmentDate = Carbon::parse($installation['appointment_date'])->format('d/m/Y');
            $startTime = Carbon::parse($installation['start_time'])->format('H:i');
            $endTime = Carbon::parse($installation['end_time'])->format('H:i');

            // Email data
            $emailData = [
                'hardware_id' => $hardwareId,
                'company_name' => strtoupper($lead->companyDetail->company_name ?? 'N/A'),
                'technician_name' => 'Khairul Izzudin',
                'technician_phone' => '', // Add if available
                'pic_name' => $installation['pic_name'] ?? 'N/A',
                'pic_phone' => $installation['pic_phone'] ?? 'N/A',
                'pic_email' => $installation['pic_email'] ?? 'N/A',
                'appointment_date' => $appointmentDate,
                'installation_time' => "{$startTime} - {$endTime}",
                'installation_address' => $installation['installation_address'] ?? 'N/A',
                'devices' => [
                    'tc10' => $installation['tc10_units'] ?? 0,
                    'face_id5' => $installation['face_id5_units'] ?? 0,
                    'tc20' => $installation['tc20_units'] ?? 0,
                    'face_id6' => $installation['face_id6_units'] ?? 0,
                ]
            ];

            // Email subject
            $subject = "TIMETEC ONSITE INSTALLATION | {$hardwareId} | {$emailData['company_name']}";

            // Recipients
            $recipients = [
                'admin.timetec.hr@timeteccloud.com', // Admin
                $installation['pic_email'] ?? null,  // Customer
                'izzuddin@timeteccloud.com',
                $lead->getSalespersonEmail() ?? null,  // Salesperson
            ];

            // Filter out null emails
            $recipients = array_filter($recipients);

            // Send email using blade template
            Mail::send('emails.installation-appointment', $emailData, function (Message $message) use ($subject, $recipients) {
                $message->from('admin.timetec.hr@timeteccloud.com', 'TimeTec Admin')
                        ->to($recipients)
                        ->subject($subject);
            });

            Log::info("Installation email sent for appointment", [
                'hardware_id' => $hardwareId,
                'recipients' => $recipients,
                'subject' => $subject
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send installation email: " . $e->getMessage(), [
                'hardware_id' => $record->id,
                'installation' => $installation
            ]);
        }
    }

    private function validateAllocation($get, $set, $deviceQuantities, $existingAllocated)
    {
        $installations = $get('../../installations') ?? [];

        // Calculate total allocation across all installations
        $totalAllocated = [
            'tc10' => $existingAllocated['tc10'],
            'face_id5' => $existingAllocated['face_id5'],
            'tc20' => $existingAllocated['tc20'],
            'face_id6' => $existingAllocated['face_id6'],
        ];

        foreach ($installations as $installation) {
            $totalAllocated['tc10'] += (int)($installation['tc10_units'] ?? 0);
            $totalAllocated['face_id5'] += (int)($installation['face_id5_units'] ?? 0);
            $totalAllocated['tc20'] += (int)($installation['tc20_units'] ?? 0);
            $totalAllocated['face_id6'] += (int)($installation['face_id6_units'] ?? 0);
        }

        // Set validation errors if over allocation
        $errors = [];
        if ($totalAllocated['tc10'] > $deviceQuantities['tc10']) {
            $errors[] = "TC10 over-allocated by " . ($totalAllocated['tc10'] - $deviceQuantities['tc10']) . " units";
        }
        if ($totalAllocated['face_id5'] > $deviceQuantities['face_id5']) {
            $errors[] = "Face ID 5 over-allocated by " . ($totalAllocated['face_id5'] - $deviceQuantities['face_id5']) . " units";
        }
        if ($totalAllocated['tc20'] > $deviceQuantities['tc20']) {
            $errors[] = "TC20 over-allocated by " . ($totalAllocated['tc20'] - $deviceQuantities['tc20']) . " units";
        }
        if ($totalAllocated['face_id6'] > $deviceQuantities['face_id6']) {
            $errors[] = "Face ID 6 over-allocated by " . ($totalAllocated['face_id6'] - $deviceQuantities['face_id6']) . " units";
        }

        if (!empty($errors)) {
            Notification::make()
                ->title('Allocation Error')
                ->body(implode(', ', $errors))
                ->danger()
                ->send();
        }
    }

    private function validateTotalUnits($get, $set, $deviceQuantities)
    {
        $installations = $get('installations') ?? [];

        $totalAllocated = [
            'tc10' => 0,
            'face_id5' => 0,
            'tc20' => 0,
            'face_id6' => 0,
        ];

        foreach ($installations as $installation) {
            $totalAllocated['tc10'] += (int)($installation['tc10_units'] ?? 0);
            $totalAllocated['face_id5'] += (int)($installation['face_id5_units'] ?? 0);
            $totalAllocated['tc20'] += (int)($installation['tc20_units'] ?? 0);
            $totalAllocated['face_id6'] += (int)($installation['face_id6_units'] ?? 0);
        }

        // Update balance fields
        $set('tc10_balance', max(0, $deviceQuantities['tc10'] - $totalAllocated['tc10']));
        $set('face_id5_balance', max(0, $deviceQuantities['face_id5'] - $totalAllocated['face_id5']));
        $set('tc20_balance', max(0, $deviceQuantities['tc20'] - $totalAllocated['tc20']));
        $set('face_id6_balance', max(0, $deviceQuantities['face_id6'] - $totalAllocated['face_id6']));
    }

    public function render()
    {
        return view('livewire.admin-hardware-v2-dashboard.hardware-v2-pending-internal-installation-table');
    }
}
