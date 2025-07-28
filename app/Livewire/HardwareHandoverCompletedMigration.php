<?php

namespace App\Livewire;

use App\Filament\Filters\SortFilter;
use App\Models\HardwareHandover;
use App\Models\RepairAppointment;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Columns\BadgeColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Attributes\On;

class HardwareHandoverCompletedMigration extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexRepeater = 0;
    protected static ?int $indexRepeater2 = 0;
    public $lastRefreshTime;

    public function mount()
    {
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
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

    #[On('refresh-hardwarehandover-tables')]
    public function refreshData()
    {
        $this->resetTable();
        $this->lastRefreshTime = now()->format('Y-m-d H:i:s');
    }

    public function getOverdueHardwareHandovers()
    {
        return HardwareHandover::query()
            ->whereIn('status', ['Completed Migration'])
            // ->where('created_at', '<', Carbon::today()) // Only those created before today
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getOverdueHardwareHandovers())
            ->defaultSort('created_at', 'asc')
            ->emptyState(fn () => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                // Add this new filter for status
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'Draft' => 'Draft',
                        'New' => 'New',
                        'Approved' => 'Approved',
                        'Rejected' => 'Rejected',
                        'Completed' => 'Completed',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),
                SelectFilter::make('salesperson')
                    ->label('Filter by Salesperson')
                    ->options(function () {
                        return User::where('role_id', '2')
                            ->whereNot('id',15) // Exclude Testing Account
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
                TextColumn::make('handover_pdf')
                    ->label('ID')
                    ->formatStateUsing(function ($state) {
                        // If handover_pdf is null, return a placeholder
                        if (!$state) {
                            return '-';
                        }

                        // Extract just the filename without extension
                        $filename = basename($state, '.pdf');

                        // Return just the formatted ID part
                        return $filename;
                    })
                    ->color('primary') // Makes it visually appear as a link
                    ->weight('bold')
                    ->action(
                        Action::make('viewHandoverDetails')
                            ->modalHeading(' ')
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (HardwareHandover $record): View {
                                return view('components.hardware-handover')
                                    ->with('extraAttributes', ['record' => $record]);
                            })
                    ),

                TextColumn::make('lead.salesperson')
                    ->label('SalesPerson')
                    ->getStateUsing(function (HardwareHandover $record) {
                        $lead = $record->lead;
                        if (!$lead) {
                            return '-';
                        }

                        $salespersonId = $lead->salesperson;
                        return User::find($salespersonId)?->name ?? '-';
                    })
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('implementer')
                    ->label('Implementer')
                    ->visible(fn(): bool => auth()->user()->role_id !== 2),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 25, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($record->lead->id);

                        return '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    class="inline-block"
                                    style="color:#338cf0;">
                                    ' . $fullName . '
                                </a>';
                    })
                    ->html(),

                TextColumn::make('installation_type')
                    ->label('Category 1')
                    ->formatStateUsing(function ($state) {
                        return match ($state) {
                            'courier' => 'Courier',
                            'internal_installation' => 'Internal Installation',
                            'external_installation' => 'External Installation',
                            'self_pick_up' => 'Self Pick-up',
                            default => ucfirst($state),
                        };
                    })
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(fn (string $state): HtmlString => match ($state) {
                        'Draft' => new HtmlString('<span style="color: orange;">Draft</span>'),
                        'New' => new HtmlString('<span style="color: blue;">New</span>'),
                        'Approved' => new HtmlString('<span style="color: green;">Approved</span>'),
                        'Rejected' => new HtmlString('<span style="color: red;">Rejected</span>'),
                        default => new HtmlString('<span>' . ucfirst($state) . '</span>'),
                    }),
            ])
            // ->filters([
            //     // Filter for Creator
            //     SelectFilter::make('created_by')
            //         ->label('Created By')
            //         ->multiple()
            //         ->options(User::pluck('name', 'id')->toArray())
            //         ->placeholder('Select User'),

            //     // Filter by Company Name
            //     SelectFilter::make('company_name')
            //         ->label('Company')
            //         ->searchable()
            //         ->options(HardwareHandover::distinct()->pluck('company_name', 'company_name')->toArray())
            //         ->placeholder('Select Company'),
            // ])
            ->actions([
                ActionGroup::make([
                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->color('secondary')
                        ->modalHeading(' ')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        // Use a callback function instead of arrow function for more control
                        ->modalContent(function (HardwareHandover $record): View {

                            // Return the view with the record using $this->record pattern
                            return view('components.hardware-handover')
                            ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('mark_as_completed_installation')
                        ->label(fn(): HtmlString => new HtmlString('Mark as Completed:<br>Installation'))
                        ->icon('heroicon-o-wrench')
                        ->color('primary')
                        ->modalWidth('3xl')
                        ->modalHeading("Add Repair Appointment")
                        ->form([
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
                                            return Carbon::today()->toDateString();
                                        })
                                        ->reactive(),

                                    TimePicker::make('start_time')
                                        ->label('START TIME')
                                        ->required()
                                        ->seconds(false)
                                        ->reactive()
                                        ->default(function ($record = null) {
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
                                                'SITE SURVEY' => 'SITE SURVEY',
                                            ])
                                            ->default(function ($record = null) {
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

                            Hidden::make('installation_type')
                                ->default(function ($record) {
                                    // This ensures the field gets its value from the record
                                    return $record->installation_type ?? null;
                                }),

                            FileUpload::make('reseller_invoice')
                                ->label('Reseller Invoice')
                                ->helperText('Upload reseller invoice')
                                ->required(fn(callable $get) => $get('installation_type') === 'external_installation')
                                ->visible(fn(callable $get) => $get('installation_type') === 'external_installation')
                                ->disk('public')
                                ->directory('handovers/reseller_invoices')
                                ->visibility('public')
                                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                ->multiple()
                                ->maxFiles(5)
                                ->openable()
                                ->downloadable()
                                ->default(function ($record) {
                                    return $record->reseller_invoice ?? [];
                                }),

                            Grid::make(3)
                            ->schema([
                                TextInput::make('category2.pic_name')
                                    ->label('Name')
                                    ->disabled()
                                    ->required(fn(callable $get) => in_array($get('installation_type'), ['external_installation', 'internal_installation']))
                                    ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                    ->default(function ($record) {
                                        if ($record && $record->category2) {
                                            $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                            if (isset($category2['pic_name']) && !empty($category2['pic_name'])) {
                                                return $category2['pic_name'];
                                            }
                                        }

                                        // If installation_type is internal_installation, try to get from contact_detail
                                        if ($record && $record->installation_type === 'internal_installation' && $record->contact_detail) {
                                            try {
                                                $contactDetails = is_string($record->contact_detail)
                                                    ? json_decode($record->contact_detail, true)
                                                    : $record->contact_detail;

                                                if (is_array($contactDetails) && count($contactDetails) > 0 && isset($contactDetails[0]['pic_name'])) {
                                                    return $contactDetails[0]['pic_name'];
                                                }
                                            } catch (\Exception $e) {
                                                \Illuminate\Support\Facades\Log::error('Error parsing contact details', [
                                                    'error' => $e->getMessage(),
                                                    'record_id' => $record->id
                                                ]);
                                            }
                                        }

                                        // Fallback to lead information
                                        $lead = \App\Models\Lead::find($record->lead_id);
                                        return $lead->companyDetail->name ?? $lead->name ?? '';
                                    })
                                    ->visible(fn(callable $get) => in_array($get('installation_type'), ['external_installation', 'internal_installation'])),

                                TextInput::make('category2.pic_phone')
                                    ->label('HP Number')
                                    ->disabled()
                                    ->tel()
                                    ->required(fn(callable $get) => in_array($get('installation_type'), ['external_installation', 'internal_installation']))
                                    ->default(function ($record) {
                                        if ($record && $record->category2) {
                                            $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                            if (isset($category2['pic_phone']) && !empty($category2['pic_phone'])) {
                                                return $category2['pic_phone'];
                                            }
                                        }

                                        // If installation_type is internal_installation, try to get from contact_detail
                                        if ($record && $record->installation_type === 'internal_installation' && $record->contact_detail) {
                                            try {
                                                $contactDetails = is_string($record->contact_detail)
                                                    ? json_decode($record->contact_detail, true)
                                                    : $record->contact_detail;

                                                if (is_array($contactDetails) && count($contactDetails) > 0 && isset($contactDetails[0]['pic_phone'])) {
                                                    return $contactDetails[0]['pic_phone'];
                                                }
                                            } catch (\Exception $e) {
                                                \Illuminate\Support\Facades\Log::error('Error parsing contact details', [
                                                    'error' => $e->getMessage(),
                                                    'record_id' => $record->id
                                                ]);
                                            }
                                        }

                                        // Fallback to lead information
                                        $lead = \App\Models\Lead::find($record->lead_id);
                                        return $lead->companyDetail->contact_no ?? $lead->contact_no ?? '';
                                    })
                                    ->visible(fn(callable $get) => in_array($get('installation_type'), ['external_installation', 'internal_installation'])),

                                TextInput::make('category2.email')
                                    ->label('Email Address')
                                    ->disabled()
                                    ->email()
                                    ->required(fn(callable $get) => in_array($get('installation_type'), ['external_installation', 'internal_installation']))
                                    ->default(function ($record) {
                                        if ($record && $record->category2) {
                                            $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                            if (isset($category2['email']) && !empty($category2['email'])) {
                                                return $category2['email'];
                                            }
                                        }

                                        // If installation_type is internal_installation, try to get from contact_detail
                                        if ($record && $record->installation_type === 'internal_installation' && $record->contact_detail) {
                                            try {
                                                $contactDetails = is_string($record->contact_detail)
                                                    ? json_decode($record->contact_detail, true)
                                                    : $record->contact_detail;

                                                if (is_array($contactDetails) && count($contactDetails) > 0 && isset($contactDetails[0]['pic_email'])) {
                                                    return $contactDetails[0]['pic_email'];
                                                }
                                            } catch (\Exception $e) {
                                                \Illuminate\Support\Facades\Log::error('Error parsing contact details', [
                                                    'error' => $e->getMessage(),
                                                    'record_id' => $record->id
                                                ]);
                                            }
                                        }

                                        // Fallback to lead information
                                        $lead = \App\Models\Lead::find($record->lead_id);
                                        return $lead->companyDetail->email ?? $lead->email ?? '';
                                    })
                                    ->visible(fn(callable $get) => in_array($get('installation_type'), ['external_installation', 'internal_installation'])),
                            ]),
                            Repeater::make('device_serial_attachments')
                            ->schema([
                                Grid::make(2)
                                    ->schema([
                                        Textarea::make('installation_address')
                                            ->hiddenLabel()
                                            ->required()
                                            ->rows(2)
                                            ->placeholder('Provide the specific address where this device is installed')
                                            ->extraInputAttributes(['style' => 'text-transform: uppercase']),

                                        FileUpload::make('attachments')
                                            ->hiddenLabel()
                                            ->helperText('Maximum 5 images or PDF files')
                                            ->disk('public')
                                            ->directory('handovers/device_installations')
                                            ->visibility('public')
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'])
                                            ->multiple()
                                            ->maxFiles(5)
                                            ->openable()
                                            ->downloadable()
                                    ]),

                                Hidden::make('device_type'),
                                Hidden::make('serial_number')
                            ])
                            ->addable(false)
                            ->deletable(false)
                            ->columns(1)
                            ->itemLabel(function (array $state): ?string {
                                $deviceType = $state['device_type'] ?? null;
                                $serialNumber = $state['serial_number'] ?? null;

                                if (!$deviceType || !$serialNumber) {
                                    return 'Device';
                                }

                                $formattedDeviceType = match ($deviceType) {
                                    'tc10' => 'TC10',
                                    'tc20' => 'TC20',
                                    'face_id5' => 'FACE ID 5',
                                    'face_id6' => 'FACE ID 6',
                                    'time_beacon' => 'TIME BEACON',
                                    'nfc_tag' => 'NFC TAG',
                                    default => 'DEVICE'
                                };

                                return "{$formattedDeviceType} - {$serialNumber}";
                            })
                            ->visible(function () {
                                $recordId = $this->mountedTableActionRecord;
                                $record = \App\Models\HardwareHandover::find($recordId);

                                // If no record or not the right type, hide the repeater
                                if (!$record instanceof \App\Models\HardwareHandover) {
                                    return false;
                                }

                                // If no device_serials, hide the repeater
                                if (empty($record->device_serials)) {
                                    return false;
                                }

                                // Parse device serials if needed
                                $deviceSerials = is_string($record->device_serials)
                                    ? json_decode($record->device_serials, true)
                                    : $record->device_serials;

                                // If not an array or empty, hide the repeater
                                if (!is_array($deviceSerials)) {
                                    return false;
                                }

                                // Check if any device type has serials
                                $hasAnySerials = false;
                                $deviceTypes = ['tc10_serials', 'tc20_serials', 'face_id5_serials', 'face_id6_serials', 'time_beacon_serials', 'nfc_tag_serials'];

                                foreach ($deviceTypes as $type) {
                                    if (!empty($deviceSerials[$type]) && is_array($deviceSerials[$type]) && count($deviceSerials[$type]) > 0) {
                                        $hasAnySerials = true;
                                        break;
                                    }
                                }

                                return $hasAnySerials;
                            })
                            ->afterStateHydrated(function (Repeater $component, $state, callable $set) {
                                // This ensures we're using the correct record
                                $recordId = null;

                                if ($this->mountedTableActionRecord instanceof \App\Models\HardwareHandover) {
                                    // Direct record instance
                                    $record = $this->mountedTableActionRecord;
                                } else if (is_numeric($this->mountedTableActionRecord)) {
                                    // Record ID
                                    $recordId = $this->mountedTableActionRecord;
                                    $record = \App\Models\HardwareHandover::find($recordId);
                                } else {
                                    // Try to get ID from mountedTableActionRecordId
                                    $recordId = $this->mountedTableActionRecordId ?? null;
                                    $record = $recordId ? \App\Models\HardwareHandover::find($recordId) : null;
                                }

                                // Check if we found a valid record
                                if (!$record || !$record->device_serials) {
                                    \Illuminate\Support\Facades\Log::debug('No valid hardware handover record found');
                                    return;
                                }

                                // Parse device serials and continue with your existing code...
                                $deviceSerials = is_string($record->device_serials)
                                    ? json_decode($record->device_serials, true)
                                    : $record->device_serials;

                                if (!is_array($deviceSerials)) {
                                    return;
                                }

                                $serialItems = [];
                                $deviceTypeMap = [
                                    'tc10_serials' => 'tc10',
                                    'tc20_serials' => 'tc20',
                                    'face_id5_serials' => 'face_id5',
                                    'face_id6_serials' => 'face_id6',
                                    'time_beacon_serials' => 'time_beacon',
                                    'nfc_tag_serials' => 'nfc_tag'
                                ];

                                foreach ($deviceTypeMap as $serialKey => $deviceType) {
                                    if (!empty($deviceSerials[$serialKey]) && is_array($deviceSerials[$serialKey])) {
                                        foreach ($deviceSerials[$serialKey] as $serial) {
                                            if (!empty($serial['serial'])) {
                                                $serialItems[] = [
                                                    'device_type' => $deviceType,
                                                    'serial_number' => $serial['serial'],
                                                    'installation_address' => $serial['installation_address'] ?? '',
                                                    'attachments' => $serial['attachments'] ?? []
                                                ];
                                            }
                                        }
                                    }
                                }

                                $set('device_serial_attachments', $serialItems);
                            })
                        ])
                        ->action(function (HardwareHandover $record, array $data): void {
                            // Process remarks to merge with existing ones
                            if (isset($data['remarks']) && is_array($data['remarks'])) {
                                // Get existing admin remarks
                                $existingAdminRemarks = [];
                                if ($record->admin_remarks) {
                                    $existingAdminRemarks = is_string($record->admin_remarks)
                                        ? json_decode($record->admin_remarks, true)
                                        : $record->admin_remarks;

                                    if (!is_array($existingAdminRemarks)) {
                                        $existingAdminRemarks = [];
                                    }
                                }

                                // Process new remarks and encode attachments
                                foreach ($data['remarks'] as $key => $remark) {
                                    // Add user information and timestamp
                                    $data['remarks'][$key]['user_id'] = auth()->id();
                                    $data['remarks'][$key]['user_name'] = auth()->user()->name;
                                    $data['remarks'][$key]['created_at'] = now()->format('Y-m-d H:i:s');

                                    // Store attachments in a proper format
                                    if (isset($remark['attachments']) && is_array($remark['attachments'])) {
                                        $data['remarks'][$key]['attachments'] = json_encode($remark['attachments']);
                                    }
                                }

                                // Merge existing admin remarks with new ones
                                $allAdminRemarks = array_merge($existingAdminRemarks, $data['remarks']);

                                // Update the record with admin remarks and status
                                $updateData = [
                                    'completed_at' => now(),
                                    'status' => 'Completed: Installation',
                                    'admin_remarks' => json_encode($allAdminRemarks)
                                ];

                                $record->update($updateData);
                            } else {
                                // If no remarks provided, just update status
                                $record->update([
                                    'completed_at' => now(),
                                    'status' => 'Completed: Installation',
                                ]);
                            }

                            if ($record->installation_type === 'external_installation' && isset($data['reseller_invoice'])) {
                                $record->update([
                                    'reseller_invoice' => $data['reseller_invoice']
                                ]);
                            }

                            $deviceInstallationDetails = [];
                            if (isset($data['device_serial_attachments']) && is_array($data['device_serial_attachments'])) {
                                // Get current device serials
                                $deviceSerials = is_string($record->device_serials)
                                    ? json_decode($record->device_serials, true)
                                    : $record->device_serials;

                                if (!is_array($deviceSerials)) {
                                    $deviceSerials = [];
                                }

                                // Map form data to device_serials structure
                                $deviceTypeMap = [
                                    'tc10' => 'tc10_serials',
                                    'tc20' => 'tc20_serials',
                                    'face_id5' => 'face_id5_serials',
                                    'face_id6' => 'face_id6_serials',
                                    'time_beacon' => 'time_beacon_serials',
                                    'nfc_tag' => 'nfc_tag_serials'
                                ];

                                // Process each submitted device
                                foreach ($data['device_serial_attachments'] as $attachment) {
                                    $deviceType = $attachment['device_type'] ?? null;
                                    $serialNumber = $attachment['serial_number'] ?? null;
                                    $installationAddress = $attachment['installation_address'] ?? null;
                                    $uploadedAttachments = $attachment['attachments'] ?? [];

                                    if (!$deviceType || !$serialNumber) {
                                        continue;
                                    }

                                     $deviceInstallationDetails[] = [
                                        'device_type' => $deviceTypeDisplayMap[$deviceType] ?? 'DEVICE',
                                        'serial_number' => $serialNumber,
                                        'installation_address' => $installationAddress,
                                        'attachments' => $uploadedAttachments
                                    ];

                                    // Get the corresponding key in device_serials
                                    $serialsKey = $deviceTypeMap[$deviceType] ?? null;
                                    if (!$serialsKey || !isset($deviceSerials[$serialsKey])) {
                                        continue;
                                    }

                                    // Find matching serial number in the device type array
                                    foreach ($deviceSerials[$serialsKey] as $index => &$serial) {
                                        if (isset($serial['serial']) && $serial['serial'] === $serialNumber) {
                                            // Update installation address
                                            if ($installationAddress) {
                                                $serial['installation_address'] = strtoupper($installationAddress);
                                            }

                                            // Update attachments
                                            if (!empty($uploadedAttachments)) {
                                                $serial['attachments'] = $uploadedAttachments;
                                            }

                                            break;
                                        }
                                    }
                                }

                                // Update the record with modified device_serials
                                $record->update([
                                    'device_serials' => json_encode($deviceSerials)
                                ]);

                                // Log successful update
                                \Illuminate\Support\Facades\Log::info("Updated device serial attachments for handover #{$record->id}", [
                                    'device_count' => count($data['device_serial_attachments'])
                                ]);
                            }

                            // Process required attendees from form data
                            $requiredAttendeesInput = $data['required_attendees'] ?? '';
                            $attendeeEmails = [];
                            if (!empty($requiredAttendeesInput)) {
                                $attendeeEmails = array_filter(array_map('trim', explode(';', $requiredAttendeesInput)));
                            }

                            // Create a repair appointment for installation
                            $lead = $record->lead;
                            if ($lead) {
                                $appointment = new \App\Models\RepairAppointment();
                                $appointment->fill([
                                    'lead_id' => $lead->id,
                                    'type' => 'NEW INSTALLATION',
                                    'appointment_type' => 'ONSITE',
                                    'date' => $data['date'],
                                    'start_time' => $data['start_time'],
                                    'end_time' => $data['end_time'],
                                    'technician' => $data['technician'],
                                    'causer_id' => auth()->id(),
                                    'technician_assigned_date' => now(),
                                    'remarks' => $data['remarks'] ?? '',
                                    'status' => 'New',
                                    'title' => 'NEW INSTALLATION | ONSITE | TIMETEC REPAIR | ' . ($lead->companyDetail->company_name ?? $record->company_name),
                                    'required_attendees' => !empty($attendeeEmails) ? json_encode($attendeeEmails) : null,
                                ]);
                                $appointment->save();

                                // Regenerate PDF with updated information
                                try {
                                    $pdfController = new \App\Http\Controllers\GenerateHardwareHandoverPdfController();
                                    $pdfPath = $pdfController->generateInBackground($record);

                                    if ($pdfPath && $pdfPath !== $record->handover_pdf) {
                                        $record->update(['handover_pdf' => $pdfPath]);
                                    }
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error("Failed to regenerate hardware handover PDF", [
                                        'handover_id' => $record->id,
                                        'error' => $e->getMessage()
                                    ]);
                                }

                                // Set up email recipients for appointment notification
                                $recipients = []; // Admin email

                                // Add required attendees if they have valid emails
                                foreach ($attendeeEmails as $email) {
                                    if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $recipients)) {
                                        $recipients[] = $email;
                                    }
                                }

                                $technicianName = $data['technician'] ?? null;
                                $technician = null;
                                $technicianEmail = null;

                                // Check if technician is a name (string)
                                if ($technicianName && is_string($technicianName)) {
                                    // First try to find user by name (internal technician)
                                    $technician = \App\Models\User::where('name', $technicianName)->first();

                                    // If found, get the email
                                    if ($technician) {
                                        $technicianEmail = $technician->email;
                                    } else {
                                        // If not found as a user, check if it's a reseller company
                                        $reseller = \App\Models\Reseller::where('company_name', $technicianName)->first();
                                        if ($reseller) {
                                            $technicianEmail = $reseller->email ?? null;
                                        }
                                    }
                                }

                                // Add technician email to recipients if valid
                                if ($technicianEmail && filter_var($technicianEmail, FILTER_VALIDATE_EMAIL) && !in_array($technicianEmail, $recipients)) {
                                    $recipients[] = $technicianEmail;
                                }

                                // Prepare email content for appointment notification
                                $viewName = 'emails.repair_appointment_notification';
                                $emailContent = [
                                    'leadOwnerName' => $lead->lead_owner ?? 'TimeTec Support',
                                    'lead' => [
                                        'lastName' => $lead->companyDetail->name ?? $lead->name,
                                        'company' => $lead->companyDetail->company_name ?? $record->company_name ?? 'N/A',
                                        'technicianName' => $data['technician'] ?? 'N/A',
                                        'phone' => optional($lead->companyDetail)->contact_no ?? $lead->phone ?? 'N/A',
                                        'pic' => optional($lead->companyDetail)->name ?? $lead->name ?? 'N/A',
                                        'email' => optional($lead->companyDetail)->email ?? $lead->email ?? 'N/A',
                                        'date' => Carbon::parse($data['date'])->format('d/m/Y') ?? 'N/A',
                                        'startTime' => Carbon::parse($data['start_time'])->format('h:i A') ?? 'N/A',
                                        'endTime' => Carbon::parse($data['end_time'])->format('h:i A') ?? 'N/A',
                                        'leadOwnerMobileNumber' => $salesperson?->mobile_number ?? 'N/A',
                                        'repair_type' => 'NEW INSTALLATION',
                                        'appointment_type' => 'ONSITE',
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
                                        \Illuminate\Support\Facades\Mail::send($viewName, ['content' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $lead) {
                                            $companyName = $lead->companyDetail->company_name ?? 'Unknown Company';
                                            $message->from($senderEmail, $senderName)
                                                ->to($recipients)
                                                ->subject("TIMETEC REPAIR APPOINTMENT | NEW INSTALLATION | {$companyName}");
                                        });

                                        Notification::make()
                                            ->title('Installation appointment notification sent')
                                            ->success()
                                            ->body('Email notification sent to administrator and required attendees')
                                            ->send();
                                    }
                                } catch (\Exception $e) {
                                    // Handle email sending failure
                                    \Illuminate\Support\Facades\Log::error("Email sending failed for installation appointment: Error: {$e->getMessage()}");

                                    Notification::make()
                                        ->title('Email Notification Failed')
                                        ->danger()
                                        ->body('Could not send email notification: ' . $e->getMessage())
                                        ->send();
                                }
                            }

                            try {
                                if ($record->installation_type === 'internal_installation') {
                                    // Format the handover ID properly
                                    $handoverId = 'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

                                    // Get company name
                                    $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';

                                    // Create email data array
                                    $emailData = [
                                        'handover_id' => $handoverId,
                                        'company_name' => $companyName,
                                        'installation_type' => 'INTERNAL INSTALLATION',
                                        'technician_name' => $data['technician'] ?? 'Not specified',
                                        'pic_details' => [
                                            'name' => isset($data['category2']['pic_name']) ? $data['category2']['pic_name'] :
                                                ($record->contact_detail && is_array(json_decode($record->contact_detail, true)) && !empty(json_decode($record->contact_detail, true)) ?
                                                    json_decode($record->contact_detail, true)[0]['pic_name'] ?? 'Not specified' :
                                                    ($record->category2 && is_array($record->category2) ? $record->category2['pic_name'] ?? 'Not specified' : 'Not specified')),

                                            'phone' => isset($data['category2']['pic_phone']) ? $data['category2']['pic_phone'] :
                                                ($record->contact_detail && is_array(json_decode($record->contact_detail, true)) && !empty(json_decode($record->contact_detail, true)) ?
                                                    json_decode($record->contact_detail, true)[0]['pic_phone'] ?? 'Not specified' :
                                                    ($record->category2 && is_array($record->category2) ? $record->category2['pic_phone'] ?? 'Not specified' : 'Not specified')),

                                            'email' => isset($data['category2']['email']) ? $data['category2']['email'] :
                                                ($record->contact_detail && is_array(json_decode($record->contact_detail, true)) && !empty(json_decode($record->contact_detail, true)) ?
                                                    json_decode($record->contact_detail, true)[0]['pic_email'] ?? 'Not specified' :
                                                    ($record->category2 && is_array($record->category2) ? $record->category2['email'] ?? 'Not specified' : 'Not specified')),
                                        ],
                                        'devices' => $deviceInstallationDetails,
                                        'appointment_details' => [
                                            'date' => Carbon::parse($data['date'])->format('d/m/Y'),
                                            'start_time' => Carbon::parse($data['start_time'])->format('h:i A'),
                                            'end_time' => Carbon::parse($data['end_time'])->format('h:i A'),
                                        ]
                                    ];

                                    // Get authenticated user's email for sender
                                    $authUser = auth()->user();
                                    $senderEmail = $authUser->email;
                                    $senderName = $authUser->name;

                                    // Send email
                                    \Illuminate\Support\Facades\Mail::send(
                                        'emails.hardware_installation_notification',
                                        ['emailData' => $emailData],
                                        function ($message) use ($handoverId, $companyName, $senderEmail, $senderName) {
                                            $message->from($senderEmail, $senderName)
                                                ->to('admin.timetec.hr@timeteccloud.com')
                                                ->cc('izzuddin@timeteccloud.com')
                                                ->subject("HARDWARE HANDOVER ID {$handoverId} | {$companyName} | PENDING INSTALLATION");
                                        }
                                    );

                                    \Illuminate\Support\Facades\Log::info("Hardware installation notification email sent for internal installation", [
                                        'handover_id' => $handoverId,
                                        'company' => $companyName
                                    ]);
                                }
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to send hardware installation notification email", [
                                    'error' => $e->getMessage(),
                                    'handover_id' => $record->id
                                ]);
                            }

                            Notification::make()
                                ->title('Hardware handover marked as completed with installation')
                                ->success()
                                ->body('An installation appointment has been created successfully')
                                ->send();

                            // Emit event to refresh tables
                            $this->dispatch('refresh-hardwarehandover-tables');
                        }),
                    Action::make('mark_as_completed')
                        ->label(fn(): HtmlString => new HtmlString('Mark as Completed:<br>Courier'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->modalWidth('3xl')
                        // ->requiresConfirmation()
                        ->modalHeading("Mark as Completed")
                        ->modalDescription('Are you sure you want to mark this handover as completed? This will complete the hardware handover process.')
                        ->modalSubmitActionLabel('Yes, Mark as Completed')
                        ->modalCancelActionLabel('No, Cancel')
                        ->form([
                            \Filament\Forms\Components\Section::make('Category 1')
                            ->schema([
                                // Hidden field to store the actual value
                                \Filament\Forms\Components\Hidden::make('installation_type')
                                    ->default(function ($record) {
                                        return $record->installation_type ?? null;
                                    }),

                                // Display the selected installation type
                                \Filament\Forms\Components\Grid::make(1)
                                    ->schema([
                                        \Filament\Forms\Components\Placeholder::make('installation_type_display')
                                            ->label('Selected Installation Type')
                                            ->inlineLabel()
                                            ->content(function ($get) {
                                                $type = $get('installation_type');
                                                $label = match($type) {
                                                    'courier' => 'Courier',
                                                    'internal_installation' => 'Internal Installation',
                                                    'external_installation' => 'External Installation',
                                                    'self_pick_up' => 'Self Pick-up',
                                                    default => 'Not Selected'
                                                };

                                                // Different styles for different installation types
                                                $styles = match($type) {
                                                    'courier' => 'background-color: #ecfdf5; color: #065f46; padding: 8px 12px; border-radius: 4px; display: inline-block; font-weight: 500; border: 1px solid #10b981;',
                                                    'internal_installation' => 'background-color: #eff6ff; color: #1e40af; padding: 8px 12px; border-radius: 4px; display: inline-block; font-weight: 500; border: 1px solid #3b82f6;',
                                                    'external_installation' => 'background-color: #fffbeb; color: #92400e; padding: 8px 12px; border-radius: 4px; display: inline-block; font-weight: 500; border: 1px solid #f59e0b;',
                                                    default => 'background-color: #f3f4f6; color: #1f2937; padding: 8px 12px; border-radius: 4px; display: inline-block; font-weight: 500; border: 1px solid #9ca3af;',
                                                };

                                                return new \Illuminate\Support\HtmlString(
                                                    "<span style=\"{$styles}\">{$label}</span>"
                                                );
                                            })
                                    ])
                            ]),

                            \Filament\Forms\Components\Section::make('Category 2')
                                ->schema([
                                    \Filament\Forms\Components\Placeholder::make('installation_type_helper')
                                        ->label('')
                                        ->content('Please select an installation type in Step 4 to see the relevant fields')
                                        ->visible(fn(callable $get) => empty($get('installation_type')))
                                        ->inlineLabel(),

                                    \Filament\Forms\Components\Grid::make(1)
                                        ->schema([
                                            \Filament\Forms\Components\Select::make('category2.installer')
                                                ->label('Installer')
                                                ->visible(fn(callable $get) => $get('installation_type') === 'internal_installation')
                                                ->required(fn(callable $get) => $get('installation_type') === 'internal_installation')
                                                ->options(function () {
                                                    // Retrieve options from the installer table
                                                    return \App\Models\Installer::pluck('company_name', 'id')->toArray();
                                                })
                                                ->disabled()
                                                ->default(function ($record) {
                                                    // First check if record has category2 data already
                                                    if ($record && $record->category2) {
                                                        $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                        if (isset($category2['installer']) && !empty($category2['installer'])) {
                                                            return $category2['installer'];
                                                        }
                                                    }
                                                    return null;
                                                })
                                                ->searchable()
                                                ->preload(),

                                            \Filament\Forms\Components\Select::make('category2.reseller')
                                                ->label('Reseller')
                                                ->visible(fn(callable $get) => $get('installation_type') === 'external_installation')
                                                ->required(fn(callable $get) => $get('installation_type') === 'external_installation')
                                                ->options(function () {
                                                    // Retrieve options from the reseller table
                                                    return \App\Models\Reseller::pluck('company_name', 'id')->toArray();
                                                })
                                                ->disabled()
                                                ->default(function ($record) {
                                                    // First check if record has category2 data already
                                                    if ($record && $record->category2) {
                                                        $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                        if (isset($category2['reseller']) && !empty($category2['reseller'])) {
                                                            return $category2['reseller'];
                                                        }
                                                    }
                                                    return null;
                                                })
                                                ->searchable()
                                                ->preload(),

                                            \Filament\Forms\Components\Textarea::make('category2.courier_address')
                                                ->label('Courier Address')
                                                ->required(fn(callable $get) => $get('installation_type') === 'courier')
                                                ->rows(2)
                                                ->disabled()
                                                ->default(function ($record) {
                                                    // First check if record has category2 data already
                                                    if ($record && $record->category2) {
                                                        $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                        if (isset($category2['courier_address']) && !empty($category2['courier_address'])) {
                                                            return $category2['courier_address'];
                                                        }
                                                    }

                                                    // If no record data, try to get lead address
                                                    $lead = \App\Models\Lead::find($record->lead_id);
                                                    if ($lead && $lead->companyDetail) {
                                                        $address = $lead->companyDetail->company_address1 ?? '';
                                                        if (!empty($lead->companyDetail->company_address2)) {
                                                            $address .= ", " . $lead->companyDetail->company_address2;
                                                        }
                                                        if (!empty($lead->companyDetail->postcode) || !empty($lead->companyDetail->state)) {
                                                            $address .= ", " . ($lead->companyDetail->postcode ?? '') . " " .
                                                                ($lead->companyDetail->state ?? '');
                                                        }
                                                        return $address;
                                                    } else if ($lead) {
                                                        $address = $lead->address1 ?? '';
                                                        if (!empty($lead->address2)) {
                                                            $address .= ", " . $lead->address2;
                                                        }
                                                        if (!empty($lead->postcode) || !empty($lead->state)) {
                                                            $address .= ", " . ($lead->postcode ?? '') . " " . ($lead->state ?? '');
                                                        }
                                                        return $address;
                                                    }
                                                    return '';
                                                })
                                                ->visible(fn(callable $get) => $get('installation_type') === 'courier'),

                                            \Filament\Forms\Components\Grid::make(3)
                                                ->schema([
                                                    \Filament\Forms\Components\TextInput::make('category2.pic_name')
                                                        ->label('Name')
                                                        ->disabled()
                                                        ->required(fn(callable $get) => $get('installation_type') === 'external_installation')
                                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                        ->default(function ($record) {
                                                            if ($record && $record->category2) {
                                                                $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                                if (isset($category2['pic_name']) && !empty($category2['pic_name'])) {
                                                                    return $category2['pic_name'];
                                                                }
                                                            }
                                                            $lead = \App\Models\Lead::find($record->lead_id);
                                                            return $lead->companyDetail->name ?? $lead->name ?? '';
                                                        })
                                                        ->visible(fn(callable $get) => $get('installation_type') === 'external_installation'),

                                                    \Filament\Forms\Components\TextInput::make('category2.pic_phone')
                                                        ->label('HP Number')
                                                        ->disabled()
                                                        ->tel()
                                                        ->required(fn(callable $get) => $get('installation_type') === 'external_installation')
                                                        ->default(function ($record) {
                                                            if ($record && $record->category2) {
                                                                $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                                if (isset($category2['pic_phone']) && !empty($category2['pic_phone'])) {
                                                                    return $category2['pic_phone'];
                                                                }
                                                            }
                                                            $lead = \App\Models\Lead::find($record->lead_id);
                                                            return $lead->companyDetail->contact_no ?? $lead->contact_no ?? '';
                                                        })
                                                        ->visible(fn(callable $get) => $get('installation_type') === 'external_installation'),

                                                    \Filament\Forms\Components\TextInput::make('category2.email')
                                                        ->label('Email Address')
                                                        ->disabled()
                                                        ->email()
                                                        ->required(fn(callable $get) => $get('installation_type') === 'external_installation')
                                                        ->default(function ($record) {
                                                            if ($record && $record->category2) {
                                                                $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;
                                                                if (isset($category2['email']) && !empty($category2['email'])) {
                                                                    return $category2['email'];
                                                                }
                                                            }
                                                            $lead = \App\Models\Lead::find($record->lead_id);
                                                            return $lead->companyDetail->email ?? $lead->email ?? '';
                                                        })
                                                        ->visible(fn(callable $get) => $get('installation_type') === 'external_installation'),
                                                ]),
                                        ]),
                                ]),
                            Section::make('Admin Remark')
                                ->schema([
                                    Repeater::make('remarks')
                                        ->label('Admin Remarks')
                                        ->hiddenLabel(true)
                                        ->schema([
                                            Grid::make(2)
                                                ->schema([
                                                    Textarea::make('remark')
                                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                                        ->afterStateHydrated(fn($state) => Str::upper($state))
                                                        ->afterStateUpdated(fn($state) => Str::upper($state))
                                                        ->hiddenLabel(true)
                                                        ->label(function ($livewire) {
                                                            // Get the current array key from the state path
                                                            $statePath = $livewire->getFormStatePath();
                                                            $matches = [];
                                                            if (preg_match('/remarks\.(\d+)\./', $statePath, $matches)) {
                                                                $index = (int) $matches[1];
                                                                return 'Admin Remark ' . ($index + 1);
                                                            }

                                                            return 'Remark';
                                                        })
                                                        ->placeholder('Enter remark here')
                                                        ->autosize()
                                                        ->required()
                                                        ->rows(3),

                                                    FileUpload::make('attachments')
                                                        ->hiddenLabel(true)
                                                        ->disk('public')
                                                        ->directory('handovers/remark_attachments')
                                                        ->visibility('public')
                                                        ->multiple()
                                                        ->maxFiles(3)
                                                        ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/jpg'])
                                                        ->openable()
                                                        ->downloadable()
                                                        ->required()
                                                        ->getUploadedFileNameForStorageUsing(function (TemporaryUploadedFile $file, callable $get): string {
                                                            // In the context of a form within a table action, we can get the record from the mountedTableActionRecord property
                                                            $record = $this->mountedTableActionRecord;

                                                            if (!$record || !($record instanceof \App\Models\HardwareHandover)) {
                                                                // Fallback if record not available
                                                                $leadId = rand(1, 999); // Use a random number as fallback
                                                                $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                                            } else {
                                                                // Use the lead ID from the record
                                                                $leadId = $record->lead_id ?? $record->id;
                                                                $formattedId = '250' . str_pad($leadId, 3, '0', STR_PAD_LEFT);
                                                            }

                                                            // Get extension
                                                            $extension = $file->getClientOriginalExtension();

                                                            // Generate a unique identifier (timestamp) to avoid overwriting files
                                                            $timestamp = now()->format('YmdHis');
                                                            $random = rand(1000, 9999);

                                                            return "{$formattedId}-HW-REMARK-{$timestamp}-{$random}.{$extension}";
                                                        }),
                                                ])
                                        ])
                                        ->itemLabel(fn() => __('Admin Remark') . ' ' . ++self::$indexRepeater2)
                                        ->addActionLabel('Add Admin Remark')
                                        ->maxItems(5),
                                ]),
                        ])
                        ->action(function (HardwareHandover $record, array $data): void {
                            // Process remarks to merge with existing ones
                            if (isset($data['remarks']) && is_array($data['remarks'])) {
                                // Get existing admin remarks
                                $existingAdminRemarks = [];
                                if ($record->admin_remarks) {
                                    $existingAdminRemarks = is_string($record->admin_remarks)
                                        ? json_decode($record->admin_remarks, true)
                                        : $record->admin_remarks;

                                    if (!is_array($existingAdminRemarks)) {
                                        $existingAdminRemarks = [];
                                    }
                                }

                                // Process new remarks and encode attachments
                                foreach ($data['remarks'] as $key => $remark) {
                                    // Store attachments in a proper format
                                    if (isset($remark['attachments']) && is_array($remark['attachments'])) {
                                        $data['remarks'][$key]['attachments'] = json_encode($remark['attachments']);
                                    }
                                }

                                // Merge existing admin remarks with new ones
                                $allAdminRemarks = array_merge($existingAdminRemarks, $data['remarks']);

                                // Update the record with admin remarks
                                $updateData = [
                                    'completed_at' => now(),
                                    'status' => 'Completed: Courier',
                                    'admin_remarks' => json_encode($allAdminRemarks)
                                ];

                                $record->update($updateData);
                            }
                            else {
                                // If no remarks provided, just update status
                                $record->update([
                                    'completed_at' => now(),
                                    'status' => 'Completed: Courier',
                                ]);
                            }

                            // Get the implementer info
                            $implementerName = $record->implementer ?? 'Unknown';
                            $implementer = null;
                            $implementerEmail = null;

                            // Check if implementer is a name (string) or an ID
                            if ($implementerName && is_string($implementerName)) {
                                // Try to find user by name
                                $implementer = \App\Models\User::where('name', $implementerName)->first();
                                if (!$implementer) {
                                    // As fallback, check if it might be an ID despite being stored as implementer
                                    $implementer = \App\Models\User::find($implementerName);
                                }

                                // Get email if we found a user
                                $implementerEmail = $implementer?->email ?? null;
                            } else if (is_numeric($implementerName)) {
                                // If implementer is stored as an ID
                                $implementer = \App\Models\User::find($implementerName);
                                $implementerEmail = $implementer?->email ?? null;
                                $implementerName = $implementer?->name ?? 'Unknown';
                            }

                            // Get the salesperson info
                            $salespersonId = $record->lead->salesperson ?? null;
                            $salesperson = \App\Models\User::find($salespersonId);
                            $salespersonEmail = $salesperson?->email ?? null;
                            $salespersonName = $salesperson?->name ?? 'Unknown Salesperson';

                            // Get the company name
                            $companyName = $record->company_name ?? $record->lead->companyDetail->company_name ?? 'Unknown Company';

                            $record->update($updateData);

                            // Regenerate PDF with updated information
                            try {
                                $pdfController = new \App\Http\Controllers\GenerateHardwareHandoverPdfController();
                                $pdfPath = $pdfController->generateInBackground($record);

                                if ($pdfPath && $pdfPath !== $record->handover_pdf) {
                                    $record->update(['handover_pdf' => $pdfPath]);
                                }
                            } catch (\Exception $e) {
                                \Illuminate\Support\Facades\Log::error("Failed to regenerate hardware handover PDF", [
                                    'handover_id' => $record->id,
                                    'error' => $e->getMessage()
                                ]);
                            }

                            // Format the handover ID properly
                            $handoverId = 'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

                            // Get the handover PDF URL
                            $handoverFormUrl = $record->handover_pdf ? url('storage/' . $record->handover_pdf) : null;

                            $invoiceFiles = [];
                            if ($record->invoice_file) {
                                $invoiceFileArray = is_string($record->invoice_file)
                                    ? json_decode($record->invoice_file, true)
                                    : $record->invoice_file;

                                if (is_array($invoiceFileArray)) {
                                    foreach ($invoiceFileArray as $file) {
                                        $invoiceFiles[] = url('storage/' . $file);
                                    }
                                }
                            }

                            $salesOrderFiles = [];
                            if ($record->sales_order_file) {
                                $salesOrderFileArray = is_string($record->sales_order_file)
                                    ? json_decode($record->sales_order_file, true)
                                    : $record->sales_order_file;

                                if (is_array($salesOrderFileArray)) {
                                    foreach ($salesOrderFileArray as $file) {
                                        $salesOrderFiles[] = url('storage/' . $file);
                                    }
                                }
                            }

                            // Send email notification
                            try {
                                $viewName = 'emails.hardware_completed_notification';

                                // Create email content structure
                                $emailContent = [
                                    'implementer' => [
                                        'name' => $implementerName,
                                    ],
                                    'company' => [
                                        'name' => $companyName,
                                    ],
                                    'salesperson' => [
                                        'name' => $salespersonName,
                                    ],
                                    'handover_id' => $handoverId,
                                    'activatedAt' => now()->format('d M Y'),
                                    'handoverFormUrl' => $handoverFormUrl,
                                    'invoiceFiles' => $invoiceFiles,
                                    'salesOrderFiles' => $salesOrderFiles,
                                    'devices' => [
                                        'tc10' => [
                                            'quantity' => (int)$record->tc10_quantity,
                                            'status' => (int)$record->tc10_quantity > 0 ? 'Available' : 'Pending Stock'
                                        ],
                                        'tc20' => [
                                            'quantity' => (int)$record->tc20_quantity,
                                            'status' => (int)$record->tc20_quantity > 0 ? 'Available' : 'Pending Stock'
                                        ],
                                        'face_id5' => [
                                            'quantity' => (int)$record->face_id5_quantity,
                                            'status' => (int)$record->face_id5_quantity > 0 ? 'Available' : 'Pending Stock'
                                        ],
                                        'face_id6' => [
                                            'quantity' => (int)$record->face_id6_quantity,
                                            'status' => (int)$record->face_id6_quantity > 0 ? 'Available' : 'Pending Stock'
                                        ],
                                        'time_beacon' => [
                                            'quantity' => (int)$record->time_beacon_quantity,
                                            'status' => (int)$record->time_beacon_quantity > 0 ? 'Available' : 'Pending Stock'
                                        ],
                                        'nfc_tag' => [
                                            'quantity' => (int)$record->nfc_tag_quantity,
                                            'status' => (int)$record->nfc_tag_quantity > 0 ? 'Available' : 'Pending Stock'
                                        ]
                                        ],
                                    'admin_remarks' => []
                                ];

                                if ($record->admin_remarks) {
                                    $adminRemarks = is_string($record->admin_remarks)
                                        ? json_decode($record->admin_remarks, true)
                                        : $record->admin_remarks;

                                    if (is_array($adminRemarks)) {
                                        foreach ($adminRemarks as $remark) {
                                            $formattedRemark = [
                                                'text' => $remark['remark'] ?? '',
                                                'created_by' => $remark['user_name'] ?? 'Admin',
                                                'created_at' => isset($remark['created_at'])
                                                    ? Carbon::parse($remark['created_at'])->format('d M Y h:i A')
                                                    : now()->format('d M Y h:i A'),
                                                'attachments' => []
                                            ];

                                            // Process attachments for this remark
                                            if (isset($remark['attachments'])) {
                                                $attachments = is_string($remark['attachments'])
                                                    ? json_decode($remark['attachments'], true)
                                                    : $remark['attachments'];

                                                if (is_array($attachments)) {
                                                    foreach ($attachments as $attachment) {
                                                        $formattedRemark['attachments'][] = [
                                                            'url' => url('storage/' . $attachment),
                                                            'filename' => basename($attachment)
                                                        ];
                                                    }
                                                }
                                            }

                                            $emailContent['admin_remarks'][] = $formattedRemark;
                                        }
                                    }
                                }

                                // Initialize recipients array
                                $recipients = [];

                                // Add implementer email if valid
                                if ($implementerEmail && filter_var($implementerEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $implementerEmail;
                                }

                                // Add salesperson email if valid
                                if ($salespersonEmail && filter_var($salespersonEmail, FILTER_VALIDATE_EMAIL)) {
                                    $recipients[] = $salespersonEmail;
                                }

                                // Get authenticated user's email for sender
                                $authUser = auth()->user();
                                $senderEmail = $authUser->email;
                                $senderName = $authUser->name;

                                // Send email with template and custom subject format
                                if (count($recipients) > 0) {
                                    \Illuminate\Support\Facades\Mail::send($viewName, ['emailContent' => $emailContent], function ($message) use ($recipients, $senderEmail, $senderName, $handoverId, $companyName) {
                                        $message->from($senderEmail, $senderName)
                                            ->to($recipients)
                                            ->subject("HARDWARE HANDOVER ID {$handoverId} | {$companyName}");
                                    });

                                    \Illuminate\Support\Facades\Log::info("License activation email sent successfully from {$senderEmail} to: " . implode(', ', $recipients));
                                }
                            } catch (\Exception $e) {
                                // Log error but don't stop the process
                                \Illuminate\Support\Facades\Log::error("Email sending failed for hardware handover #{$record->id}: {$e->getMessage()}");
                            }

                            Notification::make()
                                ->title('Hardware handover has been completed successfully')
                                ->success()
                                ->body('Hardware handover has been marked as completed.')
                                ->send();
                        })
                ])
                ->button()
                ->color('warning')
                ->label('Actions')
            ]);
    }

    public function render()
    {
        return view('livewire.hardware-handover-completed-migration');
    }
}
