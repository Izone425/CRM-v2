<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/training-calendar.blade.php -->
<x-filament::page>
    <style>
        .calendar-container {
        margin-bottom: 2rem;
        }

        .calendar-controls {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        }

        .calendar-selectors {
        display: flex;
        align-items: center;
        gap: 1rem;
        }

        .calendar-selectors select {
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        padding: 0.375rem 0.75rem;
        }

        .bulk-manage-btn {
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        color: white;
        background-color: #2563eb;
        border-radius: 0.375rem;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s;
        }

        .bulk-manage-btn:hover {
        background-color: #3b82f6;
        }

        .bulk-manage-btn:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
        }

        /* Calendar Legend */
        .calendar-legend {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-bottom: 1.5rem;
        }

        .legend-item {
        display: flex;
        align-items: center;
        }

        .legend-color {
        width: 1rem;
        height: 1rem;
        margin-right: 0.5rem;
        }

        .legend-closed {
        background-color: #f3f4f6;
        }

        .legend-available {
        background-color: #d1fae5;
        }

        .legend-limited {
        background-color: #fef3c7;
        }

        .legend-full {
        background-color: #fee2e2;
        }

        .legend-past {
        background-color: #d1d5db;
        }

        /* Calendar Grid */
        .calendar-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 1px;
        background-color: #e5e7eb;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        overflow: hidden;
        }

        .calendar-header {
        padding: 0.5rem;
        font-weight: 500;
        text-align: center;
        background-color: #f3f4f6;
        }

        .calendar-day {
        position: relative;
        height: 8rem;
        background-color: white;
        padding: 0.25rem;
        transition: background-color 0.2s;
        }

        .calendar-day:hover {
        background-color: #f9fafb;
        }

        .calendar-day-other-month {
        color: #9ca3af;
        background-color: #f9fafb;
        }

        .calendar-day-past {
        background-color: #d1d5db;
        cursor: not-allowed;
        }

        .calendar-day-holiday {
        background-color: #fee2e2;
        }

        .calendar-day-available {
        background-color: #d1fae5;
        }

        .calendar-day-limited {
        background-color: #fef3c7;
        }

        .calendar-day-full {
        background-color: #fee2e2;
        }

        .calendar-day-today {
        box-shadow: 0 0 0 2px #2563eb;
        z-index: 1;
        }

        .calendar-day-selected {
        box-shadow: 0 0 0 2px #4f46e5;
        z-index: 1;
        }

        .calendar-day-header {
        display: flex;
        justify-content: space-between;
        }

        .day-number {
        font-weight: 500;
        }

        .day-weekday {
        font-size: 0.75rem;
        color: #6b7280;
        }

        .holiday-name {
        margin-top: 0.25rem;
        font-size: 0.75rem;
        font-weight: 500;
        color: #dc2626;
        }

        .training-status {
        margin-top: 0.25rem;
        font-size: 0.75rem;
        }

        .training-available {
        font-weight: 500;
        }

        .training-limited {
        color: #ea580c;
        font-weight: 500;
        }

        .training-available-count {
        color: #16a34a;
        }

        .training-booked {
        font-size: 0.75rem;
        color: #4b5563;
        }

        .training-closed {
        margin-top: 0.25rem;
        font-size: 0.75rem;
        color: #6b7280;
        }

        /* Modal Styles */
        .modal-overlay {
        position: fixed;
        inset: 0;
        z-index: 10;
        overflow-y: auto;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: flex-end;
        justify-content: center;
        min-height: 100vh;
        padding: 1rem;
        }

        @media (min-width: 640px) {
        .modal-overlay {
            padding: 0;
            align-items: center;
        }
        }

        .modal-container {
        background-color: white;
        border-radius: 0.5rem;
        overflow: hidden;
        text-align: left;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        width: 100%;
        max-width: 32rem;
        transform: translateY(0);
        transition: all 0.3s ease-out;
        }

        .modal-body {
        padding: 1.25rem 1rem 1rem;
        }

        @media (min-width: 640px) {
        .modal-body {
            padding: 1.5rem;
        }
        }

        .modal-title {
        margin-bottom: 1rem;
        font-size: 1.125rem;
        font-weight: 500;
        color: #111827;
        }

        .modal-form {
        display: flex;
        flex-direction: column;
        gap: 1rem;
        }

        .form-group {
        margin-bottom: 0.5rem;
        }

        .form-label {
        display: block;
        font-size: 0.875rem;
        font-weight: 500;
        color: #374151;
        margin-bottom: 0.25rem;
        }

        .form-input,
        .form-select,
        .form-textarea {
        width: 100%;
        padding: 0.375rem 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
        border-color: #6366f1;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25);
        outline: none;
        }

        .form-error {
        font-size: 0.75rem;
        color: #ef4444;
        margin-top: 0.25rem;
        }

        .modal-footer {
        background-color: #f9fafb;
        padding: 0.75rem 1rem;
        display: flex;
        flex-direction: column;
        }

        @media (min-width: 640px) {
        .modal-footer {
            flex-direction: row-reverse;
            padding: 0.75rem 1.5rem;
        }
        }

        .btn {
        display: inline-flex;
        justify-content: center;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 500;
        border-radius: 0.375rem;
        border: 1px solid transparent;
        }

        .btn-primary {
        background-color: #2563eb;
        color: white;
        }

        .btn-primary:hover {
        background-color: #3b82f6;
        }

        .btn-primary:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.5);
        }

        .btn-secondary {
        background-color: white;
        color: #374151;
        border-color: #d1d5db;
        margin-top: 0.75rem;
        }

        @media (min-width: 640px) {
        .btn-secondary {
            margin-top: 0;
            margin-left: 0.75rem;
        }
        }

        .btn-secondary:hover {
        background-color: #f9fafb;
        }

        .btn-secondary:focus {
        outline: none;
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25);
        }

        .grid-days {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .day-checkbox {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .day-checkbox input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
        }
        </style>
    <div class="calendar-container">
        <!-- Calendar Controls -->
        <div class="calendar-controls">
            <div class="calendar-selectors">
                <select wire:model="currentMonth" wire:change="changeMonth($event.target.value)">
                    @foreach($months as $key => $month)
                        <option value="{{ $key }}">{{ $month }}</option>
                    @endforeach
                </select>

                <select wire:model="currentYear" wire:change="changeYear($event.target.value)">
                    @foreach($years as $year)
                        <option value="{{ $year }}">{{ $year }}&nbsp;&nbsp;&nbsp;</option>
                    @endforeach
                </select>
            </div>

            @if($this->canManageCalendar())
                <button wire:click="$toggle('showBulkManagementModal')" class="bulk-manage-btn">
                    Manage Multiple Dates
                </button>
            @endif
        </div>

        <!-- Calendar Legend -->
        <div class="calendar-legend">
            <div class="legend-item">
                <div class="legend-color legend-closed"></div>
                <span>Closed</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-available"></div>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-limited"></div>
                <span>Limited Slots</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-full"></div>
                <span>Full/Holiday</span>
            </div>
            <div class="legend-item">
                <div class="legend-color legend-past"></div>
                <span>Past Dates</span>
            </div>
        </div>

        <!-- Calendar Grid -->
        <div class="calendar-grid">
            <!-- Calendar header (days of week) -->
            @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $dayName)
                <div class="calendar-header">{{ $dayName }}</div>
            @endforeach

            <!-- Calendar days -->
            @foreach($calendarDays as $day)
                @php
                    $dayClasses = 'calendar-day';

                    if (!$day['isCurrentMonth']) {
                        $dayClasses .= ' calendar-day-other-month';
                    }

                    if ($day['isPast']) {
                        $dayClasses .= ' calendar-day-past';
                    } elseif ($day['isHoliday']) {
                        $dayClasses .= ' calendar-day-holiday';
                    } elseif ($day['isOpenForTraining']) {
                        if ($day['availableSlots'] <= 0) {
                            $dayClasses .= ' calendar-day-full';
                        } elseif ($day['availableSlots'] <= 5) {
                            $dayClasses .= ' calendar-day-limited';
                        } else {
                            $dayClasses .= ' calendar-day-available';
                        }
                    }

                    if ($day['isToday']) {
                        $dayClasses .= ' calendar-day-today';
                    }

                    if ($day['date'] === $selectedDate) {
                        $dayClasses .= ' calendar-day-selected';
                    }
                @endphp

                    <div wire:click="selectDate('{{ $day['date'] }}')"
                        class="{{ $dayClasses }}"
                        @if(!$day['isPast'] && ($day['isOpenForTraining'] || $this->canManageCalendar()))
                        role="button"
                        @endif
                    >
                    <!-- Day number -->
                    <div class="calendar-day-header">
                        <div class="day-number {{ !$day['isCurrentMonth'] ? 'other-month' : '' }}">
                            {{ $day['day'] }}
                        </div>
                        <div class="day-weekday">{{ $day['dayOfWeek'] }}</div>
                    </div>

                    <!-- Holiday name -->
                    @if($day['isHoliday'])
                        <div class="holiday-name">
                            {{ $day['holidayName'] }}
                        </div>
                    @endif

                    <!-- Training status -->
                    @if($day['isOpenForTraining'])
                        <div class="training-status">
                            <span class="training-available">Available:</span>
                            <span class="training-available-count {{ $day['availableSlots'] <= 5 ? 'training-limited' : '' }}">
                                {{ $day['availableSlots'] }}
                            </span>
                        </div>
                        <div class="training-booked">
                            <span class="training-available">Booked:</span> {{ $day['bookedPax'] }}
                        </div>
                    @elseif(!$day['isHoliday'] && !$day['isPast'])
                        <div class="training-closed">Closed</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Booking Modal -->
    @if($bookingMode)
        <div class="modal-overlay" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="modal-container">
                <div class="modal-body">
                    <h3 class="modal-title">Book Training for {{ \Carbon\Carbon::parse($bookingDate)->format('j F Y') }}</h3>

                    <div class="modal-form">
                        <div class="form-group">
                            <label for="selectedCompany" class="form-label">Select Company</label>
                            <select wire:model="selectedCompany" id="selectedCompany" class="form-select">
                                <option value="">-- Choose a Company --</option>
                                @foreach($companies as $id => $name)
                                    <option value="{{ $id }}">{{ $name }}</option>
                                @endforeach
                            </select>
                            @error('selectedCompany')
                                <span class="form-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="paxCount" class="form-label">Number of Participants</label>
                            <select wire:model="paxCount" id="paxCount" class="form-select">
                                @for($i = 1; $i <= min(20, $calendarDays[array_search($bookingDate, array_column($calendarDays, 'date'))]['availableSlots']); $i++)
                                    <option value="{{ $i }}">{{ $i }}</option>
                                @endfor
                            </select>
                            @error('paxCount') <span class="form-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label for="attendeeName" class="form-label">Primary Attendee Name</label>
                            <input wire:model="attendeeName" type="text" id="attendeeName" class="form-input">
                            @error('attendeeName') <span class="form-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label for="attendeeEmail" class="form-label">Email</label>
                            <input wire:model="attendeeEmail" type="email" id="attendeeEmail" class="form-input">
                            @error('attendeeEmail') <span class="form-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label for="attendeePhone" class="form-label">Phone</label>
                            <input wire:model="attendeePhone" type="text" id="attendeePhone" class="form-input">
                            @error('attendeePhone') <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button wire:click="submitBooking" type="button" class="btn btn-primary">
                        Book Training
                    </button>
                    <button wire:click="cancelBooking" type="button" class="btn btn-secondary">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if($showBulkManagementModal)
        <div class="modal-overlay" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="modal-container">
                <div class="modal-body">
                    <h3 class="modal-title">Bulk Update Training Dates</h3>

                    <div class="modal-form">
                        <div class="form-group">
                            <label for="bulkStartDate" class="form-label">Start Date</label>
                            <input wire:model="bulkStartDate" type="date" id="bulkStartDate" class="form-input">
                            @error('bulkStartDate') <span class="form-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label for="bulkEndDate" class="form-label">End Date</label>
                            <input wire:model="bulkEndDate" type="date" id="bulkEndDate" class="form-input">
                            @error('bulkEndDate') <span class="form-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label for="bulkStatus" class="form-label">Status</label>
                            <select wire:model="bulkStatus" id="bulkStatus" class="form-select">
                                <option value="open">Open for Training</option>
                                <option value="closed">Closed</option>
                            </select>
                            @error('bulkStatus') <span class="form-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label for="bulkCapacity" class="form-label">Capacity</label>
                            <input wire:model="bulkCapacity" type="number" id="bulkCapacity" min="1" max="100" class="form-input">
                            @error('bulkCapacity') <span class="form-error">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label class="form-label">Select Days of Week</label>
                            <div class="grid-days">
                                <label class="day-checkbox">
                                    <input type="checkbox" wire:model="bulkSelectedDays" value="0">
                                    <span>Sunday</span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" wire:model="bulkSelectedDays" value="1">
                                    <span>Monday</span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" wire:model="bulkSelectedDays" value="2">
                                    <span>Tuesday</span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" wire:model="bulkSelectedDays" value="3">
                                    <span>Wednesday</span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" wire:model="bulkSelectedDays" value="4">
                                    <span>Thursday</span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" wire:model="bulkSelectedDays" value="5">
                                    <span>Friday</span>
                                </label>
                                <label class="day-checkbox">
                                    <input type="checkbox" wire:model="bulkSelectedDays" value="6">
                                    <span>Saturday</span>
                                </label>
                            </div>
                            @error('bulkSelectedDays') <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button wire:click="saveBulkSettings" type="button" class="btn btn-primary">
                        Save Settings
                    </button>
                    <button wire:click="$set('showBulkManagementModal', false)" type="button" class="btn btn-secondary">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Management Modal -->
    @if($managementMode)
        <div class="modal-overlay" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="modal-container">
                <div class="modal-body">
                    <h3 class="modal-title">Manage Training Date: {{ \Carbon\Carbon::parse($managementDate)->format('j F Y') }}</h3>

                    <div class="modal-form">
                        <div class="form-group">
                            <label for="dateStatus" class="form-label">Date Status</label>
                            <select wire:model="dateStatus" id="dateStatus" class="form-select">
                                <option value="open">Open for Training</option>
                                <option value="closed">Closed</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="capacity" class="form-label">Capacity</label>
                            <input wire:model="capacity" type="number" id="capacity" min="1" max="100" class="form-input">
                            @error('capacity') <span class="form-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button wire:click="updateDateSettings" type="button" class="btn btn-primary">
                        Save Settings
                    </button>
                    <button wire:click="cancelBooking" type="button" class="btn btn-secondary">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament::page>
