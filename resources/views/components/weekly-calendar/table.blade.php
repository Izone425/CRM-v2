<style>
    .grid-table {
        display: grid;
        grid-auto-flow: column;
        grid-template-columns: 2fr repeat(5, 1fr);
        grid-template-rows: 50px repeat(4, 30px);
        gap: 1px;
        background-color: #ddd;
        border: 1px solid #ddd;
        width: 100%;
        max-width: 800px;
        margin: 0.1rem auto;
    }

    .grid-cell {
        background-color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 5px;
        font-family: Arial, sans-serif;
    }

    .header {
        background-color: #e5f8fd;
        font-weight: bold;
        display: flex;
        flex-direction: column;
    }
</style>


<div x-data="{ open: false }">
    <div class="grid-table">
        <!-- Header Row -->
        <div x-on:click="open = ! open" class="grid-cell header" style="background-color:#dff0f7;">{{ $tableData['name'] }}</div>
        <div class="grid-cell" style="background-color:#f2f2f2;">Session 1</div>
        <div class="grid-cell" style="background-color:#f2f2f2;">Session 2</div>
        <div class="grid-cell" style="background-color:#f2f2f2;">Session 3</div>
        <div class="grid-cell" style="background-color:#f2f2f2;">Session 4</div>

        @foreach ($tableData['weekDateArr'] as $date)
            <div class="grid-cell header">
                <div>{{ $date['date'] }}</div>
                <div>{{ $date['day'] }}</div>
            </div>

            @if (isset($date['holiday']))
                <div class="grid-cell" style="background-color:#bfbbbb;color:black;font-size:0.8rem">P. HOLIDAY</div>
                <div class="grid-cell" style="background-color:#bfbbbb;color:black;font-size:0.8rem">P. HOLIDAY</div>
                <div class="grid-cell" style="background-color:#bfbbbb;color:black;font-size:0.8rem">P. HOLIDAY</div>
                <div class="grid-cell" style="background-color:#bfbbbb;color:black;font-size:0.8rem">P. HOLIDAY</div>


            @elseif($date['carbonDate']->isBefore($tableData['today']))
                <div class="grid-cell" style="background-color:#474747;color:white"></div>
                <div class="grid-cell" style="background-color:#474747;color:white"></div>
                <div class="grid-cell" style="background-color:#474747;color:white"></div>
                <div class="grid-cell" style="background-color:#474747;color:white"></div>

            @elseif(!empty($date['leave']) && $date['leave'][0]['session'] == 'full')
                @foreach ($date['leave'] as $leave)
                    @if ($leave['session'] == 'full')
                        <div class="grid-cell" style="background-color:#bfbbbb;color:black">On-Leave</div>
                        <div class="grid-cell" style="background-color:#bfbbbb;color:black">On-Leave</div>
                        <div class="grid-cell" style="background-color:#bfbbbb;color:black">On-Leave</div>
                        <div class="grid-cell" style="background-color:#bfbbbb;color:black">On-Leave</div>
                    @endif
                @endforeach
            @else
                @php
                    // Filter matching appointments
                    $matched = [];
                    $leaveType = '';
                    $total = 4;
                    if (!empty($date['leave'])) {
                        foreach ($date['leave'] as $leave) {
                            if ($leave['session'] == 'am') {
                                $leaveType = 'am';
                            }
                            $total = 2;
                        }
                    }

                    foreach ($tableData['appointment'] as $value) {
                        if ($date['carbonDate']->eq($value['carbonDate'])) {
                            $matched[] = $value;
                        }
                    }

                    if (!empty($date['leave'])) {
                        foreach ($date['leave'] as $leave) {
                            if ($leave['session'] == 'pm') {
                                $leaveType = 'pm';
                            }
                            $total = 2;
                        }
                    }

                    // Pad with null values if less than 4
                    $count = count($matched);
                    for ($i = $count; $i < $total; $i++) {
                        $matched[] = null;
                    }

                    // Define internal sales task types
                    $internalSalesTaskTypes = [
                        'EXHIBITION',
                        'INTERNAL MEETING',
                        'SALES MEETING',
                        'PRODUCT MEETING',
                        'TOWNHALL SESSION',
                        'FOLLOW UP SESSION',
                        'BUSINESS TRIP'
                    ];
                @endphp

                @if ($leaveType == 'am')
                    <div class="grid-cell" style="background-color:#bfbbbb;color:black">On-Leave</div>
                    <div class="grid-cell" style="background-color:#bfbbbb;color:black">On-Leave</div>
                @endif

                @foreach ($matched as $item)
                    <div @if (isset($item)) wire:click="openModal(
                        '{{ $item['date'] }}',
                        '{{ $item['start_time'] }}',
                        '{{ $item['end_time'] }}',
                        '{{ $item['salesperson'] }}',
                        '{{ isset($item['type']) ? $item['type'] : null }}')" @endif
                        class="grid-cell @if (isset($item)) cursor-pointer transition hover:brightness-90 @endif"
                        style="
                        @if (isset($item) && $item['type'] == 'NEW DEMO')
                            background-color: #71eb71;
                        @elseif (isset($item) && $item['type'] == 'WEBINAR DEMO')
                            background-color: #ffff5cbf;
                        @elseif (isset($item) && in_array($item['type'], $internalSalesTaskTypes))
                            background-color: #3b82f6; color: white;
                        @elseif (isset($item['type']))
                            background-color: #f86f6f;
                        @endif
                    ">
                        {{ $item ? $item['carbonStartTime'] : '' }}
                    </div>
                @endforeach

                @if ($leaveType == 'pm')
                    <div class="grid-cell" style="background-color:#bfbbbb;color:black">On-Leave</div>
                    <div class="grid-cell" style="background-color:#bfbbbb;color:black">On-Leave</div>
                @endif
            @endif
        @endforeach
    </div>
</div>
