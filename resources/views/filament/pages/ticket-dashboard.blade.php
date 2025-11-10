{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/ticket-dashboard.blade.php --}}
<x-filament-panels::page>
    @php
        $data = $this->getViewData();
        $softwareBugs = $data['softwareBugs'];
        $backendAssistance = $data['backendAssistance'];
        $enhancement = $data['enhancement'];
        $tickets = $data['tickets'];
        $calendar = $data['calendar'];
    @endphp

    <style>
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
            font-size: 28px;
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
        }

        .filter-pill:hover {
            border-color: #059669;
            color: #059669;
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
            padding: 8px 4px;
            font-size: 13px;
            color: #374151;
        }

        .calendar-day {
            width: 32px;
            height: 32px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            cursor: pointer;
        }

        .calendar-day:hover {
            background: #F3F4F6;
        }

        .calendar-day.today {
            background: #6366F1;
            color: white;
            font-weight: 600;
        }

        .calendar-day.other-month {
            color: #D1D5DB;
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
        }

        .close-badge {
            background: #F3F4F6;
            color: #6B7280;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            cursor: pointer;
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
        <!-- Header with Filters -->
        <div class="dashboard-header">
            <div class="filter-dropdowns">
                <select class="filter-select" wire:model.live="selectedProduct">
                    <option>All Products</option>
                    <option>TimeTec HR - Version 1</option>
                    <option>TimeTec HR - Version 2</option>
                </select>

                <select class="filter-select" wire:model.live="selectedModule">
                    <option>All Modules</option>
                    <option>Attendance</option>
                    <option>Leave</option>
                    <option>Payroll</option>
                    <option>Claims</option>
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
                        <div class="filter-pill">Critical</div>
                        <div class="filter-pill">Paid</div>
                        <div class="filter-pill">Non Critical</div>
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
                                        $days[] = ['day' => $prevMonthDays - $i, 'class' => 'other-month'];
                                    }

                                    for ($day = 1; $day <= $daysInMonth; $day++) {
                                        $isToday = $day == $currentDate->day &&
                                                  $currentMonth == $currentDate->month &&
                                                  $currentYear == $currentDate->year;
                                        $days[] = ['day' => $day, 'class' => $isToday ? 'today' : ''];
                                    }

                                    $totalCells = count($days);
                                    $remainingCells = (7 - ($totalCells % 7)) % 7;

                                    for ($day = 1; $day <= $remainingCells; $day++) {
                                        $days[] = ['day' => $day, 'class' => 'other-month'];
                                    }

                                    $weeks = array_chunk($days, 7);
                                @endphp

                                @foreach($weeks as $week)
                                    <tr>
                                        @foreach($week as $dayData)
                                            <td>
                                                <div class="calendar-day {{ $dayData['class'] }}">
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
                    </div>
                </div>

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
            </div>
        </div>
    </div>
</x-filament-panels::page>
