<x-filament-panels::page>
    <style>
        .fc-event-title {
            white-space: normal;
        }
        .fc-daygrid-dot-event {
            align-items: baseline;
        }
        .fc .fc-button {
            text-transform: capitalize;
        }
        .fc-daygrid-day {
            height: 150px;
        }
        .fc-daygrid-day-frame {
            height: 100%;
        }
    </style>

    <!-- Filter and Badges Section -->
    <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; align-items: center;">

        <!-- Total Demo Badge -->
        <div style="background-color: #4F46E5; color: white; padding: 8px 16px; border-radius: 9999px; font-size: 14px; font-weight: 600;">
            Total {{ $totalDemos }}
        </div>

        <!-- New Demo Badge -->
        <div style="background-color: #FEE2E2; color: #B91C1C; padding: 8px 16px; border-radius: 9999px; font-size: 14px; font-weight: 600;">
            Online {{ $newDemos }}
        </div>

        <!-- Second Demo Badge -->
        <div style="background-color: #FEF9C3; color: #92400E; padding: 8px 16px; border-radius: 9999px; font-size: 14px; font-weight: 600;">
            Onsite {{ $secondDemos }}
        </div>

        <!-- Webinar Demo Badge -->
        <div style="background-color: #C6FEC3; color: #67920E; padding: 8px 16px; border-radius: 9999px; font-size: 14px; font-weight: 600;">
            Webinar {{ $webinarDemos }}
        </div>

        <!-- Salesperson Filter -->
        <div class="relative">
            <form>
                <div
                    class="block w-full bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                    @click.away="open = false"
                    x-data="{
                        open: false,
                        selected: @entangle('selectedSalespersons'),
                        allSelected: @entangle('allSelected'),
                        get label() {
                            if (this.allSelected) {
                                return 'All Salesperson';
                            }
                            const count = this.selected.length;
                            if (count === 1) {
                                return '1 Salesperson Selected';
                            }
                            return `${count} Salespersons Selected`;
                        }
                    }"
                >
                    <!-- Trigger Button -->
                    <div
                        @click="open = !open"
                        class="flex items-center justify-between px-3 py-2"
                        style="width: 200px;"
                    >
                        <span x-text="label" class="truncate"></span>
                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <!-- Dropdown List -->
                    <div
                        x-show="open"
                        class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg max-h-60"
                        style="display: none;"
                    >
                        <ul class="py-1">
                            <!-- Select All Checkbox -->
                            <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                <input
                                    type="checkbox"
                                    wire:model.live="allSelected"
                                    @change="$dispatch('input', allSelected ? @json(array_keys($salespersonOptions)) : [])"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500"
                                />
                                <label class="block ml-3 text-sm font-medium text-gray-700" style="padding-left: 10px;">
                                    All Salesperson
                                </label>
                            </li>

                            <!-- Individual Salespersons -->
                            @foreach ($salespersonOptions as $id => $name)
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input
                                        type="checkbox"
                                        wire:model.live="selectedSalespersons"
                                        value="{{ $id }}"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500"
                                    />
                                    <label for="checkbox-{{ $id }}" class="block ml-3 text-sm font-medium text-gray-700" style="padding-left: 10px;">
                                        {{ $name }}
                                    </label>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </form>
        </div>

        <!-- Demo Appointment Type Filter -->
        <div>
            <form>
                <select
                    wire:model.live="selectedDemoAppointmentType"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
                    <option value="" {{ is_null($selectedDemoAppointmentType) ? 'selected' : '' }}>All Demo Appointment Types</option>
                    @foreach ($demoAppointmentTypeOptions as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <!-- Demo Type Filter -->
        <div>
            <form>
                <select
                    wire:model.live="selectedDemoType"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
                    <option value="" {{ is_null($selectedDemoType) ? 'selected' : '' }}>All Demo Types</option>
                    @foreach ($demoTypeOptions as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>

    <!-- Calendar Widget -->
    @livewire(
        \App\Filament\Widgets\CalendarWidget::class,
        [
            'salespersons' => $selectedSalespersons,
            'demoType' => $selectedDemoType,
            'demoAppointmentType' => $selectedDemoAppointmentType
        ],
        key(str()->random())
    )

    <!-- Weekly -->
    @livewire('calendar')

    <!-- For Icons -->
    <script src="https://kit.fontawesome.com/575cbb52f7.js" crossorigin="anonymous"></script> 
</x-filament-panels::page>
