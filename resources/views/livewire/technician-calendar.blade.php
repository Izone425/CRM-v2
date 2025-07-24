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

        .reseller-name {
            font-weight: bold;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: #555;
            margin-bottom: 2px;
            border-bottom: 1px dashed #ccc;
            padding-bottom: 2px;
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


<div class="flex items-center gap-2 p-6 mb-6 bg-white shadow-xl rounded-2xl">
    <div class="grid w-full grid-cols-2 gap-8 p-6 mx-auto bg-white shadow-md md:grid-cols-2 max-w-7xl rounded-xl"
        style="width:70%;">
        <h3> Filter </h3><br>

        {{-- Status Filter --}}
        <div class="relative w-full">
            <form>
                <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                    @click.away="open = false" x-data="{
                        open: false,
                        selected: @entangle('selectedStatus'),
                        allSelected: @entangle('allStatusSelected'),
                        get label() {
                            if (this.allSelected) return 'All Status'
                            else if (this.selected.length <= 0) return 'All Status'
                            else return this.selected.join(',');
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

                            <!-- Individual Status Options -->
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

        {{-- Date Picker --}}
        <div x-data="weeklyPicker()" class="w-36">
            <input type="text" x-ref="datepicker" wire:model.change='weekDate' placeholder="Date"
                class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
        </div>

        {{-- Repair Type Filter --}}
        <div class="relative w-full">
            <form>
                <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                    @click.away="open = false" x-data="{
                        open: false,
                        selected: @entangle('selectedRepairType'),
                        allSelected: @entangle('allRepairTypeSelected'),
                        get label() {
                            if (this.allSelected) return 'All Repair Types'
                            else if (this.selected.length <= 0) return 'All Repair Types'
                            else return this.selected.join(',');
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
                                <input type="checkbox" wire:model.live="allRepairTypeSelected"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                <label class="block ml-3 text-sm font-medium text-gray-700"
                                    style="padding-left: 10px;">
                                    All Repair Types
                                </label>
                            </li>

                            <!-- Individual Repair Types -->
                            @foreach ($repairTypes as $row)
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="selectedRepairType"
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

        {{-- Technicians Filter --}}
        <div class="relative w-full">
            <form>
                <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                    @click.away="open = false" x-data="{
                        open: false,
                        selected: @entangle('selectedTechnicians'),
                        allSelected: @entangle('allTechniciansSelected'),
                        get label() {
                            if (this.allSelected)
                                return 'All Technicians';
                            else if (this.selected.length <= 0)
                                return 'All Technicians';
                            else
                                return this.selected.length + ' Technicians';
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
                        class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg"
                        style="display: none; height: 30vh">
                        <ul class="py-1">
                            <!-- Select All Checkbox -->
                            <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                <input type="checkbox" wire:model.live="allTechniciansSelected"
                                    class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500"
                                    @if (auth()->user()->role_id == 9) disabled @endif />
                                <label class="block ml-3 text-sm font-medium text-gray-700"
                                    style="padding-left: 10px;">
                                    All Technicians
                                </label>
                            </li>

                            <!-- Individual Technicians -->
                            @foreach ($technicians as $row)
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="selectedTechnicians"
                                        value="{{ $row['name'] }}"
                                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500"
                                        @if (auth()->user()->role_id == 9) disabled @endif />
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

        {{-- Appointment Type Filter --}}
        <div class="relative w-full">
            <form>
                <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                    @click.away="open = false" x-data="{
                        open: false,
                        selected: @entangle('selectedAppointmentType'),
                        allSelected: @entangle('allAppointmentTypeSelected'),
                        get label() {
                            if (this.allSelected)
                                return 'All Appointment Types'
                            else if (this.selected.length <= 0)
                                return 'All Appointment Types'
                            else
                                return this.selected.join(',');
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

                            <!-- Individual Appointment Types -->
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

        {{-- @if(auth()->user()->role_id !== 9)
            <div style="display:flex;align-items:center; font-size: 0.9rem; gap: 0.3rem;" class="px-2 py-2">
                <input type="checkbox" wire:model.change="showDropdown">
                <span>{{ $showDropdown ? 'Hide Summary' : 'Show Summary' }}</span>
            </div>
        @endif --}}
    </div>

    <!-- Repair Breakdown -->
    <div class="w-full max-w-6xl p-6 mx-auto bg-white shadow-md rounded-xl">
        <div class="flex gap-6">

            <!-- Repair Type -->
            <div class="flex-1 p-4 bg-white rounded-lg shadow">
                <h3 class="text-lg font-semibold">Repair Type</h3>
                <p class="text-gray-600">Total Repairs: {{ $totalRepairs['ALL'] }}</p>

                @foreach ([
                    'NEW INSTALLATION' => '#71eb71',
                    'REPAIR' => '#ffff5cbf',
                    'MAINTENANCE SERVICE' => '#f86f6f',
                    'SITE SURVEY' => '#ffa83c',
                    'INTERNAL TECHNICIAN TASK' => '#60a5fa'
                ] as $type => $color)
                    @php
                        $count = $repairBreakdown[$type] ?? 0;
                        $percentage = $totalRepairs['ALL'] > 0 ? round(($count / $totalRepairs['ALL']) * 100, 2) : 0;
                    @endphp

                    <div class="flex justify-between mt-2 text-sm">
                        <span>{{ ucfirst(strtolower(str_replace('_', ' ', $type))) }}</span>
                        <span>{{ $count }} ({{ $percentage }}%)</span>
                    </div>

                    <div class="w-full h-3 mt-1 mb-3 bg-gray-200 rounded-md">
                        <div class="h-full rounded-md" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
                    </div>
                @endforeach
            </div>

            <!-- Repair Status -->
            <div class="flex-1 p-4 bg-white rounded-lg shadow">
                <h3 class="text-lg font-semibold">Repair Status</h3>
                <p class="text-gray-600">Total Repairs: {{ $totalRepairs['ALL'] }}</p>

                @foreach (['NEW' => '#ffff5cbf', 'DONE' => '#71eb71', 'CANCELLED' => '#f86f6f'] as $status => $color)
                    @php
                        $count = $totalRepairs[$status] ?? 0;
                        $percentage = $totalRepairs['ALL'] > 0 ? round(($count / $totalRepairs['ALL']) * 100, 2) : 0;
                    @endphp

                    <div class="flex justify-between mt-2 text-sm">
                        <span>{{ ucfirst(strtolower($status)) }}</span>
                        <span>{{ $count }} ({{ $percentage }}%)</span>
                    </div>
                    <div class="w-full h-3 mt-1 mb-3 bg-gray-200 rounded-md">
                        <div class="h-full rounded-md" style="width: {{ $percentage }}%; background-color: {{ $color }};"></div>
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
            <div>{{ $currentMonth }}</div>
        </div>
        <div class="header">
            <div class="flex">
                <button wire:click="prevWeek" style="width: 10%;"><i class="fa-solid fa-chevron-left"></i></button>
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
                <button wire:click="nextWeek" style="width: 10%;"><i class="fa-solid fa-chevron-right"></i></button>
            </div>
        </div>
    </div>

    <!-- Dropdown -->
    <div class="dropdown-summary"></div>

    @if (auth()->user()->role_id !== 9 && $showDropdown == true)
        <!-- No Repair -->
        <div class="summary-cell">
            <div class="circle-bg" style="background-color:var(--text-demo-red)">
                <i class="fa-solid fa-x" style="font-size: 1.4rem;color:white"></i>
            </div>
        </div>
        @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
            <div class="summary-cell">
                <div class="demo-avatar">
                    @if ($newRepairCount[$day]['noRepair'] < 6)
                        @foreach ($rows as $technician)
                            @if ($technician['newRepair'][$day] == 0)
                                <img data-tooltip="{{ $technician['technicianName'] }}"
                                    src="{{ $technician['technicianAvatar'] }}" alt="Technician Avatar"
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
                            @if ($rows[$i]['newRepair'][$day] == 0)
                                <img data-tooltip="{{ $rows[$i]['technicianName'] }}"
                                    src="{{ $rows[$i]['technicianAvatar'] }}" alt="Technician Avatar"
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
                                @foreach ($rows as $technician)
                                    @if ($technician['newRepair'][$day] == 0)
                                        <div class="hover-content-flexcontainer">
                                            <img src="{{ $technician['technicianAvatar'] }}"
                                                alt="Technician Avatar"
                                                style="height: 100%; width: auto; flex: 0 0 40px; max-width: 40px;"
                                                data-tooltip="{{ $technician['technicianName'] }}"
                                                @mouseover="show($event)" @mousemove="updatePosition($event)"
                                                @mouseout="hide()" />
                                            <span style="width: 70%;flex: 1;text-align: left">{{ $technician['technicianName'] }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <!-- 1 Repair -->
        <div class="summary-cell">
            <div class="circle-bg" style="background-color:#e6e632">
                <i class="fa-solid fa-1" style="font-size: 1.4rem; color: white"></i>
            </div>
        </div>
        @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
            <div class="summary-cell">
                <div class="demo-avatar">
                    @if ($newRepairCount[$day]['oneRepair'] < 6)
                        @foreach ($rows as $technician)
                            @if ($technician['newRepair'][$day] == 1)
                                <img data-tooltip="{{ $technician['technicianName'] }}"
                                    src="{{ $technician['technicianAvatar'] }}" alt="Technician Avatar"
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
                            @if ($rows[$i]['newRepair'][$day] == 1)
                                <img data-tooltip="{{ $rows[$i]['technicianName'] }}"
                                    src="{{ $rows[$i]['technicianAvatar'] }}" alt="Technician Avatar"
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
                                @foreach ($rows as $technician)
                                    @if ($technician['newRepair'][$day] == 1)
                                        <div class="hover-content-flexcontainer">
                                            <img src="{{ $technician['technicianAvatar'] }}"
                                                alt="Technician Avatar"
                                                style="height: 100%; width: auto; flex: 0 0 40px; max-width: 40px;"
                                                data-tooltip="{{ $technician['technicianName'] }}"
                                                @mouseover="show($event)" @mousemove="updatePosition($event)"
                                                @mouseout="hide()" />
                                            <span style="width: 70%;flex: 1;text-align: left">{{ $technician['technicianName'] }}</span>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        <!-- 2+ Repairs -->
        <div class="summary-cell">
            <div class="circle-bg" style="background-color: #30ad2a">
                <i class="fa-solid fa-2" style="font-size: 1.4rem; color: white"></i>
            </div>
        </div>
        @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
            <div class="summary-cell">
                <div class="demo-avatar">
                    @if ($newRepairCount[$day]['multipleRepair'] < 6)
                        @foreach ($rows as $technician)
                            @if ($technician['newRepair'][$day] >= 2)
                                <img data-tooltip="{{ $technician['technicianName'] }}"
                                    src="{{ $technician['technicianAvatar'] }}" alt="Technician Avatar"
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
                            @if ($rows[$i]['newRepair'][$day] >= 2)
                                <img data-tooltip="{{ $rows[$i]['technicianName'] }}"
                                    src="{{ $rows[$i]['technicianAvatar'] }}" alt="Technician Avatar"
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
                                @foreach ($rows as $technician)
                                    @if ($technician['newRepair'][$day] >= 2)
                                        <div class="hover-content-flexcontainer">
                                            <img src="{{ $technician['technicianAvatar'] }}"
                                                alt="Technician Avatar"
                                                style="height: 100%; width: auto; flex: 0 0 40px; max-width: 40px;"
                                                data-tooltip="{{ $technician['technicianName'] }}"
                                                @mouseover="show($event)" @mousemove="updatePosition($event)"
                                                @mouseout="hide()" />
                                            <span style="width: 70%;flex: 1;text-align: left">{{ $technician['technicianName'] }}</span>
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
                            <img src="{{ $leave['technicianAvatar'] }}" alt="Salesperson Avatar"
                                data-tooltip="{{ $leave['technicianName'] }}" @mouseover="show($event)"
                                @mousemove="updatePosition($event)" @mouseout="hide()" />
                        @endif
                    @endforeach
                </div>
            </div>
        @endfor
        {{-- @for ($day = 1; $day < 6; $day++)
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
        @endfor --}}
    @endif
</div>

<div class="calendar-body">
    <div style="position: absolute; background-color: transparent; left: 0; width: calc(0.5/5.5*100%); height: 0%;"></div>

    <!-- Public Holidays -->
    @if (isset($holidays['1']))
        <div style="position: absolute; background-color: #C2C2C2; left: calc((0.5/5.5)* 100%); width: calc((1/5.5)*100%); height: 100%; border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
            <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
            <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['1']['name'] }}</div>
        </div>
    @endif
    @if (isset($holidays['2']))
        <div style="position: absolute; background-color: #C2C2C2; left: calc((1.5/5.5)*100%); width: calc((1/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
            <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
            <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['2']['name'] }}</div>
        </div>
    @endif
    @if (isset($holidays['3']))
        <div style="position: absolute; background-color: #C2C2C2; left: calc((2.5/5.5)*100%); width: calc((1/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
            <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
            <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['3']['name'] }}</div>
        </div>
    @endif
    @if (isset($holidays['4']))
        <div style="position: absolute; background-color: #C2C2C2; left: calc((3.5/5.5)*100%); width: calc((1/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
            <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
            <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['4']['name'] }}</div>
        </div>
    @endif
    @if (isset($holidays['5']))
        <div style="position: absolute; background-color: #C2C2C2; left: calc((4.5/5.5)*100%); width: calc((1/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
            <div style="font-weight: bold;font-size: 1.2rem; ">Public Holiday</div>
            <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['5']['name'] }}</div>
        </div>
    @endif

    <!-- Technician Rows -->
    @foreach ($rows as $row)
        <div class="time">
            <div class="flex-container">
                <div class="image-container">
                    <img style="border-radius: 50%;" src="{{ $row['technicianAvatar'] }}"
                        data-tooltip="{{ $row['technicianName'] }}" @mouseover="show($event)"
                        @mousemove="updatePosition($event)" @mouseout="hide()">
                </div>
            </div>
        </div>

        @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
            <div class="day">
                @if (isset($row['leave'][$loop->iteration]))
                    <div style="padding-block: 1rem; width: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center; margin-block:0.5rem;">
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

                <div x-data="{ expanded: false }">
                    @if (count($row[$day . 'Appointments']) <= 4)
                        @foreach ($row[$day . 'Appointments'] as $appointment)
                            <div class="appointment-card"
                                @if ($appointment->status === 'Done') style="background-color: var(--bg-demo-green)"
                                @elseif ($appointment->status == 'New') style="background-color: var(--bg-demo-yellow)"
                                @else style="background-color: var(--bg-demo-red)" @endif>
                                <div class="appointment-card-bar"
                                    @if (isset($appointment->is_internal_task) && $appointment->is_internal_task)
                                        style="background-color: #3b82f6"
                                    @endif></div>
                                <div class="appointment-card-info">
                                    <div class="appointment-demo-type">
                                        @if (in_array($appointment->type, ['FINGERTEC TASK', 'TIMETEC HR TASK', 'TIMETEC PARKING TASK', 'TIMETEC PROPERTY TASK']))
                                            {{ $appointment->type }}
                                        @else
                                            {{ $appointment->type }}
                                        @endif
                                    </div>
                                    <div class="appointment-appointment-type">
                                        {{ $appointment->appointment_type }} |
                                        <span style="text-transform:uppercase">{{ $appointment->status }}</span>
                                    </div>

                                    @if (isset($appointment->is_internal_task) && $appointment->is_internal_task)
                                        <!-- For internal tasks, show the view remarks button -->
                                        <div class="appointment-company-name"
                                            x-data="{ remarkModalOpen: false }"
                                            @keydown.escape.window="remarkModalOpen = false">
                                            <button
                                                class="view-remarks-link"
                                                @click="remarkModalOpen = true">
                                                VIEW REMARK
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
                                    @else
                                        <!-- For regular appointments, show company name with link -->
                                        <div class="appointment-company-name" title="{{ $appointment->company_name }}">
                                            <a target="_blank" rel="noopener noreferrer" href={{ $appointment->url }}>
                                                {{ $appointment->company_name }}
                                            </a>
                                        </div>
                                    @endif
                                    <div class="appointment-time">{{ $appointment->start_time }} - {{ $appointment->end_time }}</div>
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
                                                    {{ $appointment->appointment_type }} |
                                                    <span style="text-transform:uppercase">{{ $appointment->status }}</span>
                                                </div>
                                                <div class="appointment-company-name">
                                                    <a target="_blank" rel="noopener noreferrer" href={{ $appointment->url }}>
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
                                                {{ $appointment->appointment_type }} |
                                                <span style="text-transform:uppercase">{{ $appointment->status }}</span>
                                            </div>
                                            <div class="appointment-company-name">
                                                <a target="_blank" rel="noopener noreferrer" href={{ $appointment->url }}>
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
            </div>
        @endforeach
    @endforeach

    <!-- Reseller Row (Combined) -->
    <div class="time">
        <div class="flex-container">
            <div style="font-weight: bold; display: flex; align-items: center; justify-content: center;">
                <img style="width: 24px; height: 24px; margin-right: 5px;" src="{{ asset('storage/uploads/photos/reseller-avatar.png') }}" alt="Resellers">
                Resellers
            </div>
        </div>
    </div>

    @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
        <div class="day">
            <div x-data="{ expanded: false }">
                @if (count($resellerAppointments[$day] ?? []) <= 4)
                    @foreach ($resellerAppointments[$day] ?? [] as $appointment)
                        <div class="appointment-card"
                            @if ($appointment->status === 'Done') style="background-color: var(--bg-demo-green)"
                            @elseif ($appointment->status == 'New')
                                style="background-color: var(--bg-demo-yellow)"
                            @else
                                style="background-color: var(--bg-demo-red)" @endif>
                            <div class="appointment-card-bar"></div>
                            <div class="appointment-card-info">
                                <!-- Display reseller company name above repair type -->
                                <div style="font-weight: bold; font-size: 0.8rem; text-transform: uppercase; color: #555; margin-bottom: 2px; border-bottom: 1px dashed #ccc; padding-bottom: 2px;">
                                    {{ $appointment->technician }}
                                </div>
                                <div class="appointment-demo-type">{{ $appointment->type }}</div>
                                <div class="appointment-appointment-type">
                                    {{ $appointment->appointment_type }} |
                                    <span style="text-transform:uppercase">{{ $appointment->status }}</span>
                                </div>
                                @if($appointment->company_name && $appointment->company_name != "No Company")
                                    <div class="appointment-company-name" title="{{ $appointment->company_name }}">
                                        <a target="_blank" rel="noopener noreferrer" href={{ $appointment->url }}>
                                            {{ $appointment->company_name }}
                                        </a>
                                    </div>
                                @endif
                                <div class="appointment-time">{{ $appointment->start_time }} -
                                    {{ $appointment->end_time }}</div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <template x-if="!expanded">
                        <div>
                            @foreach ($resellerAppointments[$day] ?? [] as $appointment)
                                @if ($loop->index < 3)
                                    <div class="appointment-card"
                                        @if ($appointment->status === 'Done') style="background-color: var(--bg-demo-green)"
                                        @elseif ($appointment->status == 'New')
                                            style="background-color: var(--bg-demo-yellow)"
                                        @else
                                            style="background-color: var(--bg-demo-red)" @endif>
                                        <div class="appointment-card-bar"></div>
                                        <div class="appointment-card-info">
                                            <!-- Display reseller company name above repair type -->
                                            <div style="font-weight: bold; font-size: 0.8rem; text-transform: uppercase; color: #555; margin-bottom: 2px; border-bottom: 1px dashed #ccc; padding-bottom: 2px;">
                                                {{ $appointment->technician }}
                                            </div>
                                            <div class="appointment-demo-type">{{ $appointment->type }}</div>
                                            <div class="appointment-appointment-type">
                                                {{ $appointment->appointment_type }} |
                                                <span style="text-transform:uppercase">{{ $appointment->status }}</span>
                                            </div>
                                            <div class="appointment-company-name">
                                                <a target="_blank" rel="noopener noreferrer" href={{ $appointment->url }}>
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
                                        +{{ count($resellerAppointments[$day]) - 3 }} more
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </template>

                    <template x-if="expanded">
                        <div>
                            @foreach ($resellerAppointments[$day] ?? [] as $appointment)
                                <div class="appointment-card"
                                    @if ($appointment->status === 'Done') style="background-color: var(--bg-demo-green)"
                                    @elseif ($appointment->status == 'New')
                                        style="background-color: var(--bg-demo-yellow)"
                                    @else
                                        style="background-color: var(--bg-demo-red)" @endif>
                                    <div class="appointment-card-bar"></div>
                                    <div class="appointment-card-info">
                                        <!-- Display reseller company name above repair type -->
                                        <div style="font-weight: bold; font-size: 0.8rem; text-transform: uppercase; color: #555; margin-bottom: 2px; border-bottom: 1px dashed #ccc; padding-bottom: 2px;">
                                            {{ $appointment->technician }}
                                        </div>
                                        <div class="appointment-demo-type">{{ $appointment->type }}</div>
                                        <div class="appointment-appointment-type">
                                            {{ $appointment->appointment_type }} |
                                            <span style="text-transform:uppercase">{{ $appointment->status }}</span>
                                        </div>
                                        <div class="appointment-company-name">
                                            <a target="_blank" rel="noopener noreferrer" href={{ $appointment->url }}>
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
        </div>
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
</script>
</div>
