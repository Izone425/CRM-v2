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

        $counter = 1;

        foreach ($paidInvoices as $invoice) {
            $receiptDate = $invoice->invoice_date
                ? Carbon::parse($invoice->invoice_date)
                : Carbon::now();

            $orNo = 'OR' . $receiptDate->format('ym') . str_pad($counter, 6, '0', STR_PAD_LEFT);

            // Map payment method to display value
            $paymentMethodMap = [
                'Online Transfer' => 'BANK TRANSFER',
                'Bank Transfer' => 'BANK TRANSFER',
                'Credit Card' => 'CREDIT CARD',
                'Cheque' => 'CHEQUE',
                'Cash' => 'CASH',
                'PayPal' => 'PAYPAL',
            ];
            $rawMethod = $invoice->payment_method ?? 'Online Transfer';
            $paymentMethod = $paymentMethodMap[$rawMethod] ?? 'BANK TRANSFER';

            HrOfficialReceipt::create([
                'or_no'                 => $orNo,
                'receipt_date'          => $receiptDate->format('Y-m-d'),
                'company_name'          => $invoice->company_name,
                'description'           => 'Payment for Invoice ' . $invoice->invoice_no,
                'currency'              => $invoice->currency ?? 'MYR',
                'amount'                => $invoice->invoice_amount ?? 0,
                'status'                => 'PAID',
                'created_by'            => $createdByEmails[array_rand($createdByEmails)],
                'invoice_no'            => $invoice->invoice_no,
                'payment_method'        => $paymentMethod,
                'software_handover_id'  => $invoice->software_handover_id,
                'handover_id'           => $invoice->handover_id,
            ]);

            $counter++;
        }
    }
}
