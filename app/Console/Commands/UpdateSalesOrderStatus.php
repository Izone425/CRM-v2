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

            // Check if sales_order_number contains comma (multiple SOs)
            if (strpos($handover->sales_order_number, ',') !== false) {
                // Handle multiple sales orders
                $salesOrderNumbers = array_map('trim', explode(',', $handover->sales_order_number));
                $this->line("  Found multiple SOs: " . implode(', ', $salesOrderNumbers));

                $statuses = [];
                $hasError = false;

                // Check each sales order individually
                foreach ($salesOrderNumbers as $soNumber) {
                    $this->line("    Checking: {$soNumber}");

                    $statusData = $this->apiService->getSalesOrderStatus($soNumber);

                    if ($statusData) {
                        $currentStatus = $statusData['status'] ?? null;
                        $this->line("      API Status: " . ($currentStatus ?? 'NULL'));

                        if ($currentStatus) {
                            $statuses[] = $currentStatus;
                        } else {
                            $statuses[] = 'Not found';
                        }
                    } else {
                        $this->error("      -> Failed to get status for {$soNumber}");
                        $statuses[] = 'Not found';
                        $hasError = true;
                    }

                    // Small delay between individual SO checks
                    usleep(250000); // 0.25 seconds
                }

                // Combine all statuses with comma
                $combinedStatus = implode(', ', $statuses);
                $this->line("  Combined Status: {$combinedStatus}");
                $this->line("  Current DB Status: {$handover->sales_order_status}");

                // Update if status changed
                if ($combinedStatus !== $handover->sales_order_status) {
                    $this->info("  -> Updating sales_order_status to: {$combinedStatus}");

                    $handover->update([
                        'sales_order_status' => $combinedStatus,
                        'last_status_check' => now(),
                    ]);

                    $updated++;
                } else {
                    $this->line("  -> No update needed");

                    // Still update the last check timestamp
                    $handover->update(['last_status_check' => now()]);
                }

                if ($hasError) {
                    $errors++;
                }

            } else {
                // Handle single sales order (existing logic)
                $statusData = $this->apiService->getSalesOrderStatus($handover->sales_order_number);

                if ($statusData) {
                    $currentStatus = $statusData['status'] ?? null;

                    $this->line("  Current API Status: " . ($currentStatus ?? 'NULL'));
                    $this->line("  Current DB Status: " . ($handover->sales_order_status ?? 'NULL'));

                    if ($currentStatus) {
                        // Update with the actual status
                        if ($currentStatus !== $handover->sales_order_status) {
                            $this->info("  -> Updating sales_order_status to: {$currentStatus}");

                            $handover->update([
                                'sales_order_status' => $currentStatus,
                                'last_status_check' => now(),
                            ]);

                            $updated++;
                        } else {
                            $this->line("  -> No update needed");

                            // Still update the last check timestamp
                            $handover->update(['last_status_check' => now()]);
                        }
                    } else {
                        // API returned data but no status - mark as "Not found"
                        $this->warn("  -> API returned no status, marking as 'Not found'");

                        $handover->update([
                            'sales_order_status' => 'Not found',
                            'last_status_check' => now(),
                        ]);

                        $updated++;
                        $errors++;
                    }
                } else {
                    // API call failed completely - mark as "Not found"
                    $this->error("  -> Failed to get status, marking as 'Not found'");

                    $handover->update([
                        'sales_order_status' => 'Not found',
                        'last_status_check' => now(),
                    ]);

                    $updated++;
                    $errors++;
                }
            }

            // Delay between handover records
            usleep(500000); // 0.5 seconds
        }

        $this->info("\nSummary:");
        $this->info("- Checked: {$handovers->count()}");
        $this->info("- Updated: {$updated}");
        $this->info("- Errors: {$errors}");

        return 0;
    }
}
