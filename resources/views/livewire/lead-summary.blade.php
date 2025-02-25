<head>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<div class="p-6 bg-white rounded-lg shadow-lg" wire:poll.1s>
    <div class="flex items-center mb-6 space-x-2">
        <i class="text-lg text-gray-500 fa fa-bookmark"></i>&nbsp;&nbsp;
        <h3 class="text-xl font-bold text-gray-800">All Lead</h3>
    </div>

    <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <!-- Total Leads Card -->
        <div class="flex flex-col items-center justify-center p-4 text-center bg-blue-50 rounded-xl">
            <div class="flex items-center justify-center space-x-4">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-3xl font-bold text-gray-800">{{ $totalLeads }}</p>
                    <p class="text-sm text-gray-600">Total Leads</p>
                </div>
            </div>
        </div>

        <!-- Status Breakdown -->
        <div class="p-6 rounded-lg shadow bg-gray-50">
            <h3 class="mb-4 text-sm font-semibold text-center text-gray-700 uppercase">Status</h3>

            <div class="flex justify-center space-x-10"> <!-- Increased spacing between circles -->
                <!-- Active Leads Doughnut -->
                <div class="text-center">
                    <div class="relative w-28 h-28"> <!-- Increased size -->
                        <svg width="130" height="130" viewBox="0 0 36 36">
                            <!-- Background Circle -->
                            <circle cx="18" cy="18" r="14" stroke="#E5E7EB" stroke-width="5" fill="none"></circle>
                            <!-- Progress Indicator -->
                            <circle cx="18" cy="18" r="14" stroke="#3B82F6" stroke-width="5" fill="none"
                                    stroke-dasharray="100, 100"
                                    stroke-dashoffset="{{ 100 - $activePercentage }}"
                                    stroke-linecap="round"
                                    transform="rotate(-90 18 18)"></circle>
                        </svg>
                        <!-- Percentage in the Center -->
                        <div class="absolute inset-0 flex items-center justify-center text-xl font-bold text-gray-900">
                            {{ $activePercentage }}%
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-700">Active</p>
                </div>
                <div>
                    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                </div>
                <!-- Inactive Leads Doughnut -->
                <div class="text-center">
                    <div class="relative w-28 h-28"> <!-- Increased size -->
                        <svg width="130" height="130" viewBox="0 0 36 36">
                            <!-- Background Circle -->
                            <circle cx="18" cy="18" r="14" stroke="#E5E7EB" stroke-width="5" fill="none"></circle>
                            <!-- Progress Indicator -->
                            <circle cx="18" cy="18" r="14" stroke="#6B7280" stroke-width="5" fill="none"
                                    stroke-dasharray="100, 100"
                                    stroke-dashoffset="{{ 100 - $inactivePercentage }}"
                                    stroke-linecap="round"
                                    transform="rotate(-90 18 18)"></circle>
                        </svg>
                        <!-- Percentage in the Center -->
                        <div class="absolute inset-0 flex items-center justify-center text-xl font-bold text-gray-900">
                            {{ $inactivePercentage }}%
                        </div>
                    </div>
                    <p class="mt-2 text-sm text-gray-700">Inactive</p>
                </div>
            </div>
        </div>


        <!-- Company Size Distribution -->
        <div class="p-4 bg-indigo-50 rounded-xl">
            <h3 class="mb-4 text-sm font-semibold text-center text-gray-700 uppercase">Company Size</h3>
            <div class="space-y-3">
                @foreach($companySizeData as $size => $count)
                    <div class="space-y-1">
                        <!-- Label & Count on Top -->
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-700">{{ ucfirst($size) }}</span>
                            <span class="text-sm font-medium text-gray-700">
                                {{ $count }} ({{ round(($count / max($totalLeads, 1)) * 100, 2) }}%)
                            </span>
                        </div>

                        <!-- Progress Bar Below -->
                        <div class="relative w-full h-3 overflow-hidden bg-gray-200 rounded-full">
                            <div class="absolute top-0 left-0 h-3 transition-all duration-500 rounded-full"
                                style="
                                    width: {{ round(($count / max($totalLeads, 1)) * 100, 2) }}%;
                                    background-color:
                                    {{ $loop->index == 0 ? '#A5D8FF' :
                                       ($loop->index == 1 ? '#60A5FA' :
                                       ($loop->index == 2 ? '#2563EB' : '#7C3AED')) }};
                                ">
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
