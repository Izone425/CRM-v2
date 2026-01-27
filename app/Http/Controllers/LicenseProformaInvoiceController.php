<?php

namespace App\Http\Controllers;

use App\Models\HrLicense;
use App\Models\SoftwareHandover;
use App\Services\CRMApiService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LicenseProformaInvoiceController extends Controller
{
    public function __invoke(Request $request, int $softwareHandoverId, string $invoiceNo)
    {
        // Get the software handover
        $softwareHandover = SoftwareHandover::with(['lead.companyDetail'])->find($softwareHandoverId);

        if (!$softwareHandover) {
            abort(404, 'Software Handover not found');
        }

        // Get hr_account_id and hr_company_id for API call
        $accountId = $softwareHandover->hr_account_id;
        $companyId = $softwareHandover->hr_company_id;

        $piData = null;

        // First check if PI data is stored in session (from modal)
        $sessionKey = 'pi_data_' . $softwareHandoverId . '_' . $invoiceNo;
        $sessionData = session()->get($sessionKey);

        if (!empty($sessionData) && !empty($sessionData['items'])) {
            $piData = $sessionData;
            // Don't clear session data so user can refresh the page
        }

        // If no session data, try to fetch PI data from TimeTec Backend API
        if (!$piData && $accountId && $companyId) {
            try {
                $apiService = app(CRMApiService::class);
                $response = $apiService->getProformaInvoiceDetails($accountId, $companyId, $invoiceNo);

                if ($response['success'] && !empty($response['data'])) {
                    $piData = $response['data'];
                }
            } catch (\Exception $e) {
                Log::warning('Failed to fetch PI from API: ' . $e->getMessage());
            }
        }

        // If still no data, build from license records in database
        if (!$piData || empty($piData['items'])) {
            $piData = $this->buildPiFromLicenseData($softwareHandover, $invoiceNo);
        }

        // Get company details
        $companyDetail = $softwareHandover->lead?->companyDetail;
        $companyName = $companyDetail?->company_name ?? $softwareHandover->company_name ?? '-';

        // Prepare image for header
        $image = file_get_contents(public_path('/img/logo-ttc.png'));
        $path_img = 'data:image/png;base64,' . base64_encode($image);

        // Generate PDF using DomPDF
        $pdf = Pdf::setOptions(['isPhpEnabled' => true, 'isRemoteEnabled' => true])
            ->loadView('pdf.license-proforma-invoice', [
                'piData' => $piData,
                'invoiceNo' => $invoiceNo,
                'softwareHandover' => $softwareHandover,
                'companyDetail' => $companyDetail,
                'companyName' => $companyName,
                'path_img' => $path_img,
            ]);

        $pdf->set_paper('a4', 'portrait');

        // Stream PDF to browser (Attachment: false means display in browser, not download)
        $filename = 'PI_' . $invoiceNo . '.pdf';
        return $pdf->stream($filename, ['Attachment' => false]);
    }

    protected function buildPiFromLicenseData(SoftwareHandover $softwareHandover, string $invoiceNo): array
    {
        // Fetch license records directly from database matching this invoice_no
        $hrLicenses = HrLicense::where('software_handover_id', $softwareHandover->id)
            ->where('invoice_no', $invoiceNo)
            ->get();

        $companyDetail = $softwareHandover->lead?->companyDetail;
        $companyName = $companyDetail?->company_name ?? $softwareHandover->company_name ?? '-';
        $companyEmail = $companyDetail?->email ?? '-';
        $companyAddress = $this->formatAddress($companyDetail);

        // Build items array
        $items = [];
        $subtotal = 0;

        foreach ($hrLicenses as $license) {
            $qty = $license->total_user ?? $license->unit ?? 0;
            $month = $license->month ?? 12;
            $startDate = $license->start_date ?? '';
            $endDate = $license->end_date ?? '';
            $licenseType = $license->license_type ?? 'TimeTec License';

            $pricePerUser = $this->getLicensePrice($licenseType);
            $amount = $qty * $pricePerUser * $month;
            $subtotal += $amount;

            $period = '';
            if ($startDate && $endDate) {
                $period = date('d/m/Y', strtotime($startDate)) . ' - ' . date('d/m/Y', strtotime($endDate));
            }

            $items[] = [
                'description' => $licenseType,
                'license_type' => $licenseType,
                'period' => $period,
                'qty' => $qty,
                'price' => $pricePerUser,
                'billing_cycle' => $month,
                'discount' => '0%',
                'amount' => $amount,
            ];
        }

        // Calculate totals
        $discount = 0;
        $sstRate = 8;
        $sst = $subtotal * ($sstRate / 100);
        $totalAmount = $subtotal + $sst;

        // Get date from first license or use current date
        $firstLicense = $hrLicenses->first();
        $invoiceDate = $firstLicense && $firstLicense->start_date
            ? date('j M Y', strtotime($firstLicense->start_date))
            : date('j M Y');

        return [
            'invoice_no' => $invoiceNo,
            'date' => $invoiceDate,
            'status' => 'PAID',
            'trx_rate' => '1',
            'currency' => 'MYR',
            'bill_to' => [
                'company_name' => $companyName,
                'email' => $companyEmail,
                'registration_no' => $companyDetail?->ssm_no ?? '',
                'address' => $companyAddress,
                'contact_name' => $companyDetail?->name ?? $softwareHandover->pic_name ?? '-',
                'contact_phone' => $companyDetail?->contact_no ?? $softwareHandover->pic_phone ?? '-',
            ],
            'items' => $items,
            'discount' => $discount,
            'subtotal' => $subtotal,
            'sst_rate' => $sstRate,
            'sst' => $sst,
            'total_amount' => $totalAmount,
            'amount_due' => $totalAmount,
        ];
    }

    protected function formatAddress($companyDetail): string
    {
        if (!$companyDetail) {
            return '-';
        }

        $parts = [];

        if (!empty($companyDetail->company_address1)) {
            $parts[] = strtoupper(trim($companyDetail->company_address1));
        }
        if (!empty($companyDetail->company_address2)) {
            $parts[] = strtoupper(trim($companyDetail->company_address2));
        }
        if (!empty($companyDetail->postcode) || !empty($companyDetail->state)) {
            $parts[] = trim(($companyDetail->postcode ?? '') . ' ' . strtoupper($companyDetail->state ?? ''));
        }
        if (!empty($companyDetail->country) && $companyDetail->country !== 'Malaysia') {
            $parts[] = trim($companyDetail->country);
        }

        return implode(', ', array_filter($parts)) ?: '-';
    }

    protected function getLicensePrice(string $licenseType): float
    {
        $pricing = [
            'TimeTec TA' => 2.00,
            'TimeTec Attendance' => 2.00,
            'TimeTec Leave' => 1.00,
            'TimeTec Claim' => 1.00,
            'TimeTec Payroll' => 1.00,
            'TimeTec Profile' => 0.50,
            'TimeTec Hire' => 1.00,
        ];

        foreach ($pricing as $key => $price) {
            if (stripos($licenseType, $key) !== false) {
                return $price;
            }
        }

        return 1.00;
    }
}
