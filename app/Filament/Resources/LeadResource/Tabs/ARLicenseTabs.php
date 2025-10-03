<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Facades\Log;

class ARLicenseTabs
{
    public static function getSchema(): array
    {
        return [
            Placeholder::make('license_summary')
                ->label('')
                ->content(function ($record) {
                    if (!$record || !$record->id) {
                        return new HtmlString('<p>No license data available</p>');
                    }

                    return self::getLicenseTable($record->id);
                })
        ];
    }

    private static function getLicenseTable($leadId): HtmlString
    {
        $licenseData = self::getLicenseData($leadId);
        $invoiceDetails = self::getInvoiceDetails($leadId);

        $html = '
        <div class="license-summary-container">
            <style>
                .license-summary-container {
                    margin: 16px 0;
                }

                .license-summary-table table,
                .invoice-details-table table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 16px 0;
                    background: white;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
                }

                .license-summary-table th,
                .license-summary-table td,
                .invoice-details-table th,
                .invoice-details-table td {
                    padding: 12px 8px;
                    text-align: center;
                    border: 1px solid #e5e7eb;
                    vertical-align: middle;
                }

                .license-summary-table th {
                    font-weight: 600;
                    color: #374151;
                    font-size: 14px;
                }

                .license-summary-table td {
                    font-size: 18px;
                    font-weight: 600;
                    color: #1f2937;
                }

                .invoice-details-table th {
                    background-color: #f9fafb !important;
                    font-weight: 600;
                    color: #374151;
                    font-size: 14px;
                }

                .invoice-details-table td {
                    font-size: 13px;
                    color: #1f2937;
                }

                /* Module column widths - 3/4 of each pair */
                .module-col {
                    width: 18.75% !important; /* 3/4 of 25% */
                    text-align: center !important;
                    padding-left: 12px !important;
                }

                /* Headcount column widths - 1/4 of each pair */
                .headcount-col {
                    width: 6.25% !important; /* 1/4 of 25% */
                    text-align: center !important;
                    font-weight: bold !important;
                }

                /* Color themes for each module - using !important to override */
                .attendance-module {
                    background-color: rgba(34, 197, 94, 0.1) !important;
                    color: rgba(34, 197, 94, 1) !important;
                }
                .attendance-count {
                    background-color: rgba(34, 197, 94, 1) !important;
                    color: white !important;
                }

                /* LEAVE - Blue Theme: rgba(37, 99, 235, 1) */
                .leave-module {
                    background-color: rgba(37, 99, 235, 0.1) !important;
                    color: rgba(37, 99, 235, 1) !important;
                }
                .leave-count {
                    background-color: rgba(37, 99, 235, 1) !important;
                    color: white !important;
                }

                /* CLAIM - Purple Theme: rgba(124, 58, 237, 1) */
                .claim-module {
                    background-color: rgba(124, 58, 237, 0.1) !important;
                    color: rgba(124, 58, 237, 1) !important;
                }
                .claim-count {
                    background-color: rgba(124, 58, 237, 1) !important;
                    color: white !important;
                }

                /* PAYROLL - Orange Theme: rgba(249, 115, 22, 1) */
                .payroll-module {
                    background-color: rgba(249, 115, 22, 0.1) !important;
                    color: rgba(249, 115, 22, 1) !important;
                }
                .payroll-count {
                    background-color: rgba(249, 115, 22, 1) !important;
                    color: white !important;
                }

                .invoice-header {
                    background-color: #f3f4f6 !important;
                    font-weight: 700;
                    color: #1f2937;
                    font-size: 15px;
                }

                .invoice-group {
                    margin-bottom: 24px;
                }

                .invoice-title {
                    background-color: #e5e7eb;
                    padding: 8px 12px;
                    font-weight: 600;
                    color: #374151;
                    border-radius: 4px;
                    margin-bottom: 8px;
                }

                .invoice-link {
                    color: #2563eb;
                    text-decoration: none;
                    font-weight: 600;
                }

                .invoice-link:hover {
                    color: #1d4ed8;
                    text-decoration: underline;
                }

                .product-row-ta {
                    background-color: rgba(34, 197, 94, 0.1) !important;
                }
                .product-row-leave {
                    background-color: rgba(37, 99, 235, 0.1) !important;
                }
                .product-row-claim {
                    background-color: rgba(124, 58, 237, 0.1) !important;
                }
                .product-row-payroll {
                    background-color: rgba(249, 115, 22, 0.1) !important;
                }

                .text-right { text-align: right; }
                .text-left { text-align: left; }
            </style>

