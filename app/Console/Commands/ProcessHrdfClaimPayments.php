<?php

namespace App\Console\Commands;

use App\Models\HrdfClaim;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessHrdfClaimPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hrdf:process-claim-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process HRDF claim payments and update status based on payment status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Starting HRDF claim payment processing...');
        $processedCount = 0;
        $fullyPaidCount = 0;
        $skippedCount = 0;

        // Get all HRDF claims that have invoice numbers but are not yet marked as received
        $hrdfClaims = HrdfClaim::whereNotNull('invoice_number')
            ->where('invoice_number', '!=', '')
            ->whereIn('claim_status', ['SUBMITTED', 'APPROVED'])
            ->get();

        Log::info('Found ' . $hrdfClaims->count() . ' HRDF claims to check for payment status');
        $this->info('Found ' . $hrdfClaims->count() . ' HRDF claims to check for payment status');

        foreach ($hrdfClaims as $claim) {
            try {
                $invoiceNumber = $claim->invoice_number;

                // Get payment status for this invoice
                $paymentStatus = $this->getPaymentStatusForInvoice($invoiceNumber);
                $this->line("Claim {$claim->hrdf_grant_id} - Invoice {$invoiceNumber} has status: {$paymentStatus}");

                // Only process if payment is fully received
                if ($paymentStatus === 'Full Payment') {
                    $oldStatus = $claim->claim_status;

                    $claim->update([
                        'claim_status' => 'RECEIVED',
                        'paid_at' => now(),
                        'payment_verified_at' => now()
                    ]);

                    // Also update related HRDF handover if exists
                    if ($claim->hrdfHandover) {
                        $claim->hrdfHandover->update([
                            'payment_status' => 'Fully Paid',
                            'payment_verified_at' => now()
                        ]);
                    }

                    $processedCount++;
                    $fullyPaidCount++;

                    $this->info("✓ Updated claim {$claim->hrdf_grant_id} from '{$oldStatus}' to 'RECEIVED' (Full Payment)");

                    Log::info("Processed HRDF claim payment status", [
                        'claim_id' => $claim->id,
                        'hrdf_grant_id' => $claim->hrdf_grant_id,
                        'invoice_number' => $invoiceNumber,
                        'old_status' => $oldStatus,
                        'new_status' => 'RECEIVED',
                        'payment_status' => $paymentStatus
                    ]);

                } else {
                    // Skip partial payments and unpaid - no action needed
                    $skippedCount++;
                    $this->line("- Skipped claim {$claim->hrdf_grant_id} - Payment status: {$paymentStatus} (waiting for full payment)");
                }

            } catch (\Exception $e) {
                $this->error("Error processing claim #{$claim->id}: " . $e->getMessage());
                Log::error("Error processing HRDF claim #{$claim->id}: " . $e->getMessage());
            }
        }

        $this->info("=== Processing Summary ===");
        $this->info("Total claims checked: " . $hrdfClaims->count());
        $this->info("Claims updated to RECEIVED: {$fullyPaidCount}");
        $this->info("Claims skipped (not fully paid): {$skippedCount}");

        Log::info("HRDF claim payment processing completed", [
            'total_checked' => $hrdfClaims->count(),
            'updated_to_received' => $fullyPaidCount,
            'skipped_not_full_payment' => $skippedCount
        ]);

        return Command::SUCCESS;
    }

    /**
     * Get payment status for an invoice using the same logic as InvoicesTable
     */
    private function getPaymentStatusForInvoice(string $invoiceNo): string
    {
        try {
            // Get the total invoice amount for this invoice number
            $totalInvoiceAmount = Invoice::where('invoice_no', $invoiceNo)->sum('invoice_amount');

            // Look for this invoice in debtor_agings table
            $debtorAging = DB::table('debtor_agings')
                ->where('invoice_number', $invoiceNo)
                ->first();

            // If no matching record in debtor_agings or outstanding is 0
            if (!$debtorAging || (float)$debtorAging->outstanding === 0.0) {
                return 'Full Payment';
            }

            // If outstanding equals total invoice amount
            if ((float)$debtorAging->outstanding === (float)$totalInvoiceAmount) {
                return 'UnPaid';
            }

            // If outstanding is less than invoice amount but greater than 0
            if ((float)$debtorAging->outstanding < (float)$totalInvoiceAmount && (float)$debtorAging->outstanding > 0) {
                return 'Partial Payment';
            }

            // Fallback (shouldn't normally reach here)
            return 'UnPaid';

        } catch (\Exception $e) {
            $this->error("Error checking payment status for invoice {$invoiceNo}: " . $e->getMessage());
            Log::error("Error checking payment status for invoice {$invoiceNo}: " . $e->getMessage());
            return 'UnPaid';
        }
    }
}
