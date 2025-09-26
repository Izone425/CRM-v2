<?php

namespace App\Filament\Resources\LeadResource\Tabs;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Placeholder;

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
                    padding: 12px 16px;
                    text-align: center;
                    border: 1px solid #e5e7eb;
                }

                .license-summary-table th,
                .invoice-details-table th {
                    background-color: #f9fafb;
                    font-weight: 600;
                    color: #374151;
                    font-size: 14px;
                }

                .license-summary-table td {
                    font-size: 18px;
                    font-weight: 500;
                    color: #1f2937;
                }

                .invoice-details-table td {
                    font-size: 13px;
                    color: #1f2937;
                }

                .license-summary-table .attendance { background-color: #fef3c7; }
                .license-summary-table .leave { background-color: #d1fae5; }
                .license-summary-table .claim { background-color: #dbeafe; }
                .license-summary-table .payroll { background-color: #fce7f3; }

                .invoice-header {
                    background-color: #f3f4f6;
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

                .product-row-ta { background-color: rgba(254, 243, 199, 0.3); }
                .product-row-leave { background-color: rgba(209, 250, 229, 0.3); }
                .product-row-claim { background-color: rgba(219, 234, 254, 0.3); }
                .product-row-payroll { background-color: rgba(252, 231, 243, 0.3); }

                .text-right { text-align: right; }
                .text-left { text-align: left; }
            </style>

            <!-- License Summary Table -->
            <div class="license-summary-table">
                <table>
                    <thead>
                        <tr>
                            <th class="attendance">Attendance</th>
                            <th class="leave">Leave</th>
                            <th class="claim">Claim</th>
                            <th class="payroll">Payroll</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="attendance">' . $licenseData['attendance'] . '</td>
                            <td class="leave">' . $licenseData['leave'] . '</td>
                            <td class="claim">' . $licenseData['claim'] . '</td>
                            <td class="payroll">' . $licenseData['payroll'] . '</td>
                        </tr>
                    </tbody>
                </table>
            </div>';

        // Invoice Details Tables
        if (!empty($invoiceDetails)) {
            $html .= '<div class="invoice-details-container">';

            foreach ($invoiceDetails as $invoiceNumber => $invoiceData) {
                $html .= '
                <div class="invoice-group">
                    <div class="invoice-title">Invoice: ' . $invoiceNumber . '</div>
                    <div class="invoice-details-table">
                        <table>
                            <thead>
                                <tr class="invoice-header">
                                    <th class="text-left">Product Name</th>
                                    <th>Qty</th>
                                    <th class="text-right">Price (RM)</th>
                                    <th>Billing Cycle</th>
                                    <th class="text-right">Discount (%)</th>
                                    <th class="text-right">Amount (RM)</th>
                                    <th>Start Date</th>
                                    <th>Expiry Date</th>
                                </tr>
                            </thead>
                            <tbody>';

                foreach ($invoiceData['products'] as $product) {
                    $productType = self::getProductType($product['f_name']);
                    $html .= '
                                <tr class="product-row-' . $productType . '">
                                    <td class="text-left">' . htmlspecialchars($product['f_name']) . '</td>
                                    <td>' . $product['f_unit'] . '</td>
                                    <td class="text-right">' . number_format($product['unit_price'], 2) . '</td>
                                    <td>' . ($product['billing_cycle'] ?? 'Annual') . '</td>
                                    <td class="text-right">' . ($product['discount'] ?? '0.00') . '</td>
                                    <td class="text-right">' . number_format($product['f_total_amount'], 2) . '</td>
                                    <td>' . date('d M Y', strtotime($product['f_start_date'])) . '</td>
                                    <td>' . date('d M Y', strtotime($product['f_expiry_date'])) . '</td>
                                </tr>';
                }

                $html .= '
                            </tbody>
                            <tfoot>
                                <tr style="background-color: #f9fafb; font-weight: 600;">
                                    <td colspan="5" class="text-right">Total Amount:</td>
                                    <td class="text-right">RM ' . number_format($invoiceData['total_amount'], 2) . '</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        return new HtmlString($html);
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

        // Then get all active licenses for this company from crm_expiring_license
        $licenses = DB::connection('frontenddb')->table('crm_expiring_license')
            ->where('f_company_id', (int) $renewal->f_company_id)
            ->whereDate('f_expiry_date', '>=', today())
            ->get(['f_name', 'f_unit']);

        $totals = [
            'attendance' => 0,
            'leave' => 0,
            'claim' => 0,
            'payroll' => 0
        ];

        foreach ($licenses as $license) {
            $licenseName = $license->f_name;
            $unit = (int) $license->f_unit;

            // Attendance licenses
            if (strpos($licenseName, 'TimeTec TA') !== false) {
                if (strpos($licenseName, '(10 User License)') !== false) {
                    $totals['attendance'] += 10 * $unit;
                } elseif (strpos($licenseName, '(1 User License)') !== false) {
                    $totals['attendance'] += 1 * $unit;
                }
            }

            // Leave licenses
            if (strpos($licenseName, 'TimeTec Leave') !== false) {
                if (strpos($licenseName, '(10 User License)') !== false || strpos($licenseName, '(10 Leave License)') !== false) {
                    $totals['leave'] += 10 * $unit;
                } elseif (strpos($licenseName, '(1 User License)') !== false || strpos($licenseName, '(1 Leave License)') !== false) {
                    $totals['leave'] += 1 * $unit;
                }
            }

            // Claim licenses
            if (strpos($licenseName, 'TimeTec Claim') !== false) {
                if (strpos($licenseName, '(10 User License)') !== false || strpos($licenseName, '(10 Claim License)') !== false) {
                    $totals['claim'] += 10 * $unit;
                } elseif (strpos($licenseName, '(1 User License)') !== false || strpos($licenseName, '(1 Claim License)') !== false) {
                    $totals['claim'] += 1 * $unit;
                }
            }

            // Payroll licenses
            if (strpos($licenseName, 'TimeTec Payroll') !== false) {
                if (strpos($licenseName, '(10 Payroll License)') !== false) {
                    $totals['payroll'] += 10 * $unit;
                } elseif (strpos($licenseName, '(1 Payroll License)') !== false) {
                    $totals['payroll'] += 1 * $unit;
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

        // Get all license details with invoice information
        $licenses = DB::connection('frontenddb')->table('crm_expiring_license')
            ->where('f_company_id', (int) $renewal->f_company_id)
            ->whereDate('f_expiry_date', '>=', today())
            ->get([
                'f_name', 'f_unit', 'f_total_amount', 'f_start_date',
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
            $billingCycle = $invoiceDetail ? $invoiceDetail->f_billing_cycle : 'Annual';

            // Calculate final amount based on reseller status
            if ($reseller && $reseller->f_rate) {
                // With reseller: f_total_amount - f_gst_amount
                $finalAmount = $invoiceDetail ? ($invoiceDetail->f_total_amount - $invoiceDetail->f_gst_amount) : $license->f_total_amount;

                // Calculate discount rate: (f_sales_amount - f_total_amount) / f_sales_amount * 100
                if ($invoiceDetail && $invoiceDetail->f_sales_amount > 0) {
                    $discountRate = (($invoiceDetail->f_sales_amount - $finalAmount) / $invoiceDetail->f_sales_amount) * 100;
                    $discountRate = number_format($discountRate, 2);
                } else {
                    $discountRate = $reseller->f_rate; // Fallback to reseller rate
                }
            } else {
                // No reseller: f_sales_amount
                $finalAmount = $invoiceDetail ? $invoiceDetail->f_sales_amount : $license->f_total_amount;
                $discountRate = '0.00'; // No reseller commission
            }

            // Calculate unit price after excluding commission (for display purposes)
            $unitPriceAfterCommission = $quantity > 0 ? $finalAmount / $quantity : 0;

            if (!isset($invoiceGroups[$invoiceNo])) {
                $invoiceGroups[$invoiceNo] = [
                    'products' => [],
                    'total_amount' => 0
                ];
            }

            $invoiceGroups[$invoiceNo]['products'][] = [
                'f_name' => $license->f_name,
                'f_unit' => $quantity, // From crm_invoice_details
                'unit_price' => $unitPrice, // Unit price after commission exclusion
                'original_unit_price' => $unitPrice, // Original price from crm_invoice_details
                'f_total_amount' => $finalAmount, // Show net amount after commission deduction
                'f_start_date' => $license->f_start_date,
                'f_expiry_date' => $license->f_expiry_date,
                'billing_cycle' => $billingCycle, // From crm_invoice_details
                'discount' => $discountRate // Show reseller f_rate here
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
