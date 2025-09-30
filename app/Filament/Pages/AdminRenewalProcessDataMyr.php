<?php

namespace App\Filament\Pages;

use App\Models\ActivityLog;
use App\Models\CompanyDetail;
use App\Models\Lead;
use App\Models\Renewal;
use Carbon\Carbon;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Malzariey\FilamentDaterangepickerFilter\Fields\DateRangePicker;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;

// Create a temporary model for the renewal data
class RenewalDataMyr extends Model
{
    protected $connection = 'frontenddb';

    protected $table = 'crm_expiring_license';

    protected $primaryKey = 'f_company_id';

    public $timestamps = false;

    // Define excluded products in one place
    public static $excludedProducts = [
        'TimeTec VMS Corporate (1 Floor License)',
        'TimeTec VMS SME (1 Location License)',
        'TimeTec Patrol (1 Checkpoint License)',
        'TimeTec Patrol (10 Checkpoint License)',
        'Other',
        'TimeTec Profile (10 User License)',
    ];

    public function getKey()
    {
        $key = $this->getAttribute($this->getKeyName());

        return $key !== null ? (string) $key : 'record-'.uniqid();
    }

    // Helper method to apply product exclusions to query
    public static function applyProductExclusions($query)
    {
        foreach (self::$excludedProducts as $excludedProduct) {
            $query->where('f_name', 'NOT LIKE', '%'.$excludedProduct.'%');
        }

        return $query;
    }

    // Get reseller information for a company
    public static function getResellerForCompany($companyId)
    {
        try {
            return DB::connection('frontenddb')->table('crm_reseller_link')
                ->select('reseller_name', 'f_rate')
                ->where('f_id', $companyId)
                ->first();
        } catch (\Exception $e) {
            Log::error("Error fetching reseller for company $companyId: ".$e->getMessage());

            return null;
        }
    }

    // Get invoices for a specific company
    public static function getInvoicesForCompany($companyId, $startDate = null, $endDate = null)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');

            if (! $startDate || ! $endDate) {
                $startDate = $today;
                $endDate = Carbon::now()->addDays(60)->format('Y-m-d');
            }

            $query = DB::connection('frontenddb')->table('crm_expiring_license')
                ->select([
                    'f_invoice_no',
                    'f_currency',
                    DB::raw('MAX(f_total_amount) AS invoice_total_amount'),
                    DB::raw('SUM(f_unit) AS invoice_total_units'),
                    DB::raw('COUNT(*) AS invoice_product_count'),
                    DB::raw('MIN(f_expiry_date) AS invoice_earliest_expiry'),
                    DB::raw('MAX(f_expiry_date) AS invoice_latest_expiry'),
                    DB::raw('ANY_VALUE(f_company_name) AS f_company_name'),
                    DB::raw('ANY_VALUE(f_company_id) AS f_company_id'),
                ])
                ->where('f_company_id', $companyId)
                ->where('f_expiry_date', '>=', $startDate)
                ->where('f_expiry_date', '<=', $endDate)
                ->where('f_currency', 'MYR');

            // Apply product exclusions
            foreach (self::$excludedProducts as $excludedProduct) {
                $query->where('f_name', 'NOT LIKE', '%'.$excludedProduct.'%');
            }

            return $query->groupBy('f_invoice_no', 'f_currency')
                ->having(DB::raw('COUNT(*)'), '>', 0)
                ->orderBy('f_invoice_no', 'ASC')
                ->get(); // Remove ->toArray() to keep as objects
        } catch (\Exception $e) {
            Log::error("Error fetching invoices for company $companyId: ".$e->getMessage());

            return collect(); // Return empty collection instead of empty array
        }
    }

    public static function getProductsForInvoice($companyId, $invoiceNo, $startDate = null, $endDate = null)
    {
        try {
            $today = Carbon::now()->format('Y-m-d');

            if (! $startDate || ! $endDate) {
                $startDate = $today;
                $endDate = Carbon::now()->addDays(60)->format('Y-m-d');
            }

            $query = DB::connection('frontenddb')->table('crm_expiring_license')
                ->select([
                    'f_currency',
                    'f_id',
                    'f_company_name',
                    'f_company_id',
                    'f_name',
                    'f_invoice_no',
                    'f_total_amount',
                    'f_unit',
                    'f_start_date',
                    'f_expiry_date',
                    'Created',
                    'payer',
                    'payer_id',
                    'f_created_time',
                ])
                ->where('f_company_id', $companyId)
                ->where('f_invoice_no', $invoiceNo)
                ->where('f_expiry_date', '>=', $startDate)
                ->where('f_expiry_date', '<=', $endDate)
                ->where('f_currency', 'MYR');

            // Apply product exclusions
            foreach (self::$excludedProducts as $excludedProduct) {
                $query->where('f_name', 'NOT LIKE', '%'.$excludedProduct.'%');
            }

            return $query->orderBy('f_expiry_date', 'ASC')
                ->get(); // Remove ->toArray() to keep as objects
        } catch (\Exception $e) {
            Log::error("Error fetching products for company $companyId and invoice $invoiceNo: ".$e->getMessage());

            return collect(); // Return empty collection instead of empty array
        }
    }
}

