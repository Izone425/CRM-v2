<?php

namespace App\Console\Commands;

use App\Models\HardwareHandover;
use App\Models\HardwareHandoverV2;
use App\Services\SalesOrderApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateSalesOrderStatus extends Command
{
    protected $signature = 'sales-order:update-status {--dry-run : Run without making changes}';
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

        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Get all hardware handovers that have sales order numbers
        $handovers = HardwareHandoverV2::whereNotNull('sales_order_number')
            ->where('sales_order_number', '!=', '')
            ->whereIn('status', ['Pending Stock', 'Approved']) // Only check pending ones
            ->get();

        $this->info("Found {$handovers->count()} handovers to check");

        $updated = 0;
        $errors = 0;

        foreach ($handovers as $handover) {
            $this->info("Checking SO: {$handover->sales_order_number} (ID: {$handover->id})");

            $statusData = $this->apiService->getSalesOrderStatus($handover->sales_order_number);

            if ($statusData) {
                $currentStatus = $statusData['status'] ?? null;

                $this->line("  Current API Status: {$currentStatus}");
                $this->line("  Current DB Status: {$handover->status}");

                // Map API status to our system status
                $newStatus = $this->mapApiStatusToSystemStatus($currentStatus);

                if ($newStatus && $newStatus !== $handover->status) {
                    $this->info("  -> Updating to: {$newStatus}");

                    if (!$dryRun) {
                        $handover->update([
                            'status' => $newStatus,
                            'sales_order_status' => $currentStatus,
                            'last_status_check' => now(),
                        ]);

                        // Log the status change
                        Log::info('Sales order status updated', [
                            'handover_id' => $handover->id,
                            'so_no' => $handover->sales_order_number,
                            'old_status' => $handover->status,
                            'new_status' => $newStatus,
                            'api_status' => $currentStatus,
                        ]);
                    }

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

        if ($dryRun) {
            $this->warn("DRY RUN - No actual changes were made");
        }

        return 0;
    }

    private function mapApiStatusToSystemStatus(?string $apiStatus): ?string
    {
        if (!$apiStatus) {
            return null;
        }

        // Map API statuses to your system statuses
        return match(strtolower($apiStatus)) {
            'packing' => 'Ready for Delivery',
            'packed' => 'Ready for Delivery',
            'shipped' => 'Shipped',
            'delivered' => 'Delivered',
            'cancelled' => 'Cancelled',
            'pending' => 'Pending Stock',
            'processing' => 'Approved',
            default => null, // Don't update if status is unknown
        };
    }
}
