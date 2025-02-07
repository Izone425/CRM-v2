<div x-data="tooltipHandler()">
    <style>
        :root {
            --bar-color-blue: #F6F8FF;
            --bar-color-orange: #ff9500;
            --bg-color-border: #E5E7EB;
            --bg-color-white: white;
            --icon-color: black;
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
        .summary-cell img {
            height: 50px;
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
            background-color: rgba(252, 158, 162, 0.2);
            display: flex;
            flex-direction: row;
        }

        .appointment-card-bar {
            background-color: var(--bar-color-blue);
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
            grid-template-columns: repeat(6, 1fr);
            row-gap: 3px;
            column-gap: 1px;
        }

        .demo-avatar img {
            border-radius: 50%;
            height: 45px;
            width: 45px;
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
    </style>



    <div class="calendar-header">
        <div class="header-row">
            <div class="header">
                Time
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

        <!-- No New Demo -->
        <div class="summary-cell">
            <div class="circle-bg">
                <i class="fa-solid fa-x"></i>
            </div>
        </div>
        @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
            <div class="summary-cell">
                <div class="demo-avatar">
                    @if (count($rows) < 6)
                        @foreach ($rows as $salesperson)
                            @if ($salesperson['newDemo'][$day] == 0)
                                <img src="{{ $salesperson['salespersonAvatar'] }}" alt="Salesperson Avatar" />
                            @endif
                        @endforeach
                    @else
                        @php
                            $counter = 0;
                        @endphp
                        @for ($i = 0; $i < count($rows); $i++)
                            @if ($counter >= 5)
                            @break
                        @endif

                        @if ($rows[$i]['newDemo'][$day] == 0)
                            <img data-tooltip="{{ $rows[$i]['salespersonName'] }}"
                                src="{{ $rows[$i]['salespersonAvatar'] }}" alt="Salesperson Avatar"
                                @mouseover="show($event)" @mousemove="updatePosition($event)" @mouseout="hide()" />
                            @php
                                $counter++;
                            @endphp
                        @endif
                    @endfor

                    <div class="hover-container" style="position: relative">
                        <div class="circle-bg">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <div class="hover-content"
                            style="position: absolute; background-color: grey; flex-direction: column; z-index: 10000; width: 150px;justify-content: space-between;">
                            @foreach ($rows as $salesperson)
                                @if ($salesperson['newDemo'][$day] == 0)
                                    <div style="display: flex; flex-direction: row;">
                                    {{-- Image for popup --}}
                                        <img style="width:30%" src="{{ $salesperson['salespersonAvatar'] }}"
                                            alt="Salesperson Avatar"
                                            data-tooltip="{{ $salesperson['salespersonName'] }}"
                                            @mouseover="show($event)" @mousemove="updatePosition($event)"
                                            @mouseout="hide()" />
                                        <span style="width: 70%">{{ $salesperson['salespersonName'] }}</span>
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
        <div class="circle-bg">
            <i class="fa-solid fa-1"></i>
        </div>
    </div>
    @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
        <div class="summary-cell">
            <div class="demo-avatar">
                @foreach ($rows as $salesperson)
                    @if ($salesperson['newDemo'][$day] == 1)
                        <img src="{{ $salesperson['salespersonAvatar'] }}" 
                        data-tooltip="{{$salesperson['salespersonName']}}"
                        @mouseover="show($event)" @mousemove="updatePosition($event)" @mouseout="hide()"
                        alt="Salesperson Avatar" />
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach

    <!-- 2 New Demo -->
    <div class="summary-cell">
        <div class="circle-bg">
            <i class="fa-solid fa-2"></i>
        </div>
    </div>

    @foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day)
        <div class="summary-cell">
            <div class="demo-avatar">
                @foreach ($rows as $salesperson)
                    @if ($salesperson['newDemo'][$day] == 2)
                        <img src="{{ $salesperson['salespersonAvatar'] }}" alt="Salesperson Avatar" />
                    @endif
                @endforeach
            </div>
        </div>
    @endforeach

    <!-- On Leave -->
    <div class="summary-cell">
        <img src={{ asset('img/leave-icon-white.svg') }} alt="Description of the image" style="fill: white;">
    </div>
    @for ($day = 1; $day < 6; $day++)
        <div class="summary-cell">
            <div class="demo-avatar">
                @foreach ($leaves as $leave)
                    @if ($leave['day_of_week'] == $day)
                        <img src="{{ $leave['salespersonAvatar'] }}" alt="Salesperson Avatar" />
                    @endif
                @endforeach
            </div>
        </div>
    @endfor
</div>

<div class="calendar-body">

    <div
        style="position: absolute; background-color: transparent; left: 0; width: calc(0.5/5.5*100%); height: 0%;">
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
                <img style="border-radius: 50%;"
                        src="{{ $row['salespersonAvatar'] }}"
                        data-tooltip="{{$row['salespersonName']}}"
                        @mouseover="show($event)" @mousemove="updatePosition($event)" @mouseout="hide()">
                </div>
            </div>
        </div>
        <div class="day">
            @if (isset($row['leave'][1]))
                <div
                    style="padding-block: 1rem; width: 100%; height: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center;">
                    <div style="flex:1; text-align: center;">
                        <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                        <div style="font-size: 0.8rem;font-style: italic;">{{ $row['leave'][1]['leave_type'] }}
                        </div>
                    </div>
                </div>
            @else
                {{-- END OF CUSTOM
                @foreach ($row['mondayAppointments'] as $appointment)
                    <div class="appointment-card">
                        <div class="appointment-card-bar"></div>
                        <div class="appointment-card-info">
                            <div class="appointment-demo-type">{{ $appointment->type }}</div>
                            <div class="appointment-appointment-type">{{ $appointment->appointment_type }}</div>
                            <div class="appointment-company-name"><a
                                    href={{ $appointment->url }}>{{ $appointment->company_name }}</a></div>
                            <div class="appointment-time">{{ $appointment->start_time }} -
                                {{ $appointment->end_time }}</div>
                        </div>
                    </div>
                @endforeach --}}

                {{-- CUSTOM --}}
                <div x-data="{ expanded: false }">
                    @if (count($row['mondayAppointments']) <= 4)
                        @foreach ($row['mondayAppointments'] as $appointment)
                            <div class="appointment-card">
                                <div class="appointment-card-bar"></div>
                                <div class="appointment-card-info">
                                    <div class="appointment-demo-type">{{ $appointment->type }}</div>
                                    <div class="appointment-appointment-type">{{ $appointment->appointment_type }}
                                    </div>
                                    <div class="appointment-company-name"><a
                                            href={{ $appointment->url }}>{{ $appointment->company_name }}</a>
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
                                {{-- If higher than 3  --}}
                                @foreach ($row['mondayAppointments'] as $appointment)
                                    @if ($loop->index < 3)
                                        <div class="appointment-card">
                                            <div class="appointment-card-bar"></div>
                                            <div class="appointment-card-info">
                                                <div class="appointment-demo-type">{{ $appointment->type }}</div>
                                                <div class="appointment-appointment-type">
                                                    {{ $appointment->appointment_type }}</div>
                                                <div class="appointment-company-name"><a
                                                        href={{ $appointment->url }}>{{ $appointment->company_name }}</a>
                                                </div>
                                                <div class="appointment-time">{{ $appointment->start_time }} -
                                                    {{ $appointment->end_time }}</div>
                                            </div>
                                        </div>
                                    @elseif($loop->index === 3)
                                        <div class="card mb-2 p-2 border rounded bg-gray-200 text-center cursor-pointer"
                                            @click="expanded = true">
                                            +{{ count($cards) - 2 }} more
                                        </div>
                                    @endif
                                @endforeach
                                {{--  --}}

                            </div>
                        </template>

                        <template x-if="expanded">
                            <div>
                                {{-- When expanded, display all cards --}}
                                @foreach ($row['mondayAppointments'] as $appointment)
                                    <div class="appointment-card">
                                        <div class="appointment-card-bar"></div>
                                        <div class="appointment-card-info">
                                            <div class="appointment-demo-type">{{ $appointment->type }}</div>
                                            <div class="appointment-appointment-type">
                                                {{ $appointment->appointment_type }}
                                            </div>
                                            <div class="appointment-company-name"><a
                                                    href={{ $appointment->url }}>{{ $appointment->company_name }}</a>
                                            </div>
                                            <div class="appointment-time">{{ $appointment->start_time }} -
                                                {{ $appointment->end_time }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </template>
                    @endif
                </div>
            @endif
        </div>
        <div class="day">
            @if (isset($row['leave'][2]))
                <div
                    style="padding-block: 1rem; width: 100%; height: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center;">
                    <div style="flex:1; text-align: center;">
                        <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                        <div style="font-size: 0.8rem;font-style: italic;">{{ $row['leave'][2]['leave_type'] }}
                        </div>
                    </div>
                </div>
            @else
                @foreach ($row['tuesdayAppointments'] as $appointment)
                    <div class="appointment-card">
                        <div class="appointment-card-bar"></div>
                        <div class="appointment-card-info">
                            <div class="appointment-demo-type">{{ $appointment->type }}</div>
                            <div class="appointment-appointment-type">{{ $appointment->appointment_type }}</div>
                            <div class="appointment-company-name"><a
                                    href={{ $appointment->url }}>{{ $appointment->company_name }}</a></div>
                            <div class="appointment-time">{{ $appointment->start_time }} -
                                {{ $appointment->end_time }}</div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <div class="day">
            @if (isset($row['leave'][3]))
                <div
                    style="padding-block: 1rem; width: 100%; height: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center;">
                    <div style="flex:1; text-align: center;">
                        <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                        <div style="font-size: 0.8rem;font-style: italic;">{{ $row['leave'][3]['leave_type'] }}
                        </div>
                    </div>
                </div>
            @else
                @foreach ($row['wednesdayAppointments'] as $appointment)
                    <div class="appointment-card">
                        <div class="appointment-card-bar"></div>
                        <div class="appointment-card-info">
                            <div class="appointment-demo-type">{{ $appointment->type }}</div>
                            <div class="appointment-appointment-type">{{ $appointment->appointment_type }}</div>
                            <div class="appointment-company-name"><a
                                    href={{ $appointment->url }}>{{ $appointment->company_name }}</a></div>
                            <div class="appointment-time">{{ $appointment->start_time }} -
                                {{ $appointment->end_time }}</div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <div class="day">
            @if (isset($row['leave'][4]))
                <div
                    style="padding-block: 1rem; width: 100%; height: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center;">
                    <div style="flex:1; text-align: center;">
                        <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                        <div style="font-size: 0.8rem;font-style: italic;">{{ $row['leave'][4]['leave_type'] }}
                        </div>
                    </div>
                </div>
            @else
                @foreach ($row['thursdayAppointments'] as $appointment)
                    <div class="appointment-card">
                        <div class="appointment-card-bar"></div>
                        <div class="appointment-card-info">
                            <div class="appointment-demo-type">{{ $appointment->type }}</div>
                            <div class="appointment-appointment-type">{{ $appointment->appointment_type }}</div>
                            <div class="appointment-company-name"><a
                                    href={{ $appointment->url }}>{{ $appointment->company_name }}</a></div>
                            <div class="appointment-time">{{ $appointment->start_time }} -
                                {{ $appointment->end_time }}</div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <div class="day">
            @if (isset($row['leave'][5]))
                <div
                    style="padding-block: 1rem; width: 100%; height: 100%; background-color: #E9EBF0; display: flex; justify-content: center; align-items: center;">
                    <div style="flex:1; text-align: center;">
                        <div style="font-size: 1.2rem; font-weight: bold;">On Leave</div>
                        <div style="font-size: 0.8rem;font-style: italic;">{{ $row['leave'][5]['leave_type'] }}
                        </div>
                    </div>
                </div>
            @else
                {{-- @foreach ($row['fridayAppointments'] as $appointment)
                    <div class="appointment-card">
                        <div class="appointment-card-bar"></div>
                        <div class="appointment-card-info">
                            <div class="appointment-demo-type">{{ $appointment->type }}</div>
                            <div class="appointment-appointment-type">{{ $appointment->appointment_type }}</div>
                            <div class="appointment-company-name"><a
                                    href={{ $appointment->url }}>{{ $appointment->company_name }}</a></div>
                            <div class="appointment-time">{{ $appointment->start_time }} -
                                {{ $appointment->end_time }}</div>
                        </div>
                    </div>
                @endforeach --}}
                {{-- CUSTOM --}}
                <div x-data="{ expanded: false }">
                    @if (count($row['fridayAppointments']) <= 4)
                        @foreach ($row['fridayAppointments'] as $appointment)
                            <div class="appointment-card">
                                <div class="appointment-card-bar"></div>
                                <div class="appointment-card-info">
                                    <div class="appointment-demo-type">{{ $appointment->type }}</div>
                                    <div class="appointment-appointment-type">{{ $appointment->appointment_type }}
                                    </div>
                                    <div class="appointment-company-name"><a
                                            href={{ $appointment->url }}>{{ $appointment->company_name }}</a>
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
                                {{-- If higher than 3  --}}
                                @foreach ($row['fridayAppointments'] as $appointment)
                                    @if ($loop->index < 3)
                                        <div class="appointment-card">
                                            <div class="appointment-card-bar"></div>
                                            <div class="appointment-card-info">
                                                <div class="appointment-demo-type">{{ $appointment->type }}</div>
                                                <div class="appointment-appointment-type">
                                                    {{ $appointment->appointment_type }}</div>
                                                <div class="appointment-company-name"><a
                                                        href={{ $appointment->url }}>{{ $appointment->company_name }}</a>
                                                </div>
                                                <div class="appointment-time">{{ $appointment->start_time }} -
                                                    {{ $appointment->end_time }}</div>
                                            </div>
                                        </div>
                                    @elseif($loop->index === 3)
                                        <div class="card mb-2 p-2 border rounded bg-gray-200 text-center cursor-pointer"
                                            @click="expanded = true">
                                            +{{ count($row['fridayAppointments']) - 3 }} more
                                        </div>
                                    @endif
                                @endforeach
                                {{--  --}}

                            </div>
                        </template>

                        <template x-if="expanded">
                            <div>
                                {{-- When expanded, display all cards --}}
                                @foreach ($row['fridayAppointments'] as $appointment)
                                    <div class="appointment-card">
                                        <div class="appointment-card-bar"></div>
                                        <div class="appointment-card-info">
                                            <div class="appointment-demo-type">{{ $appointment->type }}</div>
                                            <div class="appointment-appointment-type">
                                                {{ $appointment->appointment_type }}
                                            </div>
                                            <div class="appointment-company-name"><a
                                                    href={{ $appointment->url }}>{{ $appointment->company_name }}</a>
                                            </div>
                                            <div class="appointment-time">{{ $appointment->start_time }} -
                                                {{ $appointment->end_time }}</div>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="card mb-2 p-2 border rounded bg-gray-200 text-center cursor-pointer"
                                    @click="expanded = false">
                                    Hide
                                </div>
                            </div>
                        </template>
                    @endif
                </div>
            @endif
        </div>
    @endforeach
</div>

<!-- Global tooltip container -->
<div x-show="showTooltip" :style="tooltipStyle"
    class="tooltip fixed pointer-events-none text-white text-sm px-2 py-1 rounded">
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
</script>

</div>
