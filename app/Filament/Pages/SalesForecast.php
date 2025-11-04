<?php
namespace App\Filament\Pages;

use App\Models\Invoice;
use App\Models\InvoiceDetail;
use Filament\Pages\Page;
use App\Models\User;
use App\Models\Lead;
use App\Models\ProformaInvoice;
use Livewire\Attributes\On;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SalesForecast extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static string $view = 'filament.pages.sales-forecast';
    protected static ?string $navigationLabel = 'Sales Forecast - Salesperson';
    protected static ?string $navigationGroup = 'Analysis';
    protected static ?string $title = '';
    protected static ?int $navigationSort = 9;

    public $selectedUser;
    public $selectedMonth;
    public $hotDealsTotal;
    public $invoiceTotal;
    public $proformaInvoiceTotal;
    public $users;
    public Carbon $currentDate;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        if (!$user || !($user instanceof \App\Models\User)) {
            return false;
        }

        return $user->hasRouteAccess('filament.admin.pages.sales-forecast');
    }

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

        // Clear cache for this user
        $this->clearUserCache($userId);

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

        // Clear cache for this month
        $this->clearUserCache($this->selectedUser);

        $this->calculateHotDealsTotal();
        $this->calculateInvoiceTotal();
        $this->calculateProformaInvoice();
        $this->dispatch('updateTablesForUser', $this->selectedUser, $month);
    }

    /**
     * Clear cache for a specific user and month
     */
    protected function clearUserCache($userId)
    {
        $cacheKey = "sales_forecast_invoice_{$userId}_{$this->selectedMonth}";
        Cache::forget($cacheKey);
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

    /**
     * Calculate invoice total from invoice_details table (excluding certain item codes)
     * Updated to use new separated tables structure
     */
    public function calculateInvoiceTotal()
    {
        // Create cache key based on user and month
        $cacheKey = "sales_forecast_invoice_{$this->selectedUser}_{$this->selectedMonth}";

        // Cache for 5 minutes
        $this->invoiceTotal = Cache::remember($cacheKey, 300, function () {
            // Excluded item codes
            $excludedItemCodes = [
                'SHIPPING',
                'BANKCHG',
                'DEPOSIT-MYR',
                'F.COMMISSION',
                'L.COMMISSION',
                'L.ENTITLEMENT',
                'MGT FEES',
                'PG.COMMISSION'
            ];

            // Get user's salesperson name
            $salespersonName = null;
            if ($this->selectedUser) {
                $user = User::find($this->selectedUser);
                if ($user) {
                    // Map user to salesperson name
                    $salespersonMapping = [
                        6 => 'MUIM',
                        7 => 'YASMIN',
                        8 => 'FARHANAH',
                        9 => 'JOSHUA',
                        10 => 'AZIZ',
                        11 => 'BARI',
                        12 => 'VINCE',
                    ];
                    $salespersonName = $salespersonMapping[$user->id] ?? strtoupper($user->name);
                }
            }

            // Build query with optimized raw SQL
            $placeholders = implode(',', array_fill(0, count($excludedItemCodes), '?'));

            $params = $excludedItemCodes;

            $whereConditions = [];

            // Add salesperson filter
            if ($salespersonName) {
                $whereConditions[] = "i.salesperson = ?";
                $params[] = $salespersonName;
            }

            // Exclude cancelled invoices
            $whereConditions[] = "i.invoice_status != 'V'";

            // Add month/year filter
            if ($this->selectedMonth) {
                $date = Carbon::parse($this->selectedMonth);
                $whereConditions[] = "MONTH(i.invoice_date) = ?";
                $whereConditions[] = "YEAR(i.invoice_date) = ?";
                $params[] = $date->month;
                $params[] = $date->year;
            }

            $whereClause = !empty($whereConditions) ? 'AND ' . implode(' AND ', $whereConditions) : '';

            // OPTIMIZED: Single query to get invoice total
            $result = DB::selectOne("
                SELECT
                    COALESCE(SUM(id.local_sub_total), 0) as total
                FROM invoices i
                INNER JOIN invoice_details id ON i.doc_key = id.doc_key
                WHERE id.item_code NOT IN ($placeholders)
                    $whereClause
            ", $params);

            return (float) $result->total;
        });
    }

    /**
     * Calculate proforma invoice total
     */
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

    /**
     * Helper method to get total invoice amount from invoice_details (excluding certain item codes)
     */
    protected function getTotalInvoiceAmountByDocKey(string $docKey): float
    {
        $excludedItemCodes = [
            'SHIPPING',
            'BANKCHG',
            'DEPOSIT-MYR',
            'F.COMMISSION',
            'L.COMMISSION',
            'L.ENTITLEMENT',
            'MGT FEES',
            'PG.COMMISSION'
        ];

        return InvoiceDetail::where('doc_key', $docKey)
            ->whereNotIn('item_code', $excludedItemCodes)
            ->sum('local_sub_total');
    }

    /**
     * Get invoice data with details for display
     */
    public function getInvoiceDataWithDetails()
    {
        $cacheKey = "sales_forecast_detailed_{$this->selectedUser}_{$this->selectedMonth}";

        return Cache::remember($cacheKey, 300, function () {
            $query = Invoice::query()
                ->where('invoice_status', '!=', 'V'); // Exclude cancelled invoices

            // Get user's salesperson name
            if ($this->selectedUser) {
                $user = User::find($this->selectedUser);
                if ($user) {
                    $salespersonMapping = [
                        6 => 'MUIM',
                        7 => 'YASMIN',
                        8 => 'FARHANAH',
                        9 => 'JOSHUA',
                        10 => 'AZIZ',
                        11 => 'BARI',
                        12 => 'VINCE',
                    ];
                    $salespersonName = $salespersonMapping[$user->id] ?? strtoupper($user->name);
                    $query->where('salesperson', $salespersonName);
                }
            }

            if ($this->selectedMonth) {
                $query->whereMonth('invoice_date', Carbon::parse($this->selectedMonth)->month)
                      ->whereYear('invoice_date', Carbon::parse($this->selectedMonth)->year);
            }

            $invoices = $query->get();

            // Calculate actual amounts from invoice_details
            $invoicesWithAmounts = $invoices->map(function ($invoice) {
                $invoice->actual_amount = $this->getTotalInvoiceAmountByDocKey($invoice->doc_key);
                return $invoice;
            });

            return $invoicesWithAmounts;
        });
    }
}
