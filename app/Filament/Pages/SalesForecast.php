<?php
namespace App\Filament\Pages;

use App\Models\Invoice;
use Filament\Pages\Page;
use App\Models\User;
use App\Models\Lead;
use App\Models\ProformaInvoice;
use Livewire\Attributes\On;
use Carbon\Carbon;

class SalesForecast extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string $view = 'filament.pages.sales-forecast';
    protected static ?string $navigationLabel = 'Sales Forecast - Salesperson';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 9;

    public $selectedUser;
    public $selectedMonth;
    public $hotDealsTotal;
    public $invoiceTotal;
    public $proformaInvoiceTotal;
    public $users;
    public Carbon $currentDate;

    // public static function canAccess(): bool
    // {
    //     return auth()->user()->role_id = 3;
    // }

    /**
     * Lifecycle hook - runs when the component is initialized
     */
    public function mount()
    {
        $authUser = auth()->user();
        $this->currentDate = Carbon::now();

        // Fetch only Salespersons (role_id = 2)
        $this->users = User::where('role_id', 2)->get();

        // Set default selected user based on role
        if ($authUser->role_id == 1) {
            $this->selectedUser = session('selectedUser', null);
        } elseif ($authUser->role_id == 2) {
            $this->selectedUser = $authUser->id; // Salesperson can only see their data
        }

        // Set default selected month
        $this->selectedMonth = session('selectedMonth', $this->currentDate->format('Y-m'));

        // Store in session
        session(['selectedUser' => $this->selectedUser, 'selectedMonth' => $this->selectedMonth]);
        $this->calculateHotDealsTotal();
        $this->calculateInvoiceTotal();
        $this->calculateProformaInvoice();
    }

    /**
     * Fetch salespersons for dropdown
     */
    public function getSalespersons()
    {
        return User::where('role_id', 2)->pluck('name', 'id'); // Get only salespersons
    }

    /**
     * Handle change in selected salesperson
     */
    public function updatedSelectedUser($userId)
    {
        $this->selectedUser = $userId;
        session(['selectedUser' => $userId]);
        $this->calculateHotDealsTotal();
        $this->calculateInvoiceTotal();
        $this->calculateProformaInvoice();
        $this->dispatch('updateTablesForUser', $userId, $this->selectedMonth);
    }

    /**
     * Handle change in selected month
     */
    public function updatedSelectedMonth($month)
    {
        $this->selectedMonth = $month;
        session(['selectedMonth' => $month]);
        $this->calculateHotDealsTotal();
        $this->calculateInvoiceTotal();
        $this->calculateProformaInvoice();
        $this->dispatch('updateTablesForUser', $this->selectedUser, $month);
    }

    /**
     * Calculate total deal amount for Hot leads in the selected month.
     */
    public function calculateHotDealsTotal()
    {
        $query = Lead::where('lead_status', 'Hot');

        if ($this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($this->selectedMonth) {
            $query->whereMonth('created_at', Carbon::parse($this->selectedMonth)->month)
                  ->whereYear('created_at', Carbon::parse($this->selectedMonth)->year);
        }

        $this->hotDealsTotal = $query->sum('deal_amount');
    }

    public function calculateInvoiceTotal()
    {
        $query = Invoice::query(); // Get all invoices

        if ($this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($this->selectedMonth) {
            $query->whereMonth('invoice_date', Carbon::parse($this->selectedMonth)->month)
                  ->whereYear('invoice_date', Carbon::parse($this->selectedMonth)->year);
        }

        $this->invoiceTotal = $query->sum('amount'); // Sum the 'amount' column
    }

    public function calculateProformaInvoice()
    {
        $query = ProformaInvoice::query(); // Get all invoices

        if ($this->selectedUser) {
            $query->where('salesperson', $this->selectedUser);
        }

        if ($this->selectedMonth) {
            $query->whereMonth('created_at', Carbon::parse($this->selectedMonth)->month)
                  ->whereYear('created_at', Carbon::parse($this->selectedMonth)->year);
        }

        $this->proformaInvoiceTotal = $query->sum('amount'); // Sum the 'amount' column
    }
}
