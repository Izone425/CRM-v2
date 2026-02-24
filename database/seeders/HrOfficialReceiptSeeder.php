<?php

namespace Database\Seeders;

use App\Models\HrOfficialReceipt;
use App\Models\HrSalesInvoice;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class HrOfficialReceiptSeeder extends Seeder
{
    public function run(): void
    {
        HrOfficialReceipt::truncate();

        $paidInvoices = HrSalesInvoice::where('status', 'PAID')
            ->orderBy('invoice_date', 'desc')
            ->get();

        $createdByEmails = [
            'fatimah.tarmizi@timeteccloud.com',
            'irdina.rosmanirzam@timeteccloud.com',
        ];

        $subscriberNames = [
            'Embassy of the Philippines in Canberra',
            'AEI Electronics Sdn Bhd',
            'MYSTIQUE CATZ (M) SDN BHD',
            'Credit Investment Bank Ltd',
            'Albertinia Ingenieurswerke',
            'SINOPEC TECH MIDDLE EAST LLC.',
            null, null, null, null, null, null,
        ];

        $autocountPrefixes = ['EPIN', 'ERIN', 'EHIN'];

        $counter = 1;

        foreach ($paidInvoices as $invoice) {
            $receiptDate = $invoice->invoice_date
                ? Carbon::parse($invoice->invoice_date)
                : Carbon::now();

            $orNo = 'OR' . $receiptDate->format('ym') . str_pad($counter, 6, '0', STR_PAD_LEFT);

            // Distribute payment methods realistically
            $rand = rand(1, 100);
            if ($rand <= 60) {
                $paymentMethod = 'Bank Transfer';
            } elseif ($rand <= 75) {
                $paymentMethod = 'PayPal';
            } elseif ($rand <= 85) {
                $paymentMethod = 'Razer';
            } elseif ($rand <= 95) {
                $paymentMethod = 'Point';
            } else {
                $paymentMethod = ['Credit Card', 'Cheque', 'Cash'][array_rand(['Credit Card', 'Cheque', 'Cash'])];
            }

            // Ref No: only for PayPal/Razer
            $refNo = null;
            if ($paymentMethod === 'PayPal') {
                $refNo = strtoupper(substr(md5(rand()), 0, 3)) . strtoupper(substr(md5(rand()), 0, 14)) . strtoupper(substr(md5(rand()), 0, 1));
            } elseif ($paymentMethod === 'Razer') {
                $refNo = 'ORD_' . $receiptDate->format('Ymd') . '_' . rand(1000, 9999);
            }

            // AutoCount Invoice No
            $prefix = $autocountPrefixes[array_rand($autocountPrefixes)];
            $autocountInvoiceNo = $prefix . $receiptDate->format('ym') . '-' . str_pad(rand(1, 200), 4, '0', STR_PAD_LEFT);

            // For Razer, ~50% chance autocount is empty (needs to be filled by admin)
            if ($paymentMethod === 'Razer' && rand(0, 1) === 0) {
                $autocountInvoiceNo = null;
            }

            HrOfficialReceipt::create([
                'or_no'                 => $orNo,
                'receipt_date'          => $receiptDate->format('Y-m-d'),
                'company_name'          => $invoice->company_name,
                'subscriber_name'       => $subscriberNames[array_rand($subscriberNames)],
                'description'           => 'Payment for Invoice ' . $invoice->invoice_no,
                'currency'              => $invoice->currency ?? 'MYR',
                'amount'                => $invoice->invoice_amount ?? 0,
                'status'                => 'PAID',
                'created_by'            => $createdByEmails[array_rand($createdByEmails)],
                'invoice_no'            => $invoice->invoice_no,
                'payment_method'        => $paymentMethod,
                'ref_no'                => $refNo,
                'autocount_invoice_no'  => $autocountInvoiceNo,
                'software_handover_id'  => $invoice->software_handover_id,
                'handover_id'           => $invoice->handover_id,
            ]);

            $counter++;
        }
    }
}
