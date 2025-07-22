<div x-data="tooltipHandler()">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <style>
        :root {
            --bar-color-blue: #F6F8FF;
            --bar-color-orange: #ff9500;
            --bg-color-border: #E5E7EB;
            --bg-color-white: white;
            --icon-color: black;
            --bg-demo-red: #FEE2E2;
            --bg-demo-green: #C6FEC3;
            --bg-demo-yellow: #FEF9C3;
            --text-demo-red: #B91C1C;
            --text-demo-green: #67920E;
            --text-demo-yellow: #92400E;
            --text-hyperlink-blue: #338cf0;
            --sidebar-color: black;
        }

        .calendar-header {
            display: grid;
            grid-template-columns: 0.5fr repeat(5, 1fr);
            gap: 1px;
            background: var(--bg-color-border);
            border-radius: 17px;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        /* Salespersonrow */
        .calendar-body {
            display: grid;
            grid-template-columns: 0.5fr repeat(5, 1fr);
            gap: 1px;
            background: var(--bg-color-border);
            border-radius: 17px;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        /* END */

        .header-row {
            display: grid;
            grid-template-columns: 0.5fr repeat(5, 1fr);
            grid-column: 1 / -1;
        }

        .header,
        .time,
        .day,
        .summary-cell {
            background: var(--bg-color-white);
            padding: 10px;
            min-height: 50px;
            text-align: center;
        }

        .header-date {
            font-size: 24px;
        }

        .time {
            font-weight: bold;
        }

        .dropdown-summary {
            grid-column: 1 / -1;
            background-color: var(--bar-color-blue);
            min-height: 0px;
        }

        .summary-cell {
            background-color: var(--bar-color-blue);
            min-height: 30px;
        }

        /* Leave Logo */
        .summary-cell>img {
            height: 40px;
            margin: 0 auto;
        }

        .circle-bg {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 40px;
            height: 40px;
            background-color: var(--bg-color-white);
            border-radius: 50%;
            color: var(--icon-color);
        }


        /* APPOINTMENT-CARD */
        .appointment-card {
            margin-block: 0.5rem;
            width: 100%;
            display: flex;
            flex-direction: row;
            text-align: left;
        }

        .appointment-card-bar {
            background-color: var(--sidebar-color);
            width: 12px;
        }

        .appointment-card-info {
            display: flex;
            flex: 1;
            flex-direction: column;
            padding-block: 0.25rem;
            padding-inline: 0.5rem;
        }

        .appointment-company-name {
            max-width: 200px;
            font-weight: bold;
            color: var(--text-hyperlink-blue);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            text-transform: uppercase;
        }

        /* || END || */


        /* For Salesperson Image */
        .flex-container {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            gap: 0.1rem;
            text-align: center;
        }

        .image-container {
            width: 45px;
            /* Set the container width */
            height: 45px;
            /* Set the container height */
            background-color: grey;
            /* Grey background for placeholder */
            border-radius: 50px;
            /* Rounded corners */
            flex-shrink: 0;
        }

        /* END */

        /* Summary Avatarr */

        .demo-avatar {
            display: grid;
            place-items: center;
            grid-template-columns: repeat(6, 1fr);
            grid-auto-rows: 1fr;
            column-gap: 3px;
        }

        .demo-avatar img {
            /* max-width: 40px; */
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }

        /* || END || Summary Avatar */

        /* public holiday overlay */
        .holiday-overlay {
            position: absolute;
            top: 0;
            /* Adjust dynamically with JS */
            left: calc(1 * (100% / 6));
            /* Start at column 1 */
            width: calc(5 * (100% / 6));
            /* Cover columns 1-5 */
            height: 100%;
            /* Adjust dynamically with JS */
            background: rgba(0, 0, 0, 0.5);
            pointer-events: none;
        }

        /* Initially hide the inner div */
        .hover-content {
            display: none;
            position: absolute;
            padding: 1rem;
            background-color: white;
            flex-direction: column;
            row-gap: 10px;
            right: -5px;
            top: 40px;
            z-index: 10000;
            width: 300px;
            justify-content: space-between;
            overflow-y: auto;
            max-height: 400px;
        }

        .hover-content-flexcontainer {
            display: flex;
            flex-direction: row;
            row-gap: 10px;
            column-gap: 10px;
            justify-content: flex-start;
            align-items: center
        }

        /* When hovering over the container, display the inner div */
        .hover-container:hover .hover-content {
            display: flex;
        }

        .tooltip {
            /* These styles are provided inline by Alpine via :style,
       but you can add additional styling if needed */
            z-index: 100;
        }


        .filter-badges-container {
            display: flex;
            flex-direction: column;
            margin-bottom: 1rem;
            gap: 0.5rem;
        }

        .filter-row {
            display: flex;
            flex-direction: row;
            gap: 0.25rem;
            width: 60%
        }

        .badges-row {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            gap: 0.25rem;
            width: 60%;

        }

        .badges {
            text-align: start;
            width: 100%;
            color: white;
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 1rem;
            font-weight: 600;
        }

        @media (max-width:1400px) {
            .filter-row {
                width: 75%;
            }

            .badges-row {
                width: 75%;
            }
        }

        /* Container */
        .filter-badges-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            padding: 10px;
        }

        /* Filters Section */
        .filter-row {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
            /* Align filters to the right */
            flex-wrap: wrap;
        }

        /* Individual Filter Boxes */
        .filter-row div {
            position: relative;
            width: 180px;
        }

        .filter-row select,
        .filter-row input {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
        }

        /* Badges Row */
        .badges-row {
            display: flex;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        /* Individual Badge */
        .badges {
            flex: 1;
            min-width: 150px;
            background-color: #f3f4f6;
            padding: 12px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            text-align: center;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Specific Badge Colors */
        .badges:nth-child(1) {
            background-color: #4F46E5;
            color: white;
        }

        .badges:nth-child(2) {
            background-color: #22C55E;
            color: white;
        }

        .badges:nth-child(3) {
            background-color: #FACC15;
            color: black;
        }

        .badges:nth-child(4) {
            background-color: #EF4444;
            color: white;
        }

        /* Demo Type & Status Columns */
        .demo-columns {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            flex-wrap: wrap;
        }

        /* Demo Box */
        .demo-box {
            flex: 1;
            min-width: 250px;
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .demo-box h3 {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 10px;
        }

        /* Progress Bar */
        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-bottom: 5px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background-color: #E5E7EB;
            border-radius: 4px;
            position: relative;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
        }

        .session-divider {
            flex: 0.005;
            height: 150px;
            background: #ccc;
            width: 0.5px;
        }
        .view-remarks-link {
            cursor: pointer;
            color: #3b82f6;
            text-decoration: underline;
            font-weight: bold;
            transition: color 0.2s ease;
        }

        .view-remarks-link:hover {
            color: #1d4ed8;
        }
    </style>


    <!-- Filter and Badges Section -->
    <div class="flex items-center gap-2 p-6 mb-6 bg-white shadow-xl rounded-2xl">
        <div class="grid w-full grid-cols-2 gap-8 p-6 mx-auto bg-white shadow-md md:grid-cols-2 max-w-7xl rounded-xl"
            style="width:70%;">
            <h3> Filter </h3><br>
            {{-- Status --}}
            <div class="relative w-full">
                <form>
                    <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                        @click.away="open = false" x-data="{
                            open: false,
                            selected: @entangle('selectedStatus'),
                            allSelected: @entangle('allStatusSelected'),
                            get label() {

                                if (this.allSelected)
                                    return 'All Status'

                                else if (this.selected.length <= 0)
                                    return 'All Status'

                                else {
                                    console.log(this.selected);
                                    return this.selected.join(',');
                                }
                            }
                        }">
                        <!-- Trigger Button -->
                        <div @click="open = !open" class="flex items-center justify-between px-3 py-2">
                            <span x-text="label" class="truncate"></span>
                            <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <!-- Dropdown List -->
                        <div x-show="open"
                            class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg "
                            style="display: none;">
                            <ul class="py-1">
                                <!-- Select All Checkbox -->
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="allStatusSelected"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                    <label class="block ml-3 text-sm font-medium text-gray-700"
                                        style="padding-left: 10px;">
                                        All Status
                                    </label>
                                </li>

                                <!-- Status -->
                                @foreach ($status as $row)
                                    <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                        <input type="checkbox" wire:model.live="selectedStatus"
                                            value="{{ $row }}"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                        <label for="checkbox-{{ $row }}"
                                            class="block ml-3 text-sm font-medium text-gray-700"
                                            style="padding-left: 10px;">
                                            {{ $row }}

                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </form>
            </div>

            <div x-data="weeklyPicker()" class="w-36">
                <!-- Set a fixed width -->
                <input type="text" x-ref="datepicker" wire:model.change='weekDate' placeholder="Date"
                    class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <!-- Demo Type Filter -->
            <div class="relative w-full">
                <form>
                    <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                        @click.away="open = false" x-data="{
                            open: false,
                            selected: @entangle('selectedDemoType'),
                            allSelected: @entangle('allDemoTypeSelected'),
                            get label() {

                                if (this.allSelected)
                                    return 'All Demo Type'

                                else if (this.selected.length <= 0)
                                    return 'All Demo Type'

                                else {
                                    console.log(this.selected);
                                    return this.selected.join(',');
                                }
                            }
                        }">
                        <!-- Trigger Button -->
                        <div @click="open = !open" class="flex items-center justify-between px-3 py-2">
                            <span x-text="label" class="truncate"></span>
                            <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <!-- Dropdown List -->
                        <div x-show="open"
                            class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg "
                            style="display: none;">
                            <ul class="py-1">
                                <!-- Select All Checkbox -->
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="allDemoTypeSelected"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                    <label class="block ml-3 text-sm font-medium text-gray-700"
                                        style="padding-left: 10px;">
                                        All Demo Type
                                    </label>
                                </li>

                                <!-- Individual Salespersons -->
                                @foreach ($demoTypes as $row)
                                    <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                        <input type="checkbox" wire:model.live="selectedDemoType"
                                            value="{{ $row }}"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                        <label for="checkbox-{{ $row }}"
                                            class="block ml-3 text-sm font-medium text-gray-700"
                                            style="padding-left: 10px;">
                                            {{ $row }}

                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Salesperson Filter -->
            <div class="relative w-full">
                <form>
                    <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                        @click.away="open = false" x-data="{
                            open: false,
                            selected: @entangle('selectedSalesPeople'),
                            allSelected: @entangle('allSalesPeopleSelected'),
                            get label() {
                                if (this.allSelected)
                                    return 'All Salesperson';
                                else if (this.selected.length <= 0)
                                    return 'All Salesperson';
                                else
                                    return this.selected.length + ' Salesperson';
                            }
                        }"
                        @if (auth()->user()->role_id == 2) x-bind:class="'pointer-events-none opacity-50'" @endif>

                        <!-- Trigger Button -->
                        <div @click="open = !open" class="flex items-center justify-between px-3 py-2">
                            <span x-text="label" class="truncate"></span>
                            <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <!-- Dropdown List -->
                        <div x-show="open"
                            class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg"
                            style="display: none; height: 30vh">
                            <ul class="py-1">
                                <!-- Select All Checkbox -->
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="allSalesPeopleSelected"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500"
                                        @if (auth()->user()->role_id == 2) disabled @endif />
                                    <label class="block ml-3 text-sm font-medium text-gray-700"
                                        style="padding-left: 10px;">
                                        All Salesperson
                                    </label>
                                </li>

                                <!-- Individual Salespersons -->
                                @foreach ($salesPeople as $row)
                                    <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                        <input type="checkbox" wire:model.live="selectedSalesPeople"
                                            value="{{ $row['id'] }}"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500"
                                            @if (auth()->user()->role_id == 2) disabled @endif />
                                        <label for="checkbox-{{ $row['id'] }}"
                                            class="block ml-3 text-sm font-medium text-gray-700"
                                            style="padding-left: 10px;">
                                            {{ $row['name'] }}
                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Appointment Filter -->
            <div class="relative w-full">
                <form>
                    <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                        @click.away="open = false" x-data="{
                            open: false,
                            selected: @entangle('selectedAppointmentType'),
                            allSelected: @entangle('allAppointmentTypeSelected'),
                            get label() {

                                if (this.allSelected)
                                    return 'All Appointment Type'

                                else if (this.selected.length <= 0)
                                    return 'All Appointment Type'

                                else {
                                    console.log(this.selected);
                                    return this.selected.join(',');
                                }
                            }
                        }">
                        <!-- Trigger Button -->
                        <div @click="open = !open" class="flex items-center justify-between px-3 py-2">
                            <span x-text="label" class="truncate"></span>
                            <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <!-- Dropdown List -->
                        <div x-show="open"
                            class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg "
                            style="display: none;">
                            <ul class="py-1">
                                <!-- Select All Checkbox -->
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="allAppointmentTypeSelected"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                    <label class="block ml-3 text-sm font-medium text-gray-700"
                                        style="padding-left: 10px;">
                                        All Appointment Types
                                    </label>
                                </li>

                                <!-- Individual Salespersons -->
                                @foreach ($appointmentTypes as $row)
                                    <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                        <input type="checkbox" wire:model.live="selectedAppointmentType"
                                            value="{{ $row }}"
                                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                        <label for="checkbox-{{ $row }}"
                                            class="block ml-3 text-sm font-medium text-gray-700"
                                            style="padding-left: 10px;">
                                            {{ $row }}

                                        </label>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
            <div class="relative w-full">
                <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                    @click.away="open = false"
                    x-data="{ open: false }">

                    <!-- Trigger Button -->
                    <div @click="open = !open" class="flex items-center justify-between px-3 py-2">
                        <span class="truncate">{{ $salesFilter === 'timetec_hr' ? 'TimeTec HR Sales' : 'All Sales' }}</span>
                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <!-- Dropdown List -->
                    <div x-show="open" class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg" style="display: none;">
                        <ul class="py-1">
                            <li class="flex items-center px-3 py-2 hover:bg-gray-100 {{ $salesFilter === 'timetec_hr' ? 'bg-gray-100' : '' }}"
                                wire:click="updateSalesFilter('timetec_hr')">
                                <div class="flex items-center">
                                    <span class="w-4 h-4 mr-2">
                                        @if($salesFilter === 'timetec_hr')
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="text-indigo-600">
                                                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </span>
                                    <span class="text-sm font-medium text-gray-700">TimeTec HR Sales</span>
                                </div>
                            </li>
                            <li class="flex items-center px-3 py-2 hover:bg-gray-100 {{ $salesFilter === 'all_sales' ? 'bg-gray-100' : '' }}"
                                wire:click="updateSalesFilter('all_sales')">
                                <div class="flex items-center">
                                    <span class="w-4 h-4 mr-2">
                                        @if($salesFilter === 'all_sales')
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="text-indigo-600">
                                                <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                                            </svg>
                                        @endif
                                    </span>
                                    <span class="text-sm font-medium text-gray-700">All Sales</span>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            {{-- @if(auth()->user()->role_id !== 2)
                <div style="display:flex;align-items:center; font-size: 0.9rem; gap: 0.3rem;" class="px-2 py-2">
                        <input type="checkbox" wire:model.change="showDropdown">
                        <span>{{ $showDropdown ? 'Hide Summary' : 'Show Summary' }}</span>
                </div>
            @endif --}}
        </div>

        <!-- Demo Columns -->
        <div class="w-full max-w-6xl p-6 mx-auto bg-white shadow-md rounded-xl">
            <div class="flex gap-6">

                <!-- Demo Type -->
                <div class="flex-1 p-4 bg-white rounded-lg shadow">
                    <h3 class="text-lg font-semibold">Demo Type</h3>
                    <p class="text-gray-600">Total Demo: {{ $totalDemos['ALL'] }}</p>

                    @foreach ([
                        'NEW DEMO' => '#71eb71',
                        'WEBINAR DEMO' => '#ffff5cbf',
                        'OTHERS' => '#f86f6f',
                        'INTERNAL SALES TASK' => '#3b82f6'
                    ] as $type => $color)
                        @php
                            $count = $totalDemos[$type] ?? 0;
                            $percentage = $totalDemos['ALL'] > 0 ? round(($count / $totalDemos['ALL']) * 100, 2) : 0;
                        @endphp

                        <div class="flex justify-between mt-2 text-sm">
                            <span>{{ ucfirst(strtolower(str_replace('_', ' ', $type))) }}</span>
                            <span>{{ $count }} ({{ $percentage }}%)</span>
                        </div>

                        <div
                            style="position: relative; width: 100%; height: 0.75rem; background-color: #e5e7eb; border-radius: 0.375rem;">
                            <div style="height: 100%; border-radius: 0.375rem; width: {{ $percentage }}%; background-color: {{ $color }};"
                                onmouseover="this.nextElementSibling.style.opacity = '1';"
                                onmouseout="this.nextElementSibling.style.opacity = '0';">
                            </div>

                            @if ($type === 'NEW DEMO' && !empty($newDemoCompanySizeBreakdown))
                                <div
                                    style="position: absolute; top: 100%; left: 50%; transform: translateX(-50%); background-color: black; color: white; padding: 0.5rem; font-size: 0.75rem; border-radius: 0.25rem; box-shadow: 0 2px 6px rgba(0,0,0,0.2); opacity: 0; transition: opacity 0.2s ease; white-space: nowrap; z-index: 999;">
                                    @foreach ($newDemoCompanySizeBreakdown as $label => $sizeCount)
                                        <div>{{ $label }}: {{ $sizeCount }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <!-- Demo Status -->
                <div class="flex-1 p-4 bg-white rounded-lg shadow">
                    <h3 class="text-lg font-semibold">Demo Status</h3>
                    <p class="text-gray-600">Total Demo: {{ $totalDemos['ALL'] ?? 0 }}</p>

                    @foreach (['NEW' => '#ffff5cbf', 'DONE' => '#71eb71', 'CANCELLED' => '#f86f6f'] as $status => $color)
                        @php
                            $count = $totalDemos[$status] ?? 0;
                            $percentage = $totalDemos['ALL'] > 0 ? round(($count / $totalDemos['ALL']) * 100, 2) : 0;
                        @endphp

                        <div class="flex justify-between mt-2 text-sm">
                            <span>{{ ucfirst(strtolower($status)) }}</span>
                            <span>{{ $count }} ({{ $percentage }}%)</span>
                        </div>
                        <div class="w-full h-3 bg-gray-200 rounded-md">
                            <div class="h-full rounded-md"
                                style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                        </div>
                    @endforeach
                </div>

            </div>
        </div>
    </div>
    <br>
    <!-- Calendar Section -->
    <div class="calendar-header">
        <div class="header-row">
            <div class="header"
                style="display:flex; align-items:center; justify-content:center; font-weight:bold; font-size: 1.2rem">
                <div>{{ $this->currentMonth }}</div>
            </div>
            <div class="header">
                <div class="flex">
                    <button wire:click="prevWeek" style="width: 10%;"><i
                            class="fa-solid fa-chevron-left"></i></button>
                    <span class="flex-1" @if ($weekDays[0]['today']) style="background-color: lightblue;" @endif>
                        <div class="text-center header-date">{{ $weekDays[0]['date'] }}</div>
                        <div>{{ $weekDays[0]['day'] }}</div>
                    </span>
                </div>
            </div>
            <div class="header">
                <div class="header-date">{{ $weekDays[1]['date'] }}</div>
                <div>{{ $weekDays[1]['day'] }}</div>
            </div>
            <div class="header">
                <div class="header-date">{{ $weekDays[2]['date'] }}</div>
                <div>{{ $weekDays[2]['day'] }}</div>
            </div>
            <div class="header">
                <div class="header-date">{{ $weekDays[3]['date'] }}</div>
                <div>{{ $weekDays[3]['day'] }}</div>
            </div>
            <div class="header">
                <div class="flex">
                    <div class="flex-1" @if ($weekDays[4]['today']) style="background-color: lightblue;" @endif>
                        <div class="header-date">{{ $weekDays[4]['date'] }}</div>
                        <div>{{ $weekDays[4]['day'] }}</div>
                    </div>
                    <button wire:click="nextWeek" style="width: 10%;"><i
                            class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
        </div>
        <!-- Dropdown -->
        <div class="dropdown-summary"></div>
        @if (auth()->user()->role_id !== 2 && $showDropdown == true)

                <!-- No New Demo -->
                <div class="summary-cell">
                    <div class="circle-bg" style="background-color:var(--text-demo-red)">
                        <i class="fa-solid fa-x" style="font-size: 1.4rem;color:white"></i>
                    </div>
                </div>
                @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
                    <div class="summary-cell">
                        <div class="demo-avatar">
                            @if ($newDemoCount[$day]['noDemo'] < 6)
                                @foreach ($rows as $salesperson)
                                    @if ($salesperson['newDemo'][$day] == 0)
                                        <img data-tooltip="{{ $salesperson['salespersonName'] }}"
                                            src="{{ $salesperson['salespersonAvatar'] }}" alt="Salesperson Avatar"
                                            @mouseover="show($event)" @mousemove="updatePosition($event)"
                                            @mouseout="hide()" />
                                    @endif
                                @endforeach
                            @else
                                @php
                                    $count = 0;
                                    $i = 0;
                                @endphp
                                @while ($count < 5 && $i < count($rows))
                                    @if ($rows[$i]['newDemo'][$day] == 0)
                                        <img data-tooltip="{{ $rows[$i]['salespersonName'] }}"
                                            src="{{ $rows[$i]['salespersonAvatar'] }}" alt="Salesperson Avatar"
                                            @mouseover="show($event)" @mousemove="updatePosition($event)"
                                            @mouseout="hide()" />
                                        @php $count++; @endphp
                                    @endif
                                    @php $i++; @endphp
                                @endwhile
                                <div class="hover-container" style="position: relative">
                                    <div class="circle-bg">
                                        <i class="fa-solid fa-plus"></i>
                                    </div>
                                    <div class="hover-content">

                                        @foreach ($rows as $salesperson)
                                            @if ($salesperson['newDemo'][$day] == 0)
                                                <div class="hover-content-flexcontainer">
                                                    <img src="{{ $salesperson['salespersonAvatar'] }}"
                                                        alt="Salesperson Avatar"
                                                        style="height: 100%; width: auto; flex: 0 0 40px; max-width: 40px;"
                                                        data-tooltip="{{ $salesperson['salespersonName'] }}"
                                                        @mouseover="show($event)" @mousemove="updatePosition($event)"
                                                        @mouseout="hide()" />
                                                    <span
                                                        style="width: 70%;flex: 1;text-align: left">{{ $salesperson['salespersonName'] }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <!-- 1 New Demo -->
                <div class="summary-cell">
                    <div class="circle-bg" style="background-color:#e6e632">
                        <i class="fa-solid fa-1" style="font-size: 1.4rem; color: white"></i>
                    </div>
                </div>
                @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
                    <div class="summary-cell">
                        <div class="demo-avatar">
                            @if ($newDemoCount[$day]['oneDemo'] < 6)
                                @foreach ($rows as $salesperson)
                                    @if ($salesperson['newDemo'][$day] == 1)
                                        <img data-tooltip="{{ $salesperson['salespersonName'] }}"
                                            src="{{ $salesperson['salespersonAvatar'] }}" alt="Salesperson Avatar"
                                            @mouseover="show($event)" @mousemove="updatePosition($event)"
                                            @mouseout="hide()" />
                                    @endif
                                @endforeach
                            @else
                                @php
                                    $count = 0;
                                    $i = 0;
                                @endphp
                                @while ($count < 5 && $i < count($rows))
                                    @if ($rows[$i]['newDemo'][$day] == 1)
                                        <img data-tooltip="{{ $rows[$i]['salespersonName'] }}"
                                            src="{{ $rows[$i]['salespersonAvatar'] }}" alt="Salesperson Avatar"
                                            @mouseover="show($event)" @mousemove="updatePosition($event)"
                                            @mouseout="hide()" />
                                        @php $count++; @endphp
                                    @endif
                                    @php $i++; @endphp
                                @endwhile
                                <div class="hover-container" style="position: relative">
                                    <div class="circle-bg">
                                        <i class="fa-solid fa-plus"></i>
                                    </div>
                                    <div class="hover-content">

                                        @foreach ($rows as $salesperson)
                                            @if ($salesperson['newDemo'][$day] == 1)
                                                <div class="hover-content-flexcontainer">
                                                    <img src="{{ $salesperson['salespersonAvatar'] }}"
                                                        alt="Salesperson Avatar"
                                                        style="height: 100%; width: auto; flex: 0 0 40px; max-width: 40px;"
                                                        data-tooltip="{{ $salesperson['salespersonName'] }}"
                                                        @mouseover="show($event)" @mousemove="updatePosition($event)"
                                                        @mouseout="hide()" />
                                                    <span
                                                        style="width: 70%;flex: 1;text-align: left">{{ $salesperson['salespersonName'] }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <!-- 2 New Demo -->
                <div class="summary-cell">
                    <div class="circle-bg" style="background-color: #30ad2a">
                        <i class="fa-solid fa-2" style="font-size: 1.4rem; color: white"></i>
                    </div>
                </div>

                @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
                    <div class="summary-cell">
                        <div class="demo-avatar">
                            @if ($newDemoCount[$day]['twoDemo'] < 6)
                                @foreach ($rows as $salesperson)
                                    @if ($salesperson['newDemo'][$day] == 2)
                                        <img data-tooltip="{{ $salesperson['salespersonName'] }}"
                                            src="{{ $salesperson['salespersonAvatar'] }}" alt="Salesperson Avatar"
                                            @mouseover="show($event)" @mousemove="updatePosition($event)"
                                            @mouseout="hide()" />
                                    @endif
                                @endforeach
                            @else
                                @php
                                    $count = 0;
                                    $i = 0;
                                @endphp
                                @while ($count < 5 && $i < count($rows))
                                    @if ($rows[$i]['newDemo'][$day] == 2)
                                        <img data-tooltip="{{ $rows[$i]['salespersonName'] }}"
                                            src="{{ $rows[$i]['salespersonAvatar'] }}" alt="Salesperson Avatar"
                                            @mouseover="show($event)" @mousemove="updatePosition($event)"
                                            @mouseout="hide()" />
                                        @php $count++; @endphp
                                    @endif
                                    @php $i++; @endphp
                                @endwhile
                                <div class="hover-container" style="position: relative">
                                    <div class="circle-bg">
                                        <i class="fa-solid fa-plus"></i>
                                    </div>
                                    <div class="hover-content">

                                        @foreach ($rows as $salesperson)
                                            @if ($salesperson['newDemo'][$day] == 2)
                                                <div class="hover-content-flexcontainer">
                                                    <img src="{{ $salesperson['salespersonAvatar'] }}"
                                                        alt="Salesperson Avatar"
                                                        style="height: 100%; width: auto; flex: 0 0 40px; max-width: 40px;"
                                                        data-tooltip="{{ $salesperson['salespersonName'] }}"
                                                        @mouseover="show($event)" @mousemove="updatePosition($event)"
                                                        @mouseout="hide()" />
                                                    <span
                                                        style="width: 70%;flex: 1;text-align: left">{{ $salesperson['salespersonName'] }}</span>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                <!-- On Leave -->
                <div class="summary-cell">
                    <img src={{ asset('img/leave-icon-white.svg') }} alt="TT Leave Icon">
                </div>
                @for ($day = 1; $day < 6; $day++)
                    <div class="summary-cell">
                        <div class="demo-avatar">
                            @foreach ($leaves as $leave)
                                @if ($leave['day_of_week'] == $day)
                                    <img src="{{ $leave['salespersonAvatar'] }}" alt="Salesperson Avatar"
                                        data-tooltip="{{ $leave['salespersonName'] }}" @mouseover="show($event)"
                                        @mousemove="updatePosition($event)" @mouseout="hide()" />
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endfor
        @endif
</div>



<div class="calendar-body">

    <div style="position: absolute; background-color: transparent; left: 0; width: calc(0.5/5.5*100%); height: 0%;">
    </div>

    @if (isset($holidays['1']))
        <div
            style="position: absolute; background-color: #C2C2C2; left: calc((0.5/5.5)* 100%); width: calc((1/5.5)*100%); height: 100%; border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
            <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
            <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['1']['name'] }}</div>
        </div>
    @endif
    @if (isset($holidays['2']))
        <div
            style="position: absolute; background-color: #C2C2C2; left: calc((1.5/5.5)*100%); width: calc((1/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
            <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
            <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['2']['name'] }}</div>
        </div>
    @endif
    @if (isset($holidays['3']))
        <div
            style="position: absolute; background-color: #C2C2C2; left: calc((2.5/5.5)*100%); width: calc((1/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
            <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
            <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['3']['name'] }}</div>
        </div>
    @endif
    @if (isset($holidays['4']))
        <div
            style="position: absolute; background-color: #C2C2C2; left: calc((3.5/5.5)*100%); width: calc((1/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
            <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
            <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['4']['name'] }}</div>
        </div>
    @endif
    @if (isset($holidays['5']))
        <div
            style="position: absolute; background-color: #C2C2C2; left: calc((4.5/5.5)*100%); width: calc((1/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
            <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
            <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['5']['name'] }}</div>
        </div>
    @endif

    <!-- SalesPerson Row -->
    @foreach ($rows as $row)
        <div class="time">
            <div class="flex-container">
                <div class="image-container">
                    <img style="border-radius: 50%;" src="{{ $row['salespersonAvatar'] }}"
                        data-tooltip="{{ $row['salespersonName'] }}" @mouseover="show($event)"
                        @mousemove="updatePosition($event)" @mouseout="hide()">
                </div>
            </div>

        </div>

        @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
            <div class="day">
                @if (isset($row['leave'][$loop->iteration]))
                    <div
                        style="
                        padding-block: 1rem;
                        width: 100%;
                        background-color: #E9EBF0;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        margin-block:0.5rem;
                    ">
                        <div style="flex:1; text-align: center;">
                            <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                            <div style="font-size: 0.8rem;font-style: italic;">
                                {{ $row['leave'][$loop->iteration]['leave_type'] }}
                            </div>
                            <div style="font-size: 0.8rem;">
                                {{ $row['leave'][$loop->iteration]['status'] }} |
                                @if ($row['leave'][$loop->iteration]['session'] === 'full')
                                    Full Day
                                @elseif($row['leave'][$loop->iteration]['session'] === 'am')
                                    Half AM
                                @else
                                    Half PM
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                {{-- @else --}}
                <div x-data="{ expanded: false }">
                    @if (count($row[$day . 'Appointments']) <= 4)
                        @foreach ($row[$day . 'Appointments'] as $appointment)
                            <div class="appointment-card"
                                @if ($appointment->status === 'Done') style="background-color: var(--bg-demo-green)"
                                @elseif ($appointment->status == 'New')
                                    style="background-color: var(--bg-demo-yellow)"
                                @else
                                    style="background-color: var(--bg-demo-red)" @endif>
                                <div class="appointment-card-bar"
                                    @if (isset($appointment->is_internal_task) && $appointment->is_internal_task)
                                        style="background-color: #3b82f6"
                                    @endif></div>
                                <div class="appointment-card-info">
                                    <div class="appointment-demo-type">{{ $appointment->type }}</div>
                                    <div class="appointment-appointment-type">
                                        {{ $appointment->appointment_type }} |
                                        <span style="text-transform:uppercase">{{ $appointment->status }}</span>
                                    </div>

                                    @if (isset($appointment->is_internal_task) && $appointment->is_internal_task)
                                        <!-- For internal tasks, show the remarks preview and a button to view full remarks -->
                                        <div class="appointment-company-name"
                                            x-data="{ remarkModalOpen: false }"
                                            @keydown.escape.window="remarkModalOpen = false">
                                            <button
                                                class="view-remarks-link"
                                                @click="remarkModalOpen = true">
                                                View Remark
                                            </button>

                                            <!-- Remarks Modal (Alpine.js version) -->
                                            <div x-show="remarkModalOpen"
                                                x-transition
                                                @click.outside="remarkModalOpen = false"
                                                class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50">
                                                <div class="relative w-auto p-6 mx-auto mt-20 bg-white rounded-lg shadow-xl" @click.away="remarkModalOpen = false">
                                                    <div class="flex items-start justify-between mb-4">
                                                        <h3 class="text-lg font-medium text-gray-900">{{ $appointment->type }} Remarks</h3>
                                                        <button type="button" @click="remarkModalOpen = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1.5 ml-auto inline-flex items-center">
                                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                    <div class="max-h-[60vh] overflow-y-auto p-4 bg-gray-50 rounded-lg border border-gray-200" style='color:rgb(66, 66, 66);'>
                                                        <div class="whitespace-pre-line">{!! nl2br(e($appointment->remarks ?? 'No remarks available')) !!}</div>
                                                    </div>
                                                    <div class="mt-4 text-center">
                                                        <button @click="remarkModalOpen = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                                            Close
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="appointment-time">
                                            {{ $appointment->start_time }} - {{ $appointment->end_time }}
                                        </div>
                                    @else
                                        <!-- For regular appointments, show company name with link -->
                                        <div class="appointment-company-name" title="{{ $appointment->company_name }}">
                                            <a target="_blank" rel="noopener noreferrer" href={{ $appointment->url }}>
                                                {{ $appointment->company_name }}
                                            </a>
                                        </div>
                                        <div class="appointment-time">{{ $appointment->start_time }} - {{ $appointment->end_time }}</div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @else
                        <template x-if="!expanded">
                            <div>
                                @foreach ($row[$day . 'Appointments'] as $appointment)
                                    @if ($loop->index < 3)
                                        <div class="appointment-card"
                                            @if ($appointment->status === 'Done') style="background-color: var(--bg-demo-green)"
                                            @elseif ($appointment->status == 'New')
                                                style="background-color: var(--bg-demo-yellow)"
                                            @else
                                                style="background-color: var(--bg-demo-red)" @endif>
                                            <div class="appointment-card-bar"></div>
                                            <div class="appointment-card-info">
                                                <div class="appointment-demo-type">{{ $appointment->type }}</div>
                                                <div class="appointment-appointment-type">
                                                    {{ $appointment->appointment_type }}</div>
                                                <div class="appointment-company-name">
                                                    <a target="_blank" rel="noopener noreferrer"
                                                        href={{ $appointment->url }}>
                                                        {{ $appointment->company_name }}
                                                    </a>
                                                </div>
                                                <div class="appointment-time">{{ $appointment->start_time }} -
                                                    {{ $appointment->end_time }}</div>
                                            </div>
                                        </div>
                                    @elseif($loop->index === 3)
                                        <div class="p-2 mb-2 text-center bg-gray-200 border rounded cursor-pointer card"
                                            @click="expanded = true">
                                            +{{ count($row[$day . 'Appointments']) - 3 }} more
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </template>

                        <template x-if="expanded">
                            <div>
                                @foreach ($row[$day . 'Appointments'] as $appointment)
                                    <div class="appointment-card"
                                        @if ($appointment->status === 'Done') style="background-color: var(--bg-demo-green)"
                                        @elseif ($appointment->status == 'New')
                                            style="background-color: var(--bg-demo-yellow)"
                                        @else
                                            style="background-color: var(--bg-demo-red)" @endif>
                                        <div class="appointment-card-bar"></div>
                                        <div class="appointment-card-info">
                                            <div class="appointment-demo-type">{{ $appointment->type }}</div>
                                            <div class="appointment-appointment-type">
                                                {{ $appointment->appointment_type }}</div>
                                            <div class="appointment-company-name">
                                                <a target="_blank" rel="noopener noreferrer"
                                                    href={{ $appointment->url }}>
                                                    {{ $appointment->company_name }}
                                                </a>
                                            </div>
                                            <div class="appointment-time">{{ $appointment->start_time }} -
                                                {{ $appointment->end_time }}</div>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="p-2 mb-2 text-center bg-gray-200 border rounded cursor-pointer card"
                                    @click="expanded = false">
                                    Hide
                                </div>
                            </div>
                        </template>
                    @endif
                </div>
                {{-- @endif --}}
            </div>
        @endforeach
    @endforeach

