<?php

namespace App\Console\Commands;

use App\Models\HardwareHandover;
use App\Models\HardwareHandoverV2;
use App\Services\SalesOrderApiService;
use Illuminate\Console\Command;

class UpdateSalesOrderStatus extends Command
{
    protected $signature = 'sales-order:update-status';
    protected $description = 'Update sales order status from IMS API';

    private SalesOrderApiService $apiService;

    public function __construct(SalesOrderApiService $apiService)
    {
        parent::__construct();
        $this->apiService = $apiService;
    }

    public function handle()
    {
        $this->info('Starting sales order status update...');

        // Get all hardware handovers that have sales order numbers
        $handovers = HardwareHandoverV2::whereNotNull('sales_order_number')
            ->where('sales_order_number', '!=', '')
            ->get();

        $this->info("Found {$handovers->count()} handovers to check");
        info("SalesOrderStatusUpdate: Found {$handovers->count()} handovers to check");
        $updated = 0;
        $errors = 0;

        foreach ($handovers as $handover) {
            $this->info("Checking SO: {$handover->sales_order_number} (ID: {$handover->id})");

            $statusData = $this->apiService->getSalesOrderStatus($handover->sales_order_number);

            if ($statusData) {
                $currentStatus = $statusData['status'] ?? null;

                $this->line("  Current API Status: {$currentStatus}");
                $this->line("  Current DB Status: {$handover->sales_order_status}");

                if ($currentStatus && $currentStatus !== $handover->sales_order_status) {
                    $this->info("  -> Updating sales_order_status to: {$currentStatus}");

                    $handover->update([
                        'sales_order_status' => $currentStatus,
                        'last_status_check' => now(),
                    ]);

                    $updated++;
                } else {
                    $this->line("  -> No update needed");
                }
            } else {
                $this->error("  -> Failed to get status");
                $errors++;
            }

            // Small delay to be nice to the API
            usleep(500000); // 0.5 seconds
        }

        $this->info("\nSummary:");
        $this->info("- Checked: {$handovers->count()}");
        $this->info("- Updated: {$updated}");
        $this->info("- Errors: {$errors}");
        info("\nSummary:");
        info("- Checked: {$handovers->count()}");
        info("- Updated: {$updated}");
        info("- Errors: {$errors}");
        
        return 0;
    }
}
