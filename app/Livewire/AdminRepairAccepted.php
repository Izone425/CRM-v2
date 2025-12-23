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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;

class AdminRepairAccepted extends Component implements HasForms, HasTable
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
            ->where('status', 'Accepted')
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
                        'New' => 'New',
                        'Accepted' => 'Accepted',
                        'Pending Confirmation' => 'Pending Confirmation',
                        'Pending Onsite Repair' => 'Pending Onsite Repair',
                        'Completed' => 'Completed',
                        'Inactive' => 'Inactive',
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
                        return $record->formatted_handover_id;
                    })
                    ->color('primary')
                    ->weight('bold')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('id', $direction);
                    })
                    ->action(
                        Action::make('viewRepairDetails')
                            ->modalHeading(false)
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
                    ->label('Status'),
            ])
            ->actions([
                ActionGroup::make([
                    // View detail action
                    Action::make('view')
                        ->icon('heroicon-o-eye')
                        ->modalHeading(false)
                        ->modalWidth('3xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (AdminRepair $record): View {
                            return view('components.repair-detail')
                                ->with('record', $record);
                        }),
                    Action::make('pendingConfirmation')
                        ->label('Pending Confirmation')
                        ->icon('heroicon-o-clock')
                        ->color('warning')
                        ->modalWidth('5xl')  // Increased for better display
                        ->modalHeading('Change Status to Pending Confirmation')
                        ->form([
                            Section::make('Quotations')
                            ->schema([
                                Select::make('product_quotation')
                                    ->required()
                                    ->label('Product Quotation')
                                    ->options(function (AdminRepair $record) {
                                        $leadId = $record->lead_id;

                                        // Query quotations with the lead_id and product quotation type
                                        $query = \App\Models\Quotation::where('lead_id', $leadId)
                                            ->where('quotation_type', 'product');

                                        // Add subquery to exclude quotations where the sales person has role_id = 2
                                        $query->whereNotExists(function ($subquery) {
                                            $subquery->select(DB::raw(1))
                                                ->from('users')
                                                ->whereColumn('users.id', 'quotations.sales_person_id')
                                                ->where('users.role_id', 2);
                                        });

                                        return $query->pluck('quotation_reference_no', 'id')->toArray();
                                    })
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->default(function (AdminRepair $record) {
                                        if (!$record || !$record->product_quotation) {
                                            return [];
                                        }
                                        if (is_string($record->product_quotation)) {
                                            return json_decode($record->product_quotation, true) ?? [];
                                        }
                                        return is_array($record->product_quotation) ? $record->product_quotation : [];
                                    }),

                                Select::make('hrdf_quotation')
                                    ->label('HRDF Quotation')
                                    ->options(function (AdminRepair $record) {
                                        $leadId = $record->lead_id;
                                        return \App\Models\Quotation::where('lead_id', $leadId)
                                            ->where('quotation_type', 'hrdf')
                                            ->pluck('quotation_reference_no', 'id')
                                            ->toArray();
                                    })
                                    ->multiple()
                                    ->searchable()
                                    ->preload()
                                    ->default(function (AdminRepair $record) {
                                        if (!$record || !$record->hrdf_quotation) {
                                            return [];
                                        }
                                        if (is_string($record->hrdf_quotation)) {
                                            return json_decode($record->hrdf_quotation, true) ?? [];
                                        }
                                        return is_array($record->hrdf_quotation) ? $record->hrdf_quotation : [];
                                    }),
                            ])
                            ->columns(2),

                            Repeater::make('device_invoices')
                                ->schema([
                                    Hidden::make('device_model')
                                        ->dehydrated(true),

                                    Hidden::make('device_serial')
                                        ->dehydrated(true),

                                    Hidden::make('warranty_period')
                                        ->dehydrated(true),

                                    // Then modify the DatePicker's afterStateUpdated to use callable $get
                                    DatePicker::make('invoice_date')
                                        ->label('Invoice Date')
                                        ->required()
                                        ->maxDate(now())
                                        ->live(debounce: 550)
                                        ->afterStateUpdated(function ($state, callable $set, callable $get) {
                                            if (!$state) return;

                                            try {
                                                // Parse the selected date
                                                $invoiceDate = Carbon::parse($state);
                                                $today = Carbon::now();

                                                // Calculate days difference for raw total
                                                $totalDaysRaw = $today->diffInDays($invoiceDate);

                                                // Calculate years, months, days format
                                                $dateInterval = $today->diff($invoiceDate);
                                                $years = $dateInterval->y;
                                                $months = $dateInterval->m;
                                                $days = $dateInterval->d;

                                                // Format the total days as a readable string
                                                $formattedAge = [];
                                                if ($years > 0) {
                                                    $formattedAge[] = $years . ' ' . ($years == 1 ? 'year' : 'years');
                                                }
                                                if ($months > 0) {
                                                    $formattedAge[] = $months . ' ' . ($months == 1 ? 'month' : 'months');
                                                }
                                                if ($days > 0 || (count($formattedAge) == 0)) {
                                                    $formattedAge[] = $days . ' ' . ($days == 1 ? 'day' : 'days');
                                                }

                                                // Set the total days
                                                $set('total_days', implode(', ', $formattedAge));

                                                // Get the warranty period from the hidden field set in the default function
                                                $warrantyPeriod = $get('warranty_period');
                                                if ($warrantyPeriod && preg_match('/(\d+)/', $warrantyPeriod, $matches)) {
                                                    $warrantyYears = (int)$matches[1];
                                                } else {
                                                    $warrantyYears = 1; // Default to 1 year if we can't determine
                                                }

                                                // Calculate the warranty end date based on the actual warranty years
                                                $warrantyEndDate = $invoiceDate->copy()->addYears($warrantyYears);
                                                $isInWarranty = $warrantyEndDate->isFuture();
                                                $status = $isInWarranty ? 'In Warranty' : 'Out of Warranty';

                                                // Set the warranty status
                                                $set('warranty_status', $status);

                                            } catch (\Exception $e) {
                                                Log::error('Error in DatePicker afterStateUpdated: ' . $e->getMessage());
                                            }
                                        })
                                        ->native(false)
                                        ->displayFormat('d M Y'),

                                    TextInput::make('total_days')
                                        ->label('Total Days')
                                        ->disabled()
                                        ->live()
                                        ->dehydrated(true),

                                    TextInput::make('warranty_status')
                                        ->label('Status')
                                        ->disabled()
                                        ->dehydrated(true)
                                        ->extraAttributes(function (callable $get) {
                                            $status = $get('warranty_status');
                                            return [
                                                'class' => $status === 'In Warranty' ? 'text-green-600 font-medium' : 'text-red-600 font-medium'
                                            ];
                                        }),
                                ])
                                ->columns(3)
                                ->itemLabel(fn (array $state): ?string =>
                                    $state['device_model'] ?? null
                                        ? "{$state['device_model']} (SN: {$state['device_serial']})"
                                        : null
                                )
                                ->columnSpanFull()
                                ->addable(false)
                                ->deletable(false)
                                ->reorderable(false)
                                ->default(function (AdminRepair $record) {
                                    $items = [];

                                    // Process devices
                                    if ($record->devices) {
                                        $devices = is_string($record->devices)
                                            ? json_decode($record->devices, true)
                                            : $record->devices;

                                        if (is_array($devices)) {
                                            foreach ($devices as $device) {
                                                if (!empty($device['device_model'])) {
                                                    $deviceModel = $device['device_model'];
                                                    $deviceSerial = $device['device_serial'];
                                                    $warrantyYears = $this->getDeviceWarrantyYears($deviceModel);

                                                    $items[] = [
                                                        'device_model' => $deviceModel,
                                                        'device_serial' => $deviceSerial,
                                                        'warranty_period' => $warrantyYears . ' ' . ($warrantyYears > 1 ? 'Years' : 'Year'),
                                                    ];
                                                }
                                            }
                                        }
                                    } elseif ($record->device_model) {
                                        $deviceModel = $record->device_model;
                                        $deviceSerial = $record->device_serial;
                                        $warrantyYears = $this->getDeviceWarrantyYears($deviceModel);

                                        $items[] = [
                                            'device_model' => $deviceModel,
                                            'device_serial' => $deviceSerial,
                                            'warranty_period' => $warrantyYears . ' ' . ($warrantyYears > 1 ? 'Years' : 'Year'),
                                        ];
                                    }

                                    return $items;
                                }),
                        ])
                        ->action(function (array $data, AdminRepair $record): void {
                            // Extract the device warranty data from the form
                            $deviceInvoices = $data['device_invoices'] ?? [];

                            // Process for saving to database
                            $deviceWarrantyData = [];
                            $anyInWarranty = false;

                            foreach ($deviceInvoices as $device) {
                                if (!empty($device['invoice_date'])) {
                                    try {
                                        // Safely parse the date - handle both Carbon instances and strings
                                        $invoiceDate = $device['invoice_date'] instanceof \Carbon\Carbon
                                            ? $device['invoice_date']
                                            : \Carbon\Carbon::parse($device['invoice_date']);

                                        $deviceModel = $device['device_model'];
                                        $deviceSerial = $device['device_serial'];
                                        $warrantyYears = $this->getDeviceWarrantyYears($deviceModel);
                                        $warrantyEndDate = $invoiceDate->copy()->addYears($warrantyYears);
                                        $isInWarranty = $warrantyEndDate->isFuture();
                                        $status = $isInWarranty ? 'In Warranty' : 'Out of Warranty';

                                        if ($isInWarranty) {
                                            $anyInWarranty = true;
                                        }

                                        // Store as string in consistent Y-m-d format
                                        $deviceWarrantyData[] = [
                                            'device_model' => $deviceModel,
                                            'device_serial' => $deviceSerial,
                                            'warranty_status' => $status,
                                            'invoice_date' => $invoiceDate->format('Y-m-d'),
                                        ];
                                    } catch (\Exception $e) {
                                        // Log error but continue with what we have
                                        \Illuminate\Support\Facades\Log::error("Error processing invoice date: " . $e->getMessage());

                                        // Add with unknown warranty status
                                        $deviceWarrantyData[] = [
                                            'device_model' => $device['device_model'],
                                            'device_serial' => $device['device_serial'],
                                            'warranty_status' => 'Unknown',
                                            'invoice_date' => is_string($device['invoice_date'])
                                                ? $device['invoice_date']
                                                : now()->format('Y-m-d'),
                                        ];
                                    }
                                }
                            }

                            // Overall warranty status
                            $overallStatus = $anyInWarranty ? 'In Warranty' : 'Out of Warranty';
                            if (empty($deviceWarrantyData)) {
                                $overallStatus = 'Unknown';
                            }

                            // Update the record
                            $record->update([
                                'status' => 'Pending Confirmation',
                                'warranty_status' => $overallStatus,
                                'devices_warranty' => json_encode($deviceWarrantyData),
                                'quotation_product' => json_encode($data['product_quotation'] ?? []),
                                'quotation_hrdf' => json_encode($data['hrdf_quotation'] ?? []),
                                'pending_confirmation_date' => now(),
                            ]);

                            try {
                                // Generate PDF with proper error handling
                                $record->refresh(); // Ensure we have the latest data

                                // Pre-process critical date fields to ensure they are Carbon objects
                                if ($record->submitted_at && !($record->submitted_at instanceof \Carbon\Carbon)) {
                                    try {
                                        $record->submitted_at = \Carbon\Carbon::parse($record->submitted_at);
                                    } catch (\Exception $e) {
                                        // If parsing fails, set to current time as fallback
                                        $record->submitted_at = now();
                                    }
                                }

                                // Process the devices warranty data to ensure all dates are formatted correctly
                                if ($record->devices_warranty) {
                                    $devicesWarranty = is_string($record->devices_warranty)
                                        ? json_decode($record->devices_warranty, true)
                                        : $record->devices_warranty;

                                    if (is_array($devicesWarranty)) {
                                        foreach ($devicesWarranty as &$device) {
                                            if (!empty($device['invoice_date']) && !($device['invoice_date'] instanceof \Carbon\Carbon)) {
                                                try {
                                                    // Just ensure it's in a valid format, don't convert to Carbon
                                                    if (is_string($device['invoice_date']) && strtotime($device['invoice_date']) !== false) {
                                                        // Store as string in Y-m-d format for consistency
                                                        $device['invoice_date'] = date('Y-m-d', strtotime($device['invoice_date']));
                                                    }
                                                } catch (\Exception $e) {
                                                    // If date is invalid, set to current date as fallback
                                                    $device['invoice_date'] = date('Y-m-d');
                                                }
                                            }
                                        }

                                        // Update the record with sanitized data
                                        $record->update([
                                            'devices_warranty' => json_encode($devicesWarranty)
                                        ]);

                                        // Refresh again to get the updated data
                                        $record->refresh();
                                    }
                                }

                                $pdfPath = app(\App\Http\Controllers\GenerateRepairHandoverPdfController::class)->generateInBackground($record);

                                // Store just the relative path instead of full URL
                                if ($pdfPath) {
                                    $record->update([
                                        'handover_pdf' => $pdfPath,
                                    ]);
                                }
                            } catch (\Exception $e) {
                                // Log detailed error information
                                \Illuminate\Support\Facades\Log::error("PDF generation error: " . $e->getMessage());
                                \Illuminate\Support\Facades\Log::error("Error line: " . $e->getLine() . " in " . $e->getFile());
                                \Illuminate\Support\Facades\Log::error("Stack trace: " . $e->getTraceAsString());

                                // Still notify the user about the status change even if PDF failed
                                Notification::make()
                                    ->title('Status updated but PDF generation failed')
                                    ->warning()
                                    ->body('Please check the logs for details.')
                                    ->send();
                                return;
                            }

                            Notification::make()
                                ->title('Repair handover status updated')
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

        // First, try to find an exact match in the DeviceModel table
        $deviceModelRecord = \App\Models\DeviceModel::where('name', $model)->first();

        if ($deviceModelRecord) {
            // Extract the number of years from the warranty_category
            if ($deviceModelRecord->warranty_category === '2 years') {
                return 2;
            } else {
                return 1; // Default to 1 year for '1 year' or any other value
            }
        }

        // If no exact match, try a partial match
        $deviceModelRecord = \App\Models\DeviceModel::where('name', 'like', "%{$model}%")->first();

        if ($deviceModelRecord) {
            if ($deviceModelRecord->warranty_category === '2 years') {
                return 2;
            } else {
                return 1;
            }
        }

        // If still not found, fall back to the hardcoded logic for backward compatibility
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
        return view('livewire.admin-repair-accepted');
    }
}
