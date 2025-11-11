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
    </style>

    <div class="dashboard-wrapper">
        <!-- ✅ Header with Title and Filters on same line -->
        <div class="dashboard-header">
            <h1 class="page-title">Ticket Dashboard</h1>

            <div class="filter-dropdowns">
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
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && $selectedStatus === 'RND - New' ? 'active' : '' }}"
                             wire:click="selectCategory('softwareBugs', 'RND - New')">
                            <div class="status-number">{{ $softwareBugs['new'] }}</div>
                            <div class="status-text">New</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && $selectedStatus === 'RND - In Review' ? 'active' : '' }}"
                             wire:click="selectCategory('softwareBugs', 'RND - In Review')">
                            <div class="status-number">{{ $softwareBugs['review'] }}</div>
                            <div class="status-text">Review</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && $selectedStatus === 'RND - In Progress' ? 'active' : '' }}"
                             wire:click="selectCategory('softwareBugs', 'RND - In Progress')">
                            <div class="status-number">{{ $softwareBugs['progress'] }}</div>
                            <div class="status-text">In Progress</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'softwareBugs' && $selectedStatus === 'RND - Closed' ? 'active' : '' }}"
                             wire:click="selectCategory('softwareBugs', 'RND - Closed')">
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
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && $selectedStatus === 'RND - New' ? 'active' : '' }}"
                             wire:click="selectCategory('backendAssistance', 'RND - New')">
                            <div class="status-number">{{ $backendAssistance['new'] }}</div>
                            <div class="status-text">New</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && $selectedStatus === 'RND - In Review' ? 'active' : '' }}"
                             wire:click="selectCategory('backendAssistance', 'RND - In Review')">
                            <div class="status-number">{{ $backendAssistance['review'] }}</div>
                            <div class="status-text">Review</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && $selectedStatus === 'RND - In Progress' ? 'active' : '' }}"
                             wire:click="selectCategory('backendAssistance', 'RND - In Progress')">
                            <div class="status-number">{{ $backendAssistance['progress'] }}</div>
                            <div class="status-text">In Progress</div>
                        </div>
                        <div class="status-box {{ $selectedCategory === 'backendAssistance' && $selectedStatus === 'RND - Closed' ? 'active' : '' }}"
                             wire:click="selectCategory('backendAssistance', 'RND - Closed')">
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
                                    <th style="padding: 12px; text-align: left; font-size: 12px; color: #6B7280; font-weight: 600;">TITLE</th>
                                    <th style="padding: 12px; text-align: left; font-size: 12px; color: #6B7280; font-weight: 600;">COMPANY</th>
                                    <th style="padding: 12px; text-align: left; font-size: 12px; color: #6B7280; font-weight: 600;">STATUS</th>
                                    <th style="padding: 12px; text-align: left; font-size: 12px; color: #6B7280; font-weight: 600;">CREATED</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tickets as $ticket)
                                    <tr style="border-bottom: 1px solid #F3F4F6; cursor: pointer; transition: background 0.2s;"
                                        wire:click="viewTicket({{ $ticket->id }})"
                                        onmouseover="this.style.background='#F9FAFB'"
                                        onmouseout="this.style.background='white'">
                                        <td style="padding: 12px; font-size: 13px; font-weight: 600;">#{{ $ticket->id }}</td>
                                        <td style="padding: 12px; font-size: 13px;">{{ \Str::limit($ticket->title, 50) }}</td>
                                        <td style="padding: 12px; font-size: 13px;">{{ strtoupper($ticket->company_name) }}</td>
                                        <td style="padding: 12px;">
                                            <span style="padding: 4px 8px; border-radius: 4px; font-size: 11px; background: #F3F4F6; color: #6B7280;">
                                                {{ $ticket->status }}
                                            </span>
                                        </td>
                                        <td style="padding: 12px; font-size: 13px; color: #6B7280;">
                                            {{ $ticket->created_at->format('d M Y') }}
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
            <div style="background: white; border-radius: 16px; width: 90%; max-width: 1000px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;"
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
                        <h1 style="font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 16px 0;">
                            {{ $selectedTicket->title }}
                        </h1>

                        <!-- Tabs -->
                        <div x-data="{ activeTab: 'description' }" style="margin-bottom: 24px;">
                            <div style="display: flex; gap: 24px; border-bottom: 2px solid #F3F4F6;">
                                <button @click="activeTab = 'description'"
                                        :style="activeTab === 'description' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                        style="padding: 12px 0; font-weight: 600; font-size: 14px; background: transparent; border: none; cursor: pointer; margin-bottom: -2px;">
                                    Description
                                </button>
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

                            <!-- Description Tab -->
                            <div x-show="activeTab === 'description'" style="padding: 24px 0;">
                                <div style="background: #F9FAFB; padding: 16px; border-radius: 8px;">
                                    <p style="color: #374151; line-height: 1.6; margin: 0;">
                                        {{ $selectedTicket->description ?? 'No description provided.' }}
                                    </p>
                                </div>
                            </div>

                            <!-- Comments Tab -->
                            <div x-show="activeTab === 'comments'" style="padding: 24px 0;">
                                <!-- Add Comment -->
                                <div style="margin-bottom: 24px;">
                                    <textarea wire:model="newComment"
                                            placeholder="Add a comment..."
                                            style="width: 100%; padding: 12px; border: 1px solid #E5E7EB; border-radius: 8px; font-size: 14px; resize: vertical; min-height: 80px;"></textarea>
                                    <button wire:click="addComment"
                                            style="margin-top: 8px; padding: 8px 16px; background: #6366F1; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">
                                        Post Comment
                                    </button>
                                </div>

                                <!-- Previous Comments -->
                                <div style="border-left: 3px solid #6366F1; padding-left: 16px;">
                                    <h3 style="font-size: 14px; font-weight: 600; color: #6B7280; margin-bottom: 16px;">Previous Comments</h3>

                                    @forelse($selectedTicket->comments as $comment)
                                        <div style="margin-bottom: 16px; padding: 16px; background: #F9FAFB; border-radius: 8px;">
                                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
                                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #6366F1; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px;">
                                                    {{ strtoupper(substr($comment->user->name ?? 'U', 0, 1)) }}
                                                </div>
                                                <div>
                                                    <div style="font-weight: 600; font-size: 14px; color: #111827;">
                                                        {{ $comment->user->name ?? 'Unknown User' }}
                                                        @if($comment->user->role_id)
                                                            <span style="background: #DBEAFE; color: #1E40AF; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px;">
                                                                FE (IMPLEMENTOR)
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div style="font-size: 12px; color: #9CA3AF;">
                                                        {{ $comment->created_at->diffForHumans() }}
                                                    </div>
                                                </div>
                                            </div>
                                            <p style="color: #374151; margin: 0; font-size: 14px;">{{ $comment->comment }}</p>
                                        </div>
                                    @empty
                                        <p style="color: #9CA3AF; font-size: 14px;">No comments yet.</p>
                                    @endforelse
                                </div>
                            </div>

                            <!-- Attachments Tab -->
                            <div x-show="activeTab === 'attachments'" style="padding: 24px 0;">
                                <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px;">Current Attachments</h3>

                                @forelse($selectedTicket->attachments as $attachment)
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
                                            <a href="{{ url($attachment->file_path) }}" target="_blank" style="padding: 6px 12px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; cursor: pointer; text-decoration: none; color: #374151;">
                                                👁️ View
                                            </a>
                                            <a href="{{ url($attachment->file_path) }}" download style="padding: 6px 12px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; cursor: pointer; text-decoration: none; color: #374151;">
                                                ⬇️ Download
                                            </a>
                                        </div>
                                    </div>
                                @empty
                                    <p style="color: #9CA3AF; font-size: 14px;">No attachments.</p>
                                @endforelse

                                <!-- Upload New Attachments -->
                                <div style="margin-top: 24px; padding: 24px; border: 2px dashed #E5E7EB; border-radius: 8px; text-align: center;">
                                    <div style="color: #9CA3AF; font-size: 14px; margin-bottom: 8px;">📤</div>
                                    <div style="color: #6B7280; font-size: 14px; margin-bottom: 4px;">Click to upload or drag and drop</div>
                                    <div style="color: #9CA3AF; font-size: 12px;">(Ctrl+V to paste images)</div>
                                </div>
                            </div>

                            <!-- Status Log Tab -->
                            <div x-show="activeTab === 'status'" style="padding: 24px 0;">
                                <div style="text-align: center; padding: 60px 20px;">
                                    <div style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;">⏱️</div>
                                    <p style="color: #9CA3AF; font-size: 14px;">No status log available for this ticket</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side - Other Information -->
                    <div style="padding: 24px; background: #F9FAFB;">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 20px;">
                            <h3 style="font-size: 14px; font-weight: 600; color: #6B7280; margin: 0;">OTHER INFORMATION</h3>
                            <span style="color: #9CA3AF;">›</span>
                        </div>

                        <!-- Priority -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Priority</div>
                            <div style="font-weight: 600; color: #111827;">{{ $selectedTicket->priority }}</div>
                        </div>

                        <!-- Status -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Status</div>
                            <div style="font-weight: 600; color: #111827;">{{ $selectedTicket->status }}</div>
                        </div>

                        <!-- Assignee -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Assignee</div>
                            <select style="width: 100%; padding: 8px; border: 1px solid #E5E7EB; border-radius: 6px; font-size: 14px;">
                                <option>Select Assignee</option>
                            </select>
                        </div>

                        <!-- Product -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Product</div>
                            <div style="font-weight: 600; color: #111827;">{{ $selectedTicket->product }}</div>
                        </div>

                        <!-- Module -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Module</div>
                            <div style="font-weight: 600; color: #111827;">{{ $selectedTicket->module }}</div>
                        </div>

                        <!-- Company Name -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Company Name</div>
                            <div style="font-weight: 600; color: #111827;">{{ $selectedTicket->company_name }}</div>
                        </div>

                        <!-- Requester -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Requester</div>
                            <div style="font-weight: 600; color: #111827;">{{ $selectedTicket->created_by ?? 'Unknown' }}</div>
                        </div>

                        <!-- Created Date -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Created Date</div>
                            <div style="font-weight: 600; color: #111827;">{{ $selectedTicket->created_at->format('M d, Y') }}</div>
                        </div>

                        <!-- Device Type -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Device Type</div>
                            <div style="font-weight: 600; color: #111827;">{{ $selectedTicket->device_type ?? '-' }}</div>
                        </div>

                        <!-- Browser Type -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Browser Type</div>
                            <div style="font-weight: 600; color: #111827;">{{ $selectedTicket->browser_type ?? '-' }}</div>
                        </div>

                        <!-- Windows Version -->
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.5px;">Windows Version</div>
                            <div style="font-weight: 600; color: #111827;">{{ $selectedTicket->windows_os_version ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
