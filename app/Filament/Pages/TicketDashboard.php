<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketModule;
use App\Models\TicketProduct;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TicketDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.ticket-dashboard';
    protected static ?string $navigationLabel = 'Ticket Dashboard';
    protected static ?string $title = '';

    public $selectedProduct = 'All Products';
    public $selectedModule = 'All Modules';
    public $selectedCategory = null;
    public $selectedStatus = null;
    public $selectedEnhancementStatus = null;
    public $selectedEnhancementType = null; // ✅ Add enhancement type filter
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

    public function viewTicket($ticketId): void
    {
        $this->selectedTicket = Ticket::with(['comments.user', 'attachments.uploader'])
            ->find($ticketId);
        $this->showTicketModal = true;
    }

    public function closeTicketModal(): void
    {
        $this->showTicketModal = false;
        $this->selectedTicket = null;
        $this->newComment = '';
    }

    public function addComment(): void
    {
        if (empty($this->newComment)) {
            return;
        }

        TicketComment::create([
            'ticket_id' => $this->selectedTicket->id,
            'user_id' => auth()->id(),
            'comment' => $this->newComment,
        ]);

        $this->newComment = '';

        // Refresh ticket data
        $this->selectedTicket = Ticket::with(['comments.user', 'attachments.uploader'])
            ->find($this->selectedTicket->id);
    }

    public function getViewData(): array
    {
        $tickets = Ticket::on('ticketingsystem_live')
            ->with(['product', 'module', 'priority'])
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
                return $query->whereDate('created_at', $this->selectedDate);
            })
            ->get();

        // Calculate metrics with enhancement type filtering
        $softwareBugsMetrics = $this->calculateBugsMetrics($tickets);
        $backendAssistanceMetrics = $this->calculateBackendMetrics($tickets);
        $enhancementMetrics = $this->calculateEnhancementMetrics($tickets);

        $filteredTickets = $this->getFilteredTickets($tickets);
        $calendarData = $this->getCalendarData();

        $products = TicketProduct::on('ticketingsystem_live')
            ->where('is_active', true)
            ->whereIn('id', [1, 2])
            ->pluck('name', 'name')
            ->toArray();

        $modules = TicketModule::on('ticketingsystem_live')
            ->where('is_active', true)
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

    private function calculateBugsMetrics($tickets): array
    {
        $bugs = $tickets->filter(function ($ticket) {
            $priorityName = $ticket->priority?->name ?? $ticket->priority ?? '';
            return str_contains(strtolower($priorityName), 'bug') ||
                   str_contains(strtolower($priorityName), 'software');
        });

        return [
            'total' => $bugs->count(),
            'new' => $bugs->whereIn('status', ['RND - New', 'RND - Reopen'])->count(),
            'review' => $bugs->where('status', 'RND - In Review')->count(),
            'progress' => $bugs->where('status', 'RND - In Progress')->count(),
            'closed' => $bugs->whereIn('status', ['RND - Closed', 'RND - Closed System Configuration'])->count(),
        ];
    }

    private function calculateBackendMetrics($tickets): array
    {
        $backend = $tickets->filter(function ($ticket) {
            $priorityName = $ticket->priority?->name ?? $ticket->priority ?? '';
            return str_contains(strtolower($priorityName), 'backend') ||
                   str_contains(strtolower($priorityName), 'assistance');
        });

        return [
            'total' => $backend->count(),
            'new' => $backend->whereIn('status', ['RND - New', 'RND - Reopen'])->count(),
            'review' => $backend->where('status', 'RND - In Review')->count(),
            'progress' => $backend->where('status', 'RND - In Progress')->count(),
            'closed' => $backend->whereIn('status', ['RND - Closed', 'RND - Closed System Configuration'])->count(),
        ];
    }

    private function calculateEnhancementMetrics($tickets): array
    {
        // ✅ Get all enhancements
        $enhancements = $tickets->filter(function ($ticket) {
            $priorityName = $ticket->priority?->name ?? $ticket->priority ?? '';
            return str_contains(strtolower($priorityName), 'enhancement') ||
                   str_contains(strtolower($priorityName), 'critical enhancement') ||
                   str_contains(strtolower($priorityName), 'paid') ||
                   str_contains(strtolower($priorityName), 'non-critical');
        });

        // ✅ Filter by enhancement type if selected
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
            'new' => $enhancements->whereIn('status', ['New', 'RND - New'])->count(),
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

    private function getFilteredTickets($tickets)
    {
        return $tickets
            ->when($this->selectedCategory, function ($collection) {
                return $collection->filter(function ($ticket) {
                    $priorityName = $ticket->priority?->name ?? $ticket->priority ?? '';

                    if ($this->selectedCategory === 'softwareBugs') {
                        return str_contains(strtolower($priorityName), 'bug') ||
                               str_contains(strtolower($priorityName), 'software');
                    } elseif ($this->selectedCategory === 'backendAssistance') {
                        return str_contains(strtolower($priorityName), 'backend') ||
                               str_contains(strtolower($priorityName), 'assistance');
                    } elseif ($this->selectedCategory === 'enhancement') {
                        $isEnhancement = str_contains(strtolower($priorityName), 'enhancement') ||
                                       str_contains(strtolower($priorityName), 'paid') ||
                                       str_contains(strtolower($priorityName), 'non-critical');

                        // ✅ Apply enhancement type filter
                        if ($isEnhancement && $this->selectedEnhancementType) {
                            switch ($this->selectedEnhancementType) {
                                case 'critical':
                                    return str_contains(strtolower($priorityName), 'critical enhancement');
                                case 'paid':
                                    return str_contains(strtolower($priorityName), 'paid customization');
                                case 'non-critical':
                                    return str_contains(strtolower($priorityName), 'non-critical enhancement');
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

    // ✅ Add method to select enhancement type
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
}
