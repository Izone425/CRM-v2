<div class="p-6 bg-white rounded-lg shadow" wire:poll.1s>
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center space-x-2">
            <i class="text-lg text-gray-500 fa fa-bookmark"></i>&nbsp;&nbsp;
            <h2 class="text-lg font-bold text-gray-800">Follow Up</h2>
        </div>
        <span class="text-lg font-bold text-gray-500">Count: {{ $totalFollowUpLeads }}</span>
    </div>

    <div class="flex justify-center space-x-8">
        @foreach ($statusData as $status => $count)
            @php
                $percentage = $totalFollowUpLeads > 0 ? round(($count / $totalFollowUpLeads) * 100, 2) : 0;
                $color = match($status) {
                    'RFQ-Follow Up' => '#6B7280', // Gray
                    'Hot' => '#EF4444', // Red
                    'Warm' => '#FB923C', // Orange
                    'Cold' => '#3B82F6', // Blue
                    default => '#D1D5DB', // Fallback Gray
                };
            @endphp

            <div class="text-center">
                <div class="relative w-28 h-28">
                    <svg width="130" height="130" viewBox="0 0 36 36">
                        <!-- Background Circle -->
                        <circle cx="18" cy="18" r="14" stroke="#E5E7EB" stroke-width="5" fill="none"></circle>
                        <!-- Progress Indicator -->
                        <circle cx="18" cy="18" r="14" stroke="{{ $color }}" stroke-width="5" fill="none"
                                stroke-dasharray="100, 100"
                                stroke-dashoffset="{{ 100 - $percentage }}"
                                stroke-linecap="round"
                                transform="rotate(-90 18 18)"></circle>
                    </svg>
                    <!-- Number in Center -->
                    <div class="absolute inset-0 flex items-center justify-center text-lg font-bold text-gray-900">
                        {{ $count }}
                    </div>
                </div>
                <p class="mt-2 text-sm text-gray-700">{{ $status }}</p>
            </div>
        @endforeach
    </div>
</div>
