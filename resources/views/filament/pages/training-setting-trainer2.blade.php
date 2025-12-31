<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/training-setting-trainer2.blade.php -->
<x-filament-panels::page>
    <div class="training-setting-container">
        {{-- Header Controls --}}
        <div class="header-controls">
            <div class="trainer-info">
                <h2 class="trainer-title">🎓 Trainer 2 Schedule</h2>
                <span class="trainer-subtitle">Manage training sessions for Trainer 2</span>
            </div>
            <div class="year-selection">
                <label for="year" class="year-label">Select Year:</label>
                <select wire:model.live="selectedYear" class="year-dropdown">
                    @for($year = 2025; $year <= 2027; $year++)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endfor
                </select>
            </div>

            <div class="action-buttons">
                <button wire:click="generateSchedule" class="btn btn-generate">
                    <span class="btn-icon">📅</span>
                    Generate Schedule
                </button>
            </div>
        </div>

        {{-- Quarterly Calendar View --}}
        @if($showCalendar)
            <div class="calendar-container">
                <h2 class="calendar-title">{{ $selectedYear }} Training Schedule - Trainer 2 - Quarterly View</h2>

                <div class="quarters-grid">
                    @foreach($this->quarterlyCalendar as $quarter => $quarterData)
                        <div class="quarter-section">
                            <div class="quarter-header" wire:click="toggleQuarter('{{ $quarter }}')">
                                <div class="quarter-title-section">
                                    <h3 class="quarter-title">{{ $quarterData['name'] }}</h3>
                                    <span class="quarter-count">
                                        ({{ collect($quarterData['months'])->sum(function($month) { return count($month['sessions']); }) }} weeks)
                                    </span>
                                </div>
                                <div class="quarter-toggle">
                                    <i class="toggle-icon {{ ($collapsedQuarters[$quarter] ?? false) ? 'collapsed' : 'expanded' }}">▼</i>
                                </div>
                            </div>

                            @if(!($collapsedQuarters[$quarter] ?? false))
                            <div class="quarter-calendar">
                                <div class="months-grid">
                                    @foreach($quarterData['months'] as $monthKey => $monthData)
                                        <div class="month-section">
                                            <div class="month-header">
                                                <h4 class="month-title">{{ $monthData['name'] }}</h4>
                                                <span class="month-count">({{ count($monthData['sessions']) }} weeks)</span>
                                            </div>

                                            <div class="month-sessions">
                                                @if(count($monthData['sessions']) > 0)
                                                    @foreach($monthData['sessions'] as $week)
                                            <div class="session-card status-{{ $week['status'] }}">
                                                <div class="session-header">
                                                    <div class="week-info">
                                                        <span class="week-number">Week {{ $week['week_number'] }}</span>
                                                        <span class="week-dates">
                                                            @if($week['session'])
                                                                {{ Carbon\Carbon::parse($week['session']->day1_date)->format('M j') }} -
                                                                {{ Carbon\Carbon::parse($week['session']->day3_date)->format('M j') }}
                                                            @else
                                                                {{ Carbon\Carbon::parse($week['dates']['tuesday'])->format('M j') }} -
                                                                {{ Carbon\Carbon::parse($week['dates']['thursday'])->format('M j') }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                    <div class="session-actions">
                                                        @if($week['status'] === 'missing')
                                                            <button wire:click="showDateSelectionForNewSession({{ $week['week_number'] }}, {{ json_encode($week['dates']) }})"
                                                                    class="action-btn btn-create">
                                                                ➕ Create
                                                            </button>
                                                        @elseif($week['status'] === 'needs_meeting' && $week['can_create_meeting'])
                                                            <button wire:click="showCategorySelection({{ $week['session']->id }})"
                                                                    class="action-btn btn-meeting">
                                                                📞 Meetings
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>

                                                @if($week['session'])
                                                    <div class="session-content">
                                                        <div class="session-title">
                                                            <span class="session-name">{{ $week['session']->session_number }} - Trainer 2</span>
                                                            <span class="session-badge status-{{ strtolower($week['session']->status) }}">{{ $week['session']->status }}</span>
                                                        </div>

                                                        <div class="training-schedule">
                                                            @php
                                                                $sessionDates = [
                                                                    1 => ['date' => $week['session']->day1_date, 'label' => 'Day 1'],
                                                                    2 => ['date' => $week['session']->day2_date, 'label' => 'Day 2'],
                                                                    3 => ['date' => $week['session']->day3_date, 'label' => 'Day 3']
                                                                ];
                                                            @endphp
                                                            @foreach($sessionDates as $dayNum => $dayData)
                                                                <div class="day-schedule">
                                                                    <div class="day-info">
                                                                        <span class="day-label">{{ $dayData['label'] }}</span>
                                                                        <span class="day-date">{{ Carbon\Carbon::parse($dayData['date'])->format('M j') }} ({{ Carbon\Carbon::parse($dayData['date'])->format('l') }})</span>
                                                                    </div>
                                                                    @if($week['session']->{"day{$dayNum}_meeting_link"})
                                                                        <a href="{{ $week['session']->{"day{$dayNum}_meeting_link"} }}"
                                                                           target="_blank" class="meeting-btn">
                                                                            🎯 Join
                                                                        </a>
                                                                    @else
                                                                        <span class="no-meeting-btn">⏳ Pending</span>
                                                                    @endif
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                @else
                                    <div class="empty-month">
                                        <div class="empty-icon">📅</div>
                                        <p>No sessions</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    @endforeach
                </div>
            </div>
        @endif

        {{-- Date Selection Modal --}}
        @if($showDateModal)
            <div class="modal-overlay">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>{{ $isCreatingNewSession ? 'Create New Training Session - Trainer 2' : 'Select Training Dates - Trainer 2' }}</h3>
                        <button wire:click="closeDateModal" class="modal-close">✕</button>
                    </div>

                    <div class="modal-body">
                        <p class="modal-instruction">
                            @if($isCreatingNewSession)
                                Select exactly 3 dates from Monday to Friday for this new training session:
                            @else
                                Select 1-3 dates from Monday to Friday for this training session:
                            @endif
                            <br><small class="holiday-notice">🏖️ Public holidays are disabled and cannot be selected.</small>
                        </p>

                        <div class="date-selection-grid">
                            @foreach($weekDates as $dateInfo)
                                <label class="date-option {{ in_array($dateInfo['date'], $selectedDates) ? 'selected' : '' }} {{ $dateInfo['is_holiday'] ? 'holiday-disabled' : '' }}">
                                    <input type="checkbox"
                                           wire:click="toggleDate('{{ $dateInfo['date'] }}')"
                                           class="date-input"
                                           {{ in_array($dateInfo['date'], $selectedDates) ? 'checked' : '' }}
                                           {{ $dateInfo['is_holiday'] ? 'disabled' : '' }}>
                                    <div class="date-content">
                                        <span class="day-name">{{ $dateInfo['day'] }}</span>
                                        <span class="date-formatted">{{ $dateInfo['formatted'] }}</span>
                                        @if($dateInfo['is_holiday'])
                                            <span class="holiday-label">🏖️ {{ $dateInfo['holiday_name'] }}</span>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>

                        @if(count($selectedDates) > 0)
                            <div class="selected-summary">
                                <strong>Selected dates:</strong> {{ count($selectedDates) }}/{{ $isCreatingNewSession ? '3 (required)' : '3' }}
                                @if($isCreatingNewSession && count($selectedDates) !== 3)
                                    <div class="selection-note">Please select exactly 3 dates for new training session.</div>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button wire:click="closeDateModal" class="btn btn-secondary">Cancel</button>
                        <button wire:click="createMeetingsWithSelectedDates" class="btn btn-primary">
                            {{ $isCreatingNewSession ? 'Create Session' : 'Continue' }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- Category and Module Selection Modal --}}
        @if($showCategoryModal)
            <div class="modal-overlay">
                <div class="modal-container">
                    <div class="modal-header">
                        <h3>Create Microsoft Teams Meetings - Trainer 2</h3>
                        <button wire:click="closeCategoryModal" class="modal-close">✕</button>
                    </div>

                    <div class="modal-body">
                        <div class="form-group">
                            <label class="form-label">Choose Training Category</label>
                            <div class="radio-group">
                                @foreach($trainingCategories as $key => $label)
                                    <label class="radio-option">
                                        <input type="radio" wire:model.live="selectedCategory" value="{{ $key }}" class="radio-input">
                                        <span class="radio-text">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Choose Training Module</label>
                            <div class="radio-group">
                                @foreach($trainingModules as $key => $label)
                                    <label class="radio-option">
                                        <input type="radio" wire:model.live="selectedModule" value="{{ $key }}" class="radio-input">
                                        <span class="radio-text">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button wire:click="closeCategoryModal" class="btn btn-secondary">Cancel</button>
                        <button wire:click="generateTeamsMeetings" class="btn btn-primary" wire:loading.attr="disabled" wire:target="generateTeamsMeetings">
                            <span wire:loading.remove wire:target="generateTeamsMeetings">Generate Meetings</span>
                            <span wire:loading wire:target="generateTeamsMeetings">
                                Generating...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        .training-setting-container {
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Header Controls */
        .header-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px;
            padding: 18px 24px;
            margin-bottom: 20px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.1);
        }

        .trainer-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .trainer-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        .trainer-subtitle {
            font-size: 12px;
            opacity: 0.9;
        }

        .year-selection {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .year-label {
            font-weight: 600;
            font-size: 14px;
        }

        .year-dropdown {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            background: white;
            background-image: none !important;
            color: #333;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }

        .year-dropdown:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.3);
        }

        .action-buttons {
            display: flex;
            gap: 15px;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-icon {
            font-size: 16px;
        }

        .btn-generate {
            background: linear-gradient(45deg, #e67e22, #d35400);
            color: white;
        }

        .btn-generate:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(230, 126, 34, 0.3);
        }

        .btn-danger {
            background: linear-gradient(45deg, #dc3545, #e74c3c);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(220, 53, 69, 0.3);
        }

        .btn-create {
            background: linear-gradient(45deg, #dc2626, #ef4444);
            color: white;
            font-size: 12px;
            padding: 8px 12px;
        }

        .btn-meeting {
            background: linear-gradient(45deg, #f59e0b, #fbbf24);
            color: #333;
            font-size: 12px;
            padding: 8px 12px;
        }

        /* Calendar */
        .calendar-container {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.08);
            min-height: 70vh;
        }

        .calendar-title {
            font-size: 26px;
            font-weight: 800;
            color: #1a1a1a;
            margin: 0 0 30px 0;
            text-align: center;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .quarters-grid {
            display: grid;
            gap: 20px;
        }

        .quarter-section {
            background: white;
            border-radius: 16px;
            box-shadow: 0 6px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .quarter-section:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
        }

        .quarter-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 16px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            user-select: none;
        }

        .quarter-header:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }

        .quarter-title-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .quarter-title {
            font-size: 20px;
            font-weight: 700;
            margin: 0;
        }

        .quarter-count {
            background: rgba(255, 255, 255, 0.2);
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .quarter-toggle {
            display: flex;
            align-items: center;
        }

        .toggle-icon {
            font-size: 20px;
            transition: transform 0.3s ease;
        }

        .toggle-icon.expanded {
            transform: rotate(0deg);
        }

        .toggle-icon.collapsed {
            transform: rotate(-90deg);
        }

        .quarter-calendar {
            padding: 12px;
            min-height: 250px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .months-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
        }

        .month-section {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 8px;
        }

        .month-header {
            background: linear-gradient(45deg, #495057, #6c757d);
            color: white;
            padding: 6px 10px;
            border-radius: 4px;
            margin-bottom: 8px;
            text-align: center;
        }

        .month-title {
            font-size: 12px;
            font-weight: 600;
            margin: 0;
        }

        .month-count {
            font-size: 11px;
            opacity: 0.8;
        }

        .month-sessions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }

        .empty-month {
            text-align: center;
            padding: 20px 10px;
            color: #9ca3af;
        }

        .empty-month .empty-icon {
            font-size: 24px;
            margin-bottom: 8px;
            opacity: 0.5;
        }

        .empty-month p {
            font-size: 12px;
            margin: 0;
        }

        .session-card {
            border-radius: 6px;
            padding: 10px;
            transition: all 0.3s ease;
            border: 2px solid;
            position: relative;
            overflow: hidden;
            font-size: 11px;
        }

        .session-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .session-card.status-missing {
            background: linear-gradient(135deg, #fef2f2, #fee2e2);
            border-color: #fca5a5;
        }

        .session-card.status-missing::before {
            background: linear-gradient(90deg, #dc2626, #ef4444);
        }

        .session-card.status-needs_meeting {
            background: linear-gradient(90deg, #ffda9b, #ffda9b);
            border-color: #ffda9b;
        }

        .session-card.status-needs_meeting::before {
            background: linear-gradient(90deg, #ffda9b, #ffda9b);
        }

        .session-card.status-ready {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border-color: #86efac;
        }

        .session-card.status-ready::before {
            background: linear-gradient(90deg, #16a34a, #22c55e);
        }

        .session-card.status-past {
            background: linear-gradient(135deg, #f9fafb, #f3f4f6);
            border-color: #d1d5db;
        }

        .session-card.status-past::before {
            background: linear-gradient(90deg, #6b7280, #9ca3af);
        }

        .session-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .week-info {
            flex: 1;
        }

        .week-number {
            font-size: 16px;
            font-weight: 700;
            color: #1f2937;
            display: block;
            margin-bottom: 3px;
        }

        .week-dates {
            font-size: 13px;
            color: #6b7280;
            font-weight: 500;
        }

        .session-actions {
            margin-left: 15px;
        }

        .action-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-create {
            background: linear-gradient(45deg, #dc2626, #ef4444);
            color: white;
        }

        .btn-meeting {
            background: linear-gradient(45deg, #f59e0b, #fbbf24);
            color: #92400e;
        }

        .session-content {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 8px;
            padding: 12px;
        }

        .session-title {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .session-name {
            font-size: 14px;
            font-weight: 700;
            color: #1f2937;
        }

        .session-badge {
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .session-badge.status-draft {
            background: #dbeafe;
            color: #1e40af;
        }

        .session-badge.status-scheduled {
            background: #dcfce7;
            color: #166534;
        }

        .training-schedule {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .day-schedule {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: rgba(255, 255, 255, 0.8);
            padding: 6px 10px;
            border-radius: 6px;
        }

        .day-info {
            display: flex;
            flex-direction: column;
        }

        .day-label {
            font-size: 12px;
            font-weight: 600;
            color: #374151;
        }

        .day-date {
            font-size: 11px;
            color: #6b7280;
        }

        .meeting-btn {
            background: #10b981;
            color: white;
            padding: 4px 8px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .meeting-btn:hover {
            background: #059669;
            transform: scale(1.05);
        }

        .no-meeting-btn {
            background: #f3f4f6;
            color: #6b7280;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 500;
        }

        .empty-quarter {
            text-align: center;
            padding: 60px 20px;
            color: #9ca3af;
        }

        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }

        .empty-quarter p {
            font-size: 16px;
            font-weight: 500;
            margin: 0;
        }

        /* Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal-container {
            background: white;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow: auto;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            border-bottom: 1px solid #eee;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
            color: #333;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #999;
            transition: color 0.3s ease;
        }

        .modal-close:hover {
            color: #333;
        }

        .modal-body {
            padding: 20px 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 12px;
            font-size: 15px;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .radio-option:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }

        .radio-input {
            margin: 0;
        }

        .radio-input:checked + .radio-text {
            font-weight: 600;
            color: #007bff;
        }

        .radio-option:has(.radio-input:checked) {
            border-color: #007bff;
            background: #e3f2fd;
        }

        .radio-text {
            flex: 1;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            padding: 15px 25px;
            border-top: 1px solid #eee;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-primary {
            background: linear-gradient(45deg, #007bff, #0056b3);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0, 123, 255, 0.3);
        }

        .btn-primary:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .loading-spinner {
            animation: spin 1s linear infinite;
            margin-right: 6px;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .modal-instruction {
            margin-bottom: 15px;
            color: #666;
            font-size: 13px;
            line-height: 1.4;
        }

        .holiday-notice {
            color: #dc2626;
            font-weight: 500;
            display: block;
            margin-top: 6px;
            font-size: 12px;
        }

        .date-selection-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
            margin-bottom: 15px;
        }

        .date-option {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .date-option:hover {
            border-color: #007bff;
            background: #f8f9fa;
        }

        .date-option.selected {
            border-color: #007bff;
            background: #e3f2fd;
        }

        .date-option.holiday-disabled {
            background: #fff5f5;
            border-color: #fca5a5;
            opacity: 0.7;
            cursor: not-allowed;
        }

        .date-option.holiday-disabled:hover {
            border-color: #fca5a5;
            background: #fff5f5;
        }

        .date-input {
            margin: 0;
            width: 16px;
            height: 16px;
        }

        .date-content {
            display: flex;
            flex-direction: column;
            flex: 1;
        }

        .day-name {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .date-formatted {
            color: #666;
            font-size: 12px;
        }

        .holiday-label {
            color: #dc2626;
            font-size: 11px;
            font-weight: 500;
            margin-top: 2px;
            font-style: italic;
        }

        .selected-summary {
            padding: 10px;
            background: #e3f2fd;
            border-radius: 6px;
            color: #1976d2;
            font-size: 13px;
            text-align: center;
        }

        .selection-note {
            margin-top: 6px;
            font-size: 11px;
            color: #d32f2f;
            font-style: italic;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .quarters-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .months-grid {
                grid-template-columns: 1fr;
            }

            .sessions-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            }
        }

        @media (max-width: 900px) {
            .quarters-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .training-setting-container {
                padding: 15px;
            }

            .header-controls {
                flex-direction: column;
                gap: 20px;
                text-align: center;
                padding: 20px;
            }

            .calendar-container {
                padding: 20px;
            }

            .calendar-title {
                font-size: 24px;
            }

            .quarters-grid {
                gap: 20px;
            }

            .quarter-calendar {
                padding: 15px;
            }

            .sessions-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .session-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .session-actions {
                margin-left: 0;
            }

            .modal-container {
                width: 95%;
                margin: 20px;
            }

            .modal-header,
            .modal-body,
            .modal-footer {
                padding: 15px;
            }

            .date-selection-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</x-filament-panels::page>
