<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Ticket;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class TicketDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static string $view = 'filament.pages.ticket-dashboard';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $title = 'Dashboard';

    public $selectedProduct = 'All Products';
    public $selectedModule = 'All Modules';
    public $selectedCategory = null;
    public $selectedStatus = null;
    public $selectedEnhancementStatus = null;
    public $currentMonth;
    public $currentYear;

    public function mount(): void
    {
        $this->currentMonth = Carbon::now()->month;
        $this->currentYear = Carbon::now()->year;
    }

    public function getViewData(): array
    {
        // Get all tickets with filters
        $tickets = Ticket::query()
            ->when($this->selectedProduct !== 'All Products', function ($query) {
                return $query->where('product', $this->selectedProduct);
            })
            ->when($this->selectedModule !== 'All Modules', function ($query) {
                return $query->where('module', $this->selectedModule);
            })
            ->get();

        // Calculate metrics
        $softwareBugsMetrics = $this->calculateBugsMetrics($tickets);
        $backendAssistanceMetrics = $this->calculateBackendMetrics($tickets);
        $enhancementMetrics = $this->calculateEnhancementMetrics($tickets);

        // Get filtered tickets for table
        $filteredTickets = $this->getFilteredTickets($tickets);

        // Calendar data
        $calendarData = $this->getCalendarData();

        return [
            'softwareBugs' => $softwareBugsMetrics,
            'backendAssistance' => $backendAssistanceMetrics,
            'enhancement' => $enhancementMetrics,
            'tickets' => $filteredTickets,
            'calendar' => $calendarData,
        ];
    }

    private function calculateBugsMetrics($tickets): array
    {
        $bugs = $tickets->filter(fn($t) => str_starts_with($t->priority ?? '', 'P1'));

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
        $backend = $tickets->filter(fn($t) => str_starts_with($t->priority ?? '', 'P2'));

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
        $enhancements = $tickets->where('type', 'Enhancement');

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

    private function getFilteredTickets($tickets)
    {
        return $tickets
            ->when($this->selectedCategory, function ($collection) {
                return $collection->filter(function ($ticket) {
                    if ($this->selectedCategory === 'softwareBugs') {
                        return str_starts_with($ticket->priority ?? '', 'P1');
                    } elseif ($this->selectedCategory === 'backendAssistance') {
                        return str_starts_with($ticket->priority ?? '', 'P2');
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
        } else {
            $this->selectedEnhancementStatus = $status;
            $this->selectedCategory = 'enhancement';
            $this->selectedStatus = null;
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
