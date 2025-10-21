<?php

namespace App\Console\Commands;

use App\Models\HardwareHandoverV2;
use App\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProcessFullPaymentHardwareHandover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'handovers:process-full-payment-hardware-handover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process hardware handovers with full payment and update status based on installation type';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Starting paid handovers processing...');
        $processedCount = 0;

        // Get all hardware handovers with Pending Payment status
        $pendingPaymentHandovers = HardwareHandoverV2::where('status', 'Pending Payment')
            ->whereNotNull('invoice_data')
            ->get();

        Log::info('Found ' . $pendingPaymentHandovers->count() . ' handovers in Pending Payment status');

        foreach ($pendingPaymentHandovers as $handover) {
            try {
                // Parse invoice_data JSON
                $invoiceData = json_decode($handover->invoice_data, true);

                if (!$invoiceData || !is_array($invoiceData)) {
                    $this->warn("Invalid invoice data for handover #{$handover->id}");
                    continue;
                }

                // Check if all invoices have "Full Payment" status using the same logic as InvoicesTable
                $allFullyPaid = true;
                foreach ($invoiceData as $invoice) {
                    if (!isset($invoice['invoice_no'])) {
                        $this->warn("Missing invoice_no in handover #{$handover->id}");
                        $allFullyPaid = false;
                        break;
                    }

                    $paymentStatus = $this->getPaymentStatusForInvoice($invoice['invoice_no']);

                    if ($paymentStatus !== 'Full Payment') {
                        $allFullyPaid = false;
                        $this->line("Handover #{$handover->id} - Invoice {$invoice['invoice_no']} has status: {$paymentStatus}");
                        break;
                    }
                }

                if (!$allFullyPaid) {
                    $this->line("Handover #{$handover->id} - Not all invoices are fully paid, skipping");
                    continue;
                }

                // All invoices are fully paid, process based on installation_type
                $newStatus = $this->getNewStatusByInstallationType($handover->installation_type);

                if ($newStatus) {
                    $handover->update([
                        'status' => $newStatus,
                        'fully_paid_at' => now(),
                        'installation_pending_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $processedCount++;
                    $this->info("Updated handover #{$handover->id} from 'Pending Payment' to '{$newStatus}' (Installation: {$handover->installation_type})");

                    Log::info("Processed handover #{$handover->id}: {$handover->installation_type} -> {$newStatus}");
                } else {
                    $this->warn("Unknown installation type '{$handover->installation_type}' for handover #{$handover->id}");
                }

            } catch (\Exception $e) {
                $this->error("Error processing handover #{$handover->id}: " . $e->getMessage());
                Log::error("Error processing handover #{$handover->id}: " . $e->getMessage());
            }
        }

        $this->info("=== Processing Summary ===");
        $this->info("Total handovers processed: {$processedCount}");

        Log::info("Paid handovers processing completed. Processed {$processedCount} handovers.");

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

            // if ($totalInvoiceAmount <= 0) {
            //     $this->warn("Invoice {$invoiceNo} not found in invoices table or has zero amount");
            //     return 'UnPaid';
            // }

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

    /**
     * Get new status based on installation type
     */
    private function getNewStatusByInstallationType(?string $installationType): ?string
    {
        return match(strtolower($installationType ?? '')) {
            'courier' => 'Pending: Courier',
            'self_pick_up' => 'Pending Admin: Self Pick-Up',
            'external_installation' => 'Pending: External Installation',
            'internal_installation' => 'Pending: Internal Installation',
            default => null
        };
    }
}
