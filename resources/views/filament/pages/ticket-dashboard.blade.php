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
        $frontEndNames = $data['frontEndNames'];
        $statuses = $data['statuses'];
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

        /* Fast-response module tooltip */
        .module-cell {
            position: relative;
            overflow: visible;
        }

        .module-cell:hover {
            background: #F9FAFB;
            font-weight: 600;
        }

        .module-tooltip {
            position: fixed;
            padding: 10px 14px;
            background: #1F2937;
            color: white;
            font-size: 13px;
            font-weight: 500;
            border-radius: 6px;
            z-index: 9999;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            max-width: 600px;
            min-width: 300px;
            white-space: normal;
            line-height: 1.5;
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
            transition: opacity 0.15s ease-in-out, visibility 0.15s ease-in-out;
        }

        .module-cell:hover .module-tooltip {
            opacity: 1;
            visibility: visible;
        }
    </style>

    <script>
        let tooltipEl = null;

        function showTooltip(event, title) {
            if (!title) return;

            // Create tooltip if it doesn't exist
            if (!tooltipEl) {
                tooltipEl = document.createElement('div');
                tooltipEl.className = 'module-tooltip';
                document.body.appendChild(tooltipEl);
            }

            // Set content and position
            tooltipEl.textContent = title;
            tooltipEl.style.opacity = '1';
            tooltipEl.style.visibility = 'visible';

            // Position below the cell
            const rect = event.currentTarget.getBoundingClientRect();
            tooltipEl.style.left = rect.left + 'px';
            tooltipEl.style.top = (rect.bottom + 8) + 'px';
        }

        function hideTooltip(event) {
            if (tooltipEl) {
                tooltipEl.style.opacity = '0';
                tooltipEl.style.visibility = 'hidden';
            }
        }
    </script>

    <div class="dashboard-wrapper">
        <!-- ✅ Header with Title -->
        <div class="dashboard-header">
            <h1 class="page-title">Ticket Dashboard</h1>
        </div>

        <style>
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        </style>

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
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && $selectedStatus === 'In Progress' ? 'active' : '' }}"
                            wire:click="selectCategory('softwareBugs', 'In Progress')">
                            <div class="status-number">{{ $softwareBugs['progress'] }}</div>
                            <div class="status-text">In Progress</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && ($selectedStatus === 'Completed' || $selectedStatus === 'Tickets: Live') ? 'active' : '' }}"
                            wire:click="selectCategory('softwareBugs', 'Completed')">
                            <div class="status-number">{{ $softwareBugs['completed'] }}</div>
                            <div class="status-text">Completed</div>
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
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && $selectedStatus === 'In Progress' ? 'active' : '' }}"
                            wire:click="selectCategory('backendAssistance', 'In Progress')">
                            <div class="status-number">{{ $backendAssistance['progress'] }}</div>
                            <div class="status-text">In Progress</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && ($selectedStatus === 'Completed' || $selectedStatus === 'Tickets: Live') ? 'active' : '' }}"
                            wire:click="selectCategory('backendAssistance', 'Completed')">
                            <div class="status-number">{{ $backendAssistance['completed'] }}</div>
                            <div class="status-text">Completed</div>
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
                        <div class="status-box {{ $selectedCategory === 'enhancement' && $selectedEnhancementStatus === 'New' ? 'active' : '' }}"
                             wire:click="$set('selectedEnhancementStatus', '{{ $selectedEnhancementStatus === 'New' ? null : 'New' }}'); $set('selectedCategory', '{{ $selectedEnhancementStatus === 'New' ? null : 'enhancement' }}');">
                            <div class="status-number">{{ $enhancement['new'] }}</div>
                            <div class="status-text">New</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'enhancement' && $selectedEnhancementStatus === 'Pending Release' ? 'active' : '' }}"
                             wire:click="$set('selectedEnhancementStatus', '{{ $selectedEnhancementStatus === 'Pending Release' ? null : 'Pending Release' }}'); $set('selectedCategory', '{{ $selectedEnhancementStatus === 'Pending Release' ? null : 'enhancement' }}');">
                            <div class="status-number">{{ $enhancement['pending_release'] }}</div>
                            <div class="status-text">Pending Release</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'enhancement' && $selectedEnhancementStatus === 'System Go Live' ? 'active' : '' }}"
                             wire:click="$set('selectedEnhancementStatus', '{{ $selectedEnhancementStatus === 'System Go Live' ? null : 'System Go Live' }}'); $set('selectedCategory', '{{ $selectedEnhancementStatus === 'System Go Live' ? null : 'enhancement' }}');">
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
                        {{-- Filter Icon Button --}}
                        <button type="button" wire:click="openFilterModal"
                                style="padding: 8px 12px; border: 1px solid #E5E7EB; border-radius: 6px; background: white; cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 13px; color: #6B7280; transition: all 0.2s;"
                                onmouseover="this.style.background='#F9FAFB'; this.style.borderColor='#D1D5DB'"
                                onmouseout="this.style.background='white'; this.style.borderColor='#E5E7EB'">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 01-.659 1.591l-5.432 5.432a2.25 2.25 0 00-.659 1.591v2.927a2.25 2.25 0 01-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 00-.659-1.591L3.659 7.409A2.25 2.25 0 013 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0112 3z" />
                            </svg>
                            Filters
                            @if($selectedFrontEnd || $selectedTicketStatus || $etaStartDate || $etaEndDate)
                                <span style="background: #6366F1; color: white; border-radius: 10px; padding: 2px 6px; font-size: 11px; font-weight: 600;">
                                    {{ collect([$selectedFrontEnd, $selectedTicketStatus, $etaStartDate, $etaEndDate])->filter()->count() }}
                                </span>
                            @endif
                        </button>

                        {{-- Show individual status badges when In Progress or Closed is selected --}}
                        @if(($selectedStatus === 'In Progress' || $selectedStatus === 'Closed') && !empty($selectedCombinedStatuses))
                            @foreach($selectedCombinedStatuses as $individualStatus)
                                <span class="close-badge" wire:click="removeIndividualStatus('{{ $individualStatus }}')">
                                    {{ $individualStatus }} ✕
                                </span>
                            @endforeach
                        @elseif($selectedStatus || $selectedEnhancementStatus)
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
                        @if($selectedPriority)
                            <span class="close-badge" wire:click="$set('selectedPriority', null)">
                                Priority: {{ $selectedPriority }} ✕
                            </span>
                        @endif
                        @if($selectedProduct && $selectedProduct !== 'All Products')
                            <span class="close-badge" wire:click="$set('selectedProduct', 'All Products')">
                                Product: {{ $selectedProduct }} ✕
                            </span>
                        @endif
                        @if($selectedModule && $selectedModule !== 'All Modules')
                            <span class="close-badge" wire:click="$set('selectedModule', 'All Modules')">
                                Module: {{ $selectedModule }} ✕
                            </span>
                        @endif
                        @if($selectedFrontEnd)
                            <span class="close-badge" wire:click="$set('selectedFrontEnd', null)">
                                Front End: {{ $selectedFrontEnd }} ✕
                            </span>
                        @endif
                        @if($selectedTicketStatus)
                            <span class="close-badge" wire:click="$set('selectedTicketStatus', null)">
                                Status: {{ $selectedTicketStatus }} ✕
                            </span>
                        @endif
                        @if($etaStartDate)
                            <span class="close-badge" wire:click="$set('etaStartDate', null)">
                                From: {{ \Carbon\Carbon::parse($etaStartDate)->format('d M Y') }} ✕
                            </span>
                        @endif
                        @if($etaEndDate)
                            <span class="close-badge" wire:click="$set('etaEndDate', null)">
                                To: {{ \Carbon\Carbon::parse($etaEndDate)->format('d M Y') }} ✕
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
                                    <th style="padding: 12px; text-align: left; font-size: 12px; color: #6B7280; font-weight: 600;">MODULE</th>
                                    <th style="padding: 12px; text-align: left; font-size: 12px; color: #6B7280; font-weight: 600; cursor: pointer;" wire:click="toggleEtaSort">
                                        ETA
                                        @if($etaSortDirection === 'asc')
                                            <span style="margin-left: 4px;">↑</span>
                                        @elseif($etaSortDirection === 'desc')
                                            <span style="margin-left: 4px;">↓</span>
                                        @endif
                                    </th>
                                    <th style="padding: 12px; text-align: left; font-size: 12px; color: #6B7280; font-weight: 600;">STATUS</th>
                                    <th style="padding: 12px; text-align: left; font-size: 12px; color: #6B7280; font-weight: 600;">FRONT END</th>
                                    <th style="padding: 12px; text-align: center; font-size: 12px; color: #6B7280; font-weight: 600;">PASS/FAIL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tickets as $ticket)
                                    <tr style="border-bottom: 1px solid #F3F4F6;">
                                        <td style="padding: 12px; font-size: 13px; font-weight: 600; cursor: pointer;" wire:click="viewTicket({{ $ticket->id }})">
                                            {{ $ticket->ticket_id }}
                                        </td>
                                        <td class="module-cell" style="padding: 12px; font-size: 13px; cursor: pointer;" wire:click="viewTicket({{ $ticket->id }})" onmouseenter="showTooltip(event, '{{ addslashes($ticket->title ?? '') }}')" onmouseleave="hideTooltip(event)">
                                            {{ $ticket->module->name ?? '-' }}
                                        </td>
                                        <td style="padding: 12px; font-size: 13px; color: #6B7280; cursor: pointer;" wire:click="viewTicket({{ $ticket->id }})">
                                            {{ $ticket->eta_release ? $ticket->eta_release->addHours(8)->format('d M Y') : '-' }}
                                        </td>
                                        <td style="padding: 12px; cursor: pointer;" wire:click="viewTicket({{ $ticket->id }})">
                                            <span style="padding: 4px 8px; border-radius: 4px; font-size: 11px; background: #F3F4F6; color: #6B7280;">
                                                {{ $ticket->status }}
                                            </span>
                                        </td>
                                        <td style="padding: 12px; font-size: 13px; cursor: pointer; max-width: 120px; word-break: break-word; line-height: 1.4;" wire:click="viewTicket({{ $ticket->id }})">
                                            {{ $ticket->requestor->name ?? $ticket->requestor ?? '-' }}
                                        </td>
                                        <td style="padding: 12px; text-align: center;" onclick="event.stopPropagation();">
                                            @if(in_array($ticket->status, ['Completed', 'Tickets: Live', 'Closed']))
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
                                                                {{ $ticket->passed_at->addHours(8)->format('d M Y H:i') }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                @endif
                                            @else
                                                <span style="font-size: 12px; color: #9CA3AF;">-</span>
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
        @include('filament.pages.partials.ticket-modal')
    @endif

    {{-- Reopen Modal --}}
    @if($showReopenModal && $selectedTicket)
        @include('filament.pages.partials.reopen-modal')
    @endif

    {{-- Filter Modal --}}
    @if($showFilterModal)
        <div style="position: fixed; inset: 0; z-index: 50; display: flex; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.5);"
             wire:click="closeFilterModal">
            <div style="background: white; border-radius: 16px; padding: 0; width: 700px; max-width: 90vw; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;"
                 wire:click.stop>

                {{-- Modal Header --}}
                <div style="padding: 24px 28px; border-bottom: 2px solid #F3F4F6; background: linear-gradient(to bottom, #ffffff, #fafbfc);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <h3 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0; line-height: 1.3;">Filter Tickets</h3>
                        </div>
                        <button wire:click="closeFilterModal"
                                style="background: #F3F4F6; border: none; color: #6B7280; cursor: pointer; padding: 8px; border-radius: 8px; font-size: 20px; line-height: 1; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; transition: all 0.2s;"
                                onmouseover="this.style.background='#E5E7EB'; this.style.color='#374151'"
                                onmouseout="this.style.background='#F3F4F6'; this.style.color='#6B7280'">
                            ×
                        </button>
                    </div>
                </div>

                {{-- Filter Form - Scrollable --}}
                <div style="padding: 28px; overflow-y: auto; flex: 1;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">

                        {{-- Priority Filter --}}
                        <div>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                                Priority
                            </label>
                            <select class="ticket-filter-select" wire:model="selectedPriority"
                                    style="width: 100%; padding: 11px 14px; border: 1.5px solid #D1D5DB; border-radius: 10px; font-size: 14px; background: white; transition: all 0.2s; color: #374151; font-weight: 500;"
                                    onfocus="this.style.borderColor='#6366F1'; this.style.boxShadow='0 0 0 3px rgba(99, 102, 241, 0.1)'"
                                    onblur="this.style.borderColor='#D1D5DB'; this.style.boxShadow='none'">
                                <option value="">All Priorities</option>
                                @foreach($priorities as $priority)
                                    <option value="{{ $priority }}">{{ $priority }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Product Filter --}}
                        <div>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                                Product
                            </label>
                            <select class="ticket-filter-select" wire:model="selectedProduct"
                                    style="width: 100%; padding: 11px 14px; border: 1.5px solid #D1D5DB; border-radius: 10px; font-size: 14px; background: white; transition: all 0.2s; color: #374151; font-weight: 500;"
                                    onfocus="this.style.borderColor='#10B981'; this.style.boxShadow='0 0 0 3px rgba(16, 185, 129, 0.1)'"
                                    onblur="this.style.borderColor='#D1D5DB'; this.style.boxShadow='none'">
                                <option>All Products</option>
                                @foreach($products as $product)
                                    <option value="{{ $product }}">{{ $product }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Module Filter --}}
                        <div>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                                Module
                            </label>
                            <select class="ticket-filter-select" wire:model="selectedModule"
                                    style="width: 100%; padding: 11px 14px; border: 1.5px solid #D1D5DB; border-radius: 10px; font-size: 14px; background: white; transition: all 0.2s; color: #374151; font-weight: 500;"
                                    onfocus="this.style.borderColor='#F59E0B'; this.style.boxShadow='0 0 0 3px rgba(245, 158, 11, 0.1)'"
                                    onblur="this.style.borderColor='#D1D5DB'; this.style.boxShadow='none'">
                                <option>All Modules</option>
                                @foreach($modules as $module)
                                    <option value="{{ $module }}">{{ $module }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Front End Filter --}}
                        <div>
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                                Front End
                            </label>
                            <select class="ticket-filter-select" wire:model="selectedFrontEnd"
                                    style="width: 100%; padding: 11px 14px; border: 1.5px solid #D1D5DB; border-radius: 10px; font-size: 14px; background: white; transition: all 0.2s; color: #374151; font-weight: 500;"
                                    onfocus="this.style.borderColor='#EC4899'; this.style.boxShadow='0 0 0 3px rgba(236, 72, 153, 0.1)'"
                                    onblur="this.style.borderColor='#D1D5DB'; this.style.boxShadow='none'">
                                <option value="">All Front End</option>
                                @foreach($frontEndNames as $frontEnd)
                                    <option value="{{ $frontEnd }}">{{ $frontEnd }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Status Filter - Full Width --}}
                        <div style="grid-column: 1 / -1;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 8px;">
                                Ticket Status
                            </label>
                            <select class="ticket-filter-select" wire:model="selectedTicketStatus"
                                    style="width: 100%; padding: 11px 14px; border: 1.5px solid #D1D5DB; border-radius: 10px; font-size: 14px; background: white; transition: all 0.2s; color: #374151; font-weight: 500;"
                                    onfocus="this.style.borderColor='#8B5CF6'; this.style.boxShadow='0 0 0 3px rgba(139, 92, 246, 0.1)'"
                                    onblur="this.style.borderColor='#D1D5DB'; this.style.boxShadow='none'">
                                <option value="">All Status</option>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- ETA Date Range - Full Width with Better Design --}}
                        <div style="grid-column: 1 / -1;">
                            <label style="display: flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: #374151; margin-bottom: 12px;">
                                ETA Date Range
                            </label>
                            <div style="display: grid; grid-template-columns: 1fr auto 1fr; gap: 12px; align-items: center;">
                                <div style="position: relative;">
                                    <label style="display: block; font-size: 11px; color: #6B7280; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">Start Date</label>
                                    <input type="date" wire:model="etaStartDate"
                                           style="width: 100%; padding: 11px 14px; border: 1.5px solid #D1D5DB; border-radius: 10px; font-size: 14px; background: white; transition: all 0.2s; color: #374151; font-weight: 500;"
                                           onfocus="this.style.borderColor='#EF4444'; this.style.boxShadow='0 0 0 3px rgba(239, 68, 68, 0.1)'"
                                           onblur="this.style.borderColor='#D1D5DB'; this.style.boxShadow='none'">
                                </div>
                                <div style="padding-top: 20px; color: #9CA3AF; font-weight: 600;">→</div>
                                <div style="position: relative;">
                                    <label style="display: block; font-size: 11px; color: #6B7280; margin-bottom: 6px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">End Date</label>
                                    <input type="date" wire:model="etaEndDate"
                                           style="width: 100%; padding: 11px 14px; border: 1.5px solid #D1D5DB; border-radius: 10px; font-size: 14px; background: white; transition: all 0.2s; color: #374151; font-weight: 500;"
                                           onfocus="this.style.borderColor='#EF4444'; this.style.boxShadow='0 0 0 3px rgba(239, 68, 68, 0.1)'"
                                           onblur="this.style.borderColor='#D1D5DB'; this.style.boxShadow='none'">
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Modal Footer --}}
                <div style="padding: 20px 28px; border-top: 2px solid #F3F4F6; background: #FAFBFC; display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" wire:click="clearAllFilters"
                            style="padding: 10px 20px; background: white; border: 1.5px solid #E5E7EB; border-radius: 10px; font-size: 14px; color: #6B7280; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;"
                            onmouseover="this.style.background='#FEF2F2'; this.style.borderColor='#FCA5A5'; this.style.color='#DC2626'"
                            onmouseout="this.style.background='white'; this.style.borderColor='#E5E7EB'; this.style.color='#6B7280'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        Clear All Filters
                    </button>
                    <button type="button" wire:click="closeFilterModal"
                            style="padding: 10px 28px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; border-radius: 10px; font-size: 14px; color: white; font-weight: 600; cursor: pointer; transition: all 0.2s; box-shadow: 0 4px 6px -1px rgba(102, 126, 234, 0.3); display: flex; align-items: center; gap: 8px;"
                            onmouseover="this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 12px -2px rgba(102, 126, 234, 0.4)'"
                            onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(102, 126, 234, 0.3)'">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                        </svg>
                        Apply Filters
                    </button>
                </div>
            </div>
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
