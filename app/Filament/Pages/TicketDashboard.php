<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketLog;
use App\Models\TicketComment;
use App\Models\TicketModule;
use App\Models\TicketProduct;
use App\Models\TicketPriority;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class TicketDashboard extends Page implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.ticket-dashboard';
    protected static ?string $navigationLabel = 'Ticket Dashboard';
    protected static ?string $title = '';

    public $selectedProduct = 'All Products';
    public $selectedModule = 'All Modules';
    public $selectedCategory = null;
    public $selectedStatus = null;
    public $selectedEnhancementStatus = null;
    public $selectedEnhancementType = null;
    public $currentMonth;
    public $currentYear;
    public $selectedDate;

    // Track individual combined statuses
    public $selectedCombinedStatuses = [];

    // New filter properties
    public $selectedFrontEnd = null;
    public $selectedTicketStatus = null;
    public $etaStartDate = null;
    public $etaEndDate = null;
    public $etaSortDirection = null; // 'asc' or 'desc'

    public function mount()
    {
        $this->currentMonth = now()->subHours(8)->month;
        $this->currentYear = now()->subHours(8)->year;

        // Set default filter to Completed status
        $this->selectedStatus = 'Completed';
    }

    public $selectedTicket = null;
    public $showTicketModal = false;
    public $newComment = '';
    public $attachments = [];

    // Reopen modal properties
    public $showReopenModal = false;
    public $reopenComment = '';
    public $reopenAttachments = [];

    // Filter modal property
    public $showFilterModal = false;

    // ✅ Add header actions for Create Ticket button
    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Action::make('createTicket')
    //             ->label('Create Ticket')
    //             ->icon('heroicon-o-plus')
    //             ->slideOver()
    //             ->modalWidth('3xl')
    //             ->form([
    //                 // ✅ Priority field first - controls device type visibility
    //                 Select::make('priority_id')
    //                     ->label('Priority')
    //                     ->required()
    //                     ->options(
    //                         TicketPriority::where('is_active', true)
    //                             ->pluck('name', 'id')
    //                             ->toArray()
    //                     )
    //                     ->live() // ✅ Make it reactive
    //                     ->columnSpanFull(),

    //                 Grid::make(2)
    //                     ->schema([
    //                         Select::make('product_id')
    //                             ->label('Product')
    //                             ->required()
    //                             ->options([
    //                                 1 => 'TimeTec HR - Version 1',
    //                                 2 => 'TimeTec HR - Version 2',
    //                             ])
    //                             ->live()
    //                             ->afterStateUpdated(fn (callable $set) => $set('module_id', null)),

    //                         Select::make('module_id')
    //                             ->label('Module')
    //                             ->options(function (callable $get) {
    //                                 $productId = $get('product_id');

    //                                 if (!$productId) {
    //                                     return [];
    //                                 }

    //                                 return \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
    //                                     ->table('product_has_modules')
    //                                     ->join('modules', 'product_has_modules.module_id', '=', 'modules.id')
    //                                     ->where('product_has_modules.product_id', $productId)
    //                                     ->where('modules.is_active', true)
    //                                     ->orderBy('modules.name')
    //                                     ->pluck('modules.name', 'modules.id')
    //                                     ->toArray();
    //                             })
    //                             ->required()
    //                             ->disabled(fn (callable $get): bool => !$get('product_id'))
    //                             ->placeholder('Select a product first'),
    //                     ]),

    //                 // ✅ Device Type field - shows/hides based on priority
    //                 Select::make('device_type')
    //                     ->label('Device Type')
    //                     ->options([
    //                         'Mobile' => 'Mobile',
    //                         'Browser' => 'Browser',
    //                     ])
    //                     ->live()
    //                     ->required(function (Get $get): bool {
    //                         // Required when priority is Software Bugs
    //                         $priorityId = $get('priority_id');
    //                         if (!$priorityId) return false;

    //                         $priority = TicketPriority::find($priorityId);
    //                         return $priority && str_contains(strtolower($priority->name), 'software bugs');
    //                     })
    //                     ->hidden(function (Get $get): bool {
    //                         // Hide when NOT Software Bugs priority
    //                         $priorityId = $get('priority_id');
    //                         if (!$priorityId) return true; // Hide when no priority selected

    //                         $priority = TicketPriority::find($priorityId);
    //                         return !($priority && str_contains(strtolower($priority->name), 'software bugs'));
    //                     })
    //                     ->afterStateUpdated(function (callable $set, $state) {
    //                         // Clear related fields when device type changes or is cleared
    //                         if (!$state) {
    //                             $set('mobile_type', null);
    //                             $set('browser_type', null);
    //                             $set('version_screenshot', null);
    //                             // $set('device_id', null);
    //                             // $set('os_version', null);
    //                             // $set('app_version', null);
    //                             $set('windows_version', null);
    //                         }
    //                     }),

    //                 // ✅ Mobile/Browser type selection
    //                 Grid::make(2)
    //                     ->schema([
    //                         Select::make('mobile_type')
    //                             ->label('Mobile Type')
    //                             ->options([
    //                                 'iOS' => 'iOS',
    //                                 'Android' => 'Android',
    //                                 'Huawei' => 'Huawei',
    //                             ])
    //                             ->hidden(function (Get $get): bool {
    //                                 // Hide if NOT (Software Bugs AND Mobile)
    //                                 $priorityId = $get('priority_id');
    //                                 if (!$priorityId) return true;

    //                                 $priority = TicketPriority::find($priorityId);
    //                                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

    //                                 return !($isSoftwareBugs && $get('device_type') === 'Mobile');
    //                             })
    //                             ->required(function (Get $get): bool {
    //                                 $priorityId = $get('priority_id');
    //                                 if (!$priorityId) return false;

    //                                 $priority = TicketPriority::find($priorityId);
    //                                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

    //                                 return $isSoftwareBugs && $get('device_type') === 'Mobile';
    //                             }),

    //                         Select::make('browser_type')
    //                             ->label('Browser Type')
    //                             ->options([
    //                                 'Chrome' => 'Chrome',
    //                                 'Firefox' => 'Firefox',
    //                                 'Safari' => 'Safari',
    //                                 'Edge' => 'Edge',
    //                                 'Opera' => 'Opera',
    //                             ])
    //                             ->hidden(function (Get $get): bool {
    //                                 // Hide if NOT (Software Bugs AND Browser)
    //                                 $priorityId = $get('priority_id');
    //                                 if (!$priorityId) return true;

    //                                 $priority = TicketPriority::find($priorityId);
    //                                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

    //                                 return !($isSoftwareBugs && $get('device_type') === 'Browser');
    //                             })
    //                             ->required(function (Get $get): bool {
    //                                 $priorityId = $get('priority_id');
    //                                 if (!$priorityId) return false;

    //                                 $priority = TicketPriority::find($priorityId);
    //                                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

    //                                 return $isSoftwareBugs && $get('device_type') === 'Browser';
    //                             }),
    //                     ]),

    //                 // ✅ Mobile-specific fields (version screenshot & device ID)
    //                 Grid::make(2)
    //                     ->schema([
    //                         FileUpload::make('version_screenshot')
    //                             ->label('Version Screenshot')
    //                             ->image()
    //                             ->maxSize(5120)
    //                             ->directory('version_screenshots')
    //                             ->visibility('public')
    //                             ->hidden(function (Get $get): bool {
    //                                 $priorityId = $get('priority_id');
    //                                 if (!$priorityId) return true;

    //                                 $priority = TicketPriority::find($priorityId);
    //                                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

    //                                 return !($isSoftwareBugs && $get('device_type') === 'Mobile');
    //                             })
    //                             ->required(function (Get $get): bool {
    //                                 $priorityId = $get('priority_id');
    //                                 if (!$priorityId) return false;

    //                                 $priority = TicketPriority::find($priorityId);
    //                                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

    //                                 return $isSoftwareBugs && $get('device_type') === 'Mobile';
    //                             }),

    //                         // TextInput::make('device_id')
    //                         //     ->label('Device ID')
    //                         //     ->placeholder('Enter device ID')
    //                         //     ->hidden(function (Get $get): bool {
    //                         //         $priorityId = $get('priority_id');
    //                         //         if (!$priorityId) return true;

    //                         //         $priority = TicketPriority::find($priorityId);
    //                         //         $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

    //                         //         return !($isSoftwareBugs && $get('device_type') === 'Mobile');
    //                         //     })
    //                         //     ->required(function (Get $get): bool {
    //                         //         $priorityId = $get('priority_id');
    //                         //         if (!$priorityId) return false;

    //                         //         $priority = TicketPriority::find($priorityId);
    //                         //         $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

    //                         //         return $isSoftwareBugs && $get('device_type') === 'Mobile';
    //                         //     }),
    //                     ]),

    //                 // ✅ Mobile version details (OS & App version)
    //                 // Grid::make(2)
    //                 //     ->schema([
    //                 //         TextInput::make('os_version')
    //                 //             ->label('OS Version')
    //                 //             ->placeholder('e.g., Android 14')
    //                 //             ->hidden(function (Get $get): bool {
    //                 //                 $priorityId = $get('priority_id');
    //                 //                 if (!$priorityId) return true;

    //                 //                 $priority = TicketPriority::find($priorityId);
    //                 //                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

    //                 //                 return !($isSoftwareBugs && $get('device_type') === 'Mobile');
    //                 //             })
    //                 //             ->required(function (Get $get): bool {
    //                 //                 $priorityId = $get('priority_id');
    //                 //                 if (!$priorityId) return false;

    //                 //                 $priority = TicketPriority::find($priorityId);
    //                 //                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

    //                 //                 return $isSoftwareBugs && $get('device_type') === 'Mobile';
    //                 //             }),

    //                 //         TextInput::make('app_version')
    //                 //             ->label('App Version')
    //                 //             ->placeholder('e.g., 1.2.3')
    //                 //             ->hidden(function (Get $get): bool {
    //                 //                 $priorityId = $get('priority_id');
    //                 //                 if (!$priorityId) return true;

    //                 //                 $priority = TicketPriority::find($priorityId);
    //                 //                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

    //                 //                 return !($isSoftwareBugs && $get('device_type') === 'Mobile');
    //                 //             })
    //                 //             ->required(function (Get $get): bool {
    //                 //                 $priorityId = $get('priority_id');
    //                 //                 if (!$priorityId) return false;

    //                 //                 $priority = TicketPriority::find($priorityId);
    //                 //                 $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

    //                 //                 return $isSoftwareBugs && $get('device_type') === 'Mobile';
    //                 //             }),
    //                 //     ]),

    //                 TextInput::make('windows_version')
    //                     ->label('Windows/OS Version')
    //                     ->placeholder('e.g., Windows 11, macOS 13.1 (optional)')
    //                     ->hidden(function (Get $get): bool {
    //                         $priorityId = $get('priority_id');
    //                         if (!$priorityId) return true;

    //                         $priority = TicketPriority::find($priorityId);
    //                         $isSoftwareBugs = $priority && str_contains(strtolower($priority->name), 'software bugs');

    //                         return !($isSoftwareBugs && $get('device_type') === 'Browser');
    //                     }),

    //                 // ✅ Company selection
    //                 Select::make('company_name')
    //                     ->label('Company Name')
    //                     ->searchable()
    //                     ->preload()
    //                     ->required()
    //                     ->options(function () {
    //                         return \Illuminate\Support\Facades\DB::connection('frontenddb')
    //                             ->table('crm_expiring_license')
    //                             ->select('f_company_name', 'f_created_time')
    //                             ->groupBy('f_company_name', 'f_created_time')
    //                             ->orderBy('f_company_name', 'asc')
    //                             ->get()
    //                             ->mapWithKeys(function ($company) {
    //                                 return [$company->f_company_name => strtoupper($company->f_company_name)];
    //                             })
    //                             ->toArray();
    //                     })
    //                     ->getSearchResultsUsing(function (string $search) {
    //                         return \Illuminate\Support\Facades\DB::connection('frontenddb')
    //                             ->table('crm_expiring_license')
    //                             ->select('f_company_name', 'f_created_time')
    //                             ->where('f_company_name', 'like', "%{$search}%")
    //                             ->groupBy('f_company_name', 'f_created_time')
    //                             ->orderBy('f_company_name', 'asc')
    //                             ->limit(50)
    //                             ->get()
    //                             ->mapWithKeys(function ($company) {
    //                                 return [$company->f_company_name => strtoupper($company->f_company_name)];
    //                             })
    //                             ->toArray();
    //                     })
    //                     ->getOptionLabelUsing(function ($value) {
    //                         return strtoupper($value);
    //                     })
    //                     ->columnSpanFull(),

    //                 // ✅ Internal ticket checkbox
    //                 Checkbox::make('is_internal')
    //                     ->label('Internal Ticket')
    //                     ->default(false)
    //                     ->columnSpan(1),

    //                 // ✅ Zoho ticket number
    //                 TextInput::make('zoho_id')
    //                     ->label('Zoho Ticket Number')
    //                     ->columnSpanFull(),

    //                 // ✅ Title and description
    //                 TextInput::make('title')
    //                     ->label('Title')
    //                     ->required()
    //                     ->maxLength(255)
    //                     ->columnSpanFull(),

    //                 RichEditor::make('description')
    //                     ->label('Description')
    //                     ->required()
    //                     ->columnSpanFull(),
    //             ])
    //             ->action(function (array $data): void {
    //                 try {
    //                     $authUser = auth()->user();

    //                     $ticketSystemUser = null;
    //                     if ($authUser) {
    //                         $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
    //                             ->table('users')
    //                             ->where(function ($query) use ($authUser) {
    //                                 $query->where('name', $authUser->name)
    //                                     ->orWhere('name', 'LIKE', '%' . $authUser->name . '%')
    //                                     ->orWhere('email', $authUser->email);
    //                             })
    //                             ->first();
    //                     }

    //                     $requestorId = $ticketSystemUser?->id ?? 22;

    //                     $data['status'] = 'New';
    //                     $data['requestor_id'] = $requestorId;
    //                     $data['created_date'] = now()->subHours(8)->toDateString();
    //                     $data['isPassed'] = 0;
    //                     $data['is_internal'] = $data['is_internal'] ?? false;

    //                     $productCode = $data['product_id'] == 1 ? 'HR1' : 'HR2';

    //                     $lastTicket = Ticket::where('ticket_id', 'like', "TC-{$productCode}-%")
    //                         ->orderBy('id', 'desc')
    //                         ->first();

    //                     if ($lastTicket && $lastTicket->ticket_id) {
    //                         preg_match('/TC-' . $productCode . '-(\d+)/', $lastTicket->ticket_id, $matches);
    //                         $lastNumber = isset($matches[1]) ? (int)$matches[1] : 0;
    //                         $nextNumber = $lastNumber + 1;
    //                     } else {
    //                         $nextNumber = 1;
    //                     }

    //                     $data['ticket_id'] = sprintf('TC-%s-%04d', $productCode, $nextNumber);

    //                     $ticket = Ticket::create($data);

    //                     // Get priority name for the log
    //                     $priority = TicketPriority::find($data['priority_id']);
    //                     $priorityName = $priority ? $priority->name : 'Unknown';

    //                     // Build detailed new_value string
    //                     $newValueDetails = "Ticket {$data['ticket_id']}\n";
    //                     $newValueDetails .= "Title: {$data['title']}\n";
    //                     $newValueDetails .= "Priority: {$priorityName}\n";
    //                     $newValueDetails .= "Category: {$priorityName}\n";
    //                     $newValueDetails .= "Requester: " . ($ticketSystemUser?->name ?? 'HRcrm User');

    //                     TicketLog::create([
    //                         'ticket_id' => $ticket->id,
    //                         'old_value' => 'No existing ticket',
    //                         'new_value' => $newValueDetails,
    //                         'action' => "Created new ticket {$data['ticket_id']}",
    //                         'field_name' => null,
    //                         'change_reason' => null,
    //                         'old_eta' => null,
    //                         'new_eta' => null,
    //                         'updated_by' => $requestorId,
    //                         'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
    //                         'user_role' => $ticketSystemUser?->role ?? 'Internal Staff',
    //                         'change_type' => 'ticket_creation',
    //                         'source' => 'manual',
    //                         'created_at' => now()->subHours(8),
    //                         'updated_at' => now()->subHours(8),
    //                     ]);

    //                     Notification::make()
    //                         ->title('Ticket Created')
    //                         ->success()
    //                         ->body("Ticket {$data['ticket_id']} (ID: #{$ticket->id}) has been created successfully.")
    //                         ->send();

    //                     $this->dispatch('$refresh');
    //                 } catch (\Exception $e) {
    //                     Notification::make()
    //                         ->title('Error')
    //                         ->danger()
    //                         ->body('Failed to create ticket: ' . $e->getMessage())
    //                         ->send();
    //                 }
    //             }),
    //     ];
    // }

    public function uploadAttachments(): void
    {
        $this->validate([
            'attachments.*' => 'file|max:10240', // Max 10MB per file
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

                // ✅ Create unique stored filename
                $storedFilename = time() . '_' . \Illuminate\Support\Str::random(10) . '_' .
                                \Illuminate\Support\Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) .
                                '.' . $extension;

                // ✅ Store to s3-ticketing with organized path
                $path = $file->storeAs(
                    'ticket_attachments/' . date('Y/m/d'),
                    $storedFilename,
                    's3-ticketing'
                );

                // ✅ Calculate file hash
                $fileHash = hash_file('md5', $file->getRealPath());

                // ✅ Create attachment record
                TicketAttachment::create([
                    'ticket_id' => $this->selectedTicket->id,
                    'original_filename' => $originalFilename,
                    'stored_filename' => $storedFilename,
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'file_hash' => $fileHash,
                    'uploaded_by' => $userId,
                    'created_at' => now()->subHours(8),
                    'updated_at' => now()->subHours(8),
                ]);
            }

            $this->attachments = [];
            $this->selectedTicket->refresh();
            $this->selectedTicket->load('attachments');

            Notification::make()
                ->title('Files Uploaded')
                ->success()
                ->body(count($this->attachments) . ' file(s) uploaded successfully')
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

    private function isImageFile($attachment): bool
    {
        if (str_starts_with($attachment->mime_type, 'image/')) {
            return true;
        }
        $extension = strtolower(pathinfo($attachment->original_filename, PATHINFO_EXTENSION));
        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp']);
    }

    private function handleVersionScreenshot($file): ?string
    {
        if ($file) {
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('version_screenshot', $filename, 's3-ticketing');
            return $path;
        }
        return null;
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

    public function closeTicketModal(): void
    {
        $this->showTicketModal = false;
        $this->selectedTicket = null;
        $this->newComment = '';
        $this->attachments = []; // ✅ Reset attachments
        $this->closeReopenModal();
    }

    public function closeReopenModal(): void
    {
        $this->showReopenModal = false;
        $this->reopenComment = '';
        $this->reopenAttachments = [];
        $this->form->fill();
    }

    public function openReopenModal($ticketId): void
    {
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
            $this->showReopenModal = true;
        }
    }

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
                    ->where('email', $authUser->email)
                    ->first();
            }

            $userId = $ticketSystemUser?->id ?? 22;

            TicketComment::create([
                'ticket_id' => $this->selectedTicket->id,
                'user_id' => $userId,
                'comment' => $this->newComment,
                'created_at' => now()->subHours(8),
                'updated_at' => now()->subHours(8),
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

    public function getViewData(): array
    {
        $tickets = Ticket::with(['product', 'module', 'priority'])
            ->whereIn('product_id', [1, 2])
            ->when($this->selectedProduct !== 'All Products', function ($query) {
                return $query->whereHas('product', function ($q) {
                    $q->where('name', $this->selectedProduct);
                });
            })
            ->when($this->selectedModule !== 'All Modules', function ($query) {
                return $query->whereHas('module', function ($q) {
                    $q->where('name', $this->selectedModule);
                });
            })
            ->when($this->selectedDate, function ($query) {
                return $query->whereDate('created_date', $this->selectedDate);
            })
            ->get();

        $softwareBugsMetrics = $this->calculateBugsMetrics($tickets);
        $backendAssistanceMetrics = $this->calculateBackendMetrics($tickets);
        $enhancementMetrics = $this->calculateEnhancementMetrics($tickets);

        $filteredTickets = $this->getFilteredTickets($tickets);
        $calendarData = $this->getCalendarData();

        $products = TicketProduct::where('is_active', true)
            ->whereIn('id', [1, 2])
            ->pluck('name', 'name')
            ->toArray();

        // Filter modules to only show specific ones in defined sequence
        $allowedModules = ['Profile', 'Attendance', 'Leave', 'Claim', 'Payroll'];
        $allModules = TicketModule::where('is_active', true)
            ->whereIn('name', $allowedModules)
            ->pluck('name', 'name')
            ->toArray();

        // Sort modules by the defined sequence
        $modules = [];
        foreach ($allowedModules as $moduleName) {
            if (isset($allModules[$moduleName])) {
                $modules[$moduleName] = $allModules[$moduleName];
            }
        }

        // Get unique front end names
        $frontEndNames = $tickets->map(function ($ticket) {
            return $ticket->requestor->name ?? $ticket->requestor ?? null;
        })->filter()->unique()->sort()->values()->toArray();

        // Get unique statuses
        $statuses = $tickets->pluck('status')->unique()->sort()->values()->toArray();

        return [
            'softwareBugs' => $softwareBugsMetrics,
            'backendAssistance' => $backendAssistanceMetrics,
            'enhancement' => $enhancementMetrics,
            'tickets' => $filteredTickets,
            'calendar' => $calendarData,
            'currentMonth' => $this->currentMonth,
            'currentYear' => $this->currentYear,
            'products' => $products,
            'modules' => $modules,
            'frontEndNames' => $frontEndNames,
            'statuses' => $statuses,
        ];
    }

    private function calculateBugsMetrics(Collection $tickets): array
    {
        $bugs = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');

            return str_contains($priorityName, 'bug') ||
                str_contains($priorityName, 'software');
        });

        return [
            'total' => $bugs->count(),
            'new' => $bugs->where('status', 'New')->count(),
            'progress' => $bugs->whereIn('status', ['In Review', 'In Progress', 'Reopen'])->count(),
            'completed' => $bugs->whereIn('status', ['Completed', 'Tickets: Live'])->count(),
            'closed' => $bugs->whereIn('status', ['Closed', 'Closed System Configuration'])->count(),
        ];
    }

    private function calculateBackendMetrics(Collection $tickets): array
    {
        $backend = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');

            return str_contains($priorityName, 'backend') ||
                str_contains($priorityName, 'assistance') ||
                str_contains(str_replace(' ', '', $priorityName), 'backend');
        });

        return [
            'total' => $backend->count(),
            'new' => $backend->where('status', 'New')->count(),
            'progress' => $backend->whereIn('status', ['In Review', 'In Progress', 'Reopen'])->count(),
            'completed' => $backend->whereIn('status', ['Completed', 'Tickets: Live'])->count(),
            'closed' => $backend->whereIn('status', ['Closed', 'Closed System Configuration'])->count(),
        ];
    }

    private function calculateEnhancementMetrics(Collection $tickets): array
    {
        $enhancements = $tickets->filter(function ($ticket) {
            $priorityName = strtolower($ticket->priority?->name ?? '');

            return str_contains($priorityName, 'enhancement') ||
                   str_contains($priorityName, 'critical enhancement') ||
                   str_contains($priorityName, 'paid') ||
                   str_contains($priorityName, 'customization') ||
                   str_contains($priorityName, 'non-critical');
        });

        if ($this->selectedEnhancementType) {
            $enhancements = $enhancements->filter(function ($ticket) {
                $priorityName = strtolower($ticket->priority?->name ?? '');

                switch ($this->selectedEnhancementType) {
                    case 'critical':
                        return str_contains($priorityName, 'critical enhancement');
                    case 'paid':
                        return str_contains($priorityName, 'paid customization');
                    case 'non-critical':
                        return str_contains($priorityName, 'non-critical enhancement');
                    default:
                        return true;
                }
            });
        }

        return [
            'total' => $enhancements->count(),
            'new' => $enhancements->where('status', 'New')->count(),
            'pending_release' => $enhancements->where('status', 'Pending Release')->count(),
            'system_go_live' => $enhancements->where('status', 'System Go Live')->count(),
        ];
    }

    private function getCalendarData(): array
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1);

        return [
            'month' => $date->format('F Y'),
            'days_in_month' => $date->daysInMonth,
            'first_day_of_week' => $date->dayOfWeek,
            'current_date' => Carbon::now()->addHours(8),
        ];
    }

    private function getFilteredTickets(Collection $tickets): Collection
    {
        return $tickets
            ->when($this->selectedCategory, function ($collection) {
                return $collection->filter(function ($ticket) {
                    $priorityName = strtolower($ticket->priority?->name ?? '');

                    if ($this->selectedCategory === 'softwareBugs') {
                        return str_contains($priorityName, 'bug') ||
                               str_contains($priorityName, 'software');
                    }
                    elseif ($this->selectedCategory === 'backendAssistance') {
                        return str_contains($priorityName, 'backend') ||
                               str_contains($priorityName, 'assistance') ||
                               str_contains(str_replace(' ', '', $priorityName), 'backend');
                    }
                    elseif ($this->selectedCategory === 'enhancement') {
                        $isEnhancement = str_contains($priorityName, 'enhancement') ||
                                       str_contains($priorityName, 'paid') ||
                                       str_contains($priorityName, 'customization') ||
                                       str_contains($priorityName, 'non-critical');

                        if ($isEnhancement && $this->selectedEnhancementType) {
                            switch ($this->selectedEnhancementType) {
                                case 'critical':
                                    return $priorityName == 'critical enhancement';
                                case 'paid':
                                    return $priorityName == 'paid customization';
                                case 'non-critical':
                                    return $priorityName == 'non-critical enhancement';
                            }
                        }

                        return $isEnhancement;
                    }
                    return true;
                });
            })
            ->when($this->selectedStatus, function ($collection) {
                // Handle combined status with individual selections
                if (!empty($this->selectedCombinedStatuses)) {
                    return $collection->whereIn('status', $this->selectedCombinedStatuses);
                }
                // Handle combined In Progress status
                elseif ($this->selectedStatus === 'In Progress') {
                    return $collection->whereIn('status', ['In Review', 'In Progress', 'Reopen']);
                }
                // Handle combined Completed status
                elseif ($this->selectedStatus === 'Completed') {
                    return $collection->whereIn('status', ['Completed', 'Tickets: Live']);
                }
                // Handle combined Closed status
                elseif ($this->selectedStatus === 'Closed') {
                    return $collection->whereIn('status', ['Closed', 'Closed System Configuration']);
                }
                return $collection->where('status', $this->selectedStatus);
            })
            ->when($this->selectedEnhancementStatus, function ($collection) {
                return $collection->where('status', $this->selectedEnhancementStatus);
            })
            ->when($this->selectedFrontEnd, function ($collection) {
                return $collection->filter(function ($ticket) {
                    $frontEndName = $ticket->requestor->name ?? $ticket->requestor ?? '';
                    return $frontEndName === $this->selectedFrontEnd;
                });
            })
            ->when($this->selectedTicketStatus, function ($collection) {
                return $collection->where('status', $this->selectedTicketStatus);
            })
            ->when($this->etaStartDate, function ($collection) {
                return $collection->filter(function ($ticket) {
                    return $ticket->eta_release && $ticket->eta_release >= \Carbon\Carbon::parse($this->etaStartDate);
                });
            })
            ->when($this->etaEndDate, function ($collection) {
                return $collection->filter(function ($ticket) {
                    return $ticket->eta_release && $ticket->eta_release <= \Carbon\Carbon::parse($this->etaEndDate);
                });
            })
            ->when($this->etaSortDirection, function ($collection) {
                if ($this->etaSortDirection === 'asc') {
                    return $collection->sortBy(function ($ticket) {
                        return $ticket->eta_release ?? \Carbon\Carbon::maxValue();
                    });
                } else {
                    return $collection->sortByDesc(function ($ticket) {
                        return $ticket->eta_release ?? \Carbon\Carbon::minValue();
                    });
                }
            }, function ($collection) {
                return $collection->sortByDesc('created_at');
            })
            ->values();
    }

    public function selectCategory($category, $status = null): void
    {
        if ($this->selectedCategory === $category && $this->selectedStatus === $status) {
            $this->selectedCategory = null;
            $this->selectedStatus = null;
            $this->selectedCombinedStatuses = [];
        } else {
            $this->selectedCategory = $category;
            $this->selectedStatus = $status;
            $this->selectedEnhancementStatus = null;

            // Handle combined statuses
            if ($status === 'In Progress') {
                $this->selectedCombinedStatuses = ['In Review', 'In Progress', 'Reopen'];
            } elseif ($status === 'Closed') {
                $this->selectedCombinedStatuses = ['Closed', 'Closed System Configuration'];
            } else {
                $this->selectedCombinedStatuses = [];
            }
        }
    }

    public function selectEnhancementType($type): void
    {
        if ($this->selectedEnhancementType === $type) {
            $this->selectedEnhancementType = null;
        } else {
            $this->selectedEnhancementType = $type;
            $this->selectedCategory = 'enhancement';
        }
    }

    public function removeIndividualStatus($statusToRemove): void
    {
        if (!empty($this->selectedCombinedStatuses)) {
            $this->selectedCombinedStatuses = array_diff($this->selectedCombinedStatuses, [$statusToRemove]);

            // If no statuses left, clear the filter
            if (empty($this->selectedCombinedStatuses)) {
                $this->selectedCategory = null;
                $this->selectedStatus = null;
            }
        }
    }

    public function selectDate($year, $month, $day): void
    {
        $selectedDate = Carbon::create($year, $month, $day)->format('Y-m-d');

        if ($this->selectedDate === $selectedDate) {
            $this->selectedDate = null;
        } else {
            $this->selectedDate = $selectedDate;
        }
    }

    public function previousMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->subMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function nextMonth(): void
    {
        $date = Carbon::create($this->currentYear, $this->currentMonth, 1)->addMonth();
        $this->currentMonth = $date->month;
        $this->currentYear = $date->year;
    }

    public function toggleEtaSort(): void
    {
        if ($this->etaSortDirection === null) {
            $this->etaSortDirection = 'asc';
        } elseif ($this->etaSortDirection === 'asc') {
            $this->etaSortDirection = 'desc';
        } else {
            $this->etaSortDirection = null;
        }
    }

    public function openFilterModal(): void
    {
        $this->showFilterModal = true;
    }

    public function closeFilterModal(): void
    {
        $this->showFilterModal = false;
    }

    public function clearAllFilters(): void
    {
        $this->selectedFrontEnd = null;
        $this->selectedTicketStatus = null;
        $this->etaStartDate = null;
        $this->etaEndDate = null;
        $this->etaSortDirection = null;
    }

    public function markAsPassed(int $ticketId): void
    {
        try {
            $ticket = Ticket::find($ticketId);

            if ($ticket) {
                $authUser = auth()->user();

                $ticketSystemUser = null;
                if ($authUser) {
                    $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                        ->table('users')
                        ->where('email', $authUser->email)
                        ->first();
                }

                $userId = $ticketSystemUser?->id ?? 22;
                $userName = $ticketSystemUser?->name ?? 'HRcrm User';
                $userRole = $ticketSystemUser?->role ?? 'Internal Staff';

                $oldStatus = $ticket->status;

                $ticket->update([
                    'status' => 'Closed',
                    'isPassed' => 1,
                    'passed_at' => now()->subHours(8),
                ]);

                // ✅ Create a log entry for marking ticket as passed
                TicketLog::create([
                    'ticket_id' => $ticket->id,
                    'old_value' => $oldStatus,
                    'new_value' => 'Closed',
                    'action' => "Marked ticket {$ticket->ticket_id} as passed - changed status from '{$oldStatus}' to 'Closed'.",
                    'field_name' => 'status',
                    'change_reason' => 'Ticket marked as passed',
                    'updated_by' => $userId,
                    'user_name' => $userName,
                    'user_role' => $userRole,
                    'change_type' => 'status_change',
                    'source' => 'dashboard_pass_action',
                    'created_at' => now()->subHours(8),
                    'updated_at' => now()->subHours(8),
                ]);

                if ($this->selectedTicket && $this->selectedTicket->id === $ticketId) {
                    $this->selectedTicket->refresh();
                }

                // ✅ Show success notification
                Notification::make()
                    ->title('Ticket Marked as Passed')
                    ->body("Ticket {$ticket->ticket_id} has been marked as passed and closed")
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Error marking ticket as passed: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->body('Failed to mark ticket as passed')
                ->danger()
                ->send();
        }
    }

    public function markAsFailed(int $ticketId): void
    {
        try {
            $ticket = Ticket::find($ticketId);

            if ($ticket) {
                $authUser = auth()->user();

                $ticketSystemUser = null;
                if ($authUser) {
                    $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                        ->table('users')
                        ->where('name', $authUser->name)
                        ->first();
                }

                $userId = $ticketSystemUser?->id ?? 22;
                $userName = $ticketSystemUser?->name ?? 'HRcrm User';
                $userRole = $ticketSystemUser?->role ?? 'Internal Staff';

                $oldStatus = $ticket->status;

                // ✅ Update ticket to Failed and Reopen status
                $ticket->update([
                    'isPassed' => 0,
                    'passed_at' => now()->subHours(8),
                    'status' => 'Reopen', // ✅ Change status to Reopen
                ]);

                // ✅ Create a log entry for status change
                TicketLog::create([
                    'ticket_id' => $ticket->id,
                    'old_value' => $oldStatus,
                    'new_value' => 'Reopen',
                    'updated_by' => $userId,
                    'user_name' => $userName,
                    'user_role' => $userRole,
                    'change_type' => 'status_change',
                    'source' => 'manual',
                    'remarks' => 'Ticket marked as failed and reopened',
                    'created_at' => now()->subHours(8),
                    'updated_at' => now()->subHours(8),
                ]);

                if ($this->selectedTicket && $this->selectedTicket->id === $ticketId) {
                    $this->selectedTicket->refresh();
                }

                // ✅ Show success notification
                Notification::make()
                    ->title('Ticket Marked as Failed')
                    ->body("Ticket status changed to Reopen")
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Error marking ticket as failed: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->body('Failed to update ticket status')
                ->danger()
                ->send();
        }
    }

    public function updateTicketStatus($ticketId, string $newStatus): void
    {
        try {
            $ticket = Ticket::findOrFail($ticketId);
            $authUser = auth()->user();

            $ticketSystemUser = null;
            if ($authUser) {
                $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                    ->table('users')
                    ->where('email', $authUser->email)
                    ->first();
            }

            $userId = $ticketSystemUser?->id ?? 22;
            $oldStatus = $ticket->status;

            // Update ticket status
            $ticket->update(['status' => $newStatus]);

            // ✅ Create comprehensive ticket log entry
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'old_value' => $oldStatus,
                'new_value' => $newStatus,
                'action' => "Changed status from '{$oldStatus}' to '{$newStatus}' for ticket {$ticket->ticket_id}.",
                'field_name' => 'status',
                'change_reason' => null,
                'old_eta' => null,
                'new_eta' => null,
                'updated_by' => $userId,
                'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
                'user_role' => $ticketSystemUser?->role ?? 'Internal Staff',
                'change_type' => 'status_change',
                'source' => 'dashboard_modal',
                'created_at' => now()->subHours(8),
                'updated_at' => now()->subHours(8),
            ]);

            // ✅ Refresh the selected ticket with fresh data including logs
            $this->selectedTicket = $ticket->fresh(['logs', 'comments', 'attachments', 'priority', 'product', 'module', 'requestor']);

            Notification::make()
                ->title('Status Updated')
                ->success()
                ->body("Ticket {$ticket->ticket_id} status changed from {$oldStatus} to {$newStatus}")
                ->send();

            // ✅ Refresh the dashboard data to reflect changes
            $this->dispatch('$refresh');

        } catch (\Exception $e) {
            Log::error('Error updating ticket status: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to update ticket status: ' . $e->getMessage())
                ->send();
        }
    }

    public function reopenTicket()
    {
        try {
            Log::info('Starting reopenTicket method in TicketDashboard');

            // Get the current selected ticket
            $ticket = Ticket::find($this->selectedTicket->id);
            if (!$ticket) {
                throw new \Exception('Ticket not found');
            }

            // Get proper user ID for the ticketing system
            $authUser = auth()->user();
            $ticketSystemUser = null;
            if ($authUser) {
                $ticketSystemUser = \Illuminate\Support\Facades\DB::connection('ticketingsystem_live')
                    ->table('users')
                    ->where('email', $authUser->email)
                    ->first();
            }
            $userId = $ticketSystemUser?->id ?? 22;

            // Handle file uploads first to get URLs for HTML comment
            $uploadedImageUrls = [];
            if (!empty($this->reopenAttachments)) {
                foreach ($this->reopenAttachments as $file) {
                    try {
                        if ($file && $file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                            // Generate unique filename
                            $originalFilename = $file->getClientOriginalName();
                            $extension = $file->getClientOriginalExtension();

                            $storedFilename = time() . '_' . \Illuminate\Support\Str::random(10) . '_' .
                                            \Illuminate\Support\Str::slug(pathinfo($originalFilename, PATHINFO_FILENAME)) .
                                            '.' . $extension;

                            // Store file in S3
                            $path = $file->storeAs(
                                'ticket_attachments/' . date('Y/m/d'),
                                $storedFilename,
                                's3-ticketing'
                            );

                            $fileHash = hash_file('md5', $file->getRealPath());

                            // Create attachment record with correct field names
                            $attachment = TicketAttachment::create([
                                'ticket_id' => $ticket->id,
                                'original_filename' => $originalFilename,
                                'stored_filename' => $storedFilename,
                                'file_path' => $path,
                                'file_size' => $file->getSize(),
                                'mime_type' => $file->getMimeType(),
                                'file_hash' => $fileHash,
                                'uploaded_by' => $userId,
                                'created_at' => now()->subHours(8),
                                'updated_at' => now()->subHours(8),
                            ]);

                            // If it's an image, collect the URL for HTML comment
                            if (str_starts_with($file->getMimeType(), 'image/')) {
                                $disk = Storage::disk('s3-ticketing');
                                $fileUrl = $disk->url($path);
                                $uploadedImageUrls[] = [
                                    'url' => $fileUrl,
                                    'filename' => $originalFilename
                                ];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::error('Error uploading reopen attachment: ' . $e->getMessage());
                    }
                }
            }

            // Create HTML comment combining text and images
            $htmlComment = '';
            if (!empty(trim($this->reopenComment))) {
                $htmlComment .= '<p>' . nl2br(e(trim($this->reopenComment))) . '</p>';
            }

            // Add image attachments as <p><img> tags
            foreach ($uploadedImageUrls as $image) {
                $htmlComment .= '<p><img src="' . $image['url'] . '" alt="' . e($image['filename']) . '" style="max-width: 100%; height: auto;" /></p>';
            }

            // Update ticket status and reopen reason
            $ticket->status = 'Reopen';
            if (!empty($htmlComment)) {
                $ticket->reopen_reason = $htmlComment;
            } elseif (!empty(trim($this->reopenComment))) {
                $ticket->reopen_reason = trim($this->reopenComment);
            }
            $ticket->save();

            // Also create a TicketComment for the reopen reason with HTML including images
            if (!empty($htmlComment)) {
                TicketComment::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $userId,
                    'comment' => $htmlComment,
                    'created_at' => now()->subHours(8),
                    'updated_at' => now()->subHours(8),
                ]);
            } elseif (!empty(trim($this->reopenComment))) {
                TicketComment::create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $userId,
                    'comment' => trim($this->reopenComment),
                    'created_at' => now()->subHours(8),
                    'updated_at' => now()->subHours(8),
                ]);
            }

            // Log the action
            TicketLog::create([
                'ticket_id' => $ticket->id,
                'old_value' => $this->selectedTicket->status ?? 'Closed',
                'new_value' => 'Reopen',
                'action' => "Reopened ticket {$ticket->ticket_id} from '{$this->selectedTicket->status}' to 'Reopen'.",
                'field_name' => 'status',
                'change_reason' => !empty($htmlComment) ? $htmlComment : (!empty(trim($this->reopenComment)) ? trim($this->reopenComment) : 'Ticket reopened without comment'),
                'old_eta' => null,
                'new_eta' => null,
                'updated_by' => $userId,
                'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
                'user_role' => $ticketSystemUser?->role ?? 'Support Staff',
                'change_type' => 'status_change',
                'source' => 'dashboard_reopen_modal',
                'created_at' => now()->subHours(8),
                'updated_at' => now()->subHours(8),
            ]);

            // Close modal and reset form
            $this->closeReopenModal();

            // Update selected ticket data
            $this->selectedTicket->status = 'Reopen';

            // Dispatch events to refresh dashboard
            $this->dispatch('$refresh');

            Notification::make()
                ->title('Success')
                ->success()
                ->body('Ticket has been successfully reopened')
                ->send();

        } catch (\Exception $e) {
            Log::error('Error reopening ticket: ' . $e->getMessage());

            Notification::make()
                ->title('Error')
                ->danger()
                ->body('Failed to reopen ticket: ' . $e->getMessage())
                ->send();
        }
    }
}
