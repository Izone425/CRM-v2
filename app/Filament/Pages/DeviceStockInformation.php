<?php

namespace App\Filament\Pages;

use App\Models\Inventory;
use Carbon\Carbon;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;

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
