<?php
// filepath: /var/www/html/timeteccrm/app/Livewire/AdminHardwareV2Dashboard/HardwareV2NewTable.php

namespace App\Livewire\AdminHardwareV2Dashboard;

use App\Classes\Encryptor;
use App\Filament\Filters\SortFilter;
use App\Http\Controllers\GenerateHardwareHandoverPdfController;
use App\Models\HardwareHandoverV2;
use App\Models\Lead;
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
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;

class HardwareV2PendingCourierTable extends Component implements HasForms, HasTable
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
            ->whereIn('status', ['Pending: Courier'])
            // ->where('created_at', '<', Carbon::today()) // Only those created before today
            ->orderBy('created_at', 'asc') // Oldest first since they're the most overdue
            ->with(['lead', 'lead.companyDetail', 'creator']);
    }

    public function getHardwareHandoverCount()
    {
        $query = HardwareHandoverV2::query()
            ->whereIn('status', ['Pending: Courier'])
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
            ->defaultPaginationPageOption(5)
            ->paginated([5])
            ->filters([
                SelectFilter::make('invoice_type')
                    ->label('Filter by Invoice Type')
                    ->options([
                        'single' => 'Single Invoice',
                        'combined' => 'Combined Invoice',
                    ])
                    ->placeholder('All Invoice Types')
                    ->multiple(),

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
                            ->modalWidth('4xl')
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
                        return User::find($salespersonId)?->name ?? $lead->lead_owner;
                    }),

                TextColumn::make('lead.companyDetail.company_name')
                    ->label('Company Name')
                    ->searchable()
                    ->formatStateUsing(function ($state, $record) {
                        $fullName = $state ?? 'N/A';
                        $shortened = strtoupper(Str::limit($fullName, 30, '...'));
                        $encryptedId = Encryptor::encrypt($record->lead->id);

                        // ✅ Check for subsidiary company names from proforma invoices
                        $subsidiaryNames = [];

                        if (!empty($record->proforma_invoice_product)) {
                            $piProducts = is_array($record->proforma_invoice_product)
                                ? $record->proforma_invoice_product
                                : json_decode($record->proforma_invoice_product, true);

                            if (is_array($piProducts)) {
                                foreach ($piProducts as $piId) {
                                    $quotation = \App\Models\Quotation::find($piId);
                                    if ($quotation && $quotation->subsidiary_id) {
                                        $subsidiary = $quotation->subsidiary;
                                        if ($subsidiary && $subsidiary->company_name) {
                                            $subsidiaryNames[] = strtoupper(Str::limit($subsidiary->company_name, 25, '...'));
                                        }
                                    }
                                }
                            }
                        }

                        // Build the main company link
                        $html = '<div>';

                        // ✅ Add subsidiary names at the top with different styling
                        if (!empty($subsidiaryNames)) {
                            $uniqueSubsidiaryNames = array_unique($subsidiaryNames);
                            foreach ($uniqueSubsidiaryNames as $subsidiaryName) {
                                $html .= '<div style="font-size: 10px; color: #e67e22; font-weight: bold; margin-bottom: 3px; background: #fef9e7; padding: 2px 6px; border-radius: 4px; display: inline-block; margin-right: 4px;">
                                    ' . e($subsidiaryName) . '
                                </div><br>';
                            }
                        }

                        // Main company name
                        $html .= '<a href="' . url('admin/leads/' . $encryptedId) . '"
                                    target="_blank"
                                    title="' . e($fullName) . '"
                                    style="color:#338cf0; text-decoration: none;">
                                    ' . $shortened . '
                                </a>';

                        $html .= '</div>';

                        return $html;
                    })
                    ->html(),

                TextColumn::make('installation_type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => match($state) {
                        'external_installation' => 'External Installation',
                        'internal_installation' => 'Internal Installation',
                        'self_pick_up' => 'Pick-Up',
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

                TextColumn::make('updated_at')
                    ->label('Last Modified')
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
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HardwareHandoverV2 $record): View {
                            return view('components.hardware-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('courier_completion')
                        ->label('Complete Courier')
                        ->icon('heroicon-o-truck')
                        ->color('warning')
                        ->modalWidth('3xl')
                        ->closeModalByClickingAway(false)
                        ->modalHeading(function (HardwareHandoverV2 $record) {
                            // Get company name from the lead relationship
                            $companyName = 'Unknown Company';

                            if ($record->lead && $record->lead->companyDetail && $record->lead->companyDetail->company_name) {
                                $companyName = $record->lead->companyDetail->company_name;
                            }

                            return 'Complete Courier - ' . $companyName;
                        })
                        ->form(function (HardwareHandoverV2 $record) {
                            // Get courier addresses from category data
                            $courierAddresses = $this->getCourierAddresses($record);

                            return [
                                Repeater::make('courier_details')
                                    ->label(false)
                                    ->schema([
                                        Grid::make(2)
                                            ->schema([
                                                Textarea::make('address_info')
                                                    ->label('Courier Address')
                                                    ->disabled()
                                                    ->rows(5)
                                                    ->columnSpanFull(),

                                                DatePicker::make('courier_date')
                                                    ->label('Courier Date')
                                                    ->required()
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ->minDate(now()->subDays(7)->startOfDay()),

                                                TextInput::make('courier_tracking')
                                                    ->label('GDEX Tracking Number')
                                                    ->required()
                                                    ->placeholder('Enter tracking number')
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
                                                    ->maxLength(255)
                                                    ->rules([
                                                        function () {
                                                            return function (string $attribute, $value, \Closure $fail) {
                                                                if (!empty($value)) {
                                                                    $upperValue = strtoupper($value);

                                                                    // Check for duplicates in database - both courier and external courier tracking
                                                                    $existsInDb = HardwareHandoverV2::whereNotNull('category2')
                                                                        ->get()
                                                                        ->contains(function ($record) use ($upperValue) {
                                                                            $category2 = json_decode($record->category2, true);

                                                                            if (is_array($category2)) {
                                                                                // Check regular courier_addresses for courier_tracking
                                                                                if (isset($category2['courier_addresses'])) {
                                                                                    foreach ($category2['courier_addresses'] as $address) {
                                                                                        if (isset($address['courier_tracking']) &&
                                                                                            strtoupper($address['courier_tracking']) === $upperValue) {
                                                                                            return true;
                                                                                        }
                                                                                    }
                                                                                }

                                                                                // Check external_courier_addresses for external_courier_tracking
                                                                                if (isset($category2['external_courier_addresses'])) {
                                                                                    foreach ($category2['external_courier_addresses'] as $address) {
                                                                                        if (isset($address['external_courier_tracking']) &&
                                                                                            strtoupper($address['external_courier_tracking']) === $upperValue) {
                                                                                            return true;
                                                                                        }
                                                                                    }
                                                                                }
                                                                            }
                                                                            return false;
                                                                        });

                                                                    if ($existsInDb) {
                                                                        $fail('This courier tracking number is already in use (either as regular courier or external courier). Please enter a different tracking number.');
                                                                    }
                                                                }
                                                            };
                                                        }
                                                    ]),
                                            ]),
                                    ])
                                    ->defaultItems(count($courierAddresses))
                                    ->minItems(count($courierAddresses))
                                    ->maxItems(count($courierAddresses))
                                    ->addable(false)
                                    ->deletable(false)
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->itemLabel(function (array $state): ?string {
                                        // Get the current item index from the state or use a counter
                                        static $counter = 0;

                                        // Reset counter if we're starting fresh
                                        if (empty($state)) {
                                            $counter = 0;
                                        }

                                        $counter++;
                                        return 'Address ' . $counter;
                                    })
                                    ->default(function () use ($courierAddresses) {
                                        return collect($courierAddresses)->map(function ($address, $index) {
                                            return [
                                                'address_info' => "Address " . ($index + 1) . ":\n" . $address['address']
                                            ];
                                        })->toArray();
                                    })
                                    ->columnSpanFull(),
                            ];
                        })
                        ->action(function (HardwareHandoverV2 $record, array $data): void {
                            try {
                                // Check if courier_details exists, if not skip validation
                                if (!isset($data['courier_details']) || empty($data['courier_details'])) {
                                    Notification::make()
                                        ->title('No Data')
                                        ->body('No courier details provided.')
                                        ->warning()
                                        ->send();
                                    return;
                                }

                                // Check for duplicates within current form data
                                $trackingNumbers = [];
                                foreach ($data['courier_details'] as $detail) {
                                    if (isset($detail['courier_tracking']) && !empty($detail['courier_tracking'])) {
                                        $upperValue = strtoupper($detail['courier_tracking']);
                                        $trackingNumbers[] = $upperValue;
                                    }
                                }

                                // Check for duplicates only if we have tracking numbers
                                if (!empty($trackingNumbers)) {
                                    $duplicates = array_count_values($trackingNumbers);
                                    $duplicateFound = false;
                                    foreach ($duplicates as $trackingNumber => $count) {
                                        if ($count > 1) {
                                            $duplicateFound = true;
                                            break;
                                        }
                                    }

                                    if ($duplicateFound) {
                                        Notification::make()
                                            ->title('Duplicate Tracking Numbers')
                                            ->body('Each address must have a unique tracking number. Please check your entries.')
                                            ->danger()
                                            ->send();
                                        return;
                                    }
                                }

                                // Get existing category2 data
                                $existingCategory2 = $record->category2 ? json_decode($record->category2, true) : [];

                                // Ensure it's an array
                                if (!is_array($existingCategory2)) {
                                    $existingCategory2 = [];
                                }

                                // Merge courier data into courier_addresses (not external_courier_addresses)
                                if (isset($existingCategory2['courier_addresses']) && is_array($existingCategory2['courier_addresses'])) {
                                    foreach ($data['courier_details'] as $index => $courierData) {
                                        if (isset($existingCategory2['courier_addresses'][$index])) {
                                            // Add courier fields to the existing address object
                                            if (isset($courierData['courier_date'])) {
                                                $existingCategory2['courier_addresses'][$index]['courier_date'] = $courierData['courier_date'];
                                            }
                                            if (isset($courierData['courier_tracking'])) {
                                                $existingCategory2['courier_addresses'][$index]['courier_tracking'] = $courierData['courier_tracking'];
                                            }
                                        }
                                    }
                                }

                                // Add completion metadata (optional - for tracking purposes)
                                $existingCategory2['courier_completed'] = true;
                                $existingCategory2['courier_completed_at'] = now();
                                $existingCategory2['courier_completed_by'] = auth()->id();

                                // Update the record with merged category2 data and new status
                                $record->update([
                                    'category2' => json_encode($existingCategory2),
                                    'status' => 'Completed: Courier', // Changed from 'Completed: External Installation'
                                    'completed_at' => now(),
                                ]);

                                Notification::make()
                                    ->title('Courier Completed')
                                    ->body('All courier details have been saved successfully.')
                                    ->success()
                                    ->send();

                            } catch (\Exception $e) {
                                Log::error("Error saving courier data for handover {$record->id}: " . $e->getMessage());

                                Notification::make()
                                    ->title('Error')
                                    ->body('Failed to save courier details. Please try again.')
                                    ->danger()
                                    ->send();
                            }
                        })
                        ->visible(fn (HardwareHandoverV2 $record): bool =>
                            $record->status === 'Pending: Courier' && auth()->user()->role_id !== 2
                        ),
                ])->button()
            ]);
    }

    private function getCourierAddresses(HardwareHandoverV2 $record): array
    {
        $courierAddresses = [];

        // Check category2 for courier addresses
        if ($record->category2) {
            try {
                $category2Data = json_decode($record->category2, true);

                if (is_array($category2Data)) {
                    // Check for courier_addresses array structure
                    if (isset($category2Data['courier_addresses']) && is_array($category2Data['courier_addresses'])) {
                        Log::info("Found courier_addresses array with " . count($category2Data['courier_addresses']) . " items");

                        foreach ($category2Data['courier_addresses'] as $index => $item) {
                            if (isset($item['address']) && !empty($item['address'])) {
                                // Clean up the address (remove escape slashes and format newlines)
                                $cleanAddress = str_replace(['\\/', '\\n'], ['/', "\n"], $item['address']);

                                $courierAddresses[] = [
                                    'address' => $cleanAddress,
                                ];

                                Log::info("Added courier address {$index}: " . $cleanAddress);
                            }
                        }
                    }
                    // Fallback: Check for other possible structures
                    else {
                        Log::info("Available keys in category2Data: " . implode(', ', array_keys($category2Data)));

                        // Check if addresses are stored differently
                        foreach ($category2Data as $key => $item) {
                            if (is_array($item) && isset($item['courier_address']) && !empty($item['courier_address'])) {
                                $courierAddresses[] = [
                                    'address' => $item['courier_address'],
                                ];
                                Log::info("Added fallback courier address from key {$key}: " . $item['courier_address']);
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Error parsing category 2 data for courier handover {$record->id}: " . $e->getMessage());
            }
        }

        // If no courier addresses found, create a default entry
        if (empty($courierAddresses)) {
            Log::warning("No courier addresses found for handover {$record->id}, using default");
            $courierAddresses[] = [
                'address' => 'Courier Address (Not specified)',
            ];
        }

        Log::info("Final extracted courier addresses for handover {$record->id}: ", $courierAddresses);
        return $courierAddresses;
    }

    public function render()
    {
        return view('livewire.admin-hardware-v2-dashboard.hardware-v2-pending-courier-table');
    }
}
