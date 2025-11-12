{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/ticket-dashboard.blade.php --}}
<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $softwareBugs = $data['softwareBugs'];
        $backendAssistance = $data['backendAssistance'];
        $enhancement = $data['enhancement'];
        $tickets = $data['tickets'];
        $calendar = $data['calendar'];
        $currentMonth = $data['currentMonth'];
        $currentYear = $data['currentYear'];
        $products = $data['products'];
        $modules = $data['modules'];
    @endphp

    <style>
        select:not(.choices) {
            background-image: none !important;
        }

        /* Main Layout */
        .dashboard-wrapper {
            background: #F9FAFB;
            min-height: 100vh;
            padding: 0;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        /* ✅ Add page title styling */
        .page-title {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        .filter-dropdowns {
            display: flex;
            gap: 12px;
        }

        .filter-select {
            padding: 8px 16px;
            border: 1px solid #D1D5DB;
            border-radius: 8px;
            background: white;
            font-size: 14px;
            color: #374151;
            cursor: pointer;
            min-width: 180px;
            appearance: none; /* ✅ Remove default dropdown arrow */
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: none; /* ✅ Remove any background arrow */
        }

        .filter-select:focus {
            outline: none;
            border-color: #6366F1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 570px 1fr;
            gap: 24px;
        }

        /* Left Column */
        .left-column {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* Category Cards */
        .category-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #E5E7EB;
        }

        .category-card.red {
            border-left: 4px solid #DC2626;
        }

        .category-card.blue {
            border-left: 4px solid #2563EB;
        }

        .category-card.green {
            border-left: 4px solid #059669;
        }

        .category-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .category-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }

        .red .category-icon {
            background: #FEE2E2;
        }

        .blue .category-icon {
            background: #DBEAFE;
        }

        .green .category-icon {
            background: #D1FAE5;
        }

        .category-title {
            flex: 1;
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }

        .category-badge {
            background: #F3F4F6;
            color: #6B7280;
            padding: 4px 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
        }

        /* Status Grid */
        .status-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .status-grid.three-items {
            grid-template-columns: repeat(3, 1fr);
        }

        .status-box {
            background: #FAFAFA;
            border: 1px solid #E5E7EB;
            border-radius: 8px;
            padding: 14px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .status-box:hover {
            background: white;
            border-color: #D1D5DB;
            transform: translateY(-1px);
        }

        .status-box.active {
            background: #1F2937;
            border-color: #1F2937;
        }

        .status-number {
            font-size: 20px;
            font-weight: 700;
            color: #111827;
            line-height: 1;
            margin-bottom: 6px;
        }

        .status-box.active .status-number {
            color: white;
        }

        .status-text {
            font-size: 12px;
            font-weight: 500;
            color: #6B7280;
        }

        .status-box.active .status-text {
            color: #D1D5DB;
        }

        /* Enhancement Section */
        .enhancement-filters {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
            justify-content: flex-end;
        }

        .filter-pill {
            padding: 6px 14px;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            background: white;
            font-size: 12px;
            color: #6B7280;
            cursor: pointer;
            transition: all 0.2s;
            font-weight: 500;
        }

        .filter-pill:hover {
            border-color: #059669;
            color: #059669;
        }

        /* ✅ Active state for filter pills */
        .filter-pill.active {
            background: #059669;
            border-color: #059669;
            color: white;
            font-weight: 600;
        }

        .filter-pill.active:hover {
            background: #047857;
            border-color: #047857;
            color: white;
        }

        /* Calendar */
        .calendar-wrapper {
            background: white;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #E5E7EB;
            margin-top: 16px;
        }

        .calendar-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }

        .calendar-title {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
        }

        .calendar-arrows {
            display: flex;
            gap: 4px;
        }

        .arrow-btn {
            background: transparent;
            border: none;
            color: #9CA3AF;
            cursor: pointer;
            padding: 4px;
            font-size: 18px;
        }

        .arrow-btn:hover {
            color: #374151;
        }

        .calendar-table {
            width: 100%;
            border-collapse: collapse;
        }

        .calendar-table th {
            font-size: 11px;
            font-weight: 600;
            color: #9CA3AF;
            text-transform: uppercase;
            padding: 8px 4px;
            text-align: center;
        }

        .calendar-table td {
            text-align: center;
            padding: 4px;
            font-size: 13px;
            color: #374151;
        }

        .calendar-day {
            width: 36px;
            height: 36px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
        }

        .calendar-day:hover {
            background: #F3F4F6;
        }

        .calendar-day.today {
            background: #6366F1;
            color: white;
            font-weight: 600;
        }

        .calendar-day.selected {
            background: #10B981;
            color: white;
            font-weight: 600;
        }

        .calendar-day.today.selected {
            background: #059669;
            color: white;
        }

        .calendar-day.other-month {
            color: #D1D5DB;
        }

        .calendar-day.other-month:hover {
            background: #F9FAFB;
        }

        /* Right Panel */
        .ticket-panel {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #E5E7EB;
        }

        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 1px solid #E5E7EB;
        }

        .ticket-title {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }

        .ticket-filters {
            display: flex;
            gap: 12px;
            align-items: center;
        }

        .ticket-filter-select {
            padding: 6px 12px;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            font-size: 13px;
            background: white;
            appearance: none; /* ✅ Remove dropdown arrow */
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: none;
        }

        .close-badge {
            background: #F3F4F6;
            color: #6B7280;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 500;
        }

        .close-badge:hover {
            background: #E5E7EB;
        }

        .ticket-count {
            color: #9CA3AF;
            font-size: 13px;
        }

        .empty-tickets {
            text-align: center;
            padding: 80px 20px;
        }

        .empty-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 16px;
            opacity: 0.3;
        }

        .empty-text {
            color: #9CA3AF;
            font-size: 14px;
        }

        .status-badge-wrapper:hover .status-tooltip {
            opacity: 1 !important;
        }
    </style>

    <div class="dashboard-wrapper">
        <!-- ✅ Header with Title and Filters on same line -->
        <div class="dashboard-header">
            <h1 class="page-title">Ticket Dashboard</h1>

            <div class="filter-dropdowns">
                <button type="button"
                        wire:click="mountAction('createTicket')"
                        style="padding: 8px 16px; background: #6366F1; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s;"
                        onmouseover="this.style.background='#4F46E5'"
                        onmouseout="this.style.background='#6366F1'">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Create Ticket
                </button>
                <select class="filter-select" wire:model.live="selectedProduct">
                    <option>All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product }}">{{ $product }}</option>
                    @endforeach
                </select>

                <select class="filter-select" wire:model.live="selectedModule">
                    <option>All Modules</option>
                    @foreach($modules as $module)
                        <option value="{{ $module }}">{{ $module }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Main Grid -->
        <div class="dashboard-grid">
            <!-- Left Column -->
            <div class="left-column">
                <!-- Software Bugs -->
                <div class="category-card red">
                    <div class="category-header">
                        <div class="category-icon">📋</div>
                        <div class="category-title">Software Bugs</div>
                        <div class="category-badge">{{ $softwareBugs['total'] }}</div>
                    </div>
                    <div class="status-grid">
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && $selectedStatus === 'New' ? 'active' : '' }}"
                            wire:click="selectCategory('softwareBugs', 'New')">
                            <div class="status-number">{{ $softwareBugs['new'] }}</div>
                            <div class="status-text">New</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && $selectedStatus === 'In Review' ? 'active' : '' }}"
                            wire:click="selectCategory('softwareBugs', 'In Review')">
                            <div class="status-number">{{ $softwareBugs['review'] }}</div>
                            <div class="status-text">Review</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && $selectedStatus === 'In Progress' ? 'active' : '' }}"
                            wire:click="selectCategory('softwareBugs', 'In Progress')"
                            style="position: relative;">
                            {{-- ✅ Reopen badge in top-right corner (RED theme) --}}
                            @if($softwareBugs['reopen'] > 0)
                                <div wire:click.stop="selectCategory('softwareBugs', 'Reopen')"
                                    class="{{ $selectedCategory === 'softwareBugs' && $selectedStatus === 'Reopen' ? 'active-reopen' : '' }}"
                                    style="position: absolute; top: -5px; right: -5px; font-size: 9px; color: white; background: #DC2626; font-weight: 700; padding: 2px 6px; border-radius: 3px; cursor: pointer; transition: all 0.2s; z-index: 10; line-height: 1.2;"
                                    onmouseover="this.style.background='#991B1B'; this.style.transform='scale(1.1)'"
                                    onmouseout="this.style.background='#DC2626'; this.style.transform='scale(1)'">
                                    Reopen +{{ $softwareBugs['reopen'] }}
                                </div>
                            @endif
                            <div class="status-number">{{ $softwareBugs['progress'] }}</div>
                            <div class="status-text">In Progress</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && $selectedStatus === 'Closed' ? 'active' : '' }}"
                            wire:click="selectCategory('softwareBugs', 'Closed')">
                            <div class="status-number">{{ $softwareBugs['closed'] }}</div>
                            <div class="status-text">Closed</div>
                        </div>
                    </div>
                </div>

                <!-- Backend Assistance -->
                <div class="category-card blue">
                    <div class="category-header">
                        <div class="category-icon">💻</div>
                        <div class="category-title">Backend Assistance</div>
                        <div class="category-badge">{{ $backendAssistance['total'] }}</div>
                    </div>
                    <div class="status-grid">
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && $selectedStatus === 'New' ? 'active' : '' }}"
                            wire:click="selectCategory('backendAssistance', 'New')">
                            <div class="status-number">{{ $backendAssistance['new'] }}</div>
                            <div class="status-text">New</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && $selectedStatus === 'In Review' ? 'active' : '' }}"
                            wire:click="selectCategory('backendAssistance', 'In Review')">
                            <div class="status-number">{{ $backendAssistance['review'] }}</div>
                            <div class="status-text">Review</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && $selectedStatus === 'In Progress' ? 'active' : '' }}"
                            wire:click="selectCategory('backendAssistance', 'In Progress')"
                            style="position: relative;">
                            {{-- ✅ Reopen badge in top-right corner (BLUE theme) --}}
                            @if($backendAssistance['reopen'] > 0)
                                <div wire:click.stop="selectCategory('backendAssistance', 'Reopen')"
                                    class="{{ $selectedCategory === 'backendAssistance' && $selectedStatus === 'Reopen' ? 'active-reopen' : '' }}"
                                    style="position: absolute; top: -5px; right: -5px; font-size: 9px; color: white; background: #2563EB; font-weight: 700; padding: 2px 6px; border-radius: 3px; cursor: pointer; transition: all 0.2s; z-index: 10; line-height: 1.2;"
                                    onmouseover="this.style.background='#1D4ED8'; this.style.transform='scale(1.1)'"
                                    onmouseout="this.style.background='#2563EB'; this.style.transform='scale(1)'">
                                    Reopen +{{ $backendAssistance['reopen'] }}
                                </div>
                            @endif
                            <div class="status-number">{{ $backendAssistance['progress'] }}</div>
                            <div class="status-text">In Progress</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && $selectedStatus === 'Closed' ? 'active' : '' }}"
                            wire:click="selectCategory('backendAssistance', 'Closed')">
                            <div class="status-number">{{ $backendAssistance['closed'] }}</div>
                            <div class="status-text">Closed</div>
                        </div>
                    </div>
                </div>

                <!-- Enhancement Workflow -->
                <div class="category-card green">
                    <div class="category-header">
                        <div class="category-icon">⭐</div>
                        <div class="category-title">Enhancement Workflow</div>
                        <div class="category-badge">{{ $enhancement['total'] }}</div>
                    </div>
                    <div class="enhancement-filters">
                        <div class="filter-pill {{ $selectedEnhancementType === 'critical' ? 'active' : '' }}"
                             wire:click="selectEnhancementType('critical')">
                            Critical
                        </div>
                        <div class="filter-pill {{ $selectedEnhancementType === 'paid' ? 'active' : '' }}"
                             wire:click="selectEnhancementType('paid')">
                            Paid
                        </div>
                        <div class="filter-pill {{ $selectedEnhancementType === 'non-critical' ? 'active' : '' }}"
                             wire:click="selectEnhancementType('non-critical')">
                            Non Critical
                        </div>
                    </div>
                    <div class="status-grid three-items">
                        <div class="status-box {{ $selectedEnhancementStatus === 'New' ? 'active' : '' }}"
                             wire:click="selectEnhancement('New')">
                            <div class="status-number">{{ $enhancement['new'] }}</div>
                            <div class="status-text">New</div>
                        </div>
                        <div class="status-box {{ $selectedEnhancementStatus === 'Pending Release' ? 'active' : '' }}"
                             wire:click="selectEnhancement('Pending Release')">
                            <div class="status-number">{{ $enhancement['pending_release'] }}</div>
                            <div class="status-text">Pending Release</div>
                        </div>
                        <div class="status-box {{ $selectedEnhancementStatus === 'System Go Live' ? 'active' : '' }}"
                             wire:click="selectEnhancement('System Go Live')">
                            <div class="status-number">{{ $enhancement['system_go_live'] }}</div>
                            <div class="status-text">System Go Live</div>
                        </div>
                    </div>

                    <!-- Calendar -->
                    <div class="calendar-wrapper">
                        <div class="calendar-nav">
                            <button class="arrow-btn" wire:click="previousMonth">‹</button>
                            <div class="calendar-title">{{ $calendar['month'] }}</div>
                            <button class="arrow-btn" wire:click="nextMonth">›</button>
                        </div>
                        <table class="calendar-table">
                            <thead>
                                <tr>
                                    <th>MON</th>
                                    <th>TUE</th>
                                    <th>WED</th>
                                    <th>THU</th>
                                    <th>FRI</th>
                                    <th>SAT</th>
                                    <th>SUN</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $firstDay = $calendar['first_day_of_week'];
                                    $daysInMonth = $calendar['days_in_month'];
                                    $currentDate = $calendar['current_date'];
                                    $adjustedFirstDay = $firstDay == 0 ? 6 : $firstDay - 1;

                                    $prevMonthDate = \Carbon\Carbon::create($currentYear, $currentMonth, 1)->subMonth();
                                    $prevMonthDays = $prevMonthDate->daysInMonth;

                                    $days = [];

                                    for ($i = $adjustedFirstDay - 1; $i >= 0; $i--) {
                                        $days[] = [
                                            'day' => $prevMonthDays - $i,
                                            'class' => 'other-month',
                                            'year' => $prevMonthDate->year,
                                            'month' => $prevMonthDate->month,
                                        ];
                                    }

                                    for ($day = 1; $day <= $daysInMonth; $day++) {
                                        $isToday = $day == $currentDate->day &&
                                                  $currentMonth == $currentDate->month &&
                                                  $currentYear == $currentDate->year;

                                        $dateString = \Carbon\Carbon::create($currentYear, $currentMonth, $day)->format('Y-m-d');
                                        $isSelected = $selectedDate === $dateString;

                                        $class = $isToday ? 'today' : '';
                                        if ($isSelected) {
                                            $class .= ' selected';
                                        }

                                        $days[] = [
                                            'day' => $day,
                                            'class' => trim($class),
                                            'year' => $currentYear,
                                            'month' => $currentMonth,
                                        ];
                                    }

                                    $totalCells = count($days);
                                    $remainingCells = (7 - ($totalCells % 7)) % 7;

                                    $nextMonthDate = \Carbon\Carbon::create($currentYear, $currentMonth, 1)->addMonth();

                                    for ($day = 1; $day <= $remainingCells; $day++) {
                                        $days[] = [
                                            'day' => $day,
                                            'class' => 'other-month',
                                            'year' => $nextMonthDate->year,
                                            'month' => $nextMonthDate->month,
                                        ];
                                    }

                                    $weeks = array_chunk($days, 7);
                                @endphp

                                @foreach($weeks as $week)
                                    <tr>
                                        @foreach($week as $dayData)
                                            <td>
                                                <div class="calendar-day {{ $dayData['class'] }}"
                                                     wire:click="selectDate({{ $dayData['year'] }}, {{ $dayData['month'] }}, {{ $dayData['day'] }})">
                                                    {{ $dayData['day'] }}
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Right Panel - Ticket Listing -->
            <div class="ticket-panel">
                <div class="ticket-header">
                    <div>
                        <div class="ticket-title">Ticket Listing
                            <span class="ticket-count">{{ count($tickets) }}</span>
                        </div>
                    </div>
                    <div class="ticket-filters">
                        <select class="ticket-filter-select">
                            <option>All</option>
                        </select>
                        @if($selectedStatus || $selectedEnhancementStatus)
                            <span class="close-badge" wire:click="selectCategory(null, null)">
                                {{ $selectedStatus ?? $selectedEnhancementStatus }} ✕
                            </span>
                        @endif
                        @if($selectedEnhancementType)
                            <span class="close-badge" wire:click="selectEnhancementType(null)">
                                {{ ucfirst($selectedEnhancementType) }} Enhancement ✕
                            </span>
                        @endif
                        @if($selectedDate)
                            <span class="close-badge" wire:click="$set('selectedDate', null)">
                                {{ \Carbon\Carbon::parse($selectedDate)->format('d M Y') }} ✕
                            </span>
                        @endif
                    </div>
                </div>

                @if(count($tickets) > 0)
                    <div style="overflow-x: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead style="background: #FAFAFA; border-bottom: 2px solid #E5E7EB;">
                                <tr>
                                    <th style="padding: 12px; text-align: left; font-size: 12px; color: #6B7280; font-weight: 600;">ID</th>
                                    <th style="padding: 12px; text-align: left; font-size: 12px; color: #6B7280; font-weight: 600;">SUBJECT</th>
                                    <th style="padding: 12px; text-align: left; font-size: 12px; color: #6B7280; font-weight: 600;">ETA</th>
                                    <th style="padding: 12px; text-align: left; font-size: 12px; color: #6B7280; font-weight: 600;">STATUS</th>
                                    <th style="padding: 12px; text-align: center; font-size: 12px; color: #6B7280; font-weight: 600;">PASS/FAIL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tickets as $ticket)
                                    <tr style="border-bottom: 1px solid #F3F4F6;">
                                        <td style="padding: 12px; font-size: 13px; font-weight: 600; cursor: pointer;" wire:click="viewTicket({{ $ticket->id }})">
                                            {{ $ticket->ticket_id }}
                                        </td>
                                        <td style="padding: 12px; font-size: 13px; cursor: pointer;" wire:click="viewTicket({{ $ticket->id }})">
                                            {{ \Str::limit($ticket->title, 50) }}
                                        </td>
                                        <td style="padding: 12px; font-size: 13px; color: #6B7280; cursor: pointer;" wire:click="viewTicket({{ $ticket->id }})">
                                            {{ $ticket->eta_release ? $ticket->eta_release->format('d M Y') : '-' }}
                                        </td>
                                        <td style="padding: 12px; cursor: pointer;" wire:click="viewTicket({{ $ticket->id }})">
                                            <span style="padding: 4px 8px; border-radius: 4px; font-size: 11px; background: #F3F4F6; color: #6B7280;">
                                                {{ $ticket->status }}
                                            </span>
                                        </td>
                                        <td style="padding: 12px; text-align: center;" onclick="event.stopPropagation();">
                                            @if($ticket->isPassed == 0)
                                                <div style="display: inline-flex; gap: 8px;">
                                                    <button wire:click="markAsPassed({{ $ticket->id }})"
                                                            style="padding: 6px 12px; background: #10B981; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s;"
                                                            onmouseover="this.style.background='#059669'"
                                                            onmouseout="this.style.background='#10B981'">
                                                        ✓ Pass
                                                    </button>
                                                    <button wire:click="markAsFailed({{ $ticket->id }})"
                                                            style="padding: 6px 12px; background: #EF4444; color: white; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.2s;"
                                                            onmouseover="this.style.background='#DC2626'"
                                                            onmouseout="this.style.background='#EF4444'">
                                                        ✕ Fail
                                                    </button>
                                                </div>
                                            @else
                                                <div style="display: inline-flex; align-items: center; gap: 8px;">
                                                    <span style="padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 600; {{ $ticket->isPassed == 1 ? 'background: #D1FAE5; color: #059669;' : 'background: #FEE2E2; color: #DC2626;' }}">
                                                        {{ $ticket->isPassed == 1 ? '✓ Passed' : '✕ Failed' }}
                                                    </span>
                                                    @if($ticket->passed_at)
                                                        <span style="font-size: 11px; color: #9CA3AF;">
                                                            {{ $ticket->passed_at->format('d M Y H:i') }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-tickets">
                        <div class="empty-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M8 15s1.5 2 4 2 4-2 4-2"/>
                                <line x1="9" y1="9" x2="9.01" y2="9"/>
                                <line x1="15" y1="9" x2="15.01" y2="9"/>
                            </svg>
                        </div>
                        <div class="empty-text">No tickets found for this filter</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if($showTicketModal && $selectedTicket)
        <div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 50; display: flex; align-items: center; justify-content: center;"
            wire:click="closeTicketModal">
            <div style="background: white; border-radius: 16px; width: 100%; max-width: 1150px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;"
                wire:click.stop>

                <!-- Modal Header -->
                <div style="padding: 24px; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="background: #FEF3C7; padding: 8px 12px; border-radius: 6px;">
                            <span style="color: #F59E0B; font-size: 14px; font-weight: 600;">📋 TICKET</span>
                        </div>
                        <h2 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0;">
                            TC-HR1-{{ str_pad($selectedTicket->id, 4, '0', STR_PAD_LEFT) }}
                        </h2>
                    </div>
                    <button wire:click="closeTicketModal" style="background: transparent; border: none; color: #9CA3AF; cursor: pointer; font-size: 24px;">
                        ✕
                    </button>
                </div>

                <!-- Modal Body -->
                <div style="flex: 1; overflow-y: auto; display: grid; grid-template-columns: 1fr 350px;">

                    <!-- Left Side - Main Content -->
                    <div style="padding: 24px; border-right: 1px solid #E5E7EB;">
                        <!-- Title -->
                        <h1 style="font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 24px 0;">
                            {{ $selectedTicket->title }}
                        </h1>

                        <!-- Description Section (Always visible at top) -->
                        <div style="margin-bottom: 24px;">
                            <div style="font-size: 14px; font-weight: 600; color: #6B7280; margin-bottom: 12px;">Description</div>
                            <div style="background: #f7f7fe; padding: 16px; border-radius: 8px; border: 1px solid #E5E7EB;">
                                {!! $selectedTicket->description ?? 'No description provided.' !!}
                            </div>
                        </div>

                        <!-- Tabs -->
                        <div x-data="{ activeTab: 'comments' }" style="margin-bottom: 24px;">
                            <div style="display: flex; gap: 24px; border-bottom: 2px solid #F3F4F6;">
                                <button @click="activeTab = 'comments'"
                                        :style="activeTab === 'comments' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                        style="padding: 12px 0; font-weight: 600; font-size: 14px; background: transparent; border: none; cursor: pointer; margin-bottom: -2px;">
                                    Comments
                                </button>
                                <button @click="activeTab = 'attachments'"
                                        :style="activeTab === 'attachments' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                        style="padding: 12px 0; font-weight: 600; font-size: 14px; background: transparent; border: none; cursor: pointer; margin-bottom: -2px;">
                                    Attachments ({{ $selectedTicket->attachments->count() }})
                                </button>
                                <button @click="activeTab = 'status'"
                                        :style="activeTab === 'status' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                        style="padding: 12px 0; font-weight: 600; font-size: 14px; background: transparent; border: none; cursor: pointer; margin-bottom: -2px;">
                                    Status Log
                                </button>
                            </div>

                            <!-- Comments Tab -->
                            <div x-show="activeTab === 'comments'" style="padding: 24px 0;">
                                <!-- Add Comment -->
                                <div style="margin-bottom: 24px;">
                                    <textarea wire:model="newComment"
                                            placeholder="Add a comment..."
                                            style="width: 100%; padding: 12px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px; resize: vertical; min-height: 100px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;"
                                            onfocus="this.style.borderColor='#6366F1'; this.style.outline='none';"
                                            onblur="this.style.borderColor='#E5E7EB';"></textarea>
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                                        <div style="font-size: 12px; color: #9CA3AF;">
                                            <!-- Optional: Add character count or other info -->
                                        </div>
                                        <button wire:click="addComment"
                                                style="padding: 8px 20px; background: #6366F1; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s;"
                                                onmouseover="this.style.background='#4F46E5'"
                                                onmouseout="this.style.background='#6366F1'">
                                            Add
                                        </button>
                                    </div>
                                </div>

                                <!-- Previous Comments -->
                                @if($selectedTicket->comments->count() > 0)
                                    <div style="margin-top: 24px;">
                                        <h4 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #E5E7EB;">
                                            Previous Comments
                                        </h4>
                                        @foreach($selectedTicket->comments as $comment)
                                            <div style="margin-bottom: 20px; padding: 16px; background: #F9FAFB; border-radius: 8px; border-left: 3px solid #6366F1;">
                                                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                                    <div style="width: 36px; height: 36px; border-radius: 50%; background: #6366F1; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px;">
                                                        {{ strtoupper(substr($comment->user->name ?? 'U', 0, 1)) }}
                                                    </div>
                                                    <div style="flex: 1; display: flex; align-items: center; justify-content: space-between;">
                                                        <div style="display: flex; align-items: center; gap: 8px;">
                                                            <span style="font-weight: 600; font-size: 14px; color: #111827;">
                                                                {{ $comment->user->name ?? 'Unknown User' }}
                                                            </span>
                                                            <span style="padding: 2px 8px; background: #E0E7FF; color: #4F46E5; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                                                {{ $comment->user->role ?? 'HRcrm User' }}
                                                            </span>
                                                        </div>
                                                        <div style="font-size: 12px; color: #9CA3AF;">
                                                            {{ $comment->created_at->diffForHumans() }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <p style="color: #374151; margin: 0; font-size: 14px; line-height: 1.6; white-space: pre-wrap;">{{ $comment->comment }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div style="text-align: center; padding: 60px 20px;">
                                        <div style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;">💬</div>
                                        <p style="color: #9CA3AF; font-size: 14px;">No comments yet</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Attachments Tab -->
                            <div x-show="activeTab === 'attachments'" style="padding: 24px 0;">
                                @if($selectedTicket->device_type === 'Mobile' && $selectedTicket->version_screenshot)
                                    <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px;">Version Screenshot</h3>

                                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #F9FAFB; border-radius: 8px; margin-bottom: 24px; border-left: 3px solid #6366F1;">
                                        <div style="flex: 1;">
                                            <div style="font-weight: 600; font-size: 14px; color: #111827;">App Version Screenshot</div>
                                            <div style="font-size: 12px; color: #6B7280; margin-top: 4px;">
                                                Mobile App Version
                                            </div>
                                        </div>
                                        <div style="display: flex; gap: 8px;">
                                            <a href="{{ asset('storage/' . $selectedTicket->version_screenshot) }}" target="_blank"
                                            style="padding: 6px 12px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; cursor: pointer; text-decoration: none; color: #374151; display: flex; align-items: center; gap: 4px;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                    <circle cx="12" cy="12" r="3"></circle>
                                                </svg>
                                                View
                                            </a>
                                            <a href="{{ asset('storage/' . $selectedTicket->version_screenshot) }}" download
                                            style="padding: 6px 12px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; cursor: pointer; text-decoration: none; color: #374151; display: flex; align-items: center; gap: 4px;">
                                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                    <polyline points="7 10 12 15 17 10"></polyline>
                                                    <line x1="12" y1="15" x2="12" y2="3"></line>
                                                </svg>
                                                Download
                                            </a>
                                        </div>
                                    </div>

                                    <div style="border-top: 1px solid #E5E7EB; margin: 24px 0;"></div>
                                @endif
                                @if($selectedTicket->attachments->count() > 0)
                                    <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px;">Current Attachments</h3>

                                    @foreach($selectedTicket->attachments as $attachment)
                                        <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #F9FAFB; border-radius: 8px; margin-bottom: 8px;">
                                            <div style="flex: 1;">
                                                <div style="font-weight: 600; font-size: 14px; color: #111827;">{{ $attachment->original_filename }}</div>
                                                <div style="font-size: 12px; color: #6B7280; margin-top: 4px;">
                                                    {{ $attachment->file_size_formatted }} • {{ $attachment->created_at->format('d M Y, H:i A') }}
                                                    <br>
                                                    <span style="font-style: italic;">by {{ $attachment->uploader->name ?? 'Unknown' }}</span>
                                                </div>
                                            </div>
                                            <div style="display: flex; gap: 8px;">
                                                <a href="{{ asset($attachment->file_path) }}" target="_blank"
                                                style="padding: 6px 12px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; cursor: pointer; text-decoration: none; color: #374151; display: flex; align-items: center; gap: 4px;">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                    View
                                                </a>
                                                <a href="{{ asset($attachment->file_path) }}" download
                                                style="padding: 6px 12px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; cursor: pointer; text-decoration: none; color: #374151; display: flex; align-items: center; gap: 4px;">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                                        <polyline points="7 10 12 15 17 10"></polyline>
                                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                                    </svg>
                                                    Download
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach

                                    <div style="border-top: 1px solid #E5E7EB; margin: 24px 0;"></div>
                                @else
                                    <div style="text-align: center; padding: 40px 20px;">
                                        <div style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;">📎</div>
                                        <p style="color: #9CA3AF; font-size: 14px;">No attachments yet</p>
                                    </div>
                                @endif

                                <!-- ✅ Working Upload Section -->
                                <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px;">Upload New Attachments</h3>

                                <div style="margin-bottom: 16px;">
                                    <input type="file"
                                        wire:model="attachments"
                                        multiple
                                        style="width: 100%; padding: 12px; border: 2px dashed #E5E7EB; border-radius: 8px; cursor: pointer;"
                                        accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">

                                    @error('attachments.*')
                                        <div style="color: #DC2626; font-size: 12px; margin-top: 8px;">{{ $message }}</div>
                                    @enderror

                                    <!-- Loading Indicator -->
                                    <div wire:loading wire:target="attachments" style="margin-top: 12px; color: #6366F1; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
                                            <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                                            <path d="M12 2a10 10 0 0 1 10 10" stroke-opacity="0.75"></path>
                                        </svg>
                                        Uploading files...
                                    </div>

                                    <style>
                                        @keyframes spin {
                                            from { transform: rotate(0deg); }
                                            to { transform: rotate(360deg); }
                                        }
                                    </style>
                                </div>

                                <!-- Selected Files Preview -->
                                @if(!empty($attachments))
                                    <div style="margin-bottom: 16px; padding: 12px; background: #F9FAFB; border-radius: 8px;">
                                        <div style="font-size: 13px; font-weight: 600; color: #6B7280; margin-bottom: 8px;">
                                            Selected Files ({{ count($attachments) }})
                                        </div>
                                        @foreach($attachments as $index => $file)
                                            <div style="font-size: 12px; color: #374151; padding: 4px 0;">
                                                • {{ $file->getClientOriginalName() }} ({{ number_format($file->getSize() / 1024, 2) }} KB)
                                            </div>
                                        @endforeach
                                    </div>
                                @endif

                                <!-- Upload Button -->
                                <button wire:click="uploadAttachments"
                                        @if(empty($attachments)) disabled @endif
                                        style="width: 100%; padding: 12px; background: {{ empty($attachments) ? '#D1D5DB' : '#6366F1' }}; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: {{ empty($attachments) ? 'not-allowed' : 'pointer' }}; transition: all 0.2s;"
                                        @if(!empty($attachments))
                                            onmouseover="this.style.background='#4F46E5'"
                                            onmouseout="this.style.background='#6366F1'"
                                        @endif>
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display: inline-block; margin-right: 8px; vertical-align: middle;">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="17 8 12 3 7 8"></polyline>
                                        <line x1="12" y1="3" x2="12" y2="15"></line>
                                    </svg>
                                    Upload Files
                                </button>

                                <div style="margin-top: 12px; font-size: 12px; color: #9CA3AF; text-align: center;">
                                    Maximum file size: 10MB per file
                                </div>
                            </div>

                            <!-- Status Log Tab -->
                            <div x-show="activeTab === 'status'" style="padding: 24px 0;">
                                @if($selectedTicket->logs->count() > 0)
                                    <div style="position: relative;">
                                        @foreach($selectedTicket->logs->sortByDesc('created_at') as $index => $log)
                                            <div style="display: flex; gap: 16px; margin-bottom: {{ $index < $selectedTicket->logs->count() - 1 ? '24px' : '0' }};">
                                                {{-- Timeline Connector --}}
                                                <div style="display: flex; flex-direction: column; align-items: center; position: relative;">
                                                    {{-- Timeline Dot --}}
                                                    <div style="width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; background:
                                                        @if($log->new_status === 'Completed') #F97316
                                                        @elseif($log->new_status === 'Reopen') white
                                                        @elseif($log->new_status === 'Closed' || $log->new_status === 'Closed System Configuration') #F97316
                                                        @elseif($log->new_status === 'In Progress') white
                                                        @elseif($log->new_status === 'New') white
                                                        @else white
                                                        @endif;
                                                        border: 2px solid
                                                        @if($log->new_status === 'Completed') #F97316
                                                        @elseif($log->new_status === 'Reopen') #9CA3AF
                                                        @elseif($log->new_status === 'Closed' || $log->new_status === 'Closed System Configuration') #F97316
                                                        @elseif($log->new_status === 'In Progress') #9CA3AF
                                                        @elseif($log->new_status === 'New') #9CA3AF
                                                        @else #9CA3AF
                                                        @endif;"></div>

                                                    {{-- Timeline Line (only if not last item) --}}
                                                    @if($index < $selectedTicket->logs->count() - 1)
                                                        <div style="width: 2px; background: #E5E7EB; flex: 1; margin-top: 4px; min-height: 40px;"></div>
                                                    @endif
                                                </div>

                                                {{-- Status Content --}}
                                                <div style="flex: 1; padding-bottom: 8px;">
                                                    {{-- Status Header --}}
                                                    <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 8px;">
                                                        {{-- Status Badge with Tooltip --}}
                                                        <div class="status-badge-wrapper" style="position: relative; display: inline-block;">
                                                            <span style="padding: 4px 12px; background:
                                                                @if($log->new_status === 'Completed') #FEF3C7
                                                                @elseif($log->new_status === 'Reopen') #F3F4F6
                                                                @elseif($log->new_status === 'Closed System Configuration') #FEF3C7
                                                                @elseif($log->new_status === 'In Progress') #FEF3C7
                                                                @elseif($log->new_status === 'New') #F3F4F6
                                                                @else #F3F4F6
                                                                @endif;
                                                                color:
                                                                @if($log->new_status === 'Completed') #D97706
                                                                @elseif($log->new_status === 'Reopen') #6B7280
                                                                @elseif($log->new_status === 'Closed System Configuration') #D97706
                                                                @elseif($log->new_status === 'In Progress') #D97706
                                                                @elseif($log->new_status === 'New') #6B7280
                                                                @else #6B7280
                                                                @endif;
                                                                border-radius: 6px; font-size: 13px; font-weight: 500; border: 1px solid
                                                                @if($log->new_status === 'Completed') #FDE047
                                                                @elseif($log->new_status === 'Reopen') #E5E7EB
                                                                @elseif($log->new_status === 'Closed System Configuration') #FDE047
                                                                @elseif($log->new_status === 'In Progress') #FDE047
                                                                @elseif($log->new_status === 'New') #E5E7EB
                                                                @else #E5E7EB
                                                                @endif;">
                                                                {{ $log->new_status }}
                                                            </span>
                                                            {{-- Tooltip --}}
                                                            <div class="status-tooltip" style="position: absolute; bottom: 100%; left: 0; margin-bottom: 8px; padding: 6px 10px; background: #1F2937; color: white; font-size: 11px; border-radius: 6px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.2s; z-index: 10;">
                                                                {{ $log->created_at->format('M d') }} • {{ $log->created_at->format('Y, g:i A') }}
                                                            </div>
                                                        </div>

                                                        {{-- ✅ Elapsed Time (ONLY for the latest/first log entry) --}}
                                                        @if($index === 0)
                                                            @php
                                                                $createdAt = $log->created_at;
                                                                $now = now();
                                                                $diff = $createdAt->diff($now);

                                                                $elapsed = '';
                                                                if ($diff->d > 0) {
                                                                    $elapsed .= $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ';
                                                                }
                                                                if ($diff->h > 0) {
                                                                    $elapsed .= $diff->h . ' hr' . ($diff->h > 1 ? 's' : '') . ' ';
                                                                }
                                                                if ($diff->i > 0) {
                                                                    $elapsed .= $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ';
                                                                }
                                                                $elapsed .= $diff->s . ' sec' . ($diff->s > 1 ? 's' : '');
                                                            @endphp
                                                            <div style="display: flex; align-items: center; gap: 4px; color: #3B82F6; font-size: 12px;">
                                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                                    <circle cx="12" cy="12" r="10"></circle>
                                                                    <polyline points="12 6 12 12 16 14"></polyline>
                                                                </svg>
                                                                <span>Elapsed: {{ trim($elapsed) }}</span>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    {{-- Status Meta (User info) --}}
                                                    <div style="font-size: 13px; color: #6B7280;">
                                                        Updated by {{ $log->user_name ?? 'Unknown User' }} - {{ $log->user_role ?? 'User' }}
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div style="text-align: center; padding: 60px 20px;">
                                        <div style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;">📋</div>
                                        <p style="color: #9CA3AF; font-size: 14px;">No status changes recorded</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Other Information -->
                    <div style="padding: 24px; background: #F9FAFB;">
                        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                            <h3 style="font-size: 14px; font-weight: 600; color: #6B7280; margin: 0;">Other Information</h3>
                            <button type="button" title="Hide information panel" style="background: transparent; border: none; color: #9CA3AF; cursor: pointer; padding: 4px;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="9 18 15 12 9 6"></polyline>
                                </svg>
                            </button>
                        </div>

                        <!-- Priority -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Priority</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->priority->name ?? $selectedTicket->priority ?? '-' }}</div>
                        </div>

                        <!-- Status -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Status</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->status ?? '-' }}</div>
                        </div>

                        <!-- Due Date -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Due Date</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">-</div>
                        </div>

                        <!-- ETA Release Date & Live Release Date -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                            <div>
                                <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">ETA Release Date</div>
                                <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->eta_release ? $selectedTicket->eta_release->format('M d, Y') : '-' }}</div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Live Release Date</div>
                                <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->live_release ? $selectedTicket->live_release->format('M d, Y') : '-' }}</div>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div style="border-top: 1px solid #E5E7EB; margin: 20px 0;"></div>

                        <!-- Product & Module -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                            <div>
                                <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Product</div>
                                <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->product->name ?? $selectedTicket->product ?? '-' }}</div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Module</div>
                                <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->module->name ?? $selectedTicket->module ?? '-' }}</div>
                            </div>
                        </div>

                        <!-- Company Name & Requester -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                            <div>
                                <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Company Name</div>
                                <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->company_name ?? '-' }}</div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Requester</div>
                                <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->requestor->name ?? $selectedTicket->created_by ?? '-' }}</div>
                            </div>
                        </div>

                        <!-- Zoho Ticket Number & Created Date -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                            <div>
                                <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Zoho Ticket Number</div>
                                <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->zoho_ticket_number ?? '-' }}</div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Created Date</div>
                                <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->created_at ? $selectedTicket->created_at->format('M d, Y') : '-' }}</div>
                            </div>
                        </div>

                        <!-- Divider -->
                        <div style="border-top: 1px solid #E5E7EB; margin: 20px 0;"></div>

                        <!-- Device Type, Browser Type & Windows Version -->
                        @if($selectedTicket->device_type === 'Browser')
                            <!-- ✅ BROWSER SPECIFIC FIELDS -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                <div>
                                    <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Device Type</div>
                                    <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->device_type ?? '-' }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Browser Type</div>
                                    <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->browser_type ?? '-' }}</div>
                                </div>
                            </div>

                            <div style="margin-bottom: 16px;">
                                <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Windows Version</div>
                                <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->windows_version ?? '-' }}</div>
                            </div>
                        @elseif($selectedTicket->device_type === 'Mobile')
                            <!-- ✅ MOBILE SPECIFIC FIELDS -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                <div>
                                    <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Device Type</div>
                                    <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->device_type ?? '-' }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Mobile Type</div>
                                    <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->mobile_type ?? '-' }}</div>
                                </div>
                            </div>

                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                                <div>
                                    <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Device ID</div>
                                    <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->device_id ?? '-' }}</div>
                                </div>
                                <div>
                                    <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">OS Version</div>
                                    <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->os_version ?? '-' }}</div>
                                </div>
                            </div>

                            <div style="margin-bottom: 16px;">
                                <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">App Version</div>
                                <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->app_version ?? '-' }}</div>
                            </div>

                            @if($selectedTicket->version_screenshot)
                                <div style="margin-bottom: 16px;">
                                    <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Version Screenshot</div>
                                    <a href="{{ asset('storage/' . $selectedTicket->version_screenshot) }}" target="_blank"
                                    style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; text-decoration: none; color: #6366F1; font-size: 13px; font-weight: 500; transition: all 0.2s;"
                                    onmouseover="this.style.background='#F3F4F6'"
                                    onmouseout="this.style.background='white'">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                            <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                            <polyline points="21 15 16 10 5 21"></polyline>
                                        </svg>
                                        View Screenshot
                                    </a>
                                </div>
                            @endif
                        @else
                            <!-- ✅ FALLBACK if device_type is not set -->
                            <div style="margin-bottom: 16px;">
                                <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Device Type</div>
                                <div style="font-weight: 500; color: #111827; font-size: 14px;">-</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif
    <x-filament-actions::modals />
</x-filament-panels::page>