            <!-- License Summary Table -->
            <div class="license-summary-table">
                <table>
                    <thead>
                        <tr>
                            <th class="module-col attendance-module">ATTENDANCE</th>
                            <th class="headcount-col attendance-count">' . $licenseData['attendance'] . '</th>
                            <th class="module-col leave-module">LEAVE</th>
                            <th class="headcount-col leave-count">' . $licenseData['leave'] . '</th>
                            <th class="module-col claim-module">CLAIM</th>
                            <th class="headcount-col claim-count">' . $licenseData['claim'] . '</th>
                            <th class="module-col payroll-module">PAYROLL</th>
                            <th class="headcount-col payroll-count">' . $licenseData['payroll'] . '</th>
                        </tr>
                    </thead>
                </table>
            </div>';

        // Invoice Details Tables with hyperlinked invoice numbers
        if (!empty($invoiceDetails)) {
            $html .= '<div class="invoice-details-container">';

            foreach ($invoiceDetails as $invoiceNumber => $invoiceData) {
                $companyFId = $invoiceData['f_id'] ?? null;

                if ($companyFId) {
                    // Generate encrypted f_id link
                    $encryptedFId = self::encryptCompanyId($companyFId);
                    $invoiceLink = 'https://www.timeteccloud.com/paypal_reseller_invoice?iIn=' . $encryptedFId;
                } else {
                    $invoiceLink = '#'; // Fallback if no f_id
                }

                $html .= '
                <div class="invoice-group">
                    <div class="invoice-title">Invoice: <a href="' . $invoiceLink . '" target="_blank" class="invoice-link">' . htmlspecialchars($invoiceNumber) . '</a></div>
                    <div class="invoice-details-table">
                        <table>
                            <thead>
                                <tr class="invoice-header">
                                    <th class="text-left">Product Name</th>
                                    <th>Qty</th>
                                    <th class="text-right">Price (RM)</th>
                                    <th>Billing Cycle</th>
                                    <th>Start Date</th>
                                    <th>Expiry Date</th>
                                </tr>
                            </thead>
                            <tbody>';

                foreach ($invoiceData['products'] as $product) {
                    $productType = self::getProductType($product['f_name']);
                    $html .= '
                                <tr class="product-row-' . $productType . '">
                                    <td style="text-align: left;">' . htmlspecialchars($product['f_name']) . '</td>
                                    <td>' . $product['f_unit'] . '</td>
                                    <td class="text-right">' . number_format($product['unit_price'], 2) . '</td>
                                    <td>' . ($product['billing_cycle'] ?? 'Annual') . '</td>
                                    <td>' . date('d M Y', strtotime($product['f_start_date'])) . '</td>
                                    <td>' . date('d M Y', strtotime($product['f_expiry_date'])) . '</td>
                                </tr>';
                }

                $html .= '
                            </tbody>
                        </table>
                    </div>
                </div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return new HtmlString($html);
    }

    // Add this new method for invoice encryption
    private static function encryptCompanyId($companyId): string
    {
        $aesKey = 'Epicamera@99';
        try {
            $encrypted = openssl_encrypt($companyId, "AES-128-ECB", $aesKey);
            $encryptedBase64 = base64_encode($encrypted);

            Log::info('Encrypted Company ID', [
                'original_company_id' => $companyId,
                'encrypted' => $encryptedBase64
            ]);

            return $encryptedBase64;
        } catch (\Exception $e) {
            Log::error('Company ID encryption failed: ' . $e->getMessage(), [
                'company_id' => $companyId
            ]);
            return $companyId;
        }
    }

    private static function getLicenseData($leadId): array
    {
        // First, get f_company_id from renewals table using lead_id
        $renewal = DB::table('renewals')
            ->where('lead_id', $leadId)
            ->first(['f_company_id']);

        if (!$renewal || !$renewal->f_company_id) {
            return [
                'attendance' => 0,
                'leave' => 0,
                'claim' => 0,
                'payroll' => 0
            ];
        }

        // Get all license details with invoice information
        $licenses = DB::connection('frontenddb')->table('crm_expiring_license')
            ->where('f_company_id', (int) $renewal->f_company_id)
            ->whereDate('f_expiry_date', '>=', today())
            ->get(['f_name', 'f_invoice_no']);

        $totals = [
            'attendance' => 0,
            'leave' => 0,
            'claim' => 0,
            'payroll' => 0
        ];

        foreach ($licenses as $license) {
            $licenseName = $license->f_name;
            $invoiceNo = $license->f_invoice_no ?? 'No Invoice';

            // Get quantity from crm_invoice_details table
            $invoiceDetail = DB::connection('frontenddb')->table('crm_invoice_details')
                ->where('f_invoice_no', $invoiceNo)
                ->where('f_name', $license->f_name)
                ->first(['f_quantity']);

            // Use quantity from invoice details, fallback to 1 if not found
            $quantity = $invoiceDetail ? (int) $invoiceDetail->f_quantity : 1;

            // Attendance licenses
            if (strpos($licenseName, 'TimeTec TA') !== false) {
                if (strpos($licenseName, '(10 User License)') !== false) {
                    $totals['attendance'] += 10 * $quantity; // 10 users per license * quantity
                } elseif (strpos($licenseName, '(1 User License)') !== false) {
                    $totals['attendance'] += 1 * $quantity; // 1 user per license * quantity
                }
            }

            // Leave licenses
            if (strpos($licenseName, 'TimeTec Leave') !== false) {
                if (strpos($licenseName, '(10 User License)') !== false || strpos($licenseName, '(10 Leave License)') !== false) {
                    $totals['leave'] += 10 * $quantity; // 10 users per license * quantity
                } elseif (strpos($licenseName, '(1 User License)') !== false || strpos($licenseName, '(1 Leave License)') !== false) {
                    $totals['leave'] += 1 * $quantity; // 1 user per license * quantity
                }
            }

            // Claim licenses
            if (strpos($licenseName, 'TimeTec Claim') !== false) {
                if (strpos($licenseName, '(10 User License)') !== false || strpos($licenseName, '(10 Claim License)') !== false) {
                    $totals['claim'] += 10 * $quantity; // 10 users per license * quantity
                } elseif (strpos($licenseName, '(1 User License)') !== false || strpos($licenseName, '(1 Claim License)') !== false) {
                    $totals['claim'] += 1 * $quantity; // 1 user per license * quantity
                }
            }

            // Payroll licenses
            if (strpos($licenseName, 'TimeTec Payroll') !== false) {
                if (strpos($licenseName, '(10 Payroll License)') !== false) {
                    $totals['payroll'] += 10 * $quantity; // 10 users per license * quantity
                } elseif (strpos($licenseName, '(1 Payroll License)') !== false) {
                    $totals['payroll'] += 1 * $quantity; // 1 user per license * quantity
                }
            }
        }

        return $totals;
    }

    private static function getInvoiceDetails($leadId): array
    {
        // First, get f_company_id from renewals table using lead_id
        $renewal = DB::table('renewals')
            ->where('lead_id', $leadId)
            ->first(['f_company_id']);

        if (!$renewal || !$renewal->f_company_id) {
            return [];
        }

        // Check if company has reseller
        $reseller = DB::connection('frontenddb')->table('crm_reseller_link')
            ->select('reseller_name', 'f_rate')
            ->where('f_id', (int) $renewal->f_company_id)
            ->first();

        // Get all license details with f_id included
        $licenses = DB::connection('frontenddb')->table('crm_expiring_license')
            ->where('f_company_id', (int) $renewal->f_company_id)
            ->whereDate('f_expiry_date', '>=', today())
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
                ->first(['f_quantity', 'f_unit_price', 'f_billing_cycle', 'f_sales_amount', 'f_total_amount', 'f_gst_amount']);

            // Use invoice details if found, otherwise fallback to license data
            $quantity = $invoiceDetail ? $invoiceDetail->f_quantity : $license->f_unit;
            $unitPrice = $invoiceDetail ? $invoiceDetail->f_unit_price : 0;
            $billingCycle = $invoiceDetail ? $invoiceDetail->f_billing_cycle : 0;

            // Calculate amount using: f_quantity * f_unit_price * f_billing_cycle
            $calculatedAmount = $quantity * $unitPrice * $billingCycle;

            // Use the calculated amount directly (no discount deduction)
            $finalAmount = $calculatedAmount;

            // Still show discount rate for display purposes
            $discountRate = ($reseller && $reseller->f_rate) ? $reseller->f_rate : '0.00';

            if (!isset($invoiceGroups[$invoiceNo])) {
                $invoiceGroups[$invoiceNo] = [
                    'f_id' => $license->f_id, // Store f_id for encryption
                    'products' => [],
                    'total_amount' => 0
                ];
            }

            $invoiceGroups[$invoiceNo]['products'][] = [
                'f_name' => $license->f_name,
                'f_unit' => $quantity,
                'unit_price' => $unitPrice,
                'original_unit_price' => $unitPrice,
                'f_total_amount' => $finalAmount,
                'f_start_date' => $license->f_start_date,
                'f_expiry_date' => $license->f_expiry_date,
                'billing_cycle' => $billingCycle,
                'discount' => $discountRate
            ];

            $invoiceGroups[$invoiceNo]['total_amount'] += $finalAmount;
        }

        return $invoiceGroups;
    }

    private static function getProductType($productName): string
    {
        if (strpos($productName, 'TimeTec TA') !== false) {
            return 'ta';
        } elseif (strpos($productName, 'TimeTec Leave') !== false) {
            return 'leave';
        } elseif (strpos($productName, 'TimeTec Claim') !== false) {
            return 'claim';
        } elseif (strpos($productName, 'TimeTec Payroll') !== false) {
            return 'payroll';
        }

        return 'ta'; // Default fallback
    }
}
