<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Ticket;
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
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Notifications\Notification;

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
    public $selectedDate = null;

    public $selectedTicket = null;
    public $showTicketModal = false;
    public $newComment = '';

    public function mount(): void
    {
        $this->currentMonth = Carbon::now()->month;
        $this->currentYear = Carbon::now()->year;
    }

    // ✅ Add header actions for Create Ticket button
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
                            TextInput::make('windows_version')
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

                    TextInput::make('zoho_id')
                        ->label('Zoho Ticket Number')
                        ->columnSpanFull(),

                    TextInput::make('title')
                        ->label('Title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    RichEditor::make('description')
                        ->label('Description')
                        ->required()
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

                        $data['ticket_id'] = sprintf('TC-%s-%04d', $productCode, $nextNumber);

                        $ticket = Ticket::create($data);

                        TicketLog::create([
                            'ticket_id' => $ticket->id,
                            'old_status' => null,
                            'new_status' => 'New',
                            'updated_by' => $requestorId,
                            'user_name' => $ticketSystemUser?->name ?? 'HRcrm User',
                            'user_role' => $ticketSystemUser?->role ?? 'Internal Staff',
                            'change_type' => 'ticket_creation',
                            'source' => 'manual',
                        ]);

                        Notification::make()
                            ->title('Ticket Created')
                            ->success()
                            ->body("Ticket {$data['ticket_id']} (ID: #{$ticket->id}) has been created successfully.")
                            ->send();

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

        } catch (\Exception $e) {
            Log::error('Error adding comment: ' . $e->getMessage());
        }
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

        $modules = TicketModule::where('is_active', true)
            ->pluck('name', 'name')
            ->toArray();

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
            'review' => $bugs->where('status', 'In Review')->count(),
            'progress' => $bugs->where('status', 'In Progress')->count(),
            'closed' => $bugs->where('status', 'Closed')->count(),
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
            'review' => $backend->where('status', 'In Review')->count(),
            'progress' => $backend->where('status', 'In Progress')->count(),
            'closed' => $backend->where('status', 'Closed')->count(),
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
            'current_date' => Carbon::now(),
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
                                    return str_contains($priorityName, 'critical enhancement');
                                case 'paid':
                                    return str_contains($priorityName, 'paid customization');
                                case 'non-critical':
                                    return str_contains($priorityName, 'non-critical enhancement');
                            }
                        }

                        return $isEnhancement;
                    }
                    return true;
                });
            })
            ->when($this->selectedStatus, function ($collection) {
                return $collection->where('status', $this->selectedStatus);
            })
            ->when($this->selectedEnhancementStatus, function ($collection) {
                return $collection->where('status', $this->selectedEnhancementStatus);
            })
            ->sortByDesc('created_at')
            ->values();
    }

    public function selectCategory($category, $status = null): void
    {
        if ($this->selectedCategory === $category && $this->selectedStatus === $status) {
            $this->selectedCategory = null;
            $this->selectedStatus = null;
        } else {
            $this->selectedCategory = $category;
            $this->selectedStatus = $status;
            $this->selectedEnhancementStatus = null;
        }
    }

    public function selectEnhancement($status = null): void
    {
        if ($this->selectedEnhancementStatus === $status) {
            $this->selectedEnhancementStatus = null;
            $this->selectedCategory = null;
        } else {
            $this->selectedEnhancementStatus = $status;
            $this->selectedCategory = 'enhancement';
            $this->selectedStatus = null;
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

    public function markAsPassed(int $ticketId): void
    {
        try {
            $ticket = Ticket::find($ticketId);

            if ($ticket) {
                $ticket->update([
                    'isPassed' => 1,
                    'passed_at' => now(),
                ]);

                if ($this->selectedTicket && $this->selectedTicket->id === $ticketId) {
                    $this->selectedTicket->refresh();
                }
            }
        } catch (\Exception $e) {
            Log::error('Error marking ticket as passed: ' . $e->getMessage());
        }
    }

    public function markAsFailed(int $ticketId): void
    {
        try {
            $ticket = Ticket::find($ticketId);

            if ($ticket) {
                $ticket->update([
                    'isPassed' => 0,
                    'passed_at' => now(),
                ]);

                if ($this->selectedTicket && $this->selectedTicket->id === $ticketId) {
                    $this->selectedTicket->refresh();
                }
            }
        } catch (\Exception $e) {
            Log::error('Error marking ticket as failed: ' . $e->getMessage());
        }
    }
}
