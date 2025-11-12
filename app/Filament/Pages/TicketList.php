<?php
namespace App\Filament\Pages;

use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\TicketPriority;
use App\Models\TicketModule;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class TicketList extends Page implements HasTable, HasActions, HasForms
{
    use InteractsWithTable;
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationLabel = 'Tickets';
    protected static ?string $title = 'Ticket List';
    protected static ?int $navigationSort = 3;
    protected static string $view = 'filament.pages.ticket-list';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ticket::whereIn('product_id', [1, 2]) // ✅ Removed ->on() since model has connection
            )
            ->columns([
                Tables\Columns\TextColumn::make('ticket_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->default('N/A'),

                Tables\Columns\TextColumn::make('module.name')
                    ->label('Module')
                    ->sortable()
                    ->badge()
                    ->default('N/A'),

                Tables\Columns\BadgeColumn::make('priority.name')
                    ->label('Priority')
                    ->colors([
                        'danger' => fn ($state) => str_contains(strtolower($state ?? ''), 'bug') || str_contains(strtolower($state ?? ''), 'software'),
                        'warning' => fn ($state) => str_contains(strtolower($state ?? ''), 'backend') || str_contains(strtolower($state ?? ''), 'assistance'),
                        'primary' => fn ($state) => str_contains(strtolower($state ?? ''), 'critical enhancement'),
                        'info' => fn ($state) => str_contains(strtolower($state ?? ''), 'paid') || str_contains(strtolower($state ?? ''), 'customization'),
                        'success' => fn ($state) => str_contains(strtolower($state ?? ''), 'non-critical'),
                    ])
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'gray' => 'New',
                        'warning' => 'In Progress',
                        'success' => 'Resolved',
                        'danger' => 'Closed',
                    ]),

                Tables\Columns\TextColumn::make('device_type')
                    ->label('Device')
                    ->badge()
                    ->color(fn ($state) => $state === 'Mobile' ? 'info' : 'gray'),

                Tables\Columns\TextColumn::make('zoho_id')
                    ->label('Zoho Ticket'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime('d M Y, H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('product_id') // ✅ Changed from 'product'
                    ->label('Product')
                    ->options([
                        1 => 'Version 1',
                        2 => 'Version 2',
                    ]),

                Tables\Filters\SelectFilter::make('module_id')
                    ->label('Module')
                    ->options(
                        TicketModule::where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'New' => 'New',
                        'In Progress' => 'In Progress',
                        'Resolved' => 'Resolved',
                        'Closed' => 'Closed',
                    ]),

                Tables\Filters\SelectFilter::make('priority_id')
                    ->label('Priority')
                    ->options(
                        TicketPriority::where('is_active', true)
                            ->pluck('name', 'id')
                            ->toArray()
                    ),
            ])
            ->actions([

            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createTicket')
                ->label('Create Ticket')
                ->icon('heroicon-o-plus')
                ->slideOver()
                ->modalWidth('3xl')
                ->form([
                    Grid::make(2)
                        ->schema([
                            Select::make('product_id') // ✅ Changed from 'product'
                                ->label('Product')
                                ->required()
                                ->options([
                                    1 => 'TimeTec HR - Version 1',
                                    2 => 'TimeTec HR - Version 2',
                                ]),

                            Select::make('module_id')
                                ->label('Module')
                                ->options(
                                    TicketModule::where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                        ->toArray()
                                )
                                ->required(),
                        ]),

                    Grid::make(2)
                        ->schema([
                            Select::make('device_type')
                                ->label('Device Type')
                                ->options([
                                    'Mobile' => 'Mobile',
                                    'Browser' => 'Browser',
                                ])
                                ->live()
                                ->required(),

                            Select::make('mobile_type')
                                ->label('Mobile Type')
                                ->options([
                                    'iOS' => 'iOS',
                                    'Android' => 'Android',
                                    'Huawei' => 'Huawei',
                                ])
                                ->visible(fn (Get $get): bool => $get('device_type') === 'Mobile')
                                ->required(fn (Get $get): bool => $get('device_type') === 'Mobile'),

                            Select::make('browser_type')
                                ->label('Browser Type')
                                ->options([
                                    'Chrome' => 'Chrome',
                                    'Firefox' => 'Firefox',
                                    'Safari' => 'Safari',
                                    'Edge' => 'Edge',
                                    'Opera' => 'Opera',
                                ])
                                ->visible(fn (Get $get): bool => $get('device_type') === 'Browser')
                                ->required(fn (Get $get): bool => $get('device_type') === 'Browser'),
                        ]),

                    Grid::make(2)
                        ->schema([
                            FileUpload::make('version_screenshot')
                                ->label('Version Screenshot')
                                ->image()
                                ->maxSize(5120)
                                ->directory('version_screenshots')
                                ->visibility('public')
                                ->visible(fn (Get $get): bool => $get('device_type') === 'Mobile')
                                ->required(fn (Get $get): bool => $get('device_type') === 'Mobile'),

                            TextInput::make('device_id')
                                ->label('Device ID')
                                ->placeholder('Enter device ID')
                                ->visible(fn (Get $get): bool => $get('device_type') === 'Mobile')
                                ->required(fn (Get $get): bool => $get('device_type') === 'Mobile'),
                        ]),

                    Grid::make(4)
                        ->schema([
                            TextInput::make('os_version')
                                ->label('OS Version')
                                ->placeholder('e.g., Android 14')
                                ->visible(fn (Get $get): bool => $get('device_type') === 'Mobile')
                                ->required(fn (Get $get): bool => $get('device_type') === 'Mobile')
                                ->columnSpan(1),

                            TextInput::make('app_version')
                                ->label('App Version')
                                ->placeholder('e.g., 1.2.3')
                                ->visible(fn (Get $get): bool => $get('device_type') === 'Mobile')
                                ->required(fn (Get $get): bool => $get('device_type') === 'Mobile')
                                ->columnSpan(1),
                        ])
                        ->visible(fn (Get $get): bool => $get('device_type') === 'Mobile'),

                    Grid::make(2)
                        ->schema([
                            TextInput::make('windows_version') // ✅ Changed from windows_os_version
                                ->label('Windows/OS Version')
                                ->placeholder('e.g., Windows 11, macOS 13.1 (optional)')
                                ->visible(fn (Get $get): bool => $get('device_type') === 'Browser')
                                ->columnSpan(1),
                        ])
                        ->visible(fn (Get $get): bool => $get('device_type') === 'Browser'),

                    Select::make('priority_id')
                        ->label('Priority')
                        ->required()
                        ->options(
                            TicketPriority::where('is_active', true)
                                ->pluck('name', 'id')
                                ->toArray()
                        )
                        ->columnSpanFull(),

                    Select::make('company_name')
                        ->label('Company Name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->options(function () {
                            return \Illuminate\Support\Facades\DB::connection('frontenddb')
                                ->table('crm_expiring_license')
                                ->select('f_company_name', 'f_created_time')
                                ->groupBy('f_company_name', 'f_created_time')
                                ->orderBy('f_created_time', 'desc')
                                ->get()
                                ->mapWithKeys(function ($company) {
                                    return [$company->f_company_name => strtoupper($company->f_company_name)];
                                })
                                ->toArray();
                        })
                        ->getSearchResultsUsing(function (string $search) {
                            return \Illuminate\Support\Facades\DB::connection('frontenddb')
                                ->table('crm_expiring_license')
                                ->select('f_company_name', 'f_created_time')
                                ->where('f_company_name', 'like', "%{$search}%")
                                ->groupBy('f_company_name', 'f_created_time')
                                ->orderBy('f_created_time', 'desc')
                                ->limit(50)
                                ->get()
                                ->mapWithKeys(function ($company) {
                                    return [$company->f_company_name => strtoupper($company->f_company_name)];
                                })
                                ->toArray();
                        })
                        ->getOptionLabelUsing(function ($value) {
                            return strtoupper($value);
                        })
                        ->columnSpanFull(),

                    TextInput::make('zoho_id') // ✅ Changed from zoho_ticket_number
                        ->label('Zoho Ticket Number')
                        ->columnSpanFull(),

                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    RichEditor::make('description')
                        ->label('Description')
                        ->required() // ✅ Added required since DB field is NOT NULL
                        ->columnSpanFull(),
                ])
                ->action(function (array $data): void {
                    try {
                        $authUser = auth()->user();

                        $ticketSystemUser = null;
                        if ($authUser) {
                            $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                                ->table('users')
                                ->where('name', $authUser->name)
                                ->first();
                        }

                        $requestorId = $ticketSystemUser?->id ?? 22;

                        $data['status'] = 'New';
                        $data['requestor_id'] = $requestorId;
                        $data['created_date'] = now()->toDateString();
                        $data['isPassed'] = 0;

                        $productCode = $data['product_id'] == 1 ? 'HR1' : 'HR2';

                        $lastTicket = Ticket::where('ticket_id', 'like', "TC-{$productCode}-%")
                            ->orderBy('id', 'desc')
                            ->first();

                        if ($lastTicket && $lastTicket->ticket_id) {
                            preg_match('/TC-' . $productCode . '-(\d+)/', $lastTicket->ticket_id, $matches);
                            $lastNumber = isset($matches[1]) ? (int)$matches[1] : 0;
                            $nextNumber = $lastNumber + 1;
                        } else {
                            $nextNumber = 1;
                        }

                        // Format: TC-HR1-0009
                        $data['ticket_id'] = sprintf('TC-%s-%04d', $productCode, $nextNumber);

                        $ticket = Ticket::create($data);

                        TicketLog::create([
                            'ticket_id' => $ticket->id,
                            'old_status' => null,
                            'new_status' => 'New',
                            'updated_by' => $requestorId,
                            'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
                            'user_role' => $ticketSystemUser?->role ?? 'test role',
                            'change_type' => 'ticket_creation',
                            'source' => 'manual',
                        ]);

                        Notification::make()
                            ->title('Ticket Created')
                            ->success()
                            ->body("Ticket {$data['ticket_id']} (ID: #{$ticket->id}) has been created successfully.")
                            ->send();

                        // Refresh table
                        $this->dispatch('$refresh');
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Error')
                            ->danger()
                            ->body('Failed to create ticket: ' . $e->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
