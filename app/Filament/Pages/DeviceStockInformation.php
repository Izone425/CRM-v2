<?php

namespace App\Filament\Pages;

use App\Models\Inventory;
use App\Models\DevicePurchaseItem;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DeviceStockInformation extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Device Stock Information';
    protected static ?string $title = '';
    protected static string $view = 'filament.pages.device-stock-information';
    protected static ?string $slug = 'device-stock-information';

    public function getInventoryData()
    {
        // Define the specific order for inventory items
        $orderedNames = [
            'TC10',
            'TC20',
            'FACE ID 5',
            'FACE ID 6',
            'Beacon-WMC007-V2',
            'NFC-WMC006-Y',
        ];

        // Get all inventory data
        $allInventory = Inventory::all();

        // Create an ordered collection based on our preferred order
        $orderedInventory = collect();

        // First add items in the specified order
        foreach ($orderedNames as $name) {
            $item = $allInventory->first(function ($item) use ($name) {
                // Case insensitive comparison for more flexibility
                return strtolower($item->name) === strtolower($name);
            });

            if ($item) {
                $orderedInventory->push($item);
            }
        }

        // Add any remaining items that weren't in the specified order
        $remainingItems = $allInventory->filter(function ($item) use ($orderedNames) {
            // Case insensitive check
            return !in_array(strtolower($item->name), array_map('strtolower', $orderedNames));
        })->sortBy('name');

        return $orderedInventory->concat($remainingItems);
    }

    public function getPurchaseData()
    {
        // Get the same device models as in inventory
        $inventoryModels = $this->getInventoryData()->pluck('name')->toArray();

        $purchaseData = collect();

        foreach ($inventoryModels as $model) {
            // Get quantities from device_purchase_items based on statuses
            $completedOrder = DevicePurchaseItem::where('model', $model)
                ->where('status', 'Completed Order')
                ->sum('qty');

            $completedShipping = DevicePurchaseItem::where('model', $model)
                ->where('status', 'Completed Shipping')
                ->sum('qty');

            // Create a new object with the same structure as inventory items
            $purchaseItem = (object)[
                'name' => $model,
                'completed_order' => (int)$completedOrder,
                'completed_shipping' => (int)$completedShipping,
                'total_purchase' => (int)$completedOrder + (int)$completedShipping,
            ];

            $purchaseData->push($purchaseItem);
        }

        return $purchaseData;
    }

    public function getDeviceSummary()
    {
        $inventory = $this->getInventoryData();
        $purchases = $this->getPurchaseData();
        $summary = collect();

        foreach ($inventory as $index => $item) {
            $purchaseItem = $purchases->firstWhere('name', $item->name);

            $summaryItem = (object)[
                'name' => $item->name,
                'summary1' => $item->new + $item->in_stock + $item->burning,
                'summary2' => $purchaseItem ? $purchaseItem->total_purchase : 0,
                'total_summary' => ($item->new + $item->in_stock) + ($purchaseItem ? $purchaseItem->total_purchase : 0),
            ];

            $summary->push($summaryItem);
        }

        return $summary;
    }

    // Define colors for different status levels
    public function getColorForQuantity($quantity)
    {
        if ($quantity <= 5) {
            return 'bg-red-100 text-red-800'; // Low stock
        } elseif ($quantity <= 15) {
            return 'bg-yellow-100 text-yellow-800'; // Medium stock
        } else {
            return 'bg-green-100 text-green-800'; // Good stock
        }
    }

    public function getTotalColor($inventory)
    {
        $total = $inventory->new + $inventory->in_stock;
        return $this->getColorForQuantity($total);
    }

    public function getLastUpdatedTimestamp()
    {
        // Get current date and time, set minutes and seconds to 0
        $now = Carbon::now();
        $formattedDate = $now->format('F j, Y'); // September 10, 2025
        $formattedHour = $now->format('g A'); // 3 PM

        return "Last updated: {$formattedDate} at {$formattedHour}";
    }
}