</div>

<!-- Global tooltip container -->
<div x-show="showTooltip" :style="tooltipStyle"
    class="fixed px-2 py-1 text-sm text-white rounded pointer-events-none tooltip">
    <span x-text="tooltip"></span>
</div>

<script>
    function tooltipHandler() {
        return {
            tooltip: '', // Holds the text to show
            showTooltip: false, // Controls tooltip visibility
            tooltipX: 0, // X position for the tooltip
            tooltipY: 0, // Y position for the tooltip

            // Called when the mouse enters an image
            show(event) {
                this.tooltip = event.target.dataset.tooltip;
                this.showTooltip = true;
                this.updatePosition(event);
            },

            // Update tooltip position on mouse move
            updatePosition(event) {
                // Position the tooltip near the cursor. Adjust offsets as needed.
                this.tooltipX = event.clientX;
                this.tooltipY = event.clientY - 10; // Slightly above the cursor
            },

            // Hide the tooltip when mouse leaves
            hide() {
                this.showTooltip = false;
            },

            // Compute the inline style for the tooltip (positioned relative to the viewport)
            get tooltipStyle() {
                return `left: ${this.tooltipX}px; top: ${this.tooltipY}px; transform: translate(-50%, -100%); background-color:black; z-index: 10000`;
            }
        };
    }

    function weeklyPicker() {
        return {
            init() {
                flatpickr(this.$refs.datepicker, {
                    enable: [date => date.getDay() === 1], // Only allow Mondays
                    dateFormat: 'd-m-Y'
                })
            }
        }
    }

    function showRemarksModal(remarks, type) {
        // Create modal dialog for remarks
        const modal = document.createElement('div');
        modal.style.position = 'fixed';
        modal.style.top = '0';
        modal.style.left = '0';
        modal.style.width = '100%';
        modal.style.height = '100%';
        modal.style.backgroundColor = 'rgba(0,0,0,0.5)';
        modal.style.display = 'flex';
        modal.style.alignItems = 'center';
        modal.style.justifyContent = 'center';
        modal.style.zIndex = '9999';

        const content = document.createElement('div');
        content.style.backgroundColor = 'white';
        content.style.padding = '20px';
        content.style.borderRadius = '8px';
        content.style.maxWidth = '80%';
        content.style.maxHeight = '80%';
        content.style.overflow = 'auto';
        content.style.position = 'relative';

        const closeBtn = document.createElement('button');
        closeBtn.textContent = '×';
        closeBtn.style.position = 'absolute';
        closeBtn.style.top = '10px';
        closeBtn.style.right = '10px';
        closeBtn.style.fontSize = '24px';
        closeBtn.style.border = 'none';
        closeBtn.style.background = 'none';
        closeBtn.style.cursor = 'pointer';
        closeBtn.onclick = function() {
            document.body.removeChild(modal);
        };

        const heading = document.createElement('h3');
        heading.textContent = type + ' Remarks';
        heading.style.marginBottom = '15px';
        heading.style.fontSize = '18px';
        heading.style.fontWeight = 'bold';

        // Create a formatted content container with proper handling of newlines
        const textContainer = document.createElement('div');
        textContainer.style.whiteSpace = 'pre-wrap'; // This preserves newlines and wraps text
        textContainer.style.fontFamily = 'inherit';
        textContainer.style.wordBreak = 'break-word';
        textContainer.style.maxHeight = '60vh';
        textContainer.style.overflowY = 'auto';
        textContainer.style.padding = '10px';
        textContainer.style.border = '1px solid #eee';
        textContainer.style.borderRadius = '4px';
        textContainer.style.backgroundColor = '#f9f9f9';

        // Use innerHTML with line breaks converted to <br> tags
        const formattedRemarks = remarks.replace(/\n/g, '<br>');
        textContainer.innerHTML = formattedRemarks;

        content.appendChild(closeBtn);
        content.appendChild(heading);
        content.appendChild(textContainer);
        modal.appendChild(content);

        document.body.appendChild(modal);

        // Close when clicking outside the content
        modal.onclick = function(e) {
            if (e.target === modal) {
                document.body.removeChild(modal);
            }
        };
    }
</script>

</div>
