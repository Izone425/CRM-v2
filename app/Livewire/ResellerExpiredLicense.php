<?php

namespace App\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ResellerExpiredLicense extends Component
{
    public $companies = [];
    public $expandedCompany = null;
    public $invoiceDetails = [];
    public $search = '';
    public $sortField = 'f_expiry_date';
    public $sortDirection = 'asc';

    public function mount()
    {
        $this->loadCompanies();
    }

    public function updatedSearch()
    {
        $this->loadCompanies();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->loadCompanies();
    }

    public function toggleExpand($fId)
    {
        // Convert to int for comparison
        $fId = (int) $fId;

        if ($this->expandedCompany === $fId) {
            $this->expandedCompany = null;
            $this->invoiceDetails = [];
        } else {
            $this->expandedCompany = $fId;
            $this->loadInvoiceDetails($fId);
        }
    }

    public function loadCompanies()
    {
        $reseller = Auth::guard('reseller')->user();

        if ($reseller && $reseller->reseller_id) {
            $today = Carbon::now();
            $ninetyDaysFromNow = Carbon::now()->addDays(90);

            // Step 1: Get f_id from crm_reseller_link where reseller_id matches
            $resellerLinks = DB::connection('frontenddb')
                ->table('crm_reseller_link')
                ->where('reseller_id', $reseller->reseller_id)
                ->get(['f_id', 'f_company_name']);

            $companies = [];

            foreach ($resellerLinks as $link) {
                // Apply search filter
                if ($this->search && stripos($link->f_company_name, $this->search) === false) {
                    continue;
                }

                // Step 2: Use f_id to get licenses from crm_expiring_license
                // Link crm_reseller_link.f_id with crm_expiring_license.f_company_id
                $expiringLicense = DB::connection('frontenddb')
                    ->table('crm_expiring_license')
                    ->where('f_company_id', $link->f_id)
                    ->whereDate('f_expiry_date', '>=', $today->format('Y-m-d'))
                    ->whereDate('f_expiry_date', '<=', $ninetyDaysFromNow->format('Y-m-d'))
                    ->orderBy('f_expiry_date', 'asc')
                    ->first(['f_expiry_date']);

                if ($expiringLicense) {
                    $expiryDate = Carbon::parse($expiringLicense->f_expiry_date);
                    $daysUntilExpiry = $today->diffInDays($expiryDate);

                    $companies[] = (object) [
                        'f_id' => $link->f_id,
                        'f_company_name' => $link->f_company_name,
                        'f_expiry_date' => $expiringLicense->f_expiry_date,
                        'days_until_expiry' => $daysUntilExpiry
                    ];
                }
            }

            // Sort companies
            usort($companies, function($a, $b) {
                if ($this->sortField === 'f_expiry_date') {
                    $comparison = strtotime($a->f_expiry_date) - strtotime($b->f_expiry_date);
                } else {
                    $comparison = $a->days_until_expiry - $b->days_until_expiry;
                }

                return $this->sortDirection === 'asc' ? $comparison : -$comparison;
            });

            $this->companies = $companies;
        }
    }

    public function loadInvoiceDetails($fId)
    {
        $today = Carbon::now()->format('Y-m-d');
        $ninetyDaysFromNow = Carbon::now()->addDays(90)->format('Y-m-d');

        // Get reseller information
        $reseller = DB::connection('frontenddb')->table('crm_reseller_link')
            ->select('reseller_name', 'f_rate', 'f_id')
            ->where('f_id', (int) $fId)
            ->first();

        // Get all licenses for this f_id (company)
        $licenses = DB::connection('frontenddb')
            ->table('crm_expiring_license')
            ->where('f_company_id', (int) $fId)
            ->whereDate('f_expiry_date', '>=', $today)
            ->whereDate('f_expiry_date', '<=', $ninetyDaysFromNow)
            ->get([
                'f_id', 'f_name', 'f_unit', 'f_total_amount', 'f_start_date',
                'f_expiry_date', 'f_invoice_no'
            ]);

        $invoiceGroups = [];

        foreach ($licenses as $license) {
            $invoiceNo = $license->f_invoice_no ?? 'No Invoice';

            // Get invoice details from crm_invoice_details table
            $invoiceDetail = DB::connection('frontenddb')->table('crm_invoice_details')
                ->where('f_invoice_no', $invoiceNo)
                ->where('f_name', $license->f_name)
                ->first(['f_quantity', 'f_unit_price', 'f_billing_cycle']);

            // Use invoice details if found, otherwise fallback to license data
            $quantity = $invoiceDetail ? $invoiceDetail->f_quantity : $license->f_unit;
            $unitPrice = $invoiceDetail ? $invoiceDetail->f_unit_price : 0;
            $billingCycle = $invoiceDetail ? $invoiceDetail->f_billing_cycle : 0;

            // Calculate amount using: f_quantity * f_unit_price * f_billing_cycle
            $calculatedAmount = $quantity * $unitPrice * $billingCycle;

            // Get discount rate for display
            $discountRate = ($reseller && $reseller->f_rate) ? $reseller->f_rate : '0.00';

            if (!isset($invoiceGroups[$invoiceNo])) {
                $invoiceGroups[$invoiceNo] = [
                    'f_id' => $fId,
                    'products' => [],
                    'total_amount' => 0
                ];
            }

            $invoiceGroups[$invoiceNo]['products'][] = [
                'f_name' => $license->f_name,
                'f_unit' => $quantity,
                'unit_price' => $unitPrice,
                'original_unit_price' => $unitPrice,
                'f_total_amount' => $calculatedAmount,
                'f_start_date' => $license->f_start_date,
                'f_expiry_date' => $license->f_expiry_date,
                'billing_cycle' => $billingCycle,
                'discount' => $discountRate
            ];

            $invoiceGroups[$invoiceNo]['total_amount'] += $calculatedAmount;
        }

        $this->invoiceDetails = $invoiceGroups;
    }

    private function encryptCompanyId($companyId): string
    {
        $aesKey = 'Epicamera@99';
        try {
            $encrypted = openssl_encrypt($companyId, "AES-128-ECB", $aesKey);
            return base64_encode($encrypted);
        } catch (\Exception $e) {
            return $companyId;
        }
    }

    public function render()
    {
        return view('livewire.reseller-expired-license');
    }
}
