<?php

namespace App\Livewire;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateRepairPdfController;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\AdminRepair;
use App\Models\User;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\ToggleButtons;
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
use Filament\Notifications\Notification;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\Filter;
use Livewire\Attributes\On;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class AdminRepairPendingConfirmation extends Component implements HasForms, HasTable
{
    use InteractsWithTable;
    use InteractsWithForms;

    protected static ?int $indexDeviceCounter = 0;
    protected static ?int $indexRemarkCounter = 0;

    public $selectedUser;
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

    #[On('refresh-adminrepair-tables')]
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

    public function getTableQuery(): Builder
    {
        $query = AdminRepair::query()
            ->where('status', 'Pending Confirmation')
            ->orderBy('created_at', 'desc');

        return $query;
    }

    public function table(Table $table): Table
    {
        return $table
            ->poll('300s')
            ->query($this->getTableQuery())
            ->defaultSort('created_at', 'desc')
            ->emptyState(fn() => view('components.empty-state-question'))
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('status')
                    ->label('Filter by Status')
                    ->options([
                        'Draft' => 'Draft',
                        'New' => 'New',
                        'In Progress' => 'In Progress',
                        'Awaiting Parts' => 'Awaiting Parts',
                        'Resolved' => 'Resolved',
                        'Closed' => 'Closed',
                    ])
                    ->placeholder('All Statuses')
                    ->multiple(),

                Filter::make('created_at')
                    ->form([
                        DateRangePicker::make('date_range')
                            ->label('')
                            ->placeholder('Select date range'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!empty($data['date_range'])) {
                            [$start, $end] = explode(' - ', $data['date_range']);
                            $startDate = Carbon::createFromFormat('d/m/Y', $start)->startOfDay();
                            $endDate = Carbon::createFromFormat('d/m/Y', $end)->endOfDay();
                            $query->whereBetween('created_at', [$startDate, $endDate]);
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (!empty($data['date_range'])) {
                            [$start, $end] = explode(' - ', $data['date_range']);
                            return 'From: ' . Carbon::createFromFormat('d/m/Y', $start)->format('j M Y') .
                                ' To: ' . Carbon::createFromFormat('d/m/Y', $end)->format('j M Y');
                        }
                        return null;
                    }),

                SortFilter::make("sort_by"),
            ])
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        if (!$state) {
                            return 'Unknown';
                        }
                        return 'RP_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('id', $direction);
                    })
                    ->action(
                        Action::make('viewRepairDetails')
                            ->modalHeading(' ')
                            ->modalWidth('3xl')
                            ->modalSubmitAction(false)
                            ->modalCancelAction(false)
                            ->modalContent(function (AdminRepair $record): View {
                                return view('components.repair-detail')
                                    ->with('record', $record);
                            })
                    ),

                TextColumn::make('created_at')
                    ->label('Date Created')
                    ->dateTime('d M Y, h:i A')
                    ->sortable(),

                TextColumn::make('days_elapsed')
                    ->label('Total Days')
                    ->state(function (AdminRepair $record) {
                        if (!$record->created_at) {
                            return '0 days';
                        }

                        $createdDate = Carbon::parse($record->created_at);
                        $today = Carbon::now();
                        $diffInDays = $createdDate->diffInDays($today);

                        return $diffInDays . ' ' . Str::plural('day', $diffInDays);
                    }),

                TextColumn::make('created_by')
                    ->label('Submitted By')
                    ->formatStateUsing(function ($state, AdminRepair $record) {
                        if (!$state) {
                            return 'Unknown';
                        }

                        $user = User::find($state);
                        return $user ? $user->name : 'Unknown User';
                    }),

                TextColumn::make('company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        if (!empty($record->lead_id)) {
                            $company = CompanyDetail::where('lead_id', $record->lead_id)->first();

                            if ($company) {
                                $shortened = strtoupper(Str::limit($company->company_name, 20, '...'));
                                $encryptedId = \App\Classes\Encryptor::encrypt($company->lead_id);

                                return new HtmlString('<a href="' . url('admin/leads/' . $encryptedId) . '"
                                        target="_blank"
                                        title="' . e($company->company_name) . '"
                                        class="inline-block"
                                        style="color:#338cf0;">
                                        ' . $company->company_name . '
                                    </a>');
                            }
                        }

                        // If we have a state but no company was found by lead_id
                        if ($state) {
                            $shortened = strtoupper(Str::limit($state, 20, '...'));
                            return "<span title='" . e($state) . "'>{$state}</span>";
                        }

                        return 'N/A';
                    })
                    ->html(),

                TextColumn::make('status')
                    ->label('Status')
                    ->color(fn (string $state): string => match ($state) {
                        'Draft' => 'gray',
                        'New' => 'danger',
                        'In Progress' => 'warning',
                        'Awaiting Parts' => 'info',
                        'Resolved' => 'success',
                        'Closed' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    // View detail action
                    Action::make('view')
                        ->icon('heroicon-o-eye')
                        ->modalHeading(' ')
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (AdminRepair $record): View {
                            return view('components.repair-detail')
                                ->with('record', $record);
                        }),
                    Action::make('pendingOnsiteRepair')
                        ->label('Pending Onsite Repair')
                        ->icon('heroicon-o-clock')
                        ->color('primary')
                        ->modalWidth('5xl')  // Increased for better display
                        ->modalHeading('Change Status to Pending Onsite Repair')
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
                                            $startTime = $get('start_time');
                                            if ($startTime) {
                                                return Carbon::parse($startTime)->addHour()->format('H:i');
                                            }
                                            return Carbon::now()->addMinutes(90 - (Carbon::now()->minute % 30))->format('H:i');
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
                                        ->default('REPAIR')
                                        ->required()
                                        ->label('REPAIR TYPE')
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

                            Textarea::make('appointment_remarks')
                                ->label('REMARKS')
                                ->rows(3)
                                ->autosize()
                                ->extraAlpineAttributes(['@input' => '$el.value = $el.value.toUpperCase()']),

                            TextInput::make('required_attendees')
                                ->label('Required Attendees')
                                ->helperText('Separate each email with a semicolon (e.g., email1@example.com;email2@example.com)'),

                            Grid::make(3)
                            ->schema([
                                FileUpload::make('payment_slip_file')
                                    ->label('Upload Payment Slip')
                                    ->disk('public')
                                    ->live(debounce: 500)
                                    ->directory('handovers/payment_slips')
                                    ->visibility('public')
                                    ->multiple()
                                    ->maxFiles(1)
                                    ->columnSpan(1)
                                    ->openable()
                                    ->required()
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                    ->openable(),

                                FileUpload::make('invoice_file')
                                    ->label('Upload Invoice')
                                    ->disk('public')
                                    ->directory('handovers/invoices')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                    ->multiple()
                                    ->maxFiles(10)
                                    ->columnSpan(1)
                                    ->required()
                                    ->openable(),

                                FileUpload::make('sales_order_file')
                                    ->label('Upload Sales Order')
                                    ->required()
                                    ->disk('public')
                                    ->directory('handovers/sales_orders')
                                    ->visibility('public')
                                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                                    ->multiple()
                                    ->maxFiles(10)
                                    ->columnSpan(1)
                                    ->openable(),
                            ]),
                        ])
                        ->action(function (AdminRepair $record, array $data): void {
                            $data['status'] = 'Pending Onsite Repair';

                            if (isset($data['payment_slip_file']) && is_array($data['payment_slip_file'])) {
                                $data['payment_slip_file'] = json_encode($data['payment_slip_file']);
                            }


                            if (isset($data['invoice_file']) && is_array($data['invoice_file'])) {
                                $data['invoice_file'] = json_encode($data['invoice_file']);
                            }

                            if (isset($data['sales_order_file']) && is_array($data['sales_order_file'])) {
                                // Get existing sales order files
                                $existingFiles = [];
                                if ($record->sales_order_file) {
                                    $existingFiles = is_string($record->sales_order_file)
                                        ? json_decode($record->sales_order_file, true)
                                        : $record->sales_order_file;

                                    if (!is_array($existingFiles)) {
                                        $existingFiles = [];
                                    }
                                }

                                // Merge existing files with newly uploaded ones
                                $allFiles = array_merge($existingFiles, $data['sales_order_file']);

                                // Update data with combined files
                                $data['sales_order_file'] = json_encode($allFiles);
                            }

                            $record->update($data);

                            $appointmentData = [
                                'repair_handover_id' => $record->id,
                                'lead_id' => $record->lead_id,
                                'type' => $data['type'] ?? 'REPAIR',
                                'appointment_type' => $data['appointment_type'] ?? 'ONSITE',
                                'date' => $data['date'],
                                'start_time' => $data['start_time'],
                                'end_time' => $data['end_time'],
                                'technician' => $data['technician'],
                                'causer_id' => auth()->user()->id,
                                'technician_assigned_date' => now(),
                                'remarks' => $data['appointment_remarks'] ?? null,
                                'status' => 'New',
                            ];

                            // Process required attendees if provided
                            if (!empty($data['required_attendees'])) {
                                $attendeeEmails = array_filter(array_map('trim', explode(';', $data['required_attendees'])));
                                if (!empty($attendeeEmails)) {
                                    $appointmentData['required_attendees'] = json_encode($attendeeEmails);
                                }
                            }

                            // Get lead information for the title
                            $lead = \App\Models\Lead::find($record->lead_id);
                            if ($lead && !empty($lead->companyDetail->company_name)) {
                                $appointmentData['title'] = $data['type'] . ' | ' . $data['appointment_type'] . ' | TIMETEC REPAIR | ' . $lead->companyDetail->company_name;
                            } else {
                                $appointmentData['title'] = $data['type'] . ' | ' . $data['appointment_type'] . ' | TIMETEC REPAIR';
                            }

                            // Create the appointment
                            $appointment = \App\Models\RepairAppointment::create($appointmentData);

                            // Send email notification about the appointment
                            if ($appointment && $lead) {
                                // Set up email recipients
                                $recipients = [
                                    'admin.timetec.hr@timeteccloud.com',
                                    'izzuddin@timeteccloud.com'
                                ];

                                // Process required attendees
                                $attendeeEmails = [];
                                if (!empty($data['required_attendees'])) {
                                    $attendeeEmails = array_filter(array_map('trim', explode(';', $data['required_attendees'])));

                                    // Add valid emails to recipients
                                    foreach ($attendeeEmails as $email) {
                                        if (filter_var($email, FILTER_VALIDATE_EMAIL) && !in_array($email, $recipients)) {
                                            $recipients[] = $email;
                                        }
                                    }
                                }

                                // Prepare email content
                                $viewName = 'emails.repair_appointment_notification';
                                $leadOwner = \App\Models\User::where('name', $lead->lead_owner)->first();

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
                                        'leadOwnerMobileNumber' => $leadOwner->mobile_number ?? 'N/A',
                                        'repair_type' => $data['type'],
                                        'appointment_type' => $data['appointment_type'],
                                        'remarks' => $data['appointment_remarks'] ?? 'N/A',
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
                            }

                            // Log the activity
                            \App\Models\ActivityLog::create([
                                'user_id' => auth()->id(),
                                'action' => 'Created Repair Appointment',
                                'description' => "Created a repair appointment for repair ID: {$record->id}",
                                'subject_type' => \App\Models\RepairAppointment::class,
                                'subject_id' => $appointment->id ?? null,
                            ]);

                            Notification::make()
                                ->title('Repair status updated and appointment scheduled')
                                ->success()
                                ->send();
                        })
                    ])->button()
            ]);
    }

    protected static function getSparePartOptionHtml(\App\Models\SparePart $part): string
    {
        $imageUrl = $part->picture_url ?? url('images/no-image.jpg');
        $fullImageUrl = $imageUrl; // Keep the original URL for the full view

        return '
            <div class="flex items-center w-full gap-2">
                <div class="flex-shrink-0 w-8 h-8">
                    <img src="' . e($imageUrl) . '" class="object-cover w-full h-full rounded"
                        onerror="this.onerror=null; this.src=\'' . e(url('images/no-image.jpg')) . '\'" />
                </div>
                <div class="flex-grow truncate">
                    <div class="font-medium truncate">' . e($part->name) . '</div>
                    <div class="text-xs text-gray-500 truncate">' . e($part->device_model) . '</div>
                </div>
                <div class="flex-shrink-0">
                    <button type="button"
                        onclick="event.stopPropagation(); window.open(\'' . e($fullImageUrl) . '\', \'_blank\'); return false;"
                        class="px-1 py-1 text-xs rounded text-primary-600 hover:text-primary-800">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </button>
                </div>
            </div>
        ';
    }

    protected function getDeviceWarrantyYears(string $deviceModel): int
    {
        // Standardize the model name for comparison (uppercase and trim spaces)
        $model = strtoupper(trim($deviceModel));

        // Map device models to their warranty periods
        return match (true) {
            str_contains($model, 'TC10') => 2,
            str_contains($model, 'TC20') => 2,
            str_contains($model, 'FACE ID 5') => 2,
            str_contains($model, 'FACE ID 6') => 2,
            str_contains($model, 'TA100C / HID') => 2,
            str_contains($model, 'TA100C / R') => 2,
            str_contains($model, 'TA100C / MF') => 2,
            str_contains($model, 'TA100C / R / W') => 2,
            str_contains($model, 'TA100C / MF / W') => 2,
            str_contains($model, 'TA100C / HID / W') => 2,
            str_contains($model, 'TA100C / W') => 2,
            str_contains($model, 'TIME BEACON') => 1,
            str_contains($model, 'NFC') => 1,
            // Default case
            default => 1,
        };
    }

    public function render()
    {
        return view('livewire.admin-repair-pending-confirmation');
    }
}
