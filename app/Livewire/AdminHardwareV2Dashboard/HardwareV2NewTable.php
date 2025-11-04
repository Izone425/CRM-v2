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
use Livewire\Attributes\On;

class HardwareV2NewTable extends Component implements HasForms, HasTable
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
        $this->selectedUser = $this->selectedUser ?? session('selectedUser') ?? auth()->id();

        $query = HardwareHandoverV2::query();

        if ($this->selectedUser === 'all-salespersons') {
            $query->whereIn('status', ['New', 'Approved', 'Pending Migration', 'Pending Stock']);

            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                $leadQuery->whereIn('salesperson', $salespersonIds);
            });
        } elseif (is_numeric($this->selectedUser)) {
            $userExists = User::where('id', $this->selectedUser)->where('role_id', 2)->exists();
            $query->whereIn('status', ['New', 'Approved', 'Pending Migration', 'Pending Stock']);

            if ($userExists) {
                $selectedUser = $this->selectedUser;
                $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                    $leadQuery->where('salesperson', $selectedUser);
                });
            } else {
                $query->whereHas('lead', function ($leadQuery) {
                    $leadQuery->where('salesperson', auth()->id());
                });
            }
        } else {
            // For Admin Hardware V2 Dashboard, show all relevant statuses
            $query->whereIn('status', ['New']);
        }

        $query->orderByRaw("CASE
            WHEN status = 'New' THEN 1
            WHEN status = 'Approved' THEN 2
            WHEN status = 'Pending Migration' THEN 3
            WHEN status = 'Pending Stock' THEN 4
            ELSE 5
        END")
        ->orderBy('created_at', 'desc');

        return $query;
    }

    public function getHardwareHandoverCount()
    {
        $query = HardwareHandoverV2::query();

        if ($this->selectedUser === 'all-salespersons') {
            $query->whereIn('status', ['New', 'Approved', 'Pending Migration', 'Pending Stock']);
            $salespersonIds = User::where('role_id', 2)->pluck('id');
            $query->whereHas('lead', function ($leadQuery) use ($salespersonIds) {
                $leadQuery->whereIn('salesperson', $salespersonIds);
            });
        } elseif (is_numeric($this->selectedUser)) {
            $query->whereIn('status', ['New', 'Approved', 'Pending Migration', 'Pending Stock']);
            $selectedUser = $this->selectedUser;
            $query->whereHas('lead', function ($leadQuery) use ($selectedUser) {
                $leadQuery->where('salesperson', $selectedUser);
            });
        } else {
            $query->whereIn('status', ['New']);
        }

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
                        ->modalWidth('4xl')
                        ->modalSubmitAction(false)
                        ->modalCancelAction(false)
                        ->modalContent(function (HardwareHandoverV2 $record): View {
                            return view('components.hardware-handover')
                                ->with('extraAttributes', ['record' => $record]);
                        }),

                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->modalHeading(function (HardwareHandoverV2 $record) {
                            // Get company name from the lead relationship
                            $companyName = 'Unknown Company';

                            if ($record->lead && $record->lead->companyDetail && $record->lead->companyDetail->company_name) {
                                $companyName = $record->lead->companyDetail->company_name;
                            }

                            return 'Reject Handover - ' . $companyName;
                        })
                        ->visible(fn (HardwareHandoverV2 $record): bool =>
                            $record->status === 'New' && auth()->user()->role_id !== 2
                        )
                        ->form([
                            Textarea::make('reject_reason')
                                ->label('Reason')
                                ->required()
                                ->maxLength(500)
                                ->helperText('This reason will be visible to the salesperson.')
                        ])
                        ->action(function (HardwareHandoverV2 $record, array $data): void {
                            $record->update([
                                'status' => 'Rejected',
                                'reject_reason' => $data['reject_reason'],
                                'rejected_at' => now(),
                                'rejected_by' => auth()->id(),
                            ]);

                            Notification::make()
                                ->title('Hardware Handover rejected')
                                ->body('The handover has been rejected and moved to rejected status.')
                                ->danger()
                                ->send();
                        })
                        ->requiresConfirmation(false),

                    Action::make('pending_stock')
                        ->label('Create Sales Order')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->modalWidth('2xl')
                        ->modalHeading(function (HardwareHandoverV2 $record) {
                            // Get company name from the lead relationship
                            $companyName = 'Unknown Company';

                            if ($record->lead && $record->lead->companyDetail && $record->lead->companyDetail->company_name) {
                                $companyName = $record->lead->companyDetail->company_name;
                            }

                            return 'Create Sales Order - ' . $companyName;
                        })
                        ->form([
                            Grid::make(3)
                                ->schema([
                                    TextInput::make('tc10_quantity')
                                        ->label('TC10')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->live()
                                        ->rules([
                                            function (Get $get) {
                                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $totalDevices =
                                                        ((int) ($get('tc10_quantity') ?? 0)) +
                                                        ((int) ($get('tc20_quantity') ?? 0)) +
                                                        ((int) ($get('face_id5_quantity') ?? 0)) +
                                                        ((int) ($get('face_id6_quantity') ?? 0)) +
                                                        ((int) ($get('time_beacon_quantity') ?? 0)) +
                                                        ((int) ($get('nfc_tag_quantity') ?? 0));

                                                    if ($totalDevices === 0) {
                                                        $fail('At least one device quantity must be greater than 0.');
                                                    }
                                                };
                                            }
                                        ])
                                        ->afterStateUpdated(function (Get $get, Set $set, $component) {
                                            // Trigger validation on all quantity fields when any changes
                                            $component->getContainer()->getComponent('tc20_quantity')?->validate();
                                            $component->getContainer()->getComponent('face_id5_quantity')?->validate();
                                            $component->getContainer()->getComponent('face_id6_quantity')?->validate();
                                            $component->getContainer()->getComponent('time_beacon_quantity')?->validate();
                                            $component->getContainer()->getComponent('nfc_tag_quantity')?->validate();
                                        }),

                                    TextInput::make('face_id5_quantity')
                                        ->label('FACE ID 5')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->live()
                                        ->rules([
                                            function (Get $get) {
                                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $totalDevices =
                                                        ((int) ($get('tc10_quantity') ?? 0)) +
                                                        ((int) ($get('tc20_quantity') ?? 0)) +
                                                        ((int) ($get('face_id5_quantity') ?? 0)) +
                                                        ((int) ($get('face_id6_quantity') ?? 0)) +
                                                        ((int) ($get('time_beacon_quantity') ?? 0)) +
                                                        ((int) ($get('nfc_tag_quantity') ?? 0));

                                                    if ($totalDevices === 0) {
                                                        $fail('At least one device quantity must be greater than 0.');
                                                    }
                                                };
                                            }
                                        ])
                                        ->afterStateUpdated(function (Get $get, Set $set, $component) {
                                            $component->getContainer()->getComponent('tc10_quantity')?->validate();
                                            $component->getContainer()->getComponent('tc20_quantity')?->validate();
                                            $component->getContainer()->getComponent('face_id6_quantity')?->validate();
                                            $component->getContainer()->getComponent('time_beacon_quantity')?->validate();
                                            $component->getContainer()->getComponent('nfc_tag_quantity')?->validate();
                                        }),

                                    TextInput::make('time_beacon_quantity')
                                        ->label('TIME BEACON')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->live()
                                        ->rules([
                                            function (Get $get) {
                                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $totalDevices =
                                                        ((int) ($get('tc10_quantity') ?? 0)) +
                                                        ((int) ($get('tc20_quantity') ?? 0)) +
                                                        ((int) ($get('face_id5_quantity') ?? 0)) +
                                                        ((int) ($get('face_id6_quantity') ?? 0)) +
                                                        ((int) ($get('time_beacon_quantity') ?? 0)) +
                                                        ((int) ($get('nfc_tag_quantity') ?? 0));

                                                    if ($totalDevices === 0) {
                                                        $fail('At least one device quantity must be greater than 0.');
                                                    }
                                                };
                                            }
                                        ])
                                        ->afterStateUpdated(function (Get $get, Set $set, $component) {
                                            $component->getContainer()->getComponent('tc10_quantity')?->validate();
                                            $component->getContainer()->getComponent('tc20_quantity')?->validate();
                                            $component->getContainer()->getComponent('face_id5_quantity')?->validate();
                                            $component->getContainer()->getComponent('face_id6_quantity')?->validate();
                                            $component->getContainer()->getComponent('nfc_tag_quantity')?->validate();
                                        }),

                                    TextInput::make('tc20_quantity')
                                        ->label('TC20')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->live()
                                        ->rules([
                                            function (Get $get) {
                                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $totalDevices =
                                                        ((int) ($get('tc10_quantity') ?? 0)) +
                                                        ((int) ($get('tc20_quantity') ?? 0)) +
                                                        ((int) ($get('face_id5_quantity') ?? 0)) +
                                                        ((int) ($get('face_id6_quantity') ?? 0)) +
                                                        ((int) ($get('time_beacon_quantity') ?? 0)) +
                                                        ((int) ($get('nfc_tag_quantity') ?? 0));

                                                    if ($totalDevices === 0) {
                                                        $fail('At least one device quantity must be greater than 0.');
                                                    }
                                                };
                                            }
                                        ])
                                        ->afterStateUpdated(function (Get $get, Set $set, $component) {
                                            $component->getContainer()->getComponent('tc10_quantity')?->validate();
                                            $component->getContainer()->getComponent('face_id5_quantity')?->validate();
                                            $component->getContainer()->getComponent('face_id6_quantity')?->validate();
                                            $component->getContainer()->getComponent('time_beacon_quantity')?->validate();
                                            $component->getContainer()->getComponent('nfc_tag_quantity')?->validate();
                                        }),

                                    TextInput::make('face_id6_quantity')
                                        ->label('FACE ID 6')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->live()
                                        ->rules([
                                            function (Get $get) {
                                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $totalDevices =
                                                        ((int) ($get('tc10_quantity') ?? 0)) +
                                                        ((int) ($get('tc20_quantity') ?? 0)) +
                                                        ((int) ($get('face_id5_quantity') ?? 0)) +
                                                        ((int) ($get('face_id6_quantity') ?? 0)) +
                                                        ((int) ($get('time_beacon_quantity') ?? 0)) +
                                                        ((int) ($get('nfc_tag_quantity') ?? 0));

                                                    if ($totalDevices === 0) {
                                                        $fail('At least one device quantity must be greater than 0.');
                                                    }
                                                };
                                            }
                                        ])
                                        ->afterStateUpdated(function (Get $get, Set $set, $component) {
                                            $component->getContainer()->getComponent('tc10_quantity')?->validate();
                                            $component->getContainer()->getComponent('tc20_quantity')?->validate();
                                            $component->getContainer()->getComponent('face_id5_quantity')?->validate();
                                            $component->getContainer()->getComponent('time_beacon_quantity')?->validate();
                                            $component->getContainer()->getComponent('nfc_tag_quantity')?->validate();
                                        }),

                                    TextInput::make('nfc_tag_quantity')
                                        ->label('NFC TAG')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->live()
                                        ->rules([
                                            function (Get $get) {
                                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $totalDevices =
                                                        ((int) ($get('tc10_quantity') ?? 0)) +
                                                        ((int) ($get('tc20_quantity') ?? 0)) +
                                                        ((int) ($get('face_id5_quantity') ?? 0)) +
                                                        ((int) ($get('face_id6_quantity') ?? 0)) +
                                                        ((int) ($get('time_beacon_quantity') ?? 0)) +
                                                        ((int) ($get('nfc_tag_quantity') ?? 0));

                                                    if ($totalDevices === 0) {
                                                        $fail('At least one device quantity must be greater than 0.');
                                                    }
                                                };
                                            }
                                        ])
                                        ->afterStateUpdated(function (Get $get, Set $set, $component) {
                                            $component->getContainer()->getComponent('tc10_quantity')?->validate();
                                            $component->getContainer()->getComponent('tc20_quantity')?->validate();
                                            $component->getContainer()->getComponent('face_id5_quantity')?->validate();
                                            $component->getContainer()->getComponent('face_id6_quantity')?->validate();
                                            $component->getContainer()->getComponent('time_beacon_quantity')?->validate();
                                        }),
                                ])
                                ->columnSpanFull(),

                            TextInput::make('sales_order_number')
                                ->label('Sales Order Number')
                                ->required()
                                ->placeholder('Enter the sales order number')
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
                                ->maxLength(100)
                                ->rules([
                                    'required',
                                    function () {
                                        return function (string $attribute, $value, \Closure $fail) {
                                            if (!$value) return;

                                            $upperValue = strtoupper($value);
                                            $exists = HardwareHandoverV2::where('sales_order_number', $upperValue)
                                                ->exists();

                                            if ($exists) {
                                                $fail('This sales order number already exists in the system.');
                                            }
                                        };
                                    }
                                ])
                                ->columnSpanFull(),
                        ])
                        ->action(function (HardwareHandoverV2 $record, array $data): void {
                            // Get implementer information
                            $implementerName = $record->implementer ?? null;

                            // Fallback to getting implementer from software handover if not set
                            if (!$implementerName) {
                                $softwareHandover = $record->lead ? \App\Models\SoftwareHandover::where('lead_id', $record->lead->id)
                                    ->latest()
                                    ->first() : null;

                                if ($softwareHandover && $softwareHandover->implementer) {
                                    $implementerName = $softwareHandover->implementer;
                                }
                            }

                            $salesOrderNumber = strtoupper($data['sales_order_number']);

                            $updateData = [
                                'sales_order_number' => $salesOrderNumber,
                                'tc10_quantity' => (int) $data['tc10_quantity'],
                                'tc20_quantity' => (int) $data['tc20_quantity'],
                                'face_id5_quantity' => (int) $data['face_id5_quantity'],
                                'face_id6_quantity' => (int) $data['face_id6_quantity'],
                                'time_beacon_quantity' => (int) $data['time_beacon_quantity'],
                                'nfc_tag_quantity' => (int) $data['nfc_tag_quantity'],
                                'implementer' => $implementerName,
                                'pending_stock_at' => now(),
                                'approved_at' => now(),
                                'approved_by' => auth()->id(),
                                'status' => 'Pending Stock',
                            ];

                            $record->update($updateData);

                            Notification::make()
                                ->title('Hardware Handover accepted with Pending Stock')
                                ->success()
                                ->body('Sales Order: ' . $salesOrderNumber . ' - Status updated to Pending Stock')
                                ->send();
                        })
                        ->visible(fn (HardwareHandoverV2 $record): bool =>
                            $record->status === 'New' && auth()->user()->role_id !== 2
                        ),
                ])->button()
            ]);
    }

    private function validateDeviceQuantities(Get $get, Set $set): void
    {
        $totalDevices =
            ((int) $get('tc10_quantity')) +
            ((int) $get('tc20_quantity')) +
            ((int) $get('face_id5_quantity')) +
            ((int) $get('face_id6_quantity')) +
            ((int) $get('time_beacon_quantity')) +
            ((int) $get('nfc_tag_quantity'));

        // Set a validation state that can be used by the form
        $set('device_total_validation', $totalDevices > 0 ? 'valid' : 'invalid');
    }

    public function render()
    {
        return view('livewire.admin-hardware-v2-dashboard.hardware-v2-new-table');
    }
}
