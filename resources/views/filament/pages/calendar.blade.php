
<x-filament-panels::page>
    <style>
        .fc-event-title{
            white-space: normal
        }
        .fc-daygrid-dot-event{
            align-items: baseline;
        }
        .fc .fc-button {
            text-transform: capitalize;
        }
    </style>
    <!-- Filter and Badges Section -->
    <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; align-items: center;">
        <!-- Total Demo Badge -->
        <div style="
            background-color: #4F46E5;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 600;">
            Total {{ $totalDemos }}
        </div>

        <!-- New Demo Badge -->
        <div style="
            background-color: #FEE2E2;
            color: #B91C1C;
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 600;">
            Online {{ $newDemos }}
        </div>

        <!-- Second Demo Badge -->
        <div style="
            background-color: #FEF9C3;
            color: #92400E;
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 600;">
            Onsite {{ $secondDemos }}
        </div>

        <!-- Webinar Demo Badge -->
        <div style="
            background-color: #c6fec3;
            color: #67920e;
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 600;">
            Webinar {{ $webinarDemos }}
        </div>

        <!-- Salesperson Filter -->
        <div>
            <form>
                <select
                    wire:model.live="selectedSalesperson"
                    class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                >
                    <option value="" {{ is_null($selectedSalesperson) ? 'selected' : '' }}>All Salesperson</option>
                    @foreach ($salespersonOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div>
            <!-- Demo Type Filter -->
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

        <!-- Salesperson Filter -->
        {{-- <div>
            <select
                id="salespersonDropdown"
                multiple
                wire:model.live="selectedSalespersonIds"
            >
                @foreach ($salespersonOptions as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div> --}}
    </div>

    @livewire(\App\Filament\Widgets\CalendarWidget::class, ['salesperson' => $selectedSalesperson, 'demoType' => $selectedDemoType], key(str()->random()))
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css">
    {{-- <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Choices('#salespersonDropdown', {
                removeItemButton: true, // Enable removing selected items
                placeholderValue: 'Select Salesperson',
                searchPlaceholderValue: 'Search Salesperson',
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js"></script> --}}
</x-filament-panels::page>
