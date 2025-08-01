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

            /* Leave status colors */
            --leave-full-day: #FEE2E2; /* Red for full day */
            --leave-half-day: #FEF9C3; /* Yellow for half day */
            --leave-available: #C6FEC3; /* Green for available */
            --leave-holiday: #E5E7EB; /* Grey for holiday */
        }

        .calendar-header {
            display: grid;
            grid-template-columns: 1fr repeat(5, 0.8fr); /* Changed from 0.5fr to 1fr */
            gap: 12px;
            background: var(--bg-color-border);
            border-radius: 17px;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        /* Department Calendar */
        .calendar-body {
            display: grid;
            grid-template-columns: 1fr repeat(5, 0.8fr); /* Changed from 0.5fr to 1fr */
            gap: 1px;
            background: var(--bg-color-border);
            border-radius: 17px;
            box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.08);
            position: relative;
        }

        .header-row {
            display: grid;
            grid-template-columns: 1fr repeat(5, 0.8fr); /* Changed from 0.5fr to 1fr */
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

        /* For Employee Image */
        .flex-container {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            gap: 0.5rem;
            text-align: center;
        }

        .image-container {
            width: 45px;
            height: 45px;
            background-color: grey;
            border-radius: 50px;
            flex-shrink: 0;
            overflow: hidden;
        }

        /* Department Header */
        .department-header {
            grid-column: 1 / -1;
            background-color: #EEF2FF;
            padding: 10px;
            font-weight: bold;
            color: #4F46E5;
            text-align: left;
            border-left: 4px solid #4F46E5;
        }

        /* Leave Status Styles */
        .leave-full-day {
            height: 100%;
            background-color: var(--leave-full-day);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .leave-half-day-container {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .leave-half-am {
            height: 50%;
            background-color: var(--leave-half-day);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .leave-half-pm {
            height: 50%;
            background-color: var(--leave-half-day);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .leave-available {
            height: 100%;
            background-color: var(--leave-available);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .leave-available-half {
            height: 50%;
            background-color: var(--leave-available);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .leave-holiday {
            height: 100%;
            background-color: var(--leave-holiday);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Tooltip styles */
        .tooltip {
            z-index: 100;
        }

        /* Filter styles (keeping existing styles) */
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

        .leave-summary-container {
            padding: 1.5rem;
            margin-top: 1.5rem;
            background-color: white;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .leave-summary-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
        }

        .leave-summary-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
        }

        .leave-toggle-container {
            display: flex;
            align-items: center;
            border-radius: 0.5rem;
            padding: 0.25rem;
            gap: 15px;
        }

        .toggle-button {
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            font-weight: 500;
            border-radius: 0.375rem;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            background-color: #f3f4f6;
        }

        .toggle-button-active {
            background-color: #4f46e5;
            color: white;
        }

        .toggle-button-inactive {
            color: #374151;
        }

        .toggle-button-inactive:hover {
            background-color: #e5e7eb;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 1.5rem;
            margin-top: 1rem;
        }

        @media (min-width: 768px) {
            .summary-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .summary-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }

        @media (min-width: 1280px) {
            .summary-grid {
                grid-template-columns: repeat(6, 1fr);
            }
        }

        .summary-card {
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1rem;
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            transition: box-shadow 0.2s ease;
        }

        .summary-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .department-name {
            font-size: 1rem;
            font-weight: 700;
            color: #1f2937;
        }

        .total-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            background-color: #4f46e5;
            color: white;
            border-radius: 9999px;
        }

        .leave-types {
            margin-top: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .leave-type-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .leave-indicator {
            display: flex;
            align-items: center;
        }

        .indicator-dot {
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 9999px;
            margin-right: 0.5rem;
        }

        .full-day-dot {
            background-color: #FEE2E2; /* red-400 */
        }

        .half-day-am-dot {
            background-color: #FEF9C3; /* yellow-300 */
        }

        .half-day-pm-dot {
            background-color: #FEF9C3; /* amber-400 */
        }

        .leave-label {
            font-size: 0.875rem;
            color: #4b5563;
        }

        .leave-count {
            font-weight: 500;
        }

        .card-footer {
            margin-top: 0.75rem;
            padding-top: 0.75rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .total-label {
            font-weight: 500;
            color: #1f2937;
        }

        .total-value {
            font-size: 1.125rem;
            font-weight: 700;
            color: #4f46e5;
        }

        .summary-date {
            margin-bottom: 1rem;
            font-weight: 500;
            font-size: 1rem;
            color: #4b5563;
        }
    </style>

    <div class="leave-summary-container">
        <div class="leave-summary-header">
            <!-- Toggle Buttons with Show/Hide Logic -->
            <div x-data="{ showDaily: false, showWeekly: false }">
                <div style="color: #6b7280; font-size: 0.875rem;">
                    Select "Daily Summary" or "Weekly Summary" to view leave statistics
                </div>
                <div class="leave-toggle-container" style="margin-top: 1rem;">
                    <button
                        @click="showDaily = showDaily ? false : true; showWeekly = false"
                        :class="showDaily ? 'toggle-button toggle-button-active' : 'toggle-button toggle-button-inactive'">
                        Daily Summary
                    </button>
                    <button
                        @click="showWeekly = showWeekly ? false : true; showDaily = false"
                        :class="showWeekly ? 'toggle-button toggle-button-active' : 'toggle-button toggle-button-inactive'">
                        Weekly Summary
                    </button>
                </div>

                <!-- Daily Summary Cards - Only shown when selected -->
                <div x-show="showDaily" style="margin-top: 1rem;">
                    <h4 class="summary-date">Selected Day ({{ Carbon\Carbon::parse($date)->format('d M Y') }})</h4>
                    <div class="summary-grid">
                        @foreach($todayLeaveSummary as $department => $counts)
                            <div class="summary-card">
                                <div class="card-header">
                                    <div class="department-name">{{ $department }}</div>
                                </div>
                                <div class="leave-types">
                                    <div class="leave-type-row">
                                        <div class="leave-indicator">
                                            <div class="indicator-dot full-day-dot"></div>
                                            <span class="leave-label">Full Day</span>
                                        </div>
                                        <span class="leave-count">{{ $counts['full_day'] }}</span>
                                    </div>
                                    <div class="leave-type-row">
                                        <div class="leave-indicator">
                                            <div class="indicator-dot half-day-am-dot"></div>
                                            <span class="leave-label">Half AM</span>
                                        </div>
                                        <span class="leave-count">{{ $counts['half_day_am'] }}</span>
                                    </div>
                                    <div class="leave-type-row">
                                        <div class="leave-indicator">
                                            <div class="indicator-dot half-day-pm-dot"></div>
                                            <span class="leave-label">Half PM</span>
                                        </div>
                                        <span class="leave-count">{{ $counts['half_day_pm'] }}</span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <span class="total-label">Total Leave Days</span>
                                    <span class="total-value">{{ $counts['total'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Weekly Summary Cards - Only shown when selected -->
                <div x-show="showWeekly" style="margin-top: 1rem;">
                    <h4 class="summary-date">
                        This Week ({{ Carbon\Carbon::parse($startDate)->format('d M') }} - {{ Carbon\Carbon::parse($endDate)->format('d M Y') }})
                    </h4>
                    <div class="summary-grid">
                        @foreach($leaveSummary as $department => $counts)
                            <div class="summary-card">
                                <div class="card-header">
                                    <div class="department-name">{{ $department }}</div>
                                    <div class="total-badge">
                                        {{ $counts['total'] }}
                                    </div>
                                </div>
                                <div class="leave-types">
                                    <div class="leave-type-row">
                                        <div class="leave-indicator">
                                            <div class="indicator-dot full-day-dot"></div>
                                            <span class="leave-label">Full Day</span>
                                        </div>
                                        <span class="leave-count">{{ $counts['full_day'] }}</span>
                                    </div>
                                    <div class="leave-type-row">
                                        <div class="leave-indicator">
                                            <div class="indicator-dot half-day-am-dot"></div>
                                            <span class="leave-label">Half AM</span>
                                        </div>
                                        <span class="leave-count">{{ $counts['half_day_am'] }}</span>
                                    </div>
                                    <div class="leave-type-row">
                                        <div class="leave-indicator">
                                            <div class="indicator-dot half-day-pm-dot"></div>
                                            <span class="leave-label">Half PM</span>
                                        </div>
                                        <span class="leave-count">{{ $counts['half_day_pm'] }}</span>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <span class="total-label">Total Leave Days</span>
                                    <span class="total-value">{{ $counts['total'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter and Header Section -->
    <div class="flex items-center justify-between p-6 mb-6 bg-white shadow-xl rounded-2xl">
        <h2 class="text-2xl font-bold">All Department Calendar - {{ $currentMonth }}</h2>

        <div class="flex items-center space-x-4" style="gap: 12px;">
            <!-- Date Picker -->
            <div x-data="weeklyPicker()" class="w-36">
                <input type="text" x-ref="datepicker" wire:model.change='weekDate' placeholder="Select Date"
                    class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>

            <!-- Department Filter - Add this new section -->
            <div class="w-64">
                <select wire:model.live="selectedDepartment"
                        class="block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                    <option value="all">All Departments</option>
                    @foreach(['Vice President', 'Admin Department', 'SalesPerson Department', 'Implementer Department', 'Trainer Department', 'Support Department', 'Technician Department'] as $dept)
                        <option value="{{ $dept }}">{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <!-- Calendar header -->
    <div class="calendar-header">
        <div class="header-row">
            <div class="header" style="display:flex; align-items:center; justify-content:center; font-weight:bold; font-size: 1.2rem">
                <div>
                    @if($selectedDepartment === 'all')
                        {{ $currentMonth }}
                    @else
                        {{ $selectedDepartment }} - {{ $currentMonth }}
                    @endif
                </div>
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
    </div>

    <!-- Calendar body -->
    <div class="mt-4 calendar-body">
        <!-- Check for public holidays -->
        @if (isset($holidays['1']))
            <div style="position: absolute; background-color: #C2C2C2; left: calc((1.1/5.5)* 100%); width: calc((0.9/5.5)*100%); height: 100%; border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
                <div style="font-weight: bold;font-size: 1.2rem;">Public Holiday</div>
                <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['1']['name'] }}</div>
            </div>
        @endif
        @if (isset($holidays['2']))
            <div style="position: absolute; background-color: #C2C2C2; left: calc((1.97/5.5)*100%); width: calc((0.9/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
                <div style="font-weight: bold;font-size: 1.2rem;">Public Holiday</div>
                <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['2']['name'] }}</div>
            </div>
        @endif
        @if (isset($holidays['3']))
            <div style="position: absolute; background-color: #C2C2C2; left: calc((2.86/5.5)*100%); width: calc((0.9/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
                <div style="font-weight: bold;font-size: 1.2rem;">Public Holiday</div>
                <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['3']['name'] }}</div>
            </div>
        @endif
        @if (isset($holidays['4']))
            <div style="position: absolute; background-color: #C2C2C2; left: calc((3.74/5.5)*100%); width: calc((0.9/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
                <div style="font-weight: bold;font-size: 1.2rem;">Public Holiday</div>
                <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['4']['name'] }}</div>
            </div>
        @endif
        @if (isset($holidays['5']))
            <div style="position: absolute; background-color: #C2C2C2; left: calc((4.6/5.5)*100%); width: calc((0.9/5.5)*100%); height: 100%;border: 1px solid #E5E7EB; padding-inline: 0.5rem; display: flex; align-items: center; justify-content: center; text-align: center; flex-direction: column;">
                <div style="font-weight: bold;font-size: 1.2rem;">Public Holiday</div>
                <div style="font-size: 0.8rem;font-style: italic;">{{ $holidays['5']['name'] }}</div>
            </div>
        @endif

        @php $currentDepartment = null; @endphp

        <!-- Loop through employees by department -->
        @foreach($employees as $employee)
            @if($currentDepartment !== $employee->department)
                <div class="department-header">
                    {{ $employee->department }}
                </div>
                @php $currentDepartment = $employee->department; @endphp
            @endif

            <!-- Employee name and photo cell -->
            <div class="time">
                <div class="flex-container">
                    <div class="image-container">
                        @if(isset($employee->avatar_path) && $employee->avatar_path)
                            <img src="{{ asset('storage/' . $employee->avatar_path) }}" alt="{{ $employee->name }}" class="object-cover w-full h-full">
                        @else
                            <div class="flex items-center justify-center w-full h-full text-white bg-gray-500">
                                {{ strtoupper(substr($employee->name, 0, 1)) }}
                            </div>
                        @endif
                    </div>
                    <div>
                        <div class="font-medium">{{ $employee->name }}</div>
                    </div>
                </div>
            </div>

            <!-- Days of the week -->
            @foreach(array_slice($weekDays, 0, 5) as $index => $day)
                @php
                    $date = $day['full_date'];

                    // Check if it's a public holiday
                    $isHoliday = false;
                    $holidayName = '';

                    if (isset($holidays[$index + 1])) {
                        $holiday = $holidays[$index + 1];
                        $isHoliday = true;
                        $holidayName = $holiday['name'];
                    }

                    // Check if the employee has leave on this date
                    $userLeave = null;
                    if (isset($leaves[$employee->id]) && isset($leaves[$employee->id][$date])) {
                        $userLeave = $leaves[$employee->id][$date];
                    }

                    $isFullDay = $userLeave && $userLeave['session'] === 'full';
                    $isHalfDayAM = $userLeave && $userLeave['session'] === 'am';
                    $isHalfDayPM = $userLeave && $userLeave['session'] === 'pm';
                    $leaveType = $userLeave ? $userLeave['leave_type'] : '';
                @endphp

                <div class="p-0 day">
                    @if($isHoliday)
                        <!-- Public holiday -->
                        <div class="leave-holiday">
                            <div class="font-medium">Public Holiday</div>
                            <div class="text-xs italic">{{ $holidayName }}</div>
                        </div>
                    @elseif($isFullDay)
                        <!-- Full day leave -->
                        <div class="leave-full-day">
                            <div class="font-medium">Full Day Leave</div>
                            <div class="text-xs italic">{{ $leaveType }}</div>
                        </div>
                    @elseif($isHalfDayAM)
                        <!-- Half day AM leave, PM available -->
                        <div class="leave-half-day-container">
                            <div class="leave-half-am">
                                <div class="text-xs font-medium">AM Leave</div>
                            </div>
                            <div class="leave-available-half">
                                <div class="text-xs font-medium">Available</div>
                            </div>
                        </div>
                    @elseif($isHalfDayPM)
                        <!-- Half day PM leave, AM available -->
                        <div class="leave-half-day-container">
                            <div class="leave-available-half">
                                <div class="text-xs font-medium">Available</div>
                            </div>
                            <div class="leave-half-pm">
                                <div class="text-xs font-medium">PM Leave</div>
                            </div>
                        </div>
                    @else
                        <!-- Available (default) -->
                        <div class="leave-available">
                            <div class="text-xs font-medium">Available</div>
                        </div>
                    @endif
                </div>
            @endforeach
        @endforeach
    </div>

    <!-- Legend -->
    <div class="flex items-center p-4 mt-4 space-x-6 bg-white rounded-lg shadow-md" style="gap: 12px;">
        <div class="flex items-center">
            <div class="w-4 h-4 mr-2 bg-green-200" style="background-color: #C6FEC3;"></div>
            <span class="text-sm">Available</span>
        </div>
        <div class="flex items-center">
            <div class="w-4 h-4 mr-2 bg-red-200" style="background-color: #FEE2E2;"></div>
            <span class="text-sm">Full Day Leave</span>
        </div>
        <div class="flex items-center">
            <div class="w-4 h-4 mr-2 bg-yellow-200" style="background-color: #FEF9C3;"></div>
            <span class="text-sm">Half Day Leave</span>
        </div>
        <div class="flex items-center">
            <div class="w-4 h-4 mr-2 bg-gray-300"></div>
            <span class="text-sm">Public Holiday</span>
        </div>
    </div>

    <!-- Global tooltip container -->
    <div x-show="showTooltip" :style="tooltipStyle"
        class="fixed px-2 py-1 text-sm text-white bg-black rounded pointer-events-none tooltip">
        <span x-text="tooltip"></span>
    </div>

    <script>
        function tooltipHandler() {
            return {
                tooltip: '',
                showTooltip: false,
                tooltipX: 0,
                tooltipY: 0,

                show(event) {
                    this.tooltip = event.target.dataset.tooltip;
                    this.showTooltip = true;
                    this.updatePosition(event);
                },

                updatePosition(event) {
                    this.tooltipX = event.clientX;
                    this.tooltipY = event.clientY - 10;
                },

                hide() {
                    this.showTooltip = false;
                },

                get tooltipStyle() {
                    return `left: ${this.tooltipX}px; top: ${this.tooltipY}px; transform: translate(-50%, -100%); background-color:black; z-index: 10000`;
                }
            };
        }

        function weeklyPicker() {
            return {
                init() {
                    flatpickr(this.$refs.datepicker, {
                        dateFormat: 'Y-m-d',
                        defaultDate: @json($date instanceof \Carbon\Carbon ? $date->format('Y-m-d') : $date)
                    })
                }
            }
        }
    </script>
</div>
