<?php
// filepath: /var/www/html/timeteccrm/app/Console/Commands/SyncHandoversStatus.php

namespace App\Console\Commands;

use App\Models\HardwareHandover;
use App\Models\HardwareHandoverV2;
use App\Models\SoftwareHandover;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncHandoversStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'handovers:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync hardware handovers status based on invoice type and software handover migration status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Log::info('Starting handover synchronization...');
        $singleInvoiceCount = 0;
        $combinedInvoiceCount = 0;

        // Handle Single Invoice Type - Direct to Pending Payment
        $singleInvoiceHandovers = HardwareHandoverV2::where('status', 'Pending Migration')
            ->where('invoice_type', 'single')
            ->get();

        Log::info('Found ' . $singleInvoiceHandovers->count() . ' single invoice handovers in Pending Migration');

        foreach ($singleInvoiceHandovers as $handover) {
            $handover->update([
                'status' => 'Pending Payment',
                'payment_pending_at' => now(),
                'updated_at' => now(),
            ]);

            $singleInvoiceCount++;
            $this->info("Updated single invoice hardware handover #{$handover->id} directly to Pending Payment");
        }

        // Handle Combined Invoice Type - Check Software Handover Migration
        $combinedInvoiceHandovers = HardwareHandoverV2::where('status', 'Pending Migration')
            ->where('invoice_type', 'combined')
            ->get();

        Log::info('Found ' . $combinedInvoiceHandovers->count() . ' combined invoice handovers in Pending Migration');

        foreach ($combinedInvoiceHandovers as $handover) {
            // Check if related software handover is migrated
            $migratedSoftwareHandover = SoftwareHandover::where('lead_id', $handover->lead_id)
                ->where('data_migrated', true)
                ->whereNotNull('completed_at')
                ->first();

            if ($migratedSoftwareHandover) {
                $handover->update([
                    'status' => 'Pending Payment',
                    'payment_pending_at' => now(),
                    'completed_migration_at' => now(),
                    'updated_at' => now(),
                ]);

                $combinedInvoiceCount++;
                $this->info("Updated combined invoice hardware handover #{$handover->id} to Pending Payment (software migrated)");
            } else {
                $this->line("Combined invoice hardware handover #{$handover->id} waiting for software migration");
            }
        }
        $totalCount = $singleInvoiceCount + $combinedInvoiceCount;

        $this->info("=== Sync Summary ===");
        $this->info("Single invoice handovers updated: {$singleInvoiceCount}");
        $this->info("Combined invoice handovers updated: {$combinedInvoiceCount}");
        $this->info("Total handovers updated: {$totalCount}");

        Log::info("Sync completed. Updated {$totalCount} hardware handovers. Single: {$singleInvoiceCount}, Combined: {$combinedInvoiceCount}");

        return Command::SUCCESS;
    }
}
