<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/training-setting.blade.php -->
<x-filament-panels::page>
    <div class="training-setting-wizard">
        {{-- Progress Steps --}}
        <div class="progress-container">
            <div class="progress-steps">
                @for($i = 1; $i <= 8; $i++)
                    <div class="progress-step-item {{ $i < 8 ? 'flex-1' : '' }}">
                        <div class="progress-step-circle {{ $currentStep >= $i ? 'active' : 'inactive' }}">
                            {{ $i }}
                        </div>
                        @if($i < 8)
                            <div class="progress-line {{ $currentStep > $i ? 'active' : 'inactive' }}"></div>
                        @endif
                    </div>
                @endfor
            </div>
            <div class="progress-labels">
                <div>Trainer</div>
                <div>Year</div>
                <div>Category</div>
                <div>Module</div>
                <div>Display</div>
                <div>Manual</div>
                <div>Configure</div>
                <div>Email</div>
            </div>
        </div>

        {{-- Step Content --}}
        @if($currentStep == 1)
            {{-- Step 1: Choose Trainer --}}
            <div class="step-container">
                <h2 class="step-title">Step 1: Choose Trainer Profile</h2>
                <div class="trainer-options">
                    @foreach(App\Models\TrainingSession::TRAINER_PROFILES as $key => $name)
                        <label class="trainer-option {{ $selectedTrainer === $key ? 'selected' : '' }}">
                            <input type="radio" wire:model.live="selectedTrainer" value="{{ $key }}" class="hidden-radio">
                            <div class="trainer-content">
                                <div class="trainer-avatar">
                                    {{ substr($name, -1) }}
                                </div>
                                <span class="trainer-name">{{ $name }}</span>
                            </div>
                        </label>
                    @endforeach
                </div>
                <div class="step-navigation right">
                    <button
                        wire:click="nextStep"
                        @disabled(!$selectedTrainer)
                        class="btn btn-primary {{ !$selectedTrainer ? 'disabled' : '' }}">
                        Next
                    </button>
                </div>
            </div>

        @elseif($currentStep == 2)
            {{-- Step 2: Choose Year --}}
            <div class="step-container">
                <h2 class="step-title">Step 2: Choose Year</h2>
                <div class="year-grid">
                    @for($year = now()->year - 1; $year <= now()->year + 5; $year++)
                        <label class="year-option {{ $selectedYear === $year ? 'selected' : '' }}">
                            <input type="radio" wire:model.live="selectedYear" value="{{ $year }}" class="hidden-radio">
                            <span class="year-text">{{ $year }}</span>
                        </label>
                    @endfor
                </div>
                <div class="step-navigation between">
                    <button wire:click="previousStep" class="btn btn-secondary">Previous</button>
                    <button
                        wire:click="nextStep"
                        @disabled(!$selectedYear)
                        class="btn btn-primary {{ !$selectedYear ? 'disabled' : '' }}">
                        Next
                    </button>
                </div>
            </div>

        @elseif($currentStep == 3)
            {{-- Step 3: Choose Category --}}
            <div class="step-container">
                <h2 class="step-title">Step 3: Choose Training Category</h2>
                <div class="category-options">
                    @foreach(App\Models\TrainingSession::TRAINING_CATEGORIES as $key => $name)
                        <label class="category-option {{ $selectedCategory === $key ? 'selected' : '' }}">
                            <div class="category-content">
                                <input type="radio" wire:model.live="selectedCategory" value="{{ $key }}" class="hidden-radio">
                                <span class="category-name">{{ $name }}</span>
                            </div>
                            <span class="category-max">
                                Max: {{ $key === 'HRDF' ? '50' : '100' }} participants
                            </span>
                        </label>
                    @endforeach
                </div>
                <div class="step-navigation between">
                    <button wire:click="previousStep" class="btn btn-secondary">Previous</button>
                    <button
                        wire:click="nextStep"
                        @disabled(!$selectedCategory)
                        class="btn btn-primary {{ !$selectedCategory ? 'disabled' : '' }}">
                        Next
                    </button>
                </div>
            </div>

        @elseif($currentStep == 4)
            {{-- Step 4: Choose Module --}}
            <div class="step-container">
                <h2 class="step-title">Step 4: Choose Training Module</h2>
                <div class="module-options">
                    @foreach(App\Models\TrainingSession::TRAINING_MODULES as $key => $name)
                        <label class="module-option {{ $selectedModule === $key ? 'selected' : '' }}">
                            <input type="radio" wire:model.live="selectedModule" value="{{ $key }}" class="hidden-radio">
                            <span class="module-name">{{ $name }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="step-navigation between">
                    <button wire:click="previousStep" class="btn btn-secondary">Previous</button>
                    <button
                        wire:click="nextStep"
                        @disabled(!$selectedModule)
                        class="btn btn-primary {{ !$selectedModule ? 'disabled' : '' }}">
                        Next
                    </button>
                </div>
            </div>

        @elseif($currentStep == 5)
            {{-- Step 5: Generate Schedule --}}
            <div class="step-container">
                <h2 class="step-title">Step 5: Generate Training Schedule</h2>

                <div class="summary-container">
                    <h3 class="summary-title">Configuration Summary:</h3>
                    <div class="summary-grid">
                        <div><strong>Trainer:</strong> {{ App\Models\TrainingSession::TRAINER_PROFILES[$selectedTrainer] ?? 'N/A' }}</div>
                        <div><strong>Year:</strong> {{ $selectedYear }}</div>
                        <div><strong>Category:</strong> {{ App\Models\TrainingSession::TRAINING_CATEGORIES[$selectedCategory] ?? 'N/A' }}</div>
                        <div><strong>Module:</strong> {{ App\Models\TrainingSession::TRAINING_MODULES[$selectedModule] ?? 'N/A' }}</div>
                    </div>
                </div>

                <div class="generate-container">
                    @if(!$this->getHasExistingData())
                        <button
                            wire:click="generateYearlySchedule"
                            class="btn btn-generate">
                            🤖 Auto-Generate Training Schedule
                        </button>
                        <p class="generate-description">
                            System will automatically create training sessions avoiding weekends and public holidays
                        </p>
                    @else
                        <div class="existing-data-notice">
                            <div class="warning-box">
                                <svg class="warning-icon" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                </svg>
                                Training schedule already exists for this configuration
                            </div>
                            <button
                                wire:click="nextStep"
                                class="btn btn-primary existing-btn">
                                View Existing Schedule
                            </button>
                        </div>
                    @endif
                </div>

                <div class="step-navigation left">
                    <button wire:click="previousStep" class="btn btn-secondary">Previous</button>
                </div>
            </div>
        @endif

        {{-- Display Schedule (Steps 6+) --}}
        @if($showSchedule && $currentStep >= 6)
            <div class="schedule-container">
                <div class="schedule-header">
                    <h2 class="schedule-title">Training Schedule - {{ $selectedYear }}</h2>
                    <div class="view-mode-buttons">
                        <button
                            wire:click="switchViewMode('month')"
                            class="view-mode-btn {{ $viewMode === 'month' ? 'active' : '' }}">
                            By Month
                        </button>
                        <button
                            wire:click="switchViewMode('quarter')"
                            class="view-mode-btn {{ $viewMode === 'quarter' ? 'active' : '' }}">
                            By Quarter
                        </button>
                    </div>
                </div>

                {{-- Manual Scheduling Required --}}
                @if(!empty($manualSessions) && $currentStep == 6)
                    <div class="manual-scheduling-container">
                        <h3 class="manual-title">Manual Scheduling Required</h3>
                        <p class="manual-description">The following sessions conflict with public holidays and need manual date selection:</p>

                        @foreach($manualSessions as $index => $session)
                            <div class="manual-session">
                                <div class="manual-session-header">
                                    <div class="manual-session-info">
                                        <strong>{{ $session['session_number'] }}</strong>
                                        <div class="manual-week-info">
                                            Week of {{ $session['week_start']->format('M j, Y') }}
                                        </div>
                                        @if(!empty($session['conflicting_holidays']))
                                            <div class="manual-conflicts">
                                                Conflicts: {{ implode(', ', $session['conflicting_holidays']) }}
                                            </div>
                                        @endif
                                    </div>
                                    <button
                                        wire:click="startManualScheduling({{ $index }})"
                                        class="btn btn-manual">
                                        Schedule Manually
                                    </button>
                                </div>

                                {{-- Manual Date Selection --}}
                                @if($currentManualSession === $index)
                                    <div class="manual-date-selection">
                                        <p class="date-selection-instruction">Select exactly 3 consecutive dates:</p>
                                        <div class="date-grid">
                                            @foreach($this->getAvailableDatesForManual() as $dateInfo)
                                                <button
                                                    wire:click="toggleManualDate('{{ $dateInfo['date'] }}')"
                                                    class="date-btn {{ $dateInfo['is_selected'] ? 'selected' : ($dateInfo['is_holiday'] ? 'holiday' : '') }}">
                                                    <div class="date-day">{{ $dateInfo['day_name'] }}</div>
                                                    <div class="date-formatted">{{ $dateInfo['formatted'] }}</div>
                                                    @if($dateInfo['is_holiday'])
                                                        <div class="date-holiday-label">Holiday</div>
                                                    @endif
                                                </button>
                                            @endforeach
                                        </div>
                                        <div class="date-counter">
                                            Selected: {{ count($selectedDatesForManual) }}/3 dates
                                        </div>
                                        <div class="manual-actions">
                                            <button
                                                wire:click="saveManualSession"
                                                @disabled(count($selectedDatesForManual) !== 3)
                                                class="btn btn-success {{ count($selectedDatesForManual) !== 3 ? 'disabled' : '' }}">
                                                Save Session
                                            </button>
                                            <button
                                                wire:click="currentManualSession = null"
                                                class="btn btn-secondary">
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endforeach

                        @if(empty($manualSessions))
                            <button
                                wire:click="submitPart1"
                                class="btn btn-primary full-width">
                                Complete Part 1 - Continue to Configuration
                            </button>
                        @endif
                    </div>
                @endif

                {{-- Sessions Display --}}
                @foreach($this->getScheduledSessions() as $period => $sessions)
                    <div class="period-section">
                        <h3 class="period-title">{{ $period }}</h3>
                        <div class="sessions-grid">
                            @foreach($sessions as $session)
                                <div
                                    wire:click="selectSessionForConfig({{ $session->id }})"
                                    class="session-card {{ $selectedSessionForConfig === $session->id ? 'selected' : '' }}"
                                    style="background-color: {{ $session->status === 'DRAFT' ? '#10b981' : ($session->status === 'SCHEDULED' ? '#ef4444' : '#6b7280') }}; color: white;">

                                    <div class="session-header">
                                        <h4 class="session-number">{{ $session->session_number }}</h4>
                                        <span class="session-status">{{ $session->status }}</span>
                                    </div>

                                    <div class="session-details">
                                        <div><strong>Day 1:</strong> {{ $session->day1_date->format('M j') }} - {{ $session->day1_module }}</div>
                                        <div><strong>Day 2:</strong> {{ $session->day2_date->format('M j') }} - {{ $session->day2_module }}</div>
                                        <div><strong>Day 3:</strong> {{ $session->day3_date->format('M j') }} - {{ $session->day3_module }}</div>
                                    </div>

                                    <div class="session-participants">
                                        Max Participants: {{ $session->max_participants }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                @if($showConfiguration && empty($manualSessions) && $currentStep >= 7)
                    <div class="configure-section">
                        <button
                            wire:click="goToStep(7)"
                            class="btn btn-success full-width large">
                            Configure Training Sessions
                        </button>
                    </div>
                @endif
            </div>
        @endif

        {{-- Session Configuration (Step 7) --}}
        @if($showConfiguration && $currentStep == 7 && $selectedSessionForConfig)
            @php
                $session = App\Models\TrainingSession::find($selectedSessionForConfig);
            @endphp
            <div class="config-container">
                <h2 class="config-title">Configure Training Session: {{ $session->session_number }}</h2>

                <div class="config-days">
                    {{-- Day 1 Configuration --}}
                    <div class="config-day">
                        <h3 class="day-title">Day 1 - {{ $session->day1_date->format('l, F j, Y') }}</h3>
                        <div class="config-fields">
                            <div class="field-group">
                                <label class="field-label">Module</label>
                                <input type="text" value="{{ $session->day1_module }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day1_module', $event.target.value)"
                                       class="field-input">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Deck Link</label>
                                <input type="url" value="{{ $session->day1_deck_link }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day1_deck_link', $event.target.value)"
                                       class="field-input" placeholder="Training deck URL">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Meeting Link</label>
                                <input type="url" value="{{ $session->day1_meeting_link }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day1_meeting_link', $event.target.value)"
                                       class="field-input" placeholder="Zoom/Teams meeting URL">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Meeting ID</label>
                                <input type="text" value="{{ $session->day1_meeting_id }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day1_meeting_id', $event.target.value)"
                                       class="field-input" placeholder="Meeting ID">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Meeting Password</label>
                                <input type="text" value="{{ $session->day1_meeting_password }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day1_meeting_password', $event.target.value)"
                                       class="field-input" placeholder="Meeting password">
                            </div>
                        </div>
                    </div>

                    {{-- Day 2 Configuration --}}
                    <div class="config-day">
                        <h3 class="day-title">Day 2 - {{ $session->day2_date->format('l, F j, Y') }}</h3>
                        <div class="config-fields">
                            <div class="field-group">
                                <label class="field-label">Module</label>
                                <input type="text" value="{{ $session->day2_module }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day2_module', $event.target.value)"
                                       class="field-input">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Deck Link</label>
                                <input type="url" value="{{ $session->day2_deck_link }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day2_deck_link', $event.target.value)"
                                       class="field-input" placeholder="Training deck URL">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Meeting Link</label>
                                <input type="url" value="{{ $session->day2_meeting_link }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day2_meeting_link', $event.target.value)"
                                       class="field-input" placeholder="Zoom/Teams meeting URL">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Meeting ID</label>
                                <input type="text" value="{{ $session->day2_meeting_id }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day2_meeting_id', $event.target.value)"
                                       class="field-input" placeholder="Meeting ID">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Meeting Password</label>
                                <input type="text" value="{{ $session->day2_meeting_password }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day2_meeting_password', $event.target.value)"
                                       class="field-input" placeholder="Meeting password">
                            </div>
                        </div>
                    </div>

                    {{-- Day 3 Configuration --}}
                    <div class="config-day">
                        <h3 class="day-title">Day 3 - {{ $session->day3_date->format('l, F j, Y') }}</h3>
                        <div class="config-fields">
                            <div class="field-group">
                                <label class="field-label">Module</label>
                                <input type="text" value="{{ $session->day3_module }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day3_module', $event.target.value)"
                                       class="field-input">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Deck Link</label>
                                <input type="url" value="{{ $session->day3_deck_link }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day3_deck_link', $event.target.value)"
                                       class="field-input" placeholder="Training deck URL">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Meeting Link</label>
                                <input type="url" value="{{ $session->day3_meeting_link }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day3_meeting_link', $event.target.value)"
                                       class="field-input" placeholder="Zoom/Teams meeting URL">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Meeting ID</label>
                                <input type="text" value="{{ $session->day3_meeting_id }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day3_meeting_id', $event.target.value)"
                                       class="field-input" placeholder="Meeting ID">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Meeting Password</label>
                                <input type="text" value="{{ $session->day3_meeting_password }}"
                                       wire:change="updateSessionField({{ $session->id }}, 'day3_meeting_password', $event.target.value)"
                                       class="field-input" placeholder="Meeting password">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="config-actions">
                    <button
                        wire:click="selectedSessionForConfig = null"
                        class="btn btn-secondary">
                        Back to Sessions
                    </button>
                    <button
                        wire:click="submitPart2"
                        class="btn btn-success large">
                        Complete Configuration
                    </button>
                </div>
            </div>
        @endif

        {{-- Action Buttons --}}
        <div class="main-actions">
            <button
                wire:click="resetWizard"
                class="btn btn-danger">
                Reset All
            </button>

            @if($currentStep >= 5 && !$showSchedule)
                <button
                    wire:click="goToStep(6)"
                    class="btn btn-primary">
                    View Schedule
                </button>
            @endif
        </div>
    </div>

    <style>
        .training-setting-wizard {
            max-width: 1200px;
            margin: 0 auto;
            font-family: Arial, sans-serif;
        }

        /* Progress Steps */
        .progress-container {
            margin-bottom: 32px;
        }

        .progress-steps {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .progress-step-item {
            display: flex;
            align-items: center;
        }

        .progress-step-item.flex-1 {
            flex: 1;
        }

        .progress-step-circle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            font-size: 14px;
            font-weight: 500;
        }

        .progress-step-circle.active {
            background-color: #3b82f6;
            color: white;
        }

        .progress-step-circle.inactive {
            background-color: #e5e7eb;
            color: #6b7280;
        }

        .progress-line {
            flex: 1;
            height: 4px;
            margin: 0 8px;
        }

        .progress-line.active {
            background-color: #3b82f6;
        }

        .progress-line.inactive {
            background-color: #e5e7eb;
        }

        .progress-labels {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 8px;
            font-size: 12px;
            text-align: center;
            color: #6b7280;
        }

        /* Step Container */
        .step-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-bottom: 24px;
        }

        .step-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 16px;
            color: #1f2937;
        }

        /* Buttons */
        .btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }

        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2563eb;
        }

        .btn-secondary {
            background-color: #6b7280;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #4b5563;
        }

        .btn-success {
            background-color: #10b981;
            color: white;
        }

        .btn-success:hover {
            background-color: #059669;
        }

        .btn-danger {
            background-color: #ef4444;
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
        }

        .btn-generate {
            background-color: #16a34a;
            color: white;
            padding: 12px 24px;
            font-size: 18px;
            font-weight: 500;
        }

        .btn-generate:hover {
            background-color: #15803d;
        }

        .btn-manual {
            background-color: #3b82f6;
            color: white;
            padding: 4px 12px;
            font-size: 14px;
        }

        .btn.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn.disabled:hover {
            background-color: inherit;
        }

        .btn.full-width {
            width: 100%;
        }

        .btn.large {
            padding: 12px 24px;
            font-size: 16px;
        }

        /* Navigation */
        .step-navigation {
            margin-top: 24px;
            display: flex;
        }

        .step-navigation.left {
            justify-content: flex-start;
        }

        .step-navigation.right {
            justify-content: flex-end;
        }

        .step-navigation.between {
            justify-content: space-between;
        }

        /* Trainer Options */
        .trainer-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .trainer-option {
            display: flex;
            align-items: center;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .trainer-option:hover {
            background-color: #f9fafb;
        }

        .trainer-option.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .trainer-content {
            display: flex;
            align-items: center;
        }

        .trainer-avatar {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: #3b82f6;
            color: white;
            border-radius: 50%;
            font-weight: 500;
            margin-right: 12px;
        }

        .trainer-name {
            font-weight: 500;
        }

        .hidden-radio {
            display: none;
        }

        /* Year Grid */
        .year-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }

        .year-option {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .year-option:hover {
            background-color: #f9fafb;
        }

        .year-option.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .year-text {
            font-size: 18px;
            font-weight: 500;
        }

        /* Category Options */
        .category-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .category-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .category-option:hover {
            background-color: #f9fafb;
        }

        .category-option.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .category-content {
            display: flex;
            align-items: center;
        }

        .category-name {
            font-weight: 500;
        }

        .category-max {
            font-size: 14px;
            color: #6b7280;
        }

        /* Module Options */
        .module-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .module-option {
            display: flex;
            align-items: center;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .module-option:hover {
            background-color: #f9fafb;
        }

        .module-option.selected {
            border-color: #3b82f6;
            background-color: #eff6ff;
        }

        .module-name {
            font-weight: 500;
        }

        /* Summary */
        .summary-container {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .summary-title {
            font-weight: 500;
            margin-bottom: 8px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
            font-size: 14px;
        }

        /* Generate */
        .generate-container {
            text-align: center;
        }

        .generate-description {
            margin-top: 8px;
            font-size: 14px;
            color: #6b7280;
        }

        .existing-data-notice {
            text-align: center;
        }

        .warning-box {
            display: inline-flex;
            align-items: center;
            padding: 8px 16px;
            background-color: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 8px;
            color: #92400e;
        }

        .warning-icon {
            width: 20px;
            height: 20px;
            margin-right: 8px;
        }

        .existing-btn {
            margin-top: 16px;
        }

        /* Schedule */
        .schedule-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-top: 24px;
        }

        .schedule-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }

        .schedule-title {
            font-size: 20px;
            font-weight: 600;
        }

        .view-mode-buttons {
            display: flex;
            gap: 8px;
        }

        .view-mode-btn {
            padding: 4px 12px;
            font-size: 14px;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .view-mode-btn.active {
            background-color: #3b82f6;
            color: white;
        }

        .view-mode-btn:not(.active) {
            background-color: #e5e7eb;
            color: #374151;
        }

        /* Manual Scheduling */
        .manual-scheduling-container {
            background-color: #fefce8;
            border: 1px solid #facc15;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }

        .manual-title {
            font-weight: 500;
            color: #92400e;
            margin-bottom: 12px;
        }

        .manual-description {
            font-size: 14px;
            color: #a16207;
            margin-bottom: 16px;
        }

        .manual-session {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 12px;
            margin-bottom: 12px;
        }

        .manual-session-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .manual-week-info {
            font-size: 14px;
            color: #6b7280;
        }

        .manual-conflicts {
            font-size: 12px;
            color: #dc2626;
        }

        .manual-date-selection {
            border-top: 1px solid #e5e7eb;
            padding-top: 12px;
            margin-top: 12px;
        }

        .date-selection-instruction {
            font-size: 14px;
            margin-bottom: 8px;
        }

        .date-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 8px;
            margin-bottom: 12px;
        }

        .date-btn {
            padding: 8px;
            font-size: 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            background: white;
            transition: all 0.2s ease;
        }

        .date-btn.selected {
            background-color: #10b981;
            color: white;
            border-color: #10b981;
        }

        .date-btn.holiday {
            background-color: #fef2f2;
            border-color: #fca5a5;
            color: #dc2626;
        }

        .date-day {
            font-weight: 500;
        }

        .date-holiday-label {
            font-size: 10px;
        }

        .date-counter {
            font-size: 12px;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .manual-actions {
            display: flex;
            gap: 8px;
        }

        /* Sessions */
        .period-section {
            margin-bottom: 24px;
        }

        .period-title {
            font-size: 18px;
            font-weight: 500;
            background-color: #f3f4f6;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 12px;
        }

        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px;
        }

        .session-card {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .session-card:hover {
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .session-card.selected {
            border: 2px solid #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .session-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .session-number {
            font-weight: 500;
            margin: 0;
        }

        .session-status {
            padding: 2px 8px;
            font-size: 12px;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.2);
        }

        .session-details {
            font-size: 14px;
            margin-bottom: 8px;
        }

        .session-details > div {
            margin-bottom: 4px;
        }

        .session-participants {
            padding-top: 8px;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 12px;
        }

        .configure-section {
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
            margin-top: 24px;
        }

        /* Configuration */
        .config-container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 24px;
            margin-top: 24px;
        }

        .config-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .config-days {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }

        .config-day {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
        }

        .day-title {
            font-weight: 500;
            margin-bottom: 12px;
        }

        .config-fields {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
        }

        .field-group {
            display: flex;
            flex-direction: column;
        }

        .field-label {
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 4px;
        }

        .field-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            font-size: 14px;
        }

        .field-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .config-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 24px;
        }

        /* Main Actions */
        .main-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 32px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .schedule-header {
                flex-direction: column;
                gap: 16px;
            }

            .sessions-grid {
                grid-template-columns: 1fr;
            }

            .config-fields {
                grid-template-columns: 1fr;
            }

            .config-actions {
                flex-direction: column;
                gap: 12px;
            }

            .main-actions {
                flex-direction: column;
                gap: 12px;
            }
        }
    </style>
</x-filament-panels::page>
