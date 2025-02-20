<x-filament-panels::page>
    <style>
        .navButton:hover {
            background-color: #e4e4e7;
            color: black;
            border-radius: 0.5rem
        }

    </style>


    <div x-data="{ activeTab: 1 }">
        <!-- Tab Buttons -->
        <div class="flex" style="margin-bottom: 2rem;">
            <button class="py-2 px-4 focus:outline-none navButton" :class="activeTab === 1 ? 'border-blue-500 text-blue-500' : 'text-gray-600'" :style="activeTab === 1 ? 'background-color:#431fa1;color:white;border-radius: 0.5rem': ''" ; @click="activeTab = 1">
                Weekly Calendar
            </button>
            <button class="px-4 py-2 focus:outline-none navButton" :class="activeTab === 2 ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-600'" :style="activeTab === 2 ? 'background-color:#431fa1;color:white;border-radius: 0.5rem': ''" ; @click="activeTab = 2">
                Monthly Calendar
            </button>
            <button class="px-4 py-2 focus:outline-none navButton" :class="activeTab === 3 ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-600'" :style="activeTab === 3 ? 'background-color:#431fa1;color:white;border-radius: 0.5rem': ''" ; @click="activeTab = 3">
                Analysis Demo
            </button>
            @if(auth()->user()->role_id!=2)
            <button class="px-4 py-2 focus:outline-none navButton" :class="activeTab === 4 ? 'border-b-2 border-blue-500 text-blue-500' : 'text-gray-600'" :style="activeTab === 4 ? 'background-color:#431fa1;color:white;border-radius: 0.5rem': ''" ; @click="activeTab = 4">
                Ranking Demo
            </button>
            @endif

        </div>

        <!-- Tab Content -->
        <div class="mt-4">
            <!-- Livewire Component for Tab 1 -->
            <div x-show="activeTab === 1" x-cloak>
                @livewire('calendar')
            </div>

            <!-- Livewire Component for Tab 2 -->
            <div x-show="activeTab === 2" x-cloak>
                @livewire('calendarMonthlyCalendar')
            </div>

            <!-- Livewire Component for Tab 3 -->
            <div x-show="activeTab === 3" x-cloak>
                @livewire('calendarAnalysisDemo')
            </div>

            @if(auth()->user()->role_id !== 2)
            <!-- Livewire Component for Tab 4 -->
            <div x-show="activeTab === 4" x-cloak>
                @livewire('calendarRankingForm')
            </div>
            @endif

        </div>
    </div>



    <!-- For Icons -->
    <script src="https://kit.fontawesome.com/575cbb52f7.js" crossorigin="anonymous"></script>
</x-filament-panels::page>
