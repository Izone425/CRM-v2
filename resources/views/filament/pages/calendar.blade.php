<x-filament-panels::page>

<div x-data="{ activeTab: 1 }">
    <!-- Tab Buttons -->
    <div class="flex space-x-2 border-b">
        <button
            class="px-4 py-2 focus:outline-none"
            :class="activeTab === 1 ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-600'"
            @click="activeTab = 1">
            Tab 1
        </button>
        <button
            class="px-4 py-2 focus:outline-none"
            :class="activeTab === 2 ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-600'"
            @click="activeTab = 2">
            Tab 2
        </button>
        <button
            class="px-4 py-2 focus:outline-none"
            :class="activeTab === 3 ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-600'"
            @click="activeTab = 3">
            Tab 3
        </button>
        <button
            class="px-4 py-2 focus:outline-none"
            :class="activeTab === 4 ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-600'"
            @click="activeTab = 4">
            Tab 4
        </button>
    </div>

    <!-- Tab Content -->
    <div class="mt-4">
        <!-- Livewire Component for Tab 1 -->
        <div x-show="activeTab === 1" x-cloak>
            @livewire('calendar')
        </div>

        <!-- Livewire Component for Tab 2 -->
        <div x-show="activeTab === 2" x-cloak>
            @livewire('calendarAnalysisDemo')
        </div>

        <!-- Livewire Component for Tab 3 -->
        <div x-show="activeTab === 3" x-cloak>
            @livewire('calendarMonthlyCalendar')
        </div>

        <!-- Livewire Component for Tab 4 -->
        <div x-show="activeTab === 4" x-cloak>
            @livewire('calendarRankingForm')
        </div>
    </div>
</div>



    <!-- For Icons -->
    <script src="https://kit.fontawesome.com/575cbb52f7.js" crossorigin="anonymous"></script>
</x-filament-panels::page>