<?php
namespace App\Filament\Pages;

use App\Models\Renewal;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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

    // Get reseller information for a company (copied from AdminRenewalProcessDataMyr)
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

    // Cache the analysis data
    public $analysisDataMyr = null;
    public $analysisDataUsd = null;

    public function mount(): void
    {
        // Pre-load all data once
        $this->loadAllAnalysisData();
    }

    // Load all analysis data in single batch
    protected function loadAllAnalysisData()
    {
        $cacheKeyMyr = 'renewal_analysis_myr_' . Carbon::now()->format('Y-m-d-H');
        $cacheKeyUsd = 'renewal_analysis_usd_' . Carbon::now()->format('Y-m-d-H');

        $this->analysisDataMyr = Cache::remember($cacheKeyMyr, 300, function () { // 5 min cache for testing
            return $this->getAllPeriodsAnalysis('MYR');
        });

        $this->analysisDataUsd = Cache::remember($cacheKeyUsd, 300, function () { // 5 min cache for testing
            return $this->getAllPeriodsAnalysis('USD');
        });
    }

    // Single method to get all periods analysis - following AdminRenewalProcessDataMyr approach
    protected function getAllPeriodsAnalysis($currency)
    {
        try {
            $today = Carbon::now();

            // Define all periods upfront
            $periods = [
                'current_month' => [
                    'start' => $today->copy()->startOfMonth()->format('Y-m-d'),
                    'end' => $today->copy()->endOfMonth()->format('Y-m-d')
                ],
                'next_month' => [
                    'start' => $today->copy()->addMonth()->startOfMonth()->format('Y-m-d'),
                    'end' => $today->copy()->addMonth()->endOfMonth()->format('Y-m-d')
                ],
                'next_two_months' => [
                    'start' => $today->copy()->addMonths(2)->startOfMonth()->format('Y-m-d'),
                    'end' => $today->copy()->addMonths(2)->endOfMonth()->format('Y-m-d')
                ],
                'next_three_months' => [
                    'start' => $today->copy()->addMonths(3)->startOfMonth()->format('Y-m-d'),
                    'end' => $today->copy()->addMonths(3)->endOfMonth()->format('Y-m-d')
                ]
            ];

            // Process data for each period using AdminRenewalProcessDataMyr approach
            $analysisResults = [];
            foreach ($periods as $periodName => $periodDates) {
                $analysisResults[$periodName] = [
                    'new' => $this->getNewStats($periodDates['start'], $periodDates['end'], $currency),
                    'pending_confirmation' => $this->getPendingConfirmationStats($periodDates['start'], $periodDates['end'], $currency),
                    'pending_payment' => $this->getPendingPaymentStats($currency), // No date filter for pending payment
                    'renewal_forecast' => $this->getEmptyStats() // Will be calculated below
                ];

                // Calculate renewal forecast (new + pending_confirmation)
                $analysisResults[$periodName]['renewal_forecast'] = [
                    'total_companies' => $analysisResults[$periodName]['new']['total_companies'] + $analysisResults[$periodName]['pending_confirmation']['total_companies'],
                    'total_amount' => $analysisResults[$periodName]['new']['total_amount'] + $analysisResults[$periodName]['pending_confirmation']['total_amount'],
                    'total_via_reseller' => $analysisResults[$periodName]['new']['total_via_reseller'] + $analysisResults[$periodName]['pending_confirmation']['total_via_reseller'],
                    'total_via_end_user' => $analysisResults[$periodName]['new']['total_via_end_user'] + $analysisResults[$periodName]['pending_confirmation']['total_via_end_user'],
                    'total_via_reseller_amount' => $analysisResults[$periodName]['new']['total_via_reseller_amount'] + $analysisResults[$periodName]['pending_confirmation']['total_via_reseller_amount'],
                    'total_via_end_user_amount' => $analysisResults[$periodName]['new']['total_via_end_user_amount'] + $analysisResults[$periodName]['pending_confirmation']['total_via_end_user_amount'],
                ];
            }

            return $analysisResults;

        } catch (\Exception $e) {
            Log::error("Error in getAllPeriodsAnalysis for {$currency}: " . $e->getMessage());
            return $this->getEmptyPeriodsStructure();
        }
    }

    // Copy exact method from AdminRenewalProcessDataMyr
    protected function getNewStats($startDate, $endDate, $currency)
    {
        try {
            Log::info("Getting new stats for period: {$startDate} to {$endDate}, currency: {$currency}");

            // Get renewals with new status
            $allNewRenewals = Renewal::where('renewal_progress', 'new')->get();
            Log::info("Total renewals with 'new' status: " . $allNewRenewals->count());

            if ($allNewRenewals->isEmpty()) {
                Log::info("No renewals with 'new' status found");
                return $this->getEmptyStats();
            }

            // Check expiring licenses for the date range
            $expiringCompanyIds = DB::connection('frontenddb')
                ->table('crm_expiring_license')
                ->where('f_currency', $currency)
                ->whereBetween('f_expiry_date', [$startDate, $endDate])
                ->pluck('f_company_id')
                ->unique();

            Log::info("Expiring company IDs for period: " . $expiringCompanyIds->count());
            Log::info("Sample expiring IDs: " . $expiringCompanyIds->take(5)->toJson());

            // Filter renewals that match expiring companies
            $renewals = $allNewRenewals->filter(function ($renewal) use ($expiringCompanyIds) {
                $companyId = (int) $renewal->f_company_id;
                $hasMatch = $expiringCompanyIds->contains($companyId);

                return $hasMatch;
            });

            Log::info("Final filtered renewals count: " . $renewals->count());

            if ($renewals->isEmpty()) {
                Log::info("No matching renewals found after filtering");
                return $this->getEmptyStats();
            }

            // Load relationships for the filtered renewals
            $renewals->load(['lead.quotations.items']);

            $totalCompanies = $renewals->count();
            $totalAmount = 0;
            $totalViaResellerCount = 0;
            $totalViaEndUserCount = 0;
            $totalViaResellerAmount = 0;
            $totalViaEndUserAmount = 0;

            foreach ($renewals as $renewal) {
                Log::info("Processing renewal ID {$renewal->id} for company {$renewal->f_company_id}");

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
                            $itemsAmount = $quotation->items->sum('total_before_tax');
                            $quotationAmount += $itemsAmount;
                            Log::info("Quotation {$quotation->id}: {$itemsAmount}");
                        }

                        $totalAmount += $quotationAmount;
                        Log::info("Total quotation amount for renewal {$renewal->id}: {$quotationAmount}");

                        // Check if company has reseller for amount calculation
                        $reseller = RenewalDataExpiring::getResellerForCompany((int) $renewal->f_company_id);
                        if ($reseller && $reseller->f_rate) {
                            $totalViaResellerAmount += $quotationAmount;
                            Log::info("Added to reseller amount: {$quotationAmount}");
                        } else {
                            $totalViaEndUserAmount += $quotationAmount;
                            Log::info("Added to end user amount: {$quotationAmount}");
                        }
                    }
                } else {
                    Log::info("No lead found for renewal {$renewal->id}");
                }

                // Always count companies regardless of lead mapping or quotation existence
                $reseller = RenewalDataExpiring::getResellerForCompany((int) $renewal->f_company_id);
                if ($reseller && $reseller->f_rate) {
                    $totalViaResellerCount++;
                } else {
                    $totalViaEndUserCount++;
                }
            }

            $result = [
                'total_companies' => $totalCompanies,
                'total_amount' => $totalAmount,
                'total_via_reseller' => $totalViaResellerCount,
                'total_via_end_user' => $totalViaEndUserCount,
                'total_via_reseller_amount' => $totalViaResellerAmount,
                'total_via_end_user_amount' => $totalViaEndUserAmount,
            ];

            Log::info("Final NEW stats result: " . json_encode($result));
            return $result;

        } catch (\Exception $e) {
            Log::error('Error fetching new renewal stats: '.$e->getMessage());
            Log::error('Stack trace: '.$e->getTraceAsString());
            return $this->getEmptyStats();
        }
    }

    // Copy exact method from AdminRenewalProcessDataMyr
    protected function getPendingConfirmationStats($startDate, $endDate, $currency)
    {
        try {
            // Get renewals with pending_confirmation status that fall within date range
            $renewals = Renewal::where('renewal_progress', 'pending_confirmation')
                ->with(['lead.quotations.items'])
                ->get()
                ->filter(function ($renewal) use ($startDate, $endDate, $currency) {
                    // Convert f_company_id to integer for proper matching
                    $companyId = (int) $renewal->f_company_id;

                    // Check if renewal company has expiring licenses within date range
                    $hasExpiringLicense = RenewalDataExpiring::where('f_company_id', $companyId)
                        ->whereBetween('f_expiry_date', [$startDate, $endDate])
                        ->where('f_currency', $currency)
                        ->exists();

                    return $hasExpiringLicense;
                });

            Log::info("Found renewals for PENDING_CONFIRMATION status: " . $renewals->count());

            $totalCompanies = $renewals->count();
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

                    // If quotations exist, calculate amount
                    if ($renewalQuotations->isNotEmpty()) {
                        // Calculate amount from quotations
                        $quotationAmount = 0;
                        foreach ($renewalQuotations as $quotation) {
                            $quotationAmount += $quotation->items->sum('total_before_tax');
                        }

                        $totalAmount += $quotationAmount;

                        // Check if company has reseller for amount calculation
                        $reseller = RenewalDataExpiring::getResellerForCompany((int) $renewal->f_company_id);
                        if ($reseller && $reseller->f_rate) {
                            $totalViaResellerAmount += $quotationAmount;
                        } else {
                            $totalViaEndUserAmount += $quotationAmount;
                        }
                    }
                }

                // Always count companies regardless of lead mapping or quotation existence
                $reseller = RenewalDataExpiring::getResellerForCompany((int) $renewal->f_company_id);
                if ($reseller && $reseller->f_rate) {
                    $totalViaResellerCount++;
                } else {
                    $totalViaEndUserCount++;
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
            Log::error('Error fetching pending confirmation stats: '.$e->getMessage());
            return $this->getEmptyStats();
        }
    }


    // Copy exact method from AdminRenewalProcessDataMyr (no date filtering for pending payment)
    protected function getPendingPaymentStats($currency)
    {
        try {
            // Get renewals with pending_payment status (no date filtering)
            $renewals = Renewal::where('renewal_progress', 'pending_payment')
                ->with(['lead.quotations.items'])
                ->get()
                ->filter(function ($renewal) use ($currency) {
                    // Convert f_company_id to integer for proper matching
                    $companyId = (int) $renewal->f_company_id;

                    // Only check if renewal company has ANY active licenses (not date-restricted)
                    $hasActiveLicense = RenewalDataExpiring::where('f_company_id', $companyId)
                        ->where('f_expiry_date', '>=', Carbon::now()->format('Y-m-d'))
                        ->where('f_currency', $currency)
                        ->exists();

                    return $hasActiveLicense;
                });

            Log::info("Found renewals for PENDING_PAYMENT status: " . $renewals->count());

            $totalCompanies = $renewals->count();
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

                    // If quotations exist, calculate amount
                    if ($renewalQuotations->isNotEmpty()) {
                        // Calculate amount from quotations
                        $quotationAmount = 0;
                        foreach ($renewalQuotations as $quotation) {
                            $quotationAmount += $quotation->items->sum('total_before_tax');
                        }

                        $totalAmount += $quotationAmount;

                        // Check if company has reseller for amount calculation
                        $reseller = RenewalDataExpiring::getResellerForCompany((int) $renewal->f_company_id);
                        if ($reseller && $reseller->f_rate) {
                            $totalViaResellerAmount += $quotationAmount;
                        } else {
                            $totalViaEndUserAmount += $quotationAmount;
                        }
                    }
                }

                // Always count companies regardless of lead mapping or quotation existence
                $reseller = RenewalDataExpiring::getResellerForCompany((int) $renewal->f_company_id);
                if ($reseller && $reseller->f_rate) {
                    $totalViaResellerCount++;
                } else {
                    $totalViaEndUserCount++;
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
            Log::error('Error fetching pending payment stats: '.$e->getMessage());
            return $this->getEmptyStats();
        }
    }

    // Public methods for blade template (now just return cached data)
    public function getAnalysisForecastMyr($period)
    {
        return $this->analysisDataMyr[$period] ?? $this->getEmptyAnalysisStructure();
    }

    public function getAnalysisForecastUsd($period)
    {
        return $this->analysisDataUsd[$period] ?? $this->getEmptyAnalysisStructure();
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

    protected function getEmptyAnalysisStructure()
    {
        return [
            'new' => $this->getEmptyStats(),
            'pending_confirmation' => $this->getEmptyStats(),
            'renewal_forecast' => $this->getEmptyStats(),
            'pending_payment' => $this->getEmptyStats(),
        ];
    }

    protected function getEmptyPeriodsStructure()
    {
        return [
            'current_month' => $this->getEmptyAnalysisStructure(),
            'next_month' => $this->getEmptyAnalysisStructure(),
            'next_two_months' => $this->getEmptyAnalysisStructure(),
            'next_three_months' => $this->getEmptyAnalysisStructure(),
        ];
    }

    // Method to refresh data manually
    public function refreshAnalysisData()
    {
        Cache::forget('renewal_analysis_myr_' . Carbon::now()->format('Y-m-d-H'));
        Cache::forget('renewal_analysis_usd_' . Carbon::now()->format('Y-m-d-H'));
        $this->loadAllAnalysisData();
    }
}
