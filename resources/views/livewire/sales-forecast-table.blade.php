<div>
    <!-- Sales Forecast Summary -->
    <div class="p-4 mb-4 bg-white rounded-lg shadow-md">
        <h2 class="mb-2 text-lg font-semibold">Sales Forecast Summary</h2>
        <div class="flex justify-between gap-4">
            <div class="flex-1 p-3 text-center bg-red-100 border border-red-300 rounded-lg" style="background-color: rgba(220, 38, 38, 0.1) ;">
                <p class="text-lg font-bold" style="color: rgba(220, 38, 38, 1);">Hot Deals</p>
                <p class="text-sm italic" style="color: rgba(220, 38, 38, 0.8);">(Close in this month)</p>
                <p class="text-xl font-semibold" style="color: rgba(220, 38, 38, 1);">
                    RM {{ number_format($totals['hot'] ?? 0, 2) }}
                </p>
            </div>
            <div class="flex-1 p-3 text-center bg-yellow-100 border border-yellow-300 rounded-lg" style="background-color: rgba(217, 119, 6, 0.1) ;">
                <p class="text-lg font-bold text-yellow-600" style="color: rgba(217, 119, 6, 1);">Warm Deals</p>
                <p class="text-sm italic text-red-500" style="color: rgba(217, 119, 6, 1)">(Close in next month)</p>
                <p class="text-xl font-semibold text-yellow-700" style="color: rgba(217, 119, 6, 1)">
                    RM {{ number_format($totals['warm'] ?? 0, 2) }}
                </p>
            </div>
            <div class="flex-1 p-3 text-center bg-blue-100 border border-blue-300 rounded-lg" style="background-color: rgba(250, 250, 250, 1) ;">
                <p class="text-lg font-bold text-blue-600" style="color: rgba(82, 82, 91, 1) ;">Cold Deals</p>
                <p class="text-sm italic text-red-500" style="color: rgba(82, 82, 91, 1) ;">(To be announced)</p>
                <p class="text-xl font-semibold text-blue-700" style="color: rgba(82, 82, 91, 1) ;">
                    RM {{ number_format($totals['cold'] ?? 0, 2) }}
                </p>
            </div>
        </div>
    </div>

    <!-- Render Filament Table Below -->
    {{ $this->table }}
</div>