class AdminRenewalProcessDataMyr extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $title = 'Renewal Process Data (MYR)';

    protected static ?string $navigationLabel = 'Renewal Process Data (MYR)';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 51;

    protected static string $view = 'filament.pages.admin-renewal-process-data-myr';

    public $completedRenewalStats;

    public $renewalForecastStats;

    public $newStats;

    public $pendingConfirmationStats;

    public $pendingPaymentStats;

    public $shouldRefreshStats = false;

    public function mount(): void
    {
        $this->loadData();
    }

    protected function getProductGroupMapping(): array
    {
        return [
            // TimeTec HR Group
            'timetec_hr' => [
                'TimeTec TA (1 User License)',
                'TimeTec TA (10 User License)',
                'TimeTec Leave (1 User License)',
                'TimeTec Leave (10 User License)',
                'TimeTec Claim (1 User License)',
                'TimeTec Claim (10 User License)',
                'TimeTec Payroll (1 Payroll License)',
                'TimeTec Payroll (10 Payroll License)',
            ],
            // Non-TimeTec HR Group
            'non_timetec_hr' => [
                'Face & QR Code (1 Device License)',
                'FCC Terminal License',
                'TimeTec Access (1 Door License)',
                'TimeTec Hire Business (Unlimited Job Posts)',
                'TimeTec Hire Startup (10 Job Posts)',
            ],
            // Other Division Group
            'other_division' => [
                'TimeTec VMS Corporate (1 Floor License)',
                'TimeTec VMS SME (1 Location License)',
                'TimeTec Patrol (1 Checkpoint License)',
                'TimeTec Patrol (10 Checkpoint License)',
                'Other',
                'TimeTec Profile (10 User License)',
            ],
        ];
    }

    protected function getProductGroup(string $productName): ?string
    {
        $mapping = $this->getProductGroupMapping();

        foreach ($mapping as $group => $products) {
            foreach ($products as $product) {
                if (stripos($productName, $product) !== false || $productName === $product) {
                    return $group;
                }
            }
        }

        return 'other_division'; // Default to Other Division for unmapped products
    }

    public function refreshStats()
    {
        $this->loadData();

        Notification::make()
            ->success()
            ->title('Dashboard Refreshed')
            ->body('Statistics have been updated to reflect current filters.')
            ->send();
    }

    protected function loadData($startDate = null, $endDate = null): void
    {
        // Get current filters (if any) or use defaults
        if (! $startDate || ! $endDate) {
            $today = Carbon::now()->format('Y-m-d');
            $next60Days = Carbon::now()->addDays(60)->format('Y-m-d');
            $startDate = $today;
            $endDate = $next60Days;
        }

        // Load data for each box with date filtering
        $this->completedRenewalStats = $this->getCompletedRenewalStats($startDate, $endDate);
        $this->renewalForecastStats = $this->getRenewalForecastStats($startDate, $endDate);
        $this->newStats = $this->getNewStats($startDate, $endDate);
        $this->pendingConfirmationStats = $this->getPendingConfirmationStats($startDate, $endDate);
        $this->pendingPaymentStats = $this->getPendingPaymentStats($startDate, $endDate);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh_stats')
                ->label('')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action('refreshStats')
                ->tooltip('Refresh dashboard statistics to reflect current filters'),
        ];
    }

    protected function getNewStats($startDate = null, $endDate = null)
    {
        try {
            // Set default date range if not provided
            if (!$startDate || !$endDate) {
                $today = Carbon::now()->format('Y-m-d');
                $next60Days = Carbon::now()->addDays(60)->format('Y-m-d');
                $startDate = $today;
                $endDate = $next60Days;
            }

            // Get renewals with new status that fall within date range
            $renewals = Renewal::where('renewal_progress', 'new')
                ->with(['lead.quotations.items']) // Keep the eager loading but don't require it
                ->get()
                ->filter(function ($renewal) use ($startDate, $endDate) {
                    // Check if renewal company has expiring licenses within date range
                    $hasExpiringLicense = RenewalDataMyr::where('f_company_id', $renewal->f_company_id)
                        ->whereBetween('f_expiry_date', [$startDate, $endDate])
                        ->where('f_currency', 'MYR')
                        ->exists();

                    return $hasExpiringLicense;
                });

            $totalCompanies = $renewals->count();
            $totalInvoices = 0;
            $totalAmount = 0;
            $totalViaResellerCount = 0;
            $totalViaEndUserCount = 0;
            $totalViaResellerAmount = 0;
            $totalViaEndUserAmount = 0;

            foreach ($renewals as $renewal) {
                // Only process quotation data if renewal has lead and lead exists
                if ($renewal->lead_id && $renewal->lead) {
                    // Get final renewal quotations for this lead (if they exist)
                    $renewalQuotations = $renewal->lead->quotations()
                        ->where('mark_as_final', true)
                        ->where('sales_type', 'RENEWAL SALES')
                        ->get();

                    // If quotations exist, count them and calculate amount
                    if ($renewalQuotations->isNotEmpty()) {
                        $totalInvoices += $renewalQuotations->count();

                        // Calculate amount from quotations
                        $quotationAmount = 0;
                        foreach ($renewalQuotations as $quotation) {
                            $quotationAmount += $quotation->items->sum('total_before_tax');
                        }

                        $totalAmount += $quotationAmount;

                        // Check if company has reseller for amount calculation
                        $reseller = RenewalDataMyr::getResellerForCompany($renewal->f_company_id);
                        if ($reseller && $reseller->f_rate) {
                            $totalViaResellerAmount += $quotationAmount;
                        } else {
                            $totalViaEndUserAmount += $quotationAmount;
                        }
                    }
                }

                // Always count companies regardless of lead mapping or quotation existence
                $reseller = RenewalDataMyr::getResellerForCompany($renewal->f_company_id);
                if ($reseller && $reseller->f_rate) {
                    $totalViaResellerCount++;
                } else {
                    $totalViaEndUserCount++;
                }
            }

            return [
                'total_companies' => $totalCompanies,
                'total_invoices' => $totalInvoices,
                'total_amount' => $totalAmount,
                'total_via_reseller' => $totalViaResellerCount,
                'total_via_end_user' => $totalViaEndUserCount,
                'total_via_reseller_amount' => $totalViaResellerAmount,
                'total_via_end_user_amount' => $totalViaEndUserAmount,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching new renewal stats: '.$e->getMessage());

            return [
                'total_companies' => 0,
                'total_invoices' => 0,
                'total_amount' => 0,
                'total_via_reseller' => 0,
                'total_via_end_user' => 0,
                'total_via_reseller_amount' => 0,
                'total_via_end_user_amount' => 0,
            ];
        }
    }

    protected function getPendingConfirmationStats($startDate = null, $endDate = null)
    {
        try {
            // Set default date range if not provided
            if (!$startDate || !$endDate) {
                $today = Carbon::now()->format('Y-m-d');
                $next60Days = Carbon::now()->addDays(60)->format('Y-m-d');
                $startDate = $today;
                $endDate = $next60Days;
            }

            // Get renewals with pending_confirmation status that fall within date range
            $renewals = Renewal::where('renewal_progress', 'pending_confirmation')
                ->with(['lead.quotations.items']) // Keep the eager loading but don't require it
                ->get()
                ->filter(function ($renewal) use ($startDate, $endDate) {
                    // Check if renewal company has expiring licenses within date range
                    $hasExpiringLicense = RenewalDataMyr::where('f_company_id', $renewal->f_company_id)
                        ->whereBetween('f_expiry_date', [$startDate, $endDate])
                        ->where('f_currency', 'MYR')
                        ->exists();

                    return $hasExpiringLicense;
                });

            $totalCompanies = $renewals->count();
            $totalInvoices = 0;
            $totalAmount = 0;
            $totalViaResellerCount = 0;
            $totalViaEndUserCount = 0;
            $totalViaResellerAmount = 0;
            $totalViaEndUserAmount = 0;

            foreach ($renewals as $renewal) {
                // Only process quotation data if renewal has lead and lead exists
                if ($renewal->lead_id && $renewal->lead) {
                    // Get final renewal quotations for this lead (if they exist)
                    $renewalQuotations = $renewal->lead->quotations()
                        ->where('mark_as_final', true)
                        ->where('sales_type', 'RENEWAL SALES')
                        ->get();

                    // If quotations exist, count them and calculate amount
                    if ($renewalQuotations->isNotEmpty()) {
                        $totalInvoices += $renewalQuotations->count();

                        // Calculate amount from quotations
                        $quotationAmount = 0;
                        foreach ($renewalQuotations as $quotation) {
                            $quotationAmount += $quotation->items->sum('total_before_tax');
                        }

                        $totalAmount += $quotationAmount;

                        // Check if company has reseller for amount calculation
                        $reseller = RenewalDataMyr::getResellerForCompany($renewal->f_company_id);
                        if ($reseller && $reseller->f_rate) {
                            $totalViaResellerAmount += $quotationAmount;
                        } else {
                            $totalViaEndUserAmount += $quotationAmount;
                        }
                    }
                }

                // Always count companies regardless of lead mapping or quotation existence
                $reseller = RenewalDataMyr::getResellerForCompany($renewal->f_company_id);
                if ($reseller && $reseller->f_rate) {
                    $totalViaResellerCount++;
                } else {
                    $totalViaEndUserCount++;
                }
            }

            return [
                'total_companies' => $totalCompanies,
                'total_invoices' => $totalInvoices,
                'total_amount' => $totalAmount,
                'total_via_reseller' => $totalViaResellerCount,
                'total_via_end_user' => $totalViaEndUserCount,
                'total_via_reseller_amount' => $totalViaResellerAmount,
                'total_via_end_user_amount' => $totalViaEndUserAmount,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching pending confirmation stats: '.$e->getMessage());

            return [
                'total_companies' => 0,
                'total_invoices' => 0,
                'total_amount' => 0,
                'total_via_reseller' => 0,
                'total_via_end_user' => 0,
                'total_via_reseller_amount' => 0,
                'total_via_end_user_amount' => 0,
            ];
        }
    }

    protected function getPendingPaymentStats($startDate = null, $endDate = null)
    {
        try {
            // Set default date range if not provided
            if (!$startDate || !$endDate) {
                $today = Carbon::now()->format('Y-m-d');
                $next60Days = Carbon::now()->addDays(60)->format('Y-m-d');
                $startDate = $today;
                $endDate = $next60Days;
            }

            // Get renewals with pending_payment status that fall within date range
            $renewals = Renewal::where('renewal_progress', 'pending_payment')
                ->with(['lead.quotations.items']) // Keep the eager loading but don't require it
                ->get()
                ->filter(function ($renewal) use ($startDate, $endDate) {
                    // Check if renewal company has expiring licenses within date range
                    $hasExpiringLicense = RenewalDataMyr::where('f_company_id', $renewal->f_company_id)
                        ->whereBetween('f_expiry_date', [$startDate, $endDate])
                        ->where('f_currency', 'MYR')
                        ->exists();

                    return $hasExpiringLicense;
                });

            $totalCompanies = $renewals->count();
            $totalInvoices = 0;
            $totalAmount = 0;
            $totalViaResellerCount = 0;
            $totalViaEndUserCount = 0;
            $totalViaResellerAmount = 0;
            $totalViaEndUserAmount = 0;

            foreach ($renewals as $renewal) {
                // Only process quotation data if renewal has lead and lead exists
                if ($renewal->lead_id && $renewal->lead) {
                    // Get final renewal quotations for this lead (if they exist)
                    $renewalQuotations = $renewal->lead->quotations()
                        ->where('mark_as_final', true)
                        ->where('sales_type', 'RENEWAL SALES')
                        ->get();

                    // If quotations exist, count them and calculate amount
                    if ($renewalQuotations->isNotEmpty()) {
                        $totalInvoices += $renewalQuotations->count();

                        // Calculate amount from quotations
                        $quotationAmount = 0;
                        foreach ($renewalQuotations as $quotation) {
                            $quotationAmount += $quotation->items->sum('total_before_tax');
                        }

                        $totalAmount += $quotationAmount;

                        // Check if company has reseller for amount calculation
                        $reseller = RenewalDataMyr::getResellerForCompany($renewal->f_company_id);
                        if ($reseller && $reseller->f_rate) {
                            $totalViaResellerAmount += $quotationAmount;
                        } else {
                            $totalViaEndUserAmount += $quotationAmount;
                        }
                    }
                }

                // Always count companies regardless of lead mapping or quotation existence
                $reseller = RenewalDataMyr::getResellerForCompany($renewal->f_company_id);
                if ($reseller && $reseller->f_rate) {
                    $totalViaResellerCount++;
                } else {
                    $totalViaEndUserCount++;
                }
            }

            return [
                'total_companies' => $totalCompanies,
                'total_invoices' => $totalInvoices,
                'total_amount' => $totalAmount,
                'total_via_reseller' => $totalViaResellerCount,
                'total_via_end_user' => $totalViaEndUserCount,
                'total_via_reseller_amount' => $totalViaResellerAmount,
                'total_via_end_user_amount' => $totalViaEndUserAmount,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching pending payment stats: '.$e->getMessage());

            return [
                'total_companies' => 0,
                'total_invoices' => 0,
                'total_amount' => 0,
                'total_via_reseller' => 0,
                'total_via_end_user' => 0,
                'total_via_reseller_amount' => 0,
                'total_via_end_user_amount' => 0,
            ];
        }
    }

    protected function getCompletedRenewalStats($startDate = null, $endDate = null)
    {
        try {
            // Set default date range if not provided
            if (!$startDate || !$endDate) {
                $today = Carbon::now()->format('Y-m-d');
                $next60Days = Carbon::now()->addDays(60)->format('Y-m-d');
                $startDate = $today;
                $endDate = $next60Days;
            }

            // Get renewals with completed_renewal status that fall within date range
            $renewals = Renewal::where('renewal_progress', 'completed_renewal')
                ->with(['lead.quotations.items']) // Keep the eager loading but don't require it
                ->get()
                ->filter(function ($renewal) use ($startDate, $endDate) {
                    // Check if renewal company has expiring licenses within date range
                    $hasExpiringLicense = RenewalDataMyr::where('f_company_id', $renewal->f_company_id)
                        ->whereBetween('f_expiry_date', [$startDate, $endDate])
                        ->where('f_currency', 'MYR')
                        ->exists();

                    return $hasExpiringLicense;
                });

            $totalCompanies = $renewals->count();
            $totalInvoices = 0;
            $totalAmount = 0;
            $totalViaResellerCount = 0;
            $totalViaEndUserCount = 0;
            $totalViaResellerAmount = 0;
            $totalViaEndUserAmount = 0;

            foreach ($renewals as $renewal) {
                // Only process quotation data if renewal has lead and lead exists
                if ($renewal->lead_id && $renewal->lead) {
                    // Get final renewal quotations for this lead (if they exist)
                    $renewalQuotations = $renewal->lead->quotations()
                        ->where('mark_as_final', true)
                        ->where('sales_type', 'RENEWAL SALES')
                        ->get();

                    // If quotations exist, count them and calculate amount
                    if ($renewalQuotations->isNotEmpty()) {
                        $totalInvoices += $renewalQuotations->count();

                        // Calculate amount from quotations
                        $quotationAmount = 0;
                        foreach ($renewalQuotations as $quotation) {
                            $quotationAmount += $quotation->items->sum('total_before_tax');
                        }

                        $totalAmount += $quotationAmount;

                        // Check if company has reseller for amount calculation
                        $reseller = RenewalDataMyr::getResellerForCompany($renewal->f_company_id);
                        if ($reseller && $reseller->f_rate) {
                            $totalViaResellerAmount += $quotationAmount;
                        } else {
                            $totalViaEndUserAmount += $quotationAmount;
                        }
                    }
                }

                // Always count companies regardless of lead mapping or quotation existence
                $reseller = RenewalDataMyr::getResellerForCompany($renewal->f_company_id);
                if ($reseller && $reseller->f_rate) {
                    $totalViaResellerCount++;
                } else {
                    $totalViaEndUserCount++;
                }
            }

            return [
                'total_companies' => $totalCompanies,
                'total_invoices' => $totalInvoices,
                'total_amount' => $totalAmount,
                'total_via_reseller' => $totalViaResellerCount,
                'total_via_end_user' => $totalViaEndUserCount,
                'total_via_reseller_amount' => $totalViaResellerAmount,
                'total_via_end_user_amount' => $totalViaEndUserAmount,
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching completed renewal stats: '.$e->getMessage());

            return [
                'total_companies' => 0,
                'total_invoices' => 0,
                'total_amount' => 0,
                'total_via_reseller' => 0,
                'total_via_end_user' => 0,
                'total_via_reseller_amount' => 0,
                'total_via_end_user_amount' => 0,
            ];
        }
    }

    public static function getRenewalForecastStats($startDate = null, $endDate = null)
    {
        try {
            // Create an instance to access the methods
            $instance = new self;

            // Get stats from existing methods with date filtering
            $newStats = $instance->getNewStats($startDate, $endDate);
            $pendingConfirmationStats = $instance->getPendingConfirmationStats($startDate, $endDate);
            $pendingPaymentStats = $instance->getPendingPaymentStats($startDate, $endDate);

            // Add them together
            return [
                'total_companies' => $newStats['total_companies'] + $pendingConfirmationStats['total_companies'],
                'total_invoices' => $newStats['total_invoices'] + $pendingConfirmationStats['total_invoices'],
                'total_amount' => $newStats['total_amount'] + $pendingConfirmationStats['total_amount'],
                'total_via_reseller' => $newStats['total_via_reseller'] + $pendingConfirmationStats['total_via_reseller'],
                'total_via_end_user' => $newStats['total_via_end_user'] + $pendingConfirmationStats['total_via_end_user'],
                'total_via_reseller_amount' => $newStats['total_via_reseller_amount'] + $pendingConfirmationStats['total_via_reseller_amount'],
                'total_via_end_user_amount' => $newStats['total_via_end_user_amount'] + $pendingConfirmationStats['total_via_end_user_amount'],
            ];
        } catch (\Exception $e) {
            Log::error('Error fetching renewal forecast stats: '.$e->getMessage());

            return [
                'total_companies' => 0,
                'total_invoices' => 0,
                'total_amount' => 0,
                'total_via_reseller' => 0,
                'total_via_end_user' => 0,
                'total_via_reseller_amount' => 0,
                'total_via_end_user_amount' => 0,
            ];
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $baseQuery = RenewalDataMyr::query();

                // Only show records where expiry date has not yet passed
                $today = Carbon::now()->format('Y-m-d');
                $baseQuery->whereRaw('f_expiry_date >= ?', [$today]);

                // Only show MYR currency
                $baseQuery->where('f_currency', '=', 'MYR');

                // Apply product exclusions using the helper method
                RenewalDataMyr::applyProductExclusions($baseQuery);

                // Exclude terminated renewals from the main view
                $terminatedCompanyIds = Renewal::where('renewal_progress', 'terminated')
                    ->pluck('f_company_id')
                    ->toArray();

                if (! empty($terminatedCompanyIds)) {
                    $baseQuery->whereNotIn('f_company_id', $terminatedCompanyIds);
                }

                // Apply aggregation - GROUP BY invoice to avoid duplicate amounts
                $baseQuery->selectRaw('
                    f_company_id,
                    ANY_VALUE(f_company_name) AS f_company_name,
                    ANY_VALUE(f_currency) AS f_currency,
                    SUM(DISTINCT f_total_amount) AS total_amount,
                    SUM(f_unit) AS total_units,
                    COUNT(*) AS total_products,
                    COUNT(DISTINCT f_invoice_no) AS total_invoices,
                    MIN(f_expiry_date) AS earliest_expiry,
                    ANY_VALUE(f_created_time) AS f_created_time
                ')
                    ->groupBy('f_company_id')
                    ->havingRaw('COUNT(*) > 0');

                return $baseQuery;
            })
            ->filters([
                SelectFilter::make('f_name')
                    ->label('Products')
                    ->multiple()
                    ->preload()
                    ->options(function () {
                        $today = Carbon::now()->format('Y-m-d');
                        $query = RenewalDataMyr::query()
                            ->whereRaw('f_expiry_date >= ?', [$today])
                            ->where('f_currency', '=', 'MYR');

                        // Apply product exclusions
                        RenewalDataMyr::applyProductExclusions($query);

                        return $query->distinct()
                            ->orderBy('f_name')
                            ->pluck('f_name', 'f_name')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['values'])) {
                            $subQuery = RenewalDataMyr::query()
                                ->select('f_company_id')
                                ->whereIn('f_name', $data['values'])
                                ->whereRaw('f_expiry_date >= ?', [Carbon::now()->format('Y-m-d')])
                                ->where('f_currency', '=', 'MYR');

                            // Apply product exclusions
                            RenewalDataMyr::applyProductExclusions($subQuery);

                            $subQuery->distinct();

                            $query->whereIn('f_company_id', $subQuery);
                        }
                    })
                    ->indicator('Products'),

                SelectFilter::make('product_group')
                    ->label('Product Group')
                    ->options([
                        'timetec_hr' => 'TimeTec HR',
                        'non_timetec_hr' => 'Non-TimeTec HR',
                        'other_division' => 'Other Division',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            $mapping = $this->getProductGroupMapping();
                            $selectedProducts = $mapping[$data['value']] ?? [];

                            if (! empty($selectedProducts)) {
                                // Get company IDs that have products in the selected group
                                $subQuery = RenewalDataMyr::query()
                                    ->select('f_company_id')
                                    ->whereRaw('f_expiry_date >= ?', [Carbon::now()->format('Y-m-d')])
                                    ->where('f_currency', '=', 'MYR')
                                    ->where(function ($q) use ($selectedProducts) {
                                        foreach ($selectedProducts as $product) {
                                            $q->orWhere('f_name', 'LIKE', '%'.$product.'%');
                                        }
                                    });

                                // Apply product exclusions to subquery
                                RenewalDataMyr::applyProductExclusions($subQuery);

                                $subQuery->distinct();

                                $query->whereIn('f_company_id', $subQuery);
                            }
                        }
                    })
                    ->indicator('Product Group'),

                Filter::make('earliest_expiry')
                    ->form([
                        DateRangePicker::make('date_range')
                            ->label('Expiry Date Range')
                            ->placeholder('Select expiry date range')
                            ->default(function () {
                                $today = Carbon::now()->format('d/m/Y');
                                $next60Days = Carbon::now()->addDays(60)->format('d/m/Y');

                                return $today.' - '.$next60Days;
                            }),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (empty($data['date_range'])) {
                            $today = Carbon::now()->format('Y-m-d');
                            $next60Days = Carbon::now()->addDays(60)->format('Y-m-d');
                            $query->whereBetween('f_expiry_date', [$today, $next60Days]);
                        } else {
                            try {
                                [$start, $end] = explode(' - ', $data['date_range']);
                                $startDate = Carbon::createFromFormat('d/m/Y', trim($start))->startOfDay()->format('Y-m-d');
                                $endDate = Carbon::createFromFormat('d/m/Y', trim($end))->endOfDay()->format('Y-m-d');

                                $today = Carbon::now()->format('Y-m-d');
                                if ($startDate < $today) {
                                    $startDate = $today;
                                }

                                $query->whereBetween('f_expiry_date', [$startDate, $endDate]);
                            } catch (\Exception $e) {
                                Log::error('Date filter error: '.$e->getMessage());
                                $today = Carbon::now()->format('Y-m-d');
                                $next60Days = Carbon::now()->addDays(60)->format('Y-m-d');
                                $query->whereBetween('f_expiry_date', [$today, $next60Days]);
                            }
                        }
                    })
                    ->indicateUsing(function (array $data) {
                        if (! empty($data['date_range'])) {
                            [$start, $end] = explode(' - ', $data['date_range']);

                            return 'Expiry: '.
                                Carbon::createFromFormat('d/m/Y', trim($start))->format('j M Y').
                                ' → '.
                                Carbon::createFromFormat('d/m/Y', trim($end))->format('j M Y');
                        }

                        return 'Expiry: '.
                            Carbon::now()->format('j M Y').
                            ' → '.
                            Carbon::now()->addDays(60)->format('j M Y').
                            ' (Default 60 days)';
                    }),
                SelectFilter::make('renewal_progress')
                    ->multiple()
                    ->label('Renewal Progress')
                    ->options([
                        'new' => 'New',
                        'pending_confirmation' => 'Pending Confirmation',
                        'pending_payment' => 'Pending Payment',
                        'completed_renewal' => 'Completed Payment',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['values'])) {
                            // Get company IDs with the selected renewal progress
                            $companyIds = Renewal::whereIn('renewal_progress', $data['values'])
                                ->pluck('f_company_id')
                                ->toArray();

                            if (! empty($companyIds)) {
                                $query->whereIn('f_company_id', $companyIds);
                            } else {
                                // If no companies found with this progress, return empty result
                                $query->where('f_company_id', -1);
                            }
                        }
                    })
                    ->indicator('Renewal Progress'),

                SelectFilter::make('admin_renewal')
                    ->label('Admin Renewal')
                    ->options(function () {
                        // Get all unique admin_renewal values from the database
                        $adminRenewals = Renewal::whereNotNull('admin_renewal')
                            ->distinct()
                            ->pluck('admin_renewal')
                            ->sort()
                            ->mapWithKeys(function ($name) {
                                return [$name => $name];
                            })
                            ->toArray();

                        // Add the "Unassigned" option
                        return ['unassigned' => 'Unassigned'] + $adminRenewals;
                    })
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            if ($data['value'] === 'unassigned') {
                                // For unassigned, include companies that either:
                                // 1. Don't have a renewal record, OR
                                // 2. Have admin_renewal as NULL
                                $assignedCompanyIds = Renewal::whereNotNull('admin_renewal')
                                    ->pluck('f_company_id')
                                    ->toArray();

                                if (! empty($assignedCompanyIds)) {
                                    $query->whereNotIn('f_company_id', $assignedCompanyIds);
                                }
                            } else {
                                // For specific admin assignments
                                $companyIds = Renewal::where('admin_renewal', $data['value'])
                                    ->pluck('f_company_id')
                                    ->toArray();

                                if (! empty($companyIds)) {
                                    $query->whereIn('f_company_id', $companyIds);
                                } else {
                                    // If no companies found with this admin, return empty result
                                    $query->where('f_company_id', -1);
                                }
                            }
                        }
                    })
                    ->indicator('Admin Renewal'),

                SelectFilter::make('reseller_status')
                    ->label('Reseller Status')
                    ->options([
                        'with_reseller' => 'With Reseller',
                        'without_reseller' => 'Without Reseller',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! empty($data['value'])) {
                            if ($data['value'] === 'with_reseller') {
                                // Get company IDs that have resellers
                                $resellerCompanyIds = DB::connection('frontenddb')
                                    ->table('crm_reseller_link')
                                    ->pluck('f_id')
                                    ->toArray();

                                if (! empty($resellerCompanyIds)) {
                                    $query->whereIn('f_company_id', $resellerCompanyIds);
                                } else {
                                    // If no resellers found, return empty result
                                    $query->where('f_company_id', -1);
                                }
                            } elseif ($data['value'] === 'without_reseller') {
                                // Get company IDs that don't have resellers
                                $resellerCompanyIds = DB::connection('frontenddb')
                                    ->table('crm_reseller_link')
                                    ->pluck('f_id')
                                    ->toArray();

                                if (! empty($resellerCompanyIds)) {
                                    $query->whereNotIn('f_company_id', $resellerCompanyIds);
                                }
                                // If no resellers exist at all, all companies are without resellers (no additional filter needed)
                            }
                        }
                    })
                    ->indicator('Reseller Status'),
            ])
            ->filtersFormColumns(3)
            ->columns([
                Split::make([
                    Stack::make([
                        TextColumn::make('f_company_name')
                            ->label('Company')
                            ->searchable()
                            ->formatStateUsing(fn (string $state): string => strtoupper($state))
                            ->weight('bold')
                            ->alignLeft()
                            ->grow()
                    ]),

                    Stack::make([
                        TextColumn::make('earliest_expiry')
                            ->alignCenter()
                            ->label('Expiry Date')
                            ->date('d M Y')
                            ->color(function ($state) {
                                if (!$state) return 'gray';

                                $today = Carbon::now();
                                $expiryDate = Carbon::parse($state);

                                // Color coding based on how close to expiry
                                if ($expiryDate->isToday()) {
                                    return 'danger'; // Expires today
                                } elseif ($expiryDate->diffInDays($today) <= 7) {
                                    return 'warning'; // Expires within a week
                                } elseif ($expiryDate->diffInDays($today) <= 30) {
                                    return 'info'; // Expires within a month
                                }

                                return 'gray'; // More than a month
                            }),
                    ]),

                    Stack::make([
                        TextColumn::make('f_company_id')
                            ->label('Renewal Progress')
                            ->formatStateUsing(function ($state, $record) {
                                if (!$state || !$record) return '';

                                $renewal = Renewal::where('f_company_id', $state)->first();

                                if (! $renewal || ! $renewal->renewal_progress) {
                                    return '';
                                }

                                return match ($renewal->renewal_progress) {
                                    'new' => 'New',
                                    'pending_confirmation' => 'Pending Confirmation',
                                    'pending_payment' => 'Pending Payment',
                                    'completed_renewal' => 'Completed Payment',
                                    default => ucfirst(str_replace('_', ' ', $renewal->renewal_progress))
                                };
                            })
                            ->badge()
                            ->alignLeft()
                            ->color(function ($state, $record) {
                                if (!$state || !$record) return 'gray';

                                $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                                if (! $renewal || ! $renewal->renewal_progress) {
                                    return 'gray';
                                }

                                return match ($renewal->renewal_progress) {
                                    'new' => 'info',                    // Blue
                                    'pending_confirmation' => 'warning', // Yellow
                                    'pending_payment' => 'danger',      // Red
                                    'completed_renewal' => 'success',           // Green
                                    default => 'gray'
                                };
                            })
                            ->visible(function ($state, $record) {
                                if (!$state || !$record) return false;

                                $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();
                                return $renewal;
                            }),
                    ]),

                    Stack::make([
                        TextColumn::make('total_amount')
                            ->label('Amount')
                            ->alignRight()
                            ->formatStateUsing(function ($state, $record) {
                                if (!$state || !$record) return '0.00';

                                // Get the renewal record to find the lead_id
                                $renewal = \App\Models\Renewal::where('f_company_id', $record->f_company_id)->first();

                                // Check if renewal exists and has lead_id
                                if (!$renewal || !$renewal->lead_id) {
                                    return '0.00';
                                }

                                // Get the lead and find renewal sales quotations
                                $lead = \App\Models\Lead::find($renewal->lead_id);

                                if (!$lead) {
                                    return '0.00';
                                }

                                // Get final renewal quotations for this lead
                                $renewalQuotations = $lead->quotations()
                                    ->where('mark_as_final', true)
                                    ->where('sales_type', 'RENEWAL SALES')
                                    ->get();

                                if ($renewalQuotations->isEmpty()) {
                                    return '0.00';
                                }

                                // Calculate total amount from renewal quotations
                                $totalAmount = 0;
                                foreach ($renewalQuotations as $quotation) {
                                    $totalAmount += $quotation->items->sum('total_before_tax');
                                }

                                return number_format($totalAmount, 2);
                            }),
                    ]),

                    Stack::make([
                        TextColumn::make('f_company_id')
                            ->label('Reseller')
                            ->formatStateUsing(function ($state, $record) {
                                if (!$state || !$record) return '';

                                $reseller = RenewalDataMyr::getResellerForCompany($state);
                                return $reseller ? 'Reseller' : '';
                            })
                            ->badge()
                            ->alignRight()
                            ->color('danger')
                            ->tooltip(function ($state, $record) {
                                if (!$state || !$record) return null;

                                $reseller = RenewalDataMyr::getResellerForCompany($record->f_company_id);

                                if (! $reseller) {
                                    return null;
                                }

                                $tooltipText = strtoupper("{$reseller->reseller_name}");
                                return new HtmlString($tooltipText);
                            })
                            ->visible(function ($state, $record) {
                                if (!$state || !$record) return false;

                                $reseller = RenewalDataMyr::getResellerForCompany($record->f_company_id);
                                return $reseller !== null;
                            }),
                    ]),
                ])->from('sm'),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view_lead_details')
                        ->label('View Lead Details')
                        ->icon('heroicon-o-eye')
                        ->color('info')
                        ->url(function ($record) {
                            $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                            if ($renewal && $renewal->lead_id) {
                                return route('filament.admin.resources.leads.view', [
                                    'record' => \App\Classes\Encryptor::encrypt($renewal->lead_id),
                                ]);
                            }

                            return null;
                        })
                        ->openUrlInNewTab()
                        ->visible(function ($record) {
                            // Only show if mapping is completed and lead_id exists
                            $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                            return $renewal &&
                                $renewal->mapping_status === 'completed_mapping' &&
                                $renewal->lead_id;
                        }),

                    Action::make('assign_to_me')
                        ->label('Assign to Me')
                        ->icon('heroicon-o-user')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Assign Renewal to Me')
                        ->modalDescription(fn ($record) => "Are you sure you want to assign the renewal for {$record->f_company_name} to yourself?")
                        ->modalSubmitActionLabel('Yes, Assign to Me')
                        ->modalCancelActionLabel('Cancel')
                        ->visible(function ($record) {
                            // Only show after mapping is completed AND no one is assigned yet
                            $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                            return $renewal &&
                                $renewal->mapping_status === 'completed_mapping' &&
                                $renewal->admin_renewal === null;
                        })
                        ->action(function ($record) {
                            try {
                                // Update or create renewal record with current user
                                Renewal::updateOrCreate(
                                    ['f_company_id' => $record->f_company_id],
                                    [
                                        'admin_renewal' => auth()->user()->name,
                                        'company_name' => $record->f_company_name,
                                    ]
                                );

                                Notification::make()
                                    ->success()
                                    ->title('Assignment Successful')
                                    ->body("Renewal for {$record->f_company_name} has been assigned to you.")
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Error assigning renewal: '.$e->getMessage());

                                Notification::make()
                                    ->danger()
                                    ->title('Assignment Failed')
                                    ->body('There was an error assigning the renewal. Please try again.')
                                    ->send();
                            }
                        }),

                    Action::make('assign_to_admin')
                        ->label('Assign to Admin Renewal')
                        ->icon('heroicon-o-user')
                        ->color('info')
                        ->form([
                            Select::make('admin_renewal')
                                ->label('Select Admin Renewal')
                                ->options([
                                    'Fatimah Nurnabilah' => 'Fatimah Nurnabilah',
                                ])
                                ->required()
                                ->placeholder('Select an admin'),
                        ])
                        ->action(function ($record, array $data) {
                            try {
                                // Update or create renewal record with selected admin
                                Renewal::updateOrCreate(
                                    ['f_company_id' => $record->f_company_id],
                                    [
                                        'admin_renewal' => $data['admin_renewal'],
                                        'company_name' => $record->f_company_name,
                                    ]
                                );

                                Notification::make()
                                    ->success()
                                    ->title('Assignment Successful')
                                    ->body("Renewal for {$record->f_company_name} has been assigned to {$data['admin_renewal']}.")
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Error assigning renewal: '.$e->getMessage());

                                Notification::make()
                                    ->danger()
                                    ->title('Assignment Failed')
                                    ->body('There was an error assigning the renewal. Please try again.')
                                    ->send();
                            }
                        })
                        ->modalHeading('Assign to Admin Renewal')
                        ->modalDescription(fn ($record) => "Select an admin to assign the renewal for {$record->f_company_name}.")
                        ->modalSubmitActionLabel('Assign')
                        ->modalCancelActionLabel('Cancel')
                        ->visible(function ($record) {
                            // Only show after mapping is completed AND no one is assigned yet
                            $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                            return $renewal &&
                                $renewal->mapping_status === 'completed_mapping' &&
                                $renewal->admin_renewal === null;
                        }),

                    Action::make('mapping_action')
                        ->label('Mapping')
                        ->icon('heroicon-o-link')
                        ->color('warning')
                        ->fillForm(function ($record) {
                            return [
                                'company_name' => $record->f_company_name,
                                'name' => '-',  // Default dash
                                'email' => 'fatimah.tarmizi@timeteccloud.com',  // Default email
                                'phone' => '0',  // Default dash for phone
                                'company_size' => '1-24',  // Default to SMALL
                                'country' => 'MYS',  // Default to MALAYSIA
                                'lead_source' => 'Existing Customer (Migration)',
                                'products' => ['hr'], // This matches the 'hr' key from CreateLead
                            ];
                        })
                        ->form([
                            Select::make('mapping_type')
                                ->label('Mapping Type')
                                ->options([
                                    'before_handover' => 'Before Software Handover',
                                    'after_handover' => 'After Software Handover',
                                    'onhold' => 'OnHold Mapping',
                                ])
                                ->required()
                                ->reactive(),

                            // Show Lead ID field for after handover
                            Select::make('lead_id')
                                ->label('Select Lead')
                                ->searchable()
                                ->preload()
                                ->options(function () {
                                    return Lead::with('companyDetail')
                                        ->get()
                                        ->mapWithKeys(function ($lead) {
                                            $companyName = $lead->companyDetail
                                                ? $lead->companyDetail->company_name
                                                : 'Unknown Company';

                                            $leadIdFormatted = str_pad($lead->id, 5, '0', STR_PAD_LEFT);

                                            return [
                                                $lead->id => "Lead {$leadIdFormatted} - {$companyName}",
                                            ];
                                        })
                                        ->toArray();
                                })
                                ->placeholder('Select a closed lead to map')
                                ->visible(fn ($get) => $get('mapping_type') === 'after_handover')
                                ->required(fn ($get) => $get('mapping_type') === 'after_handover')
                                ->getSearchResultsUsing(function (string $search) {
                                    return Lead::with('companyDetail')
                                        ->where(function ($query) use ($search) {
                                            $query->where('id', 'like', "%{$search}%")
                                                ->orWhereHas('companyDetail', function ($q) use ($search) {
                                                    $q->where('company_name', 'like', "%{$search}%");
                                                });
                                        })
                                        ->get()
                                        ->mapWithKeys(function ($lead) {
                                            $companyName = $lead->companyDetail
                                                ? $lead->companyDetail->company_name
                                                : 'Unknown Company';

                                            $leadIdFormatted = str_pad($lead->id, 5, '0', STR_PAD_LEFT);

                                            return [
                                                $lead->id => "Lead {$leadIdFormatted} - {$companyName}",
                                            ];
                                        })
                                        ->toArray();
                                })
                                ->getOptionLabelUsing(function ($value) {
                                    $lead = Lead::with('companyDetail')->find($value);

                                    if (! $lead) {
                                        return 'Lead not found';
                                    }

                                    $companyName = $lead->companyDetail
                                        ? $lead->companyDetail->company_name
                                        : 'Unknown Company';

                                    $leadIdFormatted = str_pad($lead->id, 5, '0', STR_PAD_LEFT);

                                    return "Lead {$leadIdFormatted} - {$companyName}";
                                }),

                            // Show Create Lead form for before handover - following CreateLead.php exactly
                            Grid::make(2)
                                ->schema([
                                    TextInput::make('company_name')
                                        ->label('Company Name')
                                        ->required()
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),

                                    TextInput::make('name')
                                        ->label('Name')
                                        ->required()
                                        ->extraInputAttributes(['style' => 'text-transform: uppercase'])
                                        ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),

                                    TextInput::make('email')
                                        ->label('Work Email Address')
                                        ->email()
                                        ->required()
                                        ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),

                                    PhoneInput::make('phone')
                                        ->label('Phone Number')
                                        ->required()
                                        ->suffixAction(
                                            \Filament\Forms\Components\Actions\Action::make('searchPhone')
                                                ->label('Verify')
                                                ->icon('heroicon-o-magnifying-glass')
                                                ->color('primary')
                                                ->action(function ($state, $set, $livewire) {
                                                    if (empty($state)) {
                                                        $set('phone_helper_text', 'Please enter a phone number to verify');

                                                        return;
                                                    }

                                                    // Show loading state
                                                    $set('phone_search_loading', true);

                                                    // Use sleep for visual effect
                                                    usleep(800000); // 0.8 second delay

                                                    // Remove the "+" symbol from the phone number for searching
                                                    $searchPhone = ltrim($state, '+');

                                                    // Check if phone already exists in the Lead table
                                                    $existingLeadsWithPhone = \App\Models\Lead::where('phone', $searchPhone)->get();

                                                    // If exists, set helper text with found lead details
                                                    if ($existingLeadsWithPhone->isNotEmpty()) {
                                                        $duplicateInfo = $existingLeadsWithPhone->map(function ($lead) {
                                                            $companyName = $lead->companyDetail ? $lead->companyDetail->company_name : 'Unknown Company';

                                                            return "• {$companyName} (Lead ID: ".str_pad($lead->id, 5, '0', STR_PAD_LEFT).')';
                                                        })->implode("\n");

                                                        // Store as plain string with HTML markup
                                                        $set('phone_helper_text', '<span style="color:red;">⚠️ This phone number is already in use:</span><br>'.nl2br(htmlspecialchars($duplicateInfo)));
                                                    } else {
                                                        // Store as plain string with HTML markup
                                                        $set('phone_helper_text', '<span style="color:green;">✓ Phone number is unique</span>');
                                                    }

                                                    // Reset loading state
                                                    $set('phone_search_loading', false);
                                                })
                                        )
                                        ->helperText(function (callable $get) {
                                            if ($get('phone_search_loading')) {
                                                return 'Verifying phone number...';
                                            }

                                            // Get the helper text which is now stored as a string with HTML markup
                                            $helperText = $get('phone_helper_text');

                                            // Convert it to HtmlString only when rendering, not when storing
                                            return $helperText ? new HtmlString($helperText) : null;
                                        })
                                        ->dehydrateStateUsing(function ($state) {
                                            // Remove the "+" symbol from the phone number
                                            return ltrim($state, '+');
                                        })
                                        ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),

                                    Select::make('company_size')
                                        ->label('Company Size')
                                        ->options([
                                            '1-24' => '1 - 24',
                                            '25-99' => '25 - 99',
                                            '100-500' => '100 - 500',
                                            '501 and Above' => '501 and Above',
                                        ])
                                        ->required()
                                        ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),

                                    Select::make('country')
                                        ->label('Country')
                                        ->searchable()
                                        ->required()
                                        ->default('MYS')
                                        ->options(function () {
                                            $filePath = storage_path('app/public/json/CountryCodes.json');

                                            if (file_exists($filePath)) {
                                                $countriesContent = file_get_contents($filePath);
                                                $countries = json_decode($countriesContent, true);

                                                return collect($countries)->mapWithKeys(function ($country) {
                                                    return [$country['Code'] => ucfirst(strtolower($country['Country']))];
                                                })->toArray();
                                            }

                                            return [];
                                        })
                                        ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),
                                ])
                                ->visible(fn ($get) => $get('mapping_type') === 'before_handover'),
                        ])
                        ->action(function ($record, array $data) {
                            return $this->handleMappingAction($record, $data);
                        })
                        ->visible(function ($record) {
                            $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                            if (! $renewal) {
                                return true;
                            }

                            return $renewal->mapping_status !== 'completed_mapping';
                        })
                        ->modalWidth('5xl')
                        ->modalHeading(fn ($record) => 'Mapping Action - '.$record->f_company_name),
                    Action::make('completed_follow_up')
                        ->label('Completed Follow Up')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Follow Up as Completed')
                        ->modalDescription('Are you sure you want to mark this follow up as completed? This will change the renewal progress to "Pending Confirmation".')
                        ->modalSubmitActionLabel('Yes, Mark as Completed')
                        ->modalCancelActionLabel('Cancel')
                        ->visible(function ($record) {
                            $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                            return $renewal &&
                                $renewal->admin_renewal !== null &&
                                $renewal->renewal_progress === 'new';
                        })
                        ->action(function ($record) {
                            try {
                                // Get the existing renewal record to preserve current progress_history
                                $existingRenewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                                // Get current progress_history or initialize as empty array
                                $progressHistory = $existingRenewal && $existingRenewal->progress_history
                                    ? json_decode($existingRenewal->progress_history, true)
                                    : [];

                                // Add new log entry
                                $newLogEntry = [
                                    'timestamp' => now()->toISOString(),
                                    'action' => 'follow_up_completed',
                                    'previous_status' => $existingRenewal ? $existingRenewal->renewal_progress : null,
                                    'new_status' => 'pending_confirmation',
                                    'performed_by' => auth()->user()->name,
                                    'performed_by_id' => auth()->user()->id,
                                    'description' => 'Follow up marked as completed - Status changed to Pending Confirmation',
                                    'company_name' => $record->f_company_name,
                                    'f_company_id' => $record->f_company_id,
                                ];

                                // Add the new entry to progress history
                                $progressHistory[] = $newLogEntry;

                                // Update or create renewal record with pending_confirmation status and updated progress_history
                                $renewal = Renewal::updateOrCreate(
                                    ['f_company_id' => $record->f_company_id],
                                    [
                                        'renewal_progress' => 'pending_confirmation',
                                        'progress_history' => json_encode($progressHistory),
                                    ]
                                );

                                Notification::make()
                                    ->success()
                                    ->title('Follow Up Completed')
                                    ->body("Follow up has been marked as completed. Renewal progress updated to 'Pending Confirmation'.")
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Error updating follow up status: '.$e->getMessage());

                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('There was an error updating the follow up status. Please try again.')
                                    ->send();
                            }
                        }),
                    Action::make('completed_payment')
                        ->label('Completed Payment')
                        ->icon('heroicon-o-credit-card')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Payment as Completed')
                        ->modalDescription('Are you sure you want to mark payment as completed? This will change the renewal progress to "Completed Renewal".')
                        ->modalSubmitActionLabel('Yes, Mark as Completed')
                        ->modalCancelActionLabel('Cancel')
                        ->visible(function ($record) {
                            $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                            return $renewal && ($renewal->renewal_progress === 'pending_confirmation' || $renewal->renewal_progress === 'pending_payment');
                        })
                        ->action(function ($record) {
                            try {
                                // Get the existing renewal record to preserve current progress_history
                                $existingRenewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                                // Get current progress_history or initialize as empty array
                                $progressHistory = $existingRenewal && $existingRenewal->progress_history
                                    ? json_decode($existingRenewal->progress_history, true)
                                    : [];

                                // Add new log entry
                                $newLogEntry = [
                                    'timestamp' => now()->toISOString(),
                                    'action' => 'payment_completed',
                                    'previous_status' => $existingRenewal ? $existingRenewal->renewal_progress : null,
                                    'new_status' => 'completed_renewal',
                                    'performed_by' => auth()->user()->name,
                                    'performed_by_id' => auth()->user()->id,
                                    'description' => 'Payment marked as completed - Renewal process completed',
                                    'company_name' => $record->f_company_name,
                                    'f_company_id' => $record->f_company_id,
                                ];

                                // Add the new entry to progress history
                                $progressHistory[] = $newLogEntry;

                                // Update renewal record
                                $renewal = Renewal::updateOrCreate(
                                    ['f_company_id' => $record->f_company_id],
                                    [
                                        'renewal_progress' => 'completed_renewal',
                                        'progress_history' => json_encode($progressHistory),
                                        'payment_completed_at' => now(),
                                        'payment_completed_by' => auth()->user()->id,
                                    ]
                                );

                                Notification::make()
                                    ->success()
                                    ->title('Payment Completed')
                                    ->body('Payment has been marked as completed. Renewal process is now complete.')
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Error updating payment status: '.$e->getMessage());

                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('There was an error updating the payment status. Please try again.')
                                    ->send();
                            }
                        }),

                    Action::make('request_invoice')
                        ->label('Request Invoice')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Request Invoice')
                        ->modalDescription('Are you sure you want to request an invoice? This will change the renewal progress to "Pending Payment".')
                        ->modalSubmitActionLabel('Yes, Request Invoice')
                        ->modalCancelActionLabel('Cancel')
                        ->visible(function ($record) {
                            $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                            return $renewal && $renewal->renewal_progress === 'pending_confirmation';
                        })
                        ->action(function ($record) {
                            try {
                                // Get the existing renewal record to preserve current progress_history
                                $existingRenewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                                // Get current progress_history or initialize as empty array
                                $progressHistory = $existingRenewal && $existingRenewal->progress_history
                                    ? json_decode($existingRenewal->progress_history, true)
                                    : [];

                                // Add new log entry
                                $newLogEntry = [
                                    'timestamp' => now()->toISOString(),
                                    'action' => 'invoice_requested',
                                    'previous_status' => $existingRenewal ? $existingRenewal->renewal_progress : null,
                                    'new_status' => 'pending_payment',
                                    'performed_by' => auth()->user()->name,
                                    'performed_by_id' => auth()->user()->id,
                                    'description' => 'Invoice requested - Status changed to Pending Payment',
                                    'company_name' => $record->f_company_name,
                                    'f_company_id' => $record->f_company_id,
                                ];

                                // Add the new entry to progress history
                                $progressHistory[] = $newLogEntry;

                                // Update renewal record
                                $renewal = Renewal::updateOrCreate(
                                    ['f_company_id' => $record->f_company_id],
                                    [
                                        'renewal_progress' => 'pending_payment',
                                        'progress_history' => json_encode($progressHistory),
                                        'invoice_requested_at' => now(),
                                        'invoice_requested_by' => auth()->user()->id,
                                    ]
                                );

                                Notification::make()
                                    ->success()
                                    ->title('Invoice Requested')
                                    ->body("Invoice has been requested. Renewal progress updated to 'Pending Payment'.")
                                    ->send();
                            } catch (\Exception $e) {
                                Log::error('Error requesting invoice: '.$e->getMessage());

                                Notification::make()
                                    ->danger()
                                    ->title('Error')
                                    ->body('There was an error requesting the invoice. Please try again.')
                                    ->send();
                            }
                        }),

                    // Action::make('claim_via_hrdf')
                    //     ->label('Claim via HRDF')
                    //     ->icon('heroicon-o-building-library')
                    //     ->color('success')
                    //     ->requiresConfirmation()
                    //     ->modalHeading('Claim via HRDF')
                    //     ->modalDescription('Are you sure you want to process HRDF claim? This will change the renewal progress to "Pending Payment".')
                    //     ->modalSubmitActionLabel('Yes, Process HRDF Claim')
                    //     ->modalCancelActionLabel('Cancel')
                    //     ->visible(function ($record) {
                    //         $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                    //         return $renewal && $renewal->renewal_progress === 'pending_confirmation';
                    //     })
                    //     ->action(function ($record) {
                    //         try {
                    //             // Get the existing renewal record to preserve current progress_history
                    //             $existingRenewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                    //             // Get current progress_history or initialize as empty array
                    //             $progressHistory = $existingRenewal && $existingRenewal->progress_history
                    //                 ? json_decode($existingRenewal->progress_history, true)
                    //                 : [];

                    //             // Add new log entry
                    //             $newLogEntry = [
                    //                 'timestamp' => now()->toISOString(),
                    //                 'action' => 'hrdf_claim_initiated',
                    //                 'previous_status' => $existingRenewal ? $existingRenewal->renewal_progress : null,
                    //                 'new_status' => 'pending_payment',
                    //                 'performed_by' => auth()->user()->name,
                    //                 'performed_by_id' => auth()->user()->id,
                    //                 'description' => 'HRDF claim initiated - Status changed to Pending Payment',
                    //                 'company_name' => $record->f_company_name,
                    //                 'f_company_id' => $record->f_company_id,
                    //             ];

                    //             // Add the new entry to progress history
                    //             $progressHistory[] = $newLogEntry;

                    //             // Update renewal record
                    //             $renewal = Renewal::updateOrCreate(
                    //                 ['f_company_id' => $record->f_company_id],
                    //                 [
                    //                     'renewal_progress' => 'pending_payment',
                    //                     'progress_history' => json_encode($progressHistory),
                    //                     'hrdf_claim_initiated_at' => now(),
                    //                     'hrdf_claim_initiated_by' => auth()->user()->id,
                    //                 ]
                    //             );

                    //             Notification::make()
                    //                 ->success()
                    //                 ->title('HRDF Claim Initiated')
                    //                 ->body("HRDF claim has been initiated. Renewal progress updated to 'Pending Payment'.")
                    //                 ->send();
                    //         } catch (\Exception $e) {
                    //             Log::error('Error initiating HRDF claim: '.$e->getMessage());

                    //             Notification::make()
                    //                 ->danger()
                    //                 ->title('Error')
                    //                 ->body('There was an error initiating the HRDF claim. Please try again.')
                    //                 ->send();
                    //         }
                    //     }),

                    // Action::make('termination')
                    //     ->label('Termination')
                    //     ->icon('heroicon-o-x-circle')
                    //     ->color('danger')
                    //     ->requiresConfirmation()
                    //     ->modalHeading('Terminate Renewal')
                    //     ->modalDescription(fn ($record) => "Are you sure you want to terminate the renewal for {$record->f_company_name}? This action will mark the renewal as terminated and remove it from the active renewal list.")
                    //     ->modalSubmitActionLabel('Yes, Terminate')
                    //     ->modalCancelActionLabel('Cancel')
                    //     ->visible(function ($record) {
                    //         $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();
                    //         // Show termination button for any status except already terminated
                    //         return $renewal && $renewal->renewal_progress !== 'terminated';
                    //     })
                    //     ->action(function ($record) {
                    //         try {
                    //             // Get the existing renewal record to preserve current progress_history
                    //             $existingRenewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                    //             // Get current progress_history or initialize as empty array
                    //             $progressHistory = $existingRenewal && $existingRenewal->progress_history
                    //                 ? json_decode($existingRenewal->progress_history, true)
                    //                 : [];

                    //             // Add new log entry
                    //             $newLogEntry = [
                    //                 'timestamp' => now()->toISOString(),
                    //                 'action' => 'renewal_terminated',
                    //                 'previous_status' => $existingRenewal ? $existingRenewal->renewal_progress : null,
                    //                 'new_status' => 'terminated',
                    //                 'performed_by' => auth()->user()->name,
                    //                 'performed_by_id' => auth()->user()->id,
                    //                 'description' => 'Renewal terminated - Removed from active renewal process',
                    //                 'company_name' => $record->f_company_name,
                    //                 'f_company_id' => $record->f_company_id,
                    //             ];

                    //             // Add the new entry to progress history
                    //             $progressHistory[] = $newLogEntry;

                    //             // Update renewal record
                    //             $renewal = Renewal::updateOrCreate(
                    //                 ['f_company_id' => $record->f_company_id],
                    //                 [
                    //                     'renewal_progress' => 'terminated',
                    //                     'progress_history' => json_encode($progressHistory),
                    //                 ]
                    //             );

                    //             Notification::make()
                    //                 ->success()
                    //                 ->title('Renewal Terminated')
                    //                 ->body("Renewal for {$record->f_company_name} has been terminated and removed from active renewals.")
                    //                 ->send();

                    //         } catch (\Exception $e) {
                    //             Log::error("Error terminating renewal: " . $e->getMessage());

                    //             Notification::make()
                    //                 ->danger()
                    //                 ->title('Error')
                    //                 ->body('There was an error terminating the renewal. Please try again.')
                    //                 ->send();
                    //         }
                    //     }),
                ])
                    ->icon('heroicon-m-ellipsis-vertical')
                    ->color('primary'),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\BulkAction::make('batch_onhold_mapping')
                        ->label('Batch Update OnHold Mapping')
                        ->icon('heroicon-o-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Batch Update OnHold Mapping')
                        ->modalDescription('Are you sure you want to set the selected renewals to OnHold Mapping status? This will also assign them to "Auto Renewal".')
                        ->modalSubmitActionLabel('Yes, Update to OnHold')
                        ->modalCancelActionLabel('Cancel')
                        ->action(function ($records) {
                            $successCount = 0;
                            $errorCount = 0;
                            $updatedCompanies = [];

                            foreach ($records as $record) {
                                try {
                                    // Update or create renewal record with onhold mapping status
                                    Renewal::updateOrCreate(
                                        ['f_company_id' => $record->f_company_id],
                                        [
                                            'company_name' => $record->f_company_name,
                                            'mapping_status' => 'onhold_mapping',
                                            'admin_renewal' => 'Auto Renewal',
                                            'updated_at' => now(),
                                        ]
                                    );

                                    $successCount++;
                                    $updatedCompanies[] = $record->f_company_name;
                                } catch (\Exception $e) {
                                    Log::error("Error updating OnHold mapping for company {$record->f_company_id}: ".$e->getMessage());
                                    $errorCount++;
                                }
                            }

                            if ($successCount > 0) {
                                Notification::make()
                                    ->success()
                                    ->title('Batch Update Successful')
                                    ->body("Successfully updated {$successCount} renewal(s) to OnHold Mapping status.".
                                        ($errorCount > 0 ? " {$errorCount} failed to update." : ''))
                                    ->send();
                            } else {
                                Notification::make()
                                    ->danger()
                                    ->title('Batch Update Failed')
                                    ->body('No renewals were updated. Please try again.')
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),

                    \Filament\Tables\Actions\BulkAction::make('batch_assign_admin')
                        ->label('Batch Assign Admin Renewal')
                        ->icon('heroicon-o-user-group')
                        ->color('info')
                        ->form([
                            Select::make('admin_renewal')
                                ->label('Select Admin Renewal')
                                ->options([
                                    'Fatimah Nurnabilah' => 'Fatimah Nurnabilah',
                                ])
                                ->required()
                                ->placeholder('Select an admin to assign')
                                ->helperText('All selected renewals will be assigned to the chosen admin.'),
                        ])
                        ->action(function ($records, array $data) {
                            $successCount = 0;
                            $errorCount = 0;
                            $skippedCount = 0;
                            $selectedAdmin = $data['admin_renewal'];

                            foreach ($records as $record) {
                                try {
                                    // Check if renewal exists and mapping status
                                    $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                                    if ($renewal && $renewal->mapping_status === 'completed_mapping') {
                                        // Update existing renewal record
                                        $renewal->update([
                                            'admin_renewal' => $selectedAdmin,
                                            'updated_at' => now(),
                                        ]);

                                        $successCount++;
                                    } elseif (! $renewal) {
                                        // Create new renewal record with completed mapping (for assignment)
                                        Renewal::create([
                                            'f_company_id' => $record->f_company_id,
                                            'company_name' => $record->f_company_name,
                                            'mapping_status' => 'completed_mapping',
                                            'follow_up_date' => now(),
                                            'follow_up_counter' => true,
                                            'admin_renewal' => $selectedAdmin,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);

                                        $successCount++;
                                    } else {
                                        // Skip records with incomplete mapping
                                        $skippedCount++;
                                    }
                                } catch (\Exception $e) {
                                    Log::error("Error batch assigning admin for company {$record->f_company_id}: ".$e->getMessage());
                                    $errorCount++;
                                }
                            }

                            if ($successCount > 0) {
                                $message = "Successfully assigned {$successCount} renewal(s) to {$selectedAdmin}.";
                                if ($skippedCount > 0) {
                                    $message .= " {$skippedCount} were skipped (mapping not completed).";
                                }
                                if ($errorCount > 0) {
                                    $message .= " {$errorCount} failed due to errors.";
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Batch Assignment Successful')
                                    ->body($message)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('No Assignments Made')
                                    ->body("No renewals were assigned. {$skippedCount} were skipped and {$errorCount} had errors.")
                                    ->send();
                            }
                        })
                        ->modalHeading('Batch Assign Admin Renewal')
                        ->modalDescription('Select an admin to assign to all selected renewal records.')
                        ->modalSubmitActionLabel('Assign Selected')
                        ->modalCancelActionLabel('Cancel')
                        ->deselectRecordsAfterCompletion(),

                    \Filament\Tables\Actions\BulkAction::make('batch_assign_to_me')
                        ->label('Batch Assign to Me')
                        ->icon('heroicon-o-user')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Batch Assign to Me')
                        ->modalDescription('Are you sure you want to assign all selected renewals to yourself?')
                        ->modalSubmitActionLabel('Yes, Assign to Me')
                        ->modalCancelActionLabel('Cancel')
                        ->action(function ($records) {
                            $successCount = 0;
                            $errorCount = 0;
                            $skippedCount = 0;
                            $currentUserName = auth()->user()->name;

                            foreach ($records as $record) {
                                try {
                                    // Check if renewal exists
                                    $renewal = Renewal::where('f_company_id', $record->f_company_id)->first();

                                    if ($renewal && $renewal->mapping_status === 'completed_mapping' && $renewal->admin_renewal === null) {
                                        // Update existing renewal record
                                        $renewal->update([
                                            'admin_renewal' => $currentUserName,
                                            'updated_at' => now(),
                                        ]);

                                        $successCount++;
                                    } elseif (! $renewal) {
                                        // Create new renewal record
                                        Renewal::create([
                                            'f_company_id' => $record->f_company_id,
                                            'company_name' => $record->f_company_name,
                                            'mapping_status' => 'completed_mapping',
                                            'follow_up_date' => now(),
                                            'follow_up_counter' => true,
                                            'admin_renewal' => $currentUserName,
                                            'created_at' => now(),
                                            'updated_at' => now(),
                                        ]);

                                        $successCount++;
                                    } else {
                                        // Skip records that don't meet criteria
                                        $skippedCount++;
                                    }
                                } catch (\Exception $e) {
                                    Log::error("Error batch assigning to self for company {$record->f_company_id}: ".$e->getMessage());
                                    $errorCount++;
                                }
                            }

                            if ($successCount > 0) {
                                $message = "Successfully assigned {$successCount} renewal(s) to yourself.";
                                if ($skippedCount > 0) {
                                    $message .= " {$skippedCount} were skipped (already assigned or other conditions).";
                                }
                                if ($errorCount > 0) {
                                    $message .= " {$errorCount} failed due to errors.";
                                }

                                Notification::make()
                                    ->success()
                                    ->title('Batch Assignment Successful')
                                    ->body($message)
                                    ->send();
                            } else {
                                Notification::make()
                                    ->warning()
                                    ->title('No Assignments Made')
                                    ->body("No renewals were assigned. {$skippedCount} were skipped and {$errorCount} had errors.")
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultPaginationPageOption(50)
            ->paginated([10, 25, 50])
            ->paginationPageOptions([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->defaultSort('earliest_expiry', 'asc');
    }

    protected function handleMappingAction($record, array $data)
    {
        $mappingType = $data['mapping_type'];

        switch ($mappingType) {
            case 'before_handover':
                try {
                    // Follow the exact same pattern as CreateLead.php

                    // Get the latest lead ID to determine the next one
                    $latestLeadId = Lead::max('id') ?? 0;
                    $nextLeadId = $latestLeadId + 1;

                    // Create CompanyDetail first (like in CreateLead)
                    $companyDetail = CompanyDetail::create([
                        'company_name' => strtoupper(trim($data['company_name'])),
                        'lead_id' => $nextLeadId,
                    ]);

                    // Convert country code to country name (like in CreateLead)
                    $countryName = $this->convertCountryCodeToName($data['country']);

                    // Remove + from phone number (like in CreateLead)
                    $phoneNumber = ltrim($data['phone'], '+');

                    // Create Lead
                    $lead = Lead::create([
                        'company_name' => $companyDetail->id, // Store CompanyDetail ID
                        'name' => strtoupper($data['name']),
                        'email' => $data['email'],
                        'phone' => $phoneNumber,
                        'company_size' => $data['company_size'],
                        'country' => $countryName,
                        'admin_renewal' => 'Fatimah Nurnabilah',
                        'lead_code' => 'Existing Customer (Migration)',
                        'products' => 'hr', // This will be stored as JSON
                        'status' => 'new',
                        'f_company_id' => $record->f_company_id, // Link to renewal data
                    ]);

                    // First ActivityLog update - for renewal mapping
                    $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                        ->orderByDesc('created_at')
                        ->first();

                    // Update the activity log description
                    if ($latestActivityLog) {
                        $latestActivityLog->update([
                            'description' => 'New lead created for renewal mapping',
                            'causer_id' => auth()->user()->id,
                        ]);
                    }

                    if (auth()->user()->role_id === 1 || auth()->user()->role_id === 3) {
                        sleep(1);
                        $lead->update([
                            'lead_owner' => auth()->user()->name,
                            'categories' => 'Inactive',
                            'stage' => null,
                            'lead_status' => 'Closed',
                            'pickup_date' => now(),
                            'closing_date' => now(),
                        ]);

                        // Second ActivityLog update - for assignment and closure
                        $latestActivityLog = ActivityLog::where('subject_id', $lead->id)
                            ->orderByDesc('id')
                            ->first();

                        if ($latestActivityLog) {
                            $latestActivityLog->update([
                                'subject_id' => $lead->id,
                                'description' => 'Lead assigned to '.auth()->user()->name.' and Mark as Closed',
                            ]);
                        }
                    }

                    // Create or update renewal record
                    Renewal::updateOrCreate(
                        ['f_company_id' => $record->f_company_id],
                        [
                            'lead_id' => $lead->id,
                            'company_name' => $data['company_name'],
                            'mapping_status' => 'completed_mapping',
                            'follow_up_date' => now(),
                            'follow_up_counter' => true,
                        ]
                    );

                    Notification::make()
                        ->success()
                        ->title('Lead Created Successfully')
                        ->body("New lead created with ID: {$lead->lead_code} and mapped to renewal.")
                        ->actions([
                            \Filament\Notifications\Actions\Action::make('view_lead')
                                ->label('View Lead')
                                ->url(route('filament.admin.resources.leads.view', ['record' => \App\Classes\Encryptor::encrypt($lead->id)]))
                                ->openUrlInNewTab(),
                        ])
                        ->send();
                } catch (\Exception $e) {
                    Log::error('Error creating lead: '.$e->getMessage());

                    Notification::make()
                        ->danger()
                        ->title('Error Creating Lead')
                        ->body('There was an error creating the lead. Please try again.')
                        ->send();
                }
                break;

            case 'after_handover':
                $leadId = $data['lead_id'];

                Renewal::updateOrCreate(
                    ['f_company_id' => $record->f_company_id],
                    [
                        'lead_id' => $leadId,
                        'company_name' => $record->f_company_name,
                        'mapping_status' => 'completed_mapping',
                        'follow_up_date' => now(),
                        'follow_up_counter' => true,
                    ]
                );

                Notification::make()
                    ->success()
                    ->title('Mapping Completed')
                    ->body("Successfully mapped to Lead ID: {$leadId}")
                    ->send();
                break;

            case 'onhold':
                Renewal::updateOrCreate(
                    ['f_company_id' => $record->f_company_id],
                    [
                        'company_name' => $record->f_company_name,
                        'mapping_status' => 'onhold_mapping',
                        'renewal_progress' => 'completed_renewal',
                        'admin_renewal' => 'Auto Renewal',
                    ]
                );

                Notification::make()
                    ->info()
                    ->title('Mapping On Hold')
                    ->body('Renewal mapping has been placed on hold.')
                    ->send();
                break;
        }
    }

    // Helper method to convert country code to name (like in CreateLead)
    protected function convertCountryCodeToName($countryCode)
    {
        $filePath = storage_path('app/public/json/CountryCodes.json');

        if (file_exists($filePath)) {
            $countriesContent = file_get_contents($filePath);
            $countries = json_decode($countriesContent, true);

            foreach ($countries as $country) {
                if ($country['Code'] === $countryCode) {
                    return ucfirst(strtolower($country['Country']));
                }
            }
        }

        return $countryCode; // Fallback
    }
}
