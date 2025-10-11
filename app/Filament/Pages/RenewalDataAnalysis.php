<?php
namespace App\Filament\Pages;

use App\Models\Renewal;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Single model for both MYR and USD analysis
class RenewalDataExpiring extends \Illuminate\Database\Eloquent\Model
{
    protected $connection = 'frontenddb';
    protected $table = 'crm_expiring_license';
    protected $primaryKey = 'f_company_id';
    public $timestamps = false;

    public function getKey()
    {
        $key = $this->getAttribute($this->getKeyName());
        return $key !== null ? (string) $key : 'record-'.uniqid();
    }

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
}

class RenewalDataAnalysis extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $title = '';

    protected static ?string $navigationLabel = 'Renewal Data Analysis';

    protected static ?string $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 52;

    protected static string $view = 'filament.pages.renewal-data-analysis';

    public function mount(): void
    {
        // Initialize any required data
    }

    // MYR Analysis Methods
    public function getAnalysisForecastMyr($period)
    {
        return $this->getAnalysisForecast($period, 'MYR');
    }

    // USD Analysis Methods
    public function getAnalysisForecastUsd($period)
    {
        return $this->getAnalysisForecast($period, 'USD');
    }

    // Common analysis method for both currencies
    public function getAnalysisForecast($period, $currency = 'MYR')
    {
        try {
            $today = Carbon::now();

            // Determine date ranges based on period
            switch ($period) {
                case 'current_month':
                    $startDate = $today->copy()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->endOfMonth()->format('Y-m-d');
                    break;

                case 'next_month':
                    $startDate = $today->copy()->addMonth()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->addMonth()->endOfMonth()->format('Y-m-d');
                    break;

                case 'next_two_months':
                    $startDate = $today->copy()->addMonths(2)->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->addMonths(2)->endOfMonth()->format('Y-m-d');
                    break;

                case 'next_three_months':
                    $startDate = $today->copy()->addMonths(3)->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->addMonths(3)->endOfMonth()->format('Y-m-d');
                    break;

                default:
                    // Default to current month
                    $startDate = $today->copy()->startOfMonth()->format('Y-m-d');
                    $endDate = $today->copy()->endOfMonth()->format('Y-m-d');
            }

            // Get stats for each category
            $newStats = $this->getAnalysisForecastByStatus('new', $startDate, $endDate, $currency);
            $pendingConfirmationStats = $this->getAnalysisForecastByStatus('pending_confirmation', $startDate, $endDate, $currency);
            $pendingPaymentStats = $this->getAnalysisForecastByStatus('pending_payment', $startDate, $endDate, $currency, true);

            // Calculate renewal forecast (combination of new + pending_confirmation)
            $renewalForecastStats = [
                'total_companies' => $newStats['total_companies'] + $pendingConfirmationStats['total_companies'],
                'total_amount' => $newStats['total_amount'] + $pendingConfirmationStats['total_amount'],
                'total_via_reseller' => $newStats['total_via_reseller'] + $pendingConfirmationStats['total_via_reseller'],
                'total_via_end_user' => $newStats['total_via_end_user'] + $pendingConfirmationStats['total_via_end_user'],
                'total_via_reseller_amount' => $newStats['total_via_reseller_amount'] + $pendingConfirmationStats['total_via_reseller_amount'],
                'total_via_end_user_amount' => $newStats['total_via_end_user_amount'] + $pendingConfirmationStats['total_via_end_user_amount'],
            ];

            return [
                'new' => $newStats,
                'pending_confirmation' => $pendingConfirmationStats,
                'renewal_forecast' => $renewalForecastStats,
                'pending_payment' => $pendingPaymentStats,
            ];
        } catch (\Exception $e) {
            Log::error("Error getting analysis forecast for period $period and currency $currency: " . $e->getMessage());

            // Return empty stats structure
            return [
                'new' => $this->getEmptyStats(),
                'pending_confirmation' => $this->getEmptyStats(),
                'renewal_forecast' => $this->getEmptyStats(),
                'pending_payment' => $this->getEmptyStats(),
            ];
        }
    }

    protected function getAnalysisForecastByStatus($status, $startDate, $endDate, $currency = 'MYR', $ignoreDateFilter = false)
    {
        try {
            // Get company IDs that have expiring licenses for the specified currency and date range
            $expiringCompanyIds = $this->getExpiringCompanyIds($startDate, $endDate, $currency, $ignoreDateFilter, $status);

            if ($expiringCompanyIds->isEmpty()) {
                return $this->getEmptyStats();
            }

            // Get renewals with specified status that match the expiring companies
            $renewals = Renewal::where('renewal_progress', $status)
                ->whereIn('f_company_id', $expiringCompanyIds)
                ->with(['lead.quotations.items'])
                ->get();

            $totalCompanies = $renewals->count();
            $totalAmount = 0;
            $totalViaResellerCount = 0;
            $totalViaEndUserCount = 0;
            $totalViaResellerAmount = 0;
            $totalViaEndUserAmount = 0;

            // Get all resellers in one query to avoid N+1 problem
            $companyIds = $renewals->pluck('f_company_id')->unique();
            $resellers = collect();

            if ($companyIds->isNotEmpty()) {
                $resellers = DB::connection('frontenddb')
                    ->table('crm_reseller_link')
                    ->whereIn('f_id', $companyIds)
                    ->pluck('f_rate', 'f_id');
            }

            foreach ($renewals as $renewal) {
                // Check if company has reseller
                $hasReseller = $resellers->has($renewal->f_company_id) && $resellers->get($renewal->f_company_id);

                // Count companies
                if ($hasReseller) {
                    $totalViaResellerCount++;
                } else {
                    $totalViaEndUserCount++;
                }

                // Only process quotation data if renewal has lead and lead exists
                if ($renewal->lead_id && $renewal->lead) {
                    // Get final renewal quotations for this lead
                    $renewalQuotations = $renewal->lead->quotations()
                        ->where('mark_as_final', true)
                        ->where('sales_type', 'RENEWAL SALES')
                        ->get();

                    // If quotations exist, calculate amount
                    if ($renewalQuotations->isNotEmpty()) {
                        $quotationAmount = 0;
                        foreach ($renewalQuotations as $quotation) {
                            // Filter quotation items by currency if needed
                            $items = $quotation->items;

                            // If quotation has currency field, filter by it
                            if ($quotation->currency && $quotation->currency !== $currency) {
                                continue; // Skip quotations not matching currency
                            }

                            $quotationAmount += $items->sum('total_before_tax');
                        }

                        $totalAmount += $quotationAmount;

                        if ($hasReseller) {
                            $totalViaResellerAmount += $quotationAmount;
                        } else {
                            $totalViaEndUserAmount += $quotationAmount;
                        }
                    }
                }
            }

            return [
                'total_companies' => $totalCompanies,
                'total_amount' => $totalAmount,
                'total_via_reseller' => $totalViaResellerCount,
                'total_via_end_user' => $totalViaEndUserCount,
                'total_via_reseller_amount' => $totalViaResellerAmount,
                'total_via_end_user_amount' => $totalViaEndUserAmount,
            ];
        } catch (\Exception $e) {
            Log::error("Error fetching analysis forecast by status $status for currency $currency: " . $e->getMessage());
            return $this->getEmptyStats();
        }
    }

    /**
     * Get company IDs that have expiring licenses for the specified criteria
     */
    protected function getExpiringCompanyIds($startDate, $endDate, $currency, $ignoreDateFilter, $status)
    {
        try {
            $query = RenewalDataExpiring::where('f_currency', $currency);

            if ($status === 'pending_payment' && $ignoreDateFilter) {
                // For pending_payment, get companies with any active licenses
                $query->where('f_expiry_date', '>=', Carbon::now()->format('Y-m-d'));
            } else {
                // For other statuses, filter by date range
                $query->whereBetween('f_expiry_date', [$startDate, $endDate]);
            }

            return $query->pluck('f_company_id')->unique();
        } catch (\Exception $e) {
            Log::error("Error getting expiring company IDs: " . $e->getMessage());
            return collect();
        }
    }

    protected function getEmptyStats()
    {
        return [
            'total_companies' => 0,
            'total_amount' => 0,
            'total_via_reseller' => 0,
            'total_via_end_user' => 0,
            'total_via_reseller_amount' => 0,
            'total_via_end_user_amount' => 0,
        ];
    }
}
