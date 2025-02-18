<div style="height: 100%">
    <style>
    @import url('https://fonts.googleapis.com/css2?family=Roboto+Mono:ital@0;1&display=swap');
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
        }

        .border-btm-right {
            border-bottom-right-radius: 20px;
        }

        .border-btm-left {
            border-bottom-left-radius: 20px;
        }

        .border-top-right {
            border-top-right-radius: 20px;
        }

        .border-top-left {
            border-top-left-radius: 20px;
        }

        .bg-green{
            background-color:var(--bg-demo-green);
        }

        .bg-red{
            background-color:var(--bg-demo-red);
        }

        .bg-yellow{
            background-color:var(--bg-demo-yellow);
        }
    </style>

    <!-- Filter and Badges Section -->
    <div style="display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; align-items: center;">

        <!-- Total Demo Badge -->
        <div
            style="background-color: #4F46E5; color: white; padding: 8px 16px; border-radius: 9999px; font-size: 14px; font-weight: 600;">
            TOTAL {{ $totalDemos['ALL'] }}
        </div>

        <!-- New Demo Badge -->
        <div
            style="background-color: var(--bg-demo-green); color: var(--text-demo-green); padding: 8px 16px; border-radius: 9999px; font-size: 14px; font-weight: 600;">
            NEW DEMO {{ $totalDemos['NEW DEMO'] }}
        </div>

        <!-- Second Demo Badge -->
        <div
            style="background-color: var(--bg-demo-yellow); color: var(--text-demo-yellow); padding: 8px 16px; border-radius: 9999px; font-size: 14px; font-weight: 600;">
            WEBINAR DEMO {{ $totalDemos['WEBINAR DEMO'] }}
        </div>

        <!-- Webinar Demo Badge -->
        <div
            style="background-color: var(--bg-demo-red); color: var(--text-demo-red); padding: 8px 16px; border-radius: 9999px; font-size: 14px; font-weight: 600;">
            OTHERS {{ $totalDemos['OTHERS'] }}
        </div>

        <!-- Salesperson Filter -->
        <div class="relative">
            <form>
                <div class="block w-full bg-white border border-gray-300 rounded-md shadow-sm cursor-pointer focus-within:ring-indigo-500 focus-within:border-indigo-500 sm:text-sm"
                    @click.away="open = false" x-data="{
                        open: false,
                        selected: 'abc',
                        get label() {
                            if (false)
                                return this.selected.name;
                            else
                                return ' '
                        }
                    }">
                    <!-- Trigger Button -->
                    <div @click="open = !open" class="flex items-center justify-between px-3 py-2"
                        style="width: 200px;">
                        <span x-text="label" class="truncate"></span>
                        <svg class="w-5 h-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    <div x-show="open"
                        class="absolute z-10 w-full mt-1 overflow-auto bg-white border border-gray-300 rounded-md shadow-lg "
                        style="display: none; height: 30vh">
                        <ul class="py-1">
                            @foreach ($salesPeople as $salesPerson)
                                <li class="flex items-center px-3 py-2 hover:bg-gray-100"
                                    wire:click="selectUser({{ $salesPerson['id'] }})">{{ $salesPerson['name'] }}
                                <li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div
        style="display: grid; grid-template-columns: repeat(7, 1fr); grid-template-rows: 0.2fr repeat(5,1fr); max-height: 70vh; gap:1px; background-color: var(--bg-color-border); max-width: 1400px;margin: 0 auto;border-radius: 20px;box-shadow: 0px 2px 4px rgba(0, 0, 0, 0.08);">
        @foreach (['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'] as $day)
            <div style="min-height:30px;padding:0.2rem; text-align:center;background-color:white;"
                @if ($day == 'MON') class="border-top-left"
                    @elseif($day == 'SUN')
                    class="border-top-right" @endif>
                {{ $day }}</div>
        @endforeach

        @for ($i = 1; $i <= 42; $i++)
            @if ($i < $firstDay)
                <div style="background-color:white;min-height:100px;"></div>
            @elseif($i > $numOfDays + $firstDay - 1)
                <div style="background-color:var(--bar-color-blue);min-height:100px;"
                    @if ($i == 36) class="border-btm-left"
                @elseif($i == 42)
                class="border-btm-right" @endif>
                </div>
            @else
                <div
                    style="background-color:white;text-align:center;display:flex; flex-direction: column;padding-inline:0.5rem;text-align:left; gap:0.1rem; overflow: hidden;white-space:nowrap; text-overflow:ellipsis;" @if ($i == 36) class="border-btm-left"
                @elseif($i == 42)
                class="border-btm-right" @endif>
                    <div style="font-weight:bold; text-align:center">{{ $i - $firstDay + 1 }}</div>
                    @if (isset($demos[$i - $firstDay + 1]))
                        @foreach ($demos[$i - $firstDay + 1] as $row)
                            <div 
                            @if ($row->type === "NEW DEMO")
                            class="bg-green"
                            @elseif($row->type === "WEBINAR DEMO")
                            class="bg-yellow"
                            @else
                            class="bg-red"
                            @endif
                            style="display: flex; flex-direction: row;">
                                <div style="width:30%; padding-inline: 0.3rem;font-family: 'Roboto Mono';">{{ $row->formattedStartTime }}</div>
                                <div style="width:70% white-space: nowrap;  overflow: hidden;text-overflow: ellipsis;padding-inline:0.3rem">{{ $row->company_name }}</div>
                             </div>
                        @endforeach
                    @endif
                </div>
            @endif
        @endfor

    </div>
</div>
