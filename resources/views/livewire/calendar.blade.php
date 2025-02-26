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
            min-height: 50px;
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

    </style>


    <!-- Filter and Badges Section -->
    <div class="filter-badges-container">

        <div class="filter-row">
            <div x-data="weeklyPicker()">
                <input type="text" x-ref="datepicker" wire:model.change='weekDate' placeholder="Date" class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm border rounded px-3 py-2">
            </div>
            {{-- Status --}}
            <div class="relative w-full">
                <form>
                    <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm" @click.away="open = false" x-data="{
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
                            <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <!-- Dropdown List -->
                        <div x-show="open" class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg " style="display: none;">
                            <ul class="py-1">
                                <!-- Select All Checkbox -->
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="allStatusSelected" class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                    <label class="block ml-3 text-sm font-medium text-gray-700" style="padding-left: 10px;">
                                        All Status
                                    </label>
                                </li>

                                <!-- Status -->
                                @foreach ($status as $row)
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="selectedStatus" value="{{ $row }}" class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                    <label for="checkbox-{{ $row }}" class="block ml-3 text-sm font-medium text-gray-700" style="padding-left: 10px;">
                                        {{ $row }}

                                    </label>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Demo Type Filter -->
            <div class="relative w-full">
                <form>
                    <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm" @click.away="open = false" x-data="{
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
                            <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <!-- Dropdown List -->
                        <div x-show="open" class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg " style="display: none;">
                            <ul class="py-1">
                                <!-- Select All Checkbox -->
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="allDemoTypeSelected" class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                    <label class="block ml-3 text-sm font-medium text-gray-700" style="padding-left: 10px;">
                                        All Demo Type
                                    </label>
                                </li>

                                <!-- Individual Salespersons -->
                                @foreach ($demoTypes as $row)
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="selectedDemoType" value="{{ $row }}" class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                    <label for="checkbox-{{ $row }}" class="block ml-3 text-sm font-medium text-gray-700" style="padding-left: 10px;">
                                        {{ $row }}

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
                    <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm" @click.away="open = false" x-data="{
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
                            <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <!-- Dropdown List -->
                        <div x-show="open" class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg " style="display: none;">
                            <ul class="py-1">
                                <!-- Select All Checkbox -->
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="allAppointmentTypeSelected" class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                    <label class="block ml-3 text-sm font-medium text-gray-700" style="padding-left: 10px;">
                                        All Appointment Types
                                    </label>
                                </li>

                                <!-- Individual Salespersons -->
                                @foreach ($appointmentTypes as $row)
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="selectedAppointmentType" value="{{ $row }}" class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                    <label for="checkbox-{{ $row }}" class="block ml-3 text-sm font-medium text-gray-700" style="padding-left: 10px;">
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
                    <div class="block bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm" @click.away="open = false" x-data="{
                        open: false,
                        selected: @entangle('selectedSalesPeople'),
                        allSelected: @entangle('allSalesPeopleSelected'),
                        get label() {
                    
                            if (this.allSelected)
                                return 'All Salesperson'
                    
                            else if (this.selected.length <= 0)
                                return 'All Salesperson'
                    
                            else
                                return this.selected.length + ' Salesperson';
                        }
                    }">
                        <!-- Trigger Button -->
                        <div @click="open = !open" class="flex items-center justify-between px-3 py-2">
                            <span x-text="label" class="truncate"></span>
                            <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </div>

                        <!-- Dropdown List -->
                        <div x-show="open" class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg " style="display: none; height: 30vh">
                            <ul class="py-1">
                                <!-- Select All Checkbox -->
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="allSalesPeopleSelected" class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                    <label class="block ml-3 text-sm font-medium text-gray-700" style="padding-left: 10px;">
                                        All Salesperson
                                    </label>
                                </li>

                                <!-- Individual Salespersons -->
                                @foreach ($salesPeople as $row)
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100">
                                    <input type="checkbox" wire:model.live="selectedSalesPeople" value="{{ $row['id'] }}" class="w-4 h-4 text-indigo-600 border-gray-300 rounded form-checkbox focus:ring-indigo-500" />
                                    <label for="checkbox-{{ $row['id'] }}" class="block ml-3 text-sm font-medium text-gray-700" style="padding-left: 10px;">
                                        {{ $row['name'] }}

                                    </label>
                                </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="badges-row">
            <!-- Total Demo Badge -->
            <div style="background-color: #4F46E5;" class="badges">
                TOTAL DEMO TYPE: <div style="float:right">{{ $totalDemos['ALL'] }}</div>
            </div>

            <!-- New Demo Badge -->
            <div style="background-color: var(--bg-demo-green); color: var(--text-demo-green);" class="badges">
                NEW DEMO: <div style="float:right">{{ $totalDemos['NEW DEMO'] }}</div>
            </div>

            <!-- Second Demo Badge -->
            <div style="background-color: var(--bg-demo-yellow); color: var(--text-demo-yellow);" class="badges">
                WEBINAR DEMO: <div style="margin-left:0.5rem; float:right">{{ $totalDemos['WEBINAR DEMO'] }}</div>
            </div>

            <!-- Webinar Demo Badge -->
            <div style="background-color: var(--bg-demo-red); color: var(--text-demo-red);" class="badges">
                OTHERS: <div style="float:right">{{ $totalDemos['OTHERS'] }}</div>
            </div>
        </div>

        <div class="badges-row">
            <!-- Total Demo Badge -->
            <div style="background-color: #4F46E5;" class="badges">
                TOTAL DEMO STATUS: <div style="float:right">{{ $totalDemos['ALL'] }}</div>
            </div>

            <!-- New Demo Badge -->
            <div style="background-color: var(--bg-demo-green); color: var(--text-demo-green);" class="badges">
                DONE: <div style="float:right">{{ $totalDemos['NEW DEMO'] }}</div>
            </div>

            <!-- Second Demo Badge -->
            <div style="background-color: var(--bg-demo-yellow); color: var(--text-demo-yellow);" class="badges">
                NEW DEMO: <div style="margin-left:0.5rem; float:right">{{ $totalDemos['WEBINAR DEMO'] }}</div>
            </div>

            <!-- Webinar Demo Badge -->
            <div style="background-color: var(--bg-demo-red); color: var(--text-demo-red);" class="badges">
                CANCELLED: <div style="float:right">{{ $totalDemos['OTHERS'] }}</div>
            </div>
        </div>

    </div>

    <!-- Calendar Section -->
    <div class="calendar-header">
        <div class="header-row">
            <div class="header" style="display:flex; align-items:center; justify-content:center; font-weight:bold; font-size: 1.2rem">
                <div>{{ $this->currentMonth }}</div>
            </div>
            <div class="header">
                <div class="flex">
                    <button wire:click="prevWeek" style="width: 10%;"><i class="fa-solid fa-chevron-left"></i></button>
                    <span class="flex-1" @if ($weekDays[0]['today']) style="background-color: lightblue;" @endif>
                        <div class="header-date text-center">{{ $weekDays[0]['date'] }}</div>
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
        @if(auth()->user()->role_id !== 2)


        <!-- No New Demo -->
        <div class="summary-cell">
            <div class="circle-bg" style="background-color:var(--text-demo-red)">
                <i class="fa-solid fa-x" style="font-size: 1.4rem;color:white"></i>
            </div>
        </div>
        @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
        <div class="summary-cell">
            <div class="demo-avatar">
                @if (count($rows)
                < 6) @foreach ($rows as $salesperson) @if ($salesperson['newDemo'][$day]==0) <img src="{{ $salesperson['salespersonAvatar'] }}" alt="Salesperson Avatar" />
                @endif
                @endforeach
                @else
                @php
                $counter = 0;
                @endphp
                @for ($i = 0; $i < count($rows); $i++) @if ($counter>= 5)
                    @break
                    @endif

                    @if ($rows[$i]['newDemo'][$day] == 0)
                    <img data-tooltip="{{ $rows[$i]['salespersonName'] }}" src="{{ $rows[$i]['salespersonAvatar'] }}" alt="Salesperson Avatar" @mouseover="show($event)" @mousemove="updatePosition($event)" @mouseout="hide()" />
                    @php
                    $counter++;
                    @endphp
                    @endif
                    @endfor
                    @if ($counter >= 5)
                    <div class="hover-container" style="position: relative">
                        <div class="circle-bg">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <div class="hover-content">
                            @php
                            $numbering = 1;
                            @endphp
                            @foreach ($rows as $salesperson)
                            @if ($salesperson['newDemo'][$day] == 0)
                            <div class="hover-content-flexcontainer">
                                <span>
                                    <div class="circle-bg" style="background-color:black;color: white">
                                        {{ $numbering }}
                                    </div>
                                </span>
                                <img src="{{ $salesperson['salespersonAvatar'] }}" alt="Salesperson Avatar" style="height: 100%; width: auto; flex: 0 0 40px; max-width: 40px;" data-tooltip="{{ $salesperson['salespersonName'] }}" @mouseover="show($event)" @mousemove="updatePosition($event)" @mouseout="hide()" />
                                <span style="width: 70%;flex: 1;text-align: left">{{ $salesperson['salespersonName'] }}</span>
                            </div>
                            @php
                            $numbering++;
                            @endphp
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
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
                @if (count($rows)
                < 6) @foreach ($rows as $salesperson) @if ($salesperson['newDemo'][$day]==1) <img src="{{ $salesperson['salespersonAvatar'] }}" alt="Salesperson Avatar" />
                @endif
                @endforeach
                @else
                @php
                $counter = 0;
                @endphp
                @for ($i = 0; $i < count($rows); $i++) @if ($counter>= 5)
                    @break
                    @endif

                    @if ($rows[$i]['newDemo'][$day] == 1)
                    <img data-tooltip="{{ $rows[$i]['salespersonName'] }}" src="{{ $rows[$i]['salespersonAvatar'] }}" alt="Salesperson Avatar" @mouseover="show($event)" @mousemove="updatePosition($event)" @mouseout="hide()" />
                    @php
                    $counter++;
                    @endphp
                    @endif
                    @endfor
                    @if ($counter >= 5)
                    <div class="hover-container" style="position: relative">
                        <div class="circle-bg">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <div class="hover-content">
                            @php
                            $numbering = 1;
                            @endphp
                            @foreach ($rows as $salesperson)
                            @if ($salesperson['newDemo'][$day] == 1)
                            <div class="hover-content-flexcontainer">
                                <span>
                                    <div class="circle-bg" style="background-color:black;color: white">
                                        {{ $numbering }}
                                    </div>
                                </span>
                                {{-- Image for popup --}}
                                <img src="{{ $salesperson['salespersonAvatar'] }}" style="max-width: 40px;" alt="Salesperson Avatar" data-tooltip="{{ $salesperson['salespersonName'] }}" @mouseover="show($event)" @mousemove="updatePosition($event)" @mouseout="hide()" />
                                <span style="width: 70%">{{ $salesperson['salespersonName'] }}</span>
                            </div>
                            @php
                            $numbering++;
                            @endphp
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
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
                @if (count($rows)
                < 6) @foreach ($rows as $salesperson) @if ($salesperson['newDemo'][$day]==2) <img src="{{ $salesperson['salespersonAvatar'] }}" alt="Salesperson Avatar" />
                @endif
                @endforeach
                @else
                @php
                $counter = 0;
                @endphp
                @for ($i = 0; $i < count($rows); $i++) @if ($counter>= 5)
                    @break
                    @endif

                    @if ($rows[$i]['newDemo'][$day] == 2)
                    <img data-tooltip="{{ $rows[$i]['salespersonName'] }}" src="{{ $rows[$i]['salespersonAvatar'] }}" alt="Salesperson Avatar" @mouseover="show($event)" @mousemove="updatePosition($event)" @mouseout="hide()" />
                    @php
                    $counter++;
                    @endphp
                    @endif
                    @endfor
                    @if ($counter >= 5)
                    <div class="hover-container" style="position: relative">
                        <div class="circle-bg">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <div class="hover-content">
                            @php
                            $numbering = 1;
                            @endphp
                            @foreach ($rows as $salesperson)
                            @if ($salesperson['newDemo'][$day] == 2)
                            <div class="hover-content-flexcontainer">
                                <span>
                                    <div class="circle-bg" style="background-color:black;color: white">
                                        {{ $numbering }}
                                    </div>
                                </span>
                                {{-- Image for popup --}}
                                <img src="{{ $salesperson['salespersonAvatar'] }}" style="max-width: 40px;" alt="Salesperson Avatar" data-tooltip="{{ $salesperson['salespersonName'] }}" @mouseover="show($event)" @mousemove="updatePosition($event)" @mouseout="hide()" />
                                <span style="width: 70%">{{ $salesperson['salespersonName'] }}</span>
                            </div>
                            @php
                            $numbering++;
                            @endphp
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                    @endif
            </div>
        </div>
        @endforeach

        <!-- On Leave -->
        <div class="summary-cell">
            <img src={{ asset('img/leave-icon-white.svg') }} alt="TT Leave Icon">
        </div>
        @for ($day = 1; $day < 6; $day++) <div class="summary-cell">
            <div class="demo-avatar">
                @foreach ($leaves as $leave)
                @if ($leave['day_of_week'] == $day)
                <img src="{{ $leave['salespersonAvatar'] }}" alt="Salesperson Avatar" data-tooltip="{{ $leave['salespersonName'] }}" @mouseover="show($event)" @mousemove="updatePosition($event)" @mouseout="hide()" />
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

    <!-- SalesPerson Row -->
    @foreach ($rows as $row)
    <div class="time">
        <div class="flex-container">
            <div class="image-container">
                <img style="border-radius: 50%;" src="{{ $row['salespersonAvatar'] }}" data-tooltip="{{ $row['salespersonName'] }}" @mouseover="show($event)" @mousemove="updatePosition($event)" @mouseout="hide()">
            </div>
        </div>
    </div>
    @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
    <div class="day">
        @if (isset($row['leave'][$loop->iteration]))
        <div style="padding-block: 1rem; width: 100%; height: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center;">
            <div style="flex:1; text-align: center;">
                <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                <div style="font-size: 0.8rem;font-style: italic;">
                    {{ $row['leave'][$loop->iteration]['leave_type'] }}
                </div>
                <div style="font-size: 0.8rem;"> {{ $row['leave'][$loop->iteration]['status'] }} |
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
        @else
        <div x-data="{ expanded: false }">
            @if (count($row[$day . 'Appointments']) <= 4) @foreach ($row[$day . 'Appointments' ] as $appointment) <div class="appointment-card" @if ($appointment->status === 'Done') style="background-color: var(--bg-demo-green)"
                @elseif ($appointment->status == 'New')
                style="background-color: var(--bg-demo-yellow)"
                @else
                style="background-color: var(--bg-demo-red)" @endif>
                <div class="appointment-card-bar"></div>
                <div class="appointment-card-info">
                    <div class="appointment-demo-type">{{ $appointment->type }}</div>
                    <div class="appointment-appointment-type">
                        {{ $appointment->appointment_type }} | <span style="text-transform:uppercase">{{$appointment->status}}<span>
                    </div>
                    <div class="appointment-company-name"><a target="_blank" rel="noopener noreferrer" href={{ $appointment->url }}>{{ $appointment->company_name }}</a>
                    </div>
                    <div class="appointment-time">{{ $appointment->start_time }} -
                        {{ $appointment->end_time }}</div>
                </div>
        </div>
        @endforeach
        @else
        {{-- More than 3 cards --}}
        <template x-if="!expanded">
            <div>
                {{-- If higher than 3 --}}
                @foreach ($row[$day . 'Appointments'] as $appointment)
                @if ($loop->index < 3) <div class="appointment-card" @if ($appointment->type === 'NEW DEMO') style="background-color: var(--bg-demo-green)"
                    @elseif ($appointment->type == 'WEBINAR DEMO')
                    style="background-color: var(--bg-demo-yellow)"
                    @else
                    style="background-color: var(--bg-demo-red)" @endif>
                    <div class="appointment-card-bar"></div>
                    <div class="appointment-card-info">
                        <div class="appointment-demo-type">{{ $appointment->type }}
                        </div>
                        <div class="appointment-appointment-type">
                            {{ $appointment->appointment_type }}</div>
                        <div class="appointment-company-name"><a target="_blank" rel="noopener noreferrer" href={{ $appointment->url }}>{{ $appointment->company_name }}</a>
                        </div>
                        <div class="appointment-time">{{ $appointment->start_time }} -
                            {{ $appointment->end_time }}</div>
                    </div>
            </div>
            @elseif($loop->index === 3)
            <div class="card mb-2 p-2 border rounded bg-gray-200 text-center cursor-pointer" @click="expanded = true">
                +{{ count($row[$day . 'Appointments']) - 3 }} more
            </div>
            @endif
            @endforeach
            {{-- --}}

    </div>
    </template>

    <template x-if="expanded">
        <div>
            {{-- When expanded, display all cards --}}
            @foreach ($row[$day . 'Appointments'] as $appointment)
            <div class="appointment-card" @if ($appointment->type === 'NEW DEMO') style="background-color: var(--bg-demo-green)"
                @elseif ($appointment->type == 'WEBINAR DEMO')
                style="background-color: var(--bg-demo-yellow)"
                @else
                style="background-color: var(--bg-demo-red)" @endif>
                <div class="appointment-card-bar"></div>
                <div class="appointment-card-info">
                    <div class="appointment-demo-type">{{ $appointment->type }}</div>
                    <div class="appointment-appointment-type">
                        {{ $appointment->appointment_type }}
                    </div>
                    <div class="appointment-company-name"><a target="_blank" rel="noopener noreferrer" href={{ $appointment->url }}>{{ $appointment->company_name }}</a>
                    </div>
                    <div class="appointment-time">{{ $appointment->start_time }} -
                        {{ $appointment->end_time }}</div>
                </div>
            </div>
            @endforeach
            <div class="card mb-2 p-2 border rounded bg-gray-200 text-center cursor-pointer" @click="expanded = false">
                Hide
            </div>
        </div>
    </template>
    @endif
</div>
@endif
</div>
@endforeach
@endforeach
</div>

<!-- Global tooltip container -->
<div x-show="showTooltip" :style="tooltipStyle" class="tooltip fixed pointer-events-none text-white text-sm px-2 py-1 rounded">
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
