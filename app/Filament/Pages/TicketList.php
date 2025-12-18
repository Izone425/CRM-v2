<?php
namespace App\Filament\Pages;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketComment;
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
use Illuminate\Support\Facades\Log;

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

    public $selectedTicket = null;
    public $showTicketModal = false;
    public $newComment = '';
    public $attachments = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function getFormStatePath(): ?string
    {
        return null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Ticket::whereIn('product_id', [1, 2]) // ✅ Removed ->on() since model has connection
            )
            ->paginated([50])
            ->paginationPageOptions([50, 100])
            ->columns([
                Tables\Columns\TextColumn::make('ticket_id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->weight('bold')
                    ->color('primary'),

                Tables\Columns\TextColumn::make('requestor.name')
                    ->label('Requestor')
                    ->searchable()
                    ->sortable()
                    ->default('Unknown User')
                    ->formatStateUsing(fn ($state) => $state ?? 'Unknown User'),

                Tables\Columns\TextColumn::make('company_name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->action(fn (Ticket $record) => $this->viewTicket($record->id)),

                    Tables\Actions\Action::make('reopen')
                        ->label('Reopen')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Reopen Ticket')
                        ->modalDescription('Are you sure you want to reopen this ticket?')
                        ->action(function (Ticket $record) {
                            $this->updateTicketStatus($record, 'Reopen');
                        })
                        ->visible(fn (Ticket $record): bool => $record->status === 'Completed'),

                    Tables\Actions\Action::make('close')
                        ->label('Close')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Close Ticket')
                        ->modalDescription('Are you sure you want to close this ticket?')
                        ->action(function (Ticket $record) {
                            $this->updateTicketStatus($record, 'Closed');
                        })
                        ->visible(fn (Ticket $record): bool => $record->status === 'Completed'),
                ])
            ])
            ->recordAction('view') // ✅ Make rows clickable
            ->recordUrl(null)
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
                            Select::make('product_id')
                                ->label('Product')
                                ->required()
                                ->options([
                                    1 => 'TimeTec HR - Version 1',
                                    2 => 'TimeTec HR - Version 2',
                                ])
                                ->live()
                                ->afterStateUpdated(fn (callable $set) => $set('module_id', null)),

                            Select::make('module_id')
                                ->label('Module')
                                ->options(function (callable $get) {
                                    $productId = $get('product_id');

                                    if (!$productId) {
                                        return [];
                                    }

                                    // ✅ Use the correct database connection
                                    return \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                                        ->table('product_has_modules')
                                        ->join('modules', 'product_has_modules.module_id', '=', 'modules.id')
                                        ->where('product_has_modules.product_id', $productId)
                                        ->where('modules.is_active', true)
                                        ->orderBy('modules.name')
                                        ->pluck('modules.name', 'modules.id')
                                        ->toArray();
                                })
                                ->required()
                                ->disabled(fn (callable $get): bool => !$get('product_id'))
                                ->placeholder('Select a product first'),
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
                                ->orderBy('f_company_name', 'asc') // ✅ Sort alphabetically
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
                                ->orderBy('f_company_name', 'asc') // ✅ Sort search results alphabetically
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
                                ->where(function ($query) use ($authUser) {
                                    $query->where('name', $authUser->name)
                                        ->orWhere('name', 'LIKE', '%' . $authUser->name . '%')
                                        ->orWhere('email', $authUser->email);
                                })
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
                            'old_value' => null,
                            'new_value' => 'New',
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

    public function viewTicket($ticketId): void
    {
        try {
            $this->selectedTicket = Ticket::with([
                'comments',
                'logs',
                'priority',
                'product',
                'module',
                'requestor',
                'attachments',
                'attachments.uploader',
            ])->find($ticketId);

            if ($this->selectedTicket) {
                $this->showTicketModal = true;
            }
        } catch (\Exception $e) {
            Log::error('Error viewing ticket: ' . $e->getMessage());
            $this->showTicketModal = false;
        }
    }

    // ✅ Add closeTicketModal method
    public function closeTicketModal(): void
    {
        $this->showTicketModal = false;
        $this->selectedTicket = null;
        $this->newComment = '';
        $this->attachments = [];
    }

    // ✅ Add addComment method
    public function addComment(): void
    {
        if (empty($this->newComment) || !$this->selectedTicket) {
            return;
        }

        try {
            $authUser = auth()->user();

            $ticketSystemUser = null;
            if ($authUser) {
                $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                    ->table('users')
                    ->where('name', $authUser->name)
                    ->first();
            }

            $userId = $ticketSystemUser?->id ?? 22;

            TicketComment::create([
                'ticket_id' => $this->selectedTicket->id,
                'user_id' => $userId,
                'comment' => $this->newComment,
            ]);

            $this->newComment = '';

            $this->selectedTicket->refresh();
            $this->selectedTicket->load('comments');

            Notification::make()
                ->title('Comment Added')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Error adding comment: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to add comment')
                ->send();
        }
    }

    // ✅ Add uploadAttachments method
    public function uploadAttachments(): void
    {
        $this->validate([
            'attachments.*' => 'file|max:10240',
        ]);

        if (empty($this->attachments) || !$this->selectedTicket) {
            Notification::make()
                ->title('No files selected')
                ->warning()
                ->send();
            return;
        }

        try {
            $authUser = auth()->user();
            $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                ->table('users')
                ->where('name', $authUser->name)
                ->first();

            $userId = $ticketSystemUser?->id ?? 22;

            foreach ($this->attachments as $file) {
                $originalFilename = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();

                $storedFilename = time() . '_' . \Illuminate\Support\Str::random(10) . '_' .
                                \Illuminate\Support\Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) .
                                '.' . $extension;

                $path = $file->storeAs(
                    'ticket_attachments/' . date('Y/m/d'),
                    $storedFilename,
                    's3-ticketing'
                );

                $fileHash = hash_file('md5', $file->getRealPath());

                TicketAttachment::create([
                    'ticket_id' => $this->selectedTicket->id,
                    'original_filename' => $originalFilename,
                    'stored_filename' => $storedFilename,
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'file_hash' => $fileHash,
                    'uploaded_by' => $userId,
                ]);
            }

            $this->attachments = [];
            $this->selectedTicket->refresh();
            $this->selectedTicket->load('attachments');

            Notification::make()
                ->title('Files Uploaded')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Log::error('Error uploading attachments: ' . $e->getMessage());

            Notification::make()
                ->title('Upload Failed')
                ->danger()
                ->body('Failed to upload files: ' . $e->getMessage())
                ->send();
        }
    }

    // ✅ Add form schema for comment
    protected function getFormSchema(): array
    {
        return [
            RichEditor::make('newComment')
                ->label('')
                ->placeholder('Add a comment...')
                ->required()
                ->toolbarButtons([
                    'attachFiles',
                    'bold',
                    'italic',
                    'underline',
                    'strike',
                    'bulletList',
                    'orderedList',
                    'h2',
                    'h3',
                    'link',
                    'undo',
                    'redo',
                ])
                ->disableToolbarButtons([
                    'codeBlock',
                ])
        ];
    }

    // ✅ Add helper methods
    private function isImageFile($attachment): bool
    {
        if (str_starts_with($attachment->mime_type, 'image/')) {
            return true;
        }
        $extension = strtolower(pathinfo($attachment->original_filename, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']);
    }

    public function updateTicketStatus(Ticket $ticket, string $newStatus): void
    {
        try {
            $authUser = auth()->user();

            $ticketSystemUser = null;
            if ($authUser) {
                $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                    ->table('users')
                    ->where('name', $authUser->name)
                    ->first();
            }

            $userId = $ticketSystemUser?->id ?? 22;
            $oldStatus = $ticket->status;

            // Update ticket status
            $ticket->update(['status' => $newStatus]);

            // Create log entry
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'old_value' => $oldStatus,
                'new_value' => $newStatus,
                'updated_by' => $userId,
                'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
                'user_role' => $ticketSystemUser?->role ?? 'test role',
                'change_type' => 'status_change',
                'source' => 'manual',
            ]);

            Notification::make()
                ->title('Status Updated')
                ->success()
                ->body("Ticket {$ticket->ticket_id} status changed from {$oldStatus} to {$newStatus}")
                ->send();

            // Refresh table
            $this->dispatch('$refresh');

        } catch (\Exception $e) {
            Log::error('Error updating ticket status: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to update ticket status')
                ->send();
        }
    }
}
