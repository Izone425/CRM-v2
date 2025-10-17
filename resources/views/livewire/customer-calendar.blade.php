{{-- filepath: /var/www/html/timeteccrm/resources/views/livewire/customer-calendar.blade.php --}}
<div>
    <style>
        .calendar-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }

        .calendar-header-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .calendar-day {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0.75rem;
            min-height: 100px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            border: 2px solid transparent;
        }

        .calendar-day:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 1);
        }

        .calendar-day.other-month {
            background: rgba(249, 250, 251, 0.7);
            color: #9ca3af;
        }

        .calendar-day.today {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 2px solid #3b82f6;
            box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
        }

        .calendar-day.past {
            background: rgba(243, 244, 246, 0.8);
            color: #9ca3af;
            cursor: not-allowed;
        }

        .calendar-day.weekend {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border-left: 4px solid #f59e0b;
        }

        .calendar-day.holiday {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            border-left: 4px solid #ef4444;
        }

        .calendar-day.bookable {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            border: 2px solid #22c55e;
            box-shadow: 0 0 15px rgba(34, 197, 94, 0.2);
        }

        .calendar-day.bookable:hover {
            background: linear-gradient(135deg, #bbf7d0 0%, #a7f3d0 100%);
            transform: translateY(-4px);
            box-shadow: 0 12px 30px rgba(34, 197, 94, 0.3);
        }

        .calendar-day.has-meeting {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            border: 2px solid #6366f1;
            box-shadow: 0 0 20px rgba(99, 102, 241, 0.3);
        }

        .calendar-day.disabled {
            background: rgba(243, 244, 246, 0.6);
            color: #9ca3af;
            cursor: not-allowed;
        }

        .day-number {
            font-weight: 700;
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
        }

        .available-count {
            font-size: 0.75rem;
            color: #059669;
            font-weight: 600;
            background: rgba(5, 150, 105, 0.1);
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            text-align: center;
        }

        .meeting-indicator {
            font-size: 0.75rem;
            color: #6366f1;
            font-weight: 600;
            background: rgba(99, 102, 241, 0.1);
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            text-align: center;
        }

        .existing-bookings {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #0ea5e9;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .booking-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            margin-bottom: 1rem;
            border-left: 4px solid #6366f1;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .booking-card:last-child {
            margin-bottom: 0;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .status-confirmed {
            background: #d1fae5;
            color: #065f46;
        }

        .status-new {
            background: #dbeafe;
            color: #1e40af;
        }

        /* Rest of existing styles remain the same */
        .calendar-days-header {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 2px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 16px 16px 0 0;
            overflow: hidden;
            margin-bottom: 2px;
        }

        .header-day {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            padding: 1rem 0.75rem;
            text-align: center;
            font-weight: 700;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.875rem;
        }

        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 50;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-container {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 48rem;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.3s ease-out;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 2rem;
            color: white;
            border-radius: 20px 20px 0 0;
        }

        .modal-body {
            padding: 2rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 0.875rem;
            transition: all 0.2s;
            background: #fafafa;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            outline: none;
            background: white;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
            font-size: 0.875rem;
            min-width: 200px; /* Add minimum width */
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            min-height: 50px; /* Add minimum height for two lines */
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: #6b7280;
            color: white;
            min-height: 50px; /* Match height */
        }

        .btn-secondary:hover {
            background: #4b5563;
            transform: translateY(-1px);
        }

        /* Update modal footer to give more space */
        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid #e5e7eb;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            border-radius: 0 0 20px 20px;
            background: #f8fafc;
            flex-wrap: wrap; /* Allow wrapping on smaller screens */
        }

        .session-option {
            padding: 1.25rem;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 0.75rem;
            background: #fafafa;
        }

        .session-option:hover {
            border-color: #667eea;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            transform: translateY(-1px);
        }

        .session-option.selected {
            border-color: #667eea;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.2);
        }

        .implementer-info {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 2px solid #3b82f6;
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        .legend-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            padding: 1.5rem;
            margin-top: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .nav-button {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            padding: 0.75rem;
            color: #374151;
            font-weight: 600;
            transition: all 0.2s;
        }

        .nav-button:hover {
            background: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .month-title {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .modal-container.max-w-2xl {
            max-width: 42rem;
        }

        .progress-step {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }

        .progress-step.active {
            background: #3b82f6;
            font-weight: 600;
        }

        .resource-download {
            transition: all 0.2s ease;
        }

        .resource-download:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .modal-container {
            background: white;
            border-radius: 20px;
            width: 100%;
            max-width: 48rem;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: slideUp 0.3s ease-out;

            /* Hide scrollbar for webkit browsers (Chrome, Safari, Edge) */
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
        }

        .modal-container::-webkit-scrollbar {
            display: none; /* Safari and Chrome */
        }

        /* Also hide scrollbar for modal body if needed */
        .modal-body {
            padding: 2rem;
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
        }

        .modal-body::-webkit-scrollbar {
            display: none; /* Safari and Chrome */
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .btn:disabled:hover {
            transform: none !important;
            box-shadow: none !important;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        .animate-spin {
            animation: spin 1s linear infinite;
        }
    </style>

    <div class="calendar-container">
        <!-- Header Section -->
        <div class="calendar-header-section">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="mb-2 text-2xl font-bold text-gray-900">📅 Schedule Your Kick-Off Meeting</h2>
                    <p class="text-gray-600">
                        @if($hasExistingBooking)
                            Your kick-off meeting has been scheduled
                        @else
                            Select an available date to book your implementation session
                        @endif
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <button wire:click="previousMonth" class="nav-button">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>

                    <h3 class="text-xl font-semibold text-gray-700 min-w-[200px] text-center">
                        {{ $currentDate->format('F Y') }}
                    </h3>

                    <button wire:click="nextMonth" class="nav-button">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Existing Bookings -->
            @if($hasExistingBooking)
                <div class="existing-bookings">
                    <h4 class="flex items-center mb-4 text-lg font-semibold text-gray-800">
                        🎯 Your Scheduled Meetings
                    </h4>
                    @foreach($existingBookings as $booking)
                        <div class="booking-card">
                            <div class="flex items-center justify-between mb-2">
                                <div>
                                    <h5 class="font-semibold text-gray-800">📅 {{ $booking['date'] }}</h5>
                                    <p class="text-sm text-gray-600">🕒 {{ $booking['time'] }} ({{ $booking['session'] }})</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 gap-2 text-sm text-gray-600 md:grid-cols-2">
                                <div>👨‍💼 <strong>Implementer:</strong> {{ $booking['implementer'] }}</div>
                                <div>📍 <strong>Type:</strong> {{ $booking['appointment_type'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <!-- Assigned Implementer Info -->
            @if($assignedImplementer)
                <div class="implementer-info">
                    <div class="flex items-center justify-center mb-2">
                        @if($assignedImplementer['avatar_path'])
                            <img src="{{ asset('storage/' . $assignedImplementer['avatar_path']) }}"
                                 alt="{{ $assignedImplementer['name'] }}"
                                 class="w-12 h-12 mr-3 border-2 border-white rounded-full shadow-lg">
                        @else
                            <div class="flex items-center justify-center w-12 h-12 mr-3 font-semibold text-white bg-indigo-500 rounded-full">
                                {{ substr($assignedImplementer['name'], 0, 1) }}
                            </div>
                        @endif
                        <div>
                            <h4 class="font-semibold text-gray-800">Your Assigned Implementer</h4>
                            <p class="font-medium text-indigo-600">{{ $assignedImplementer['name'] }}</p>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600">All sessions will be scheduled with your dedicated implementer</p>
                </div>
            @else
                <div class="implementer-info bg-amber-50 border-amber-200">
                    <div class="text-center">
                        <div class="flex items-center justify-center w-12 h-12 mx-auto mb-2 rounded-full bg-amber-500">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h4 class="mb-1 font-semibold text-amber-800">No Implementer Assigned</h4>
                        <p class="text-sm text-amber-700">Please contact support to assign an implementer to your account</p>
                    </div>
                </div>
            @endif
        </div>

        <!-- Calendar Days Header -->
        <div class="calendar-days-header">
            <div class="header-day">Sun</div>
            <div class="header-day">Mon</div>
            <div class="header-day">Tue</div>
            <div class="header-day">Wed</div>
            <div class="header-day">Thu</div>
            <div class="header-day">Fri</div>
            <div class="header-day">Sat</div>
        </div>

        <!-- Calendar Grid -->
        <div class="calendar-grid">
            @foreach($monthlyData as $dayData)
                <div class="calendar-day
                    {{ !$dayData['isCurrentMonth'] ? 'other-month' : '' }}
                    {{ $dayData['isToday'] ? 'today' : '' }}
                    {{ $dayData['isPast'] ? 'past' : '' }}
                    {{ $dayData['isWeekend'] ? 'weekend' : '' }}
                    {{ $dayData['isPublicHoliday'] ? 'holiday' : '' }}
                    {{ $dayData['hasCustomerMeeting'] ? 'has-meeting' : '' }}
                    {{ $dayData['canBook'] ? 'bookable' : '' }}
                    {{ $hasExistingBooking && !$dayData['hasCustomerMeeting'] ? 'disabled' : '' }}"
                    @if($dayData['canBook'])
                        wire:click="openBookingModal('{{ $dayData['dateString'] }}')"
                    @endif>

                    <div class="day-number">{{ $dayData['day'] }}</div>

                    @if($dayData['hasCustomerMeeting'])
                        <div class="meeting-indicator">📅 Your Meeting</div>
                    @elseif($dayData['isPublicHoliday'])
                        <div class="text-xs font-semibold text-red-600">🏛️ Holiday</div>
                    @elseif($dayData['isWeekend'])
                        <div class="text-xs font-semibold text-amber-600">🎯 Weekend</div>
                    @elseif($dayData['isPast'])
                        <div class="text-xs text-gray-500">📅 Past</div>
                    @elseif($hasExistingBooking)
                        <div class="text-xs text-gray-400">🔒 Booking Complete</div>
                    @elseif($dayData['availableCount'] > 0)
                        <div class="available-count">✨ {{ $dayData['availableCount'] }} available</div>
                    @elseif($dayData['isCurrentMonth'])
                        <div class="text-xs font-medium text-red-500">🔒 Fully booked</div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Legend -->
        <div class="legend-container">
            <h4 class="mb-3 font-semibold text-gray-700">📋 Calendar Legend</h4>
            <div class="grid grid-cols-2 gap-4 text-sm md:grid-cols-5">
                <div class="flex items-center">
                    <div class="w-4 h-4 mr-2 border-2 border-green-500 rounded bg-gradient-to-br from-green-200 to-green-300"></div>
                    <span>Available</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 mr-2 border-2 border-indigo-500 rounded bg-gradient-to-br from-indigo-200 to-indigo-300"></div>
                    <span>Your Meeting</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 mr-2 rounded bg-gradient-to-br from-amber-100 to-amber-200"></div>
                    <span>Weekend</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 mr-2 rounded bg-gradient-to-br from-red-100 to-red-200"></div>
                    <span>Holiday</span>
                </div>
                <div class="flex items-center">
                    <div class="w-4 h-4 mr-2 bg-gray-200 rounded"></div>
                    <span>Unavailable</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal (same as before) -->
    @if($showBookingModal)
        <div class="modal-overlay">
            <div class="modal-container">
                <div class="modal-header">
                    <h3 class="mb-2 text-2xl font-bold">🚀 Book Your Kick-Off Meeting</h3>
                    <p class="text-indigo-100">{{ Carbon\Carbon::parse($selectedDate)->format('l, F j, Y') }}</p>
                </div>

                <div class="modal-body">
                    <!-- Available Sessions -->
                    <div class="form-group">
                        <label class="form-label">⏰ Select Available Session</label>
                        @foreach($availableSessions as $index => $session)
                            <div class="session-option {{ $selectedSession && $selectedSession['session_name'] === $session['session_name'] ? 'selected' : '' }}"
                                wire:click="selectSession({{ $index }})">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="font-semibold text-gray-800">{{ $session['session_name'] }}</div>
                                        <div class="text-sm font-medium text-gray-600">🕒 {{ $session['formatted_time'] }}</div>
                                    </div>
                                    <div class="text-sm font-medium text-indigo-600">
                                        👨‍💼 with {{ $session['implementer_name'] }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Appointment Type -->
                    <div class="form-group">
                        <label for="appointmentType" class="form-label">📱 Meeting Type <span class="text-red-600">*</span></label>
                        <select wire:model="appointmentType" id="appointmentType" class="form-select">
                            <option value="ONLINE">💻 Online Meeting (Teams/Zoom)</option>
                        </select>
                    </div>

                    <!-- Required Attendees -->
                    <div class="form-group">
                        <label for="requiredAttendees" class="form-label">👥 Required Attendees <span class="text-red-600">*</span></label>
                        <input type="text" wire:model="requiredAttendees" id="requiredAttendees" class="form-input"
                            placeholder="john@example.com;jane@example.com">
                        <p class="mt-2 text-xs text-gray-500">📝 Separate multiple emails with semicolons (;)</p>
                    </div>

                    <!-- Remarks -->
                    <div class="form-group">
                        <label for="remarks" class="form-label">💬 Additional Notes (Optional)</label>
                        <textarea wire:model="remarks" id="remarks" class="form-textarea" rows="4"
                            placeholder="Any special requirements, agenda items, or notes for your implementer..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button wire:click="submitBooking" class="btn btn-primary"
                        {{ !$selectedSession ? 'disabled' : '' }}
                        wire:loading.attr="disabled" wire:target="submitBooking">
                        <span wire:loading.remove wire:target="submitBooking">📨 Submit Booking Request</span>
                        <span wire:loading wire:target="submitBooking" class="flex items-center">
                            <svg class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Processing...
                        </span>
                    </button>
                    <button wire:click="closeBookingModal" class="btn btn-secondary"
                        wire:loading.attr="disabled" wire:target="submitBooking">
                        ❌ Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Success Modal -->
    @if($showSuccessModal && $submittedBooking)
        <div class="modal-overlay">
            <div class="max-w-2xl modal-container">
                <!-- Header with TimeTec branding -->
                <div class="text-center modal-header">
                    <div class="mb-6">
                        <!-- TimeTec logo or branding -->
                        <div class="flex justify-center mb-4">
                            <div class="p-3 bg-white rounded-full shadow-lg">
                                <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                        </div>
                        <h1 class="mb-2 text-3xl font-bold text-white">Welcome to</h1>
                        <div class="text-4xl font-bold text-white">
                            time<span class="text-blue-300">Tec</span>
                        </div>
                    </div>
                </div>

                <div class="text-center modal-body">
                    <!-- Success Content -->
                    <div class="mb-8">
                        <h2 class="mb-4 text-3xl font-bold text-green-600">Booking Submitted!</h2>
                        <p class="mb-6 text-lg text-gray-600">
                            Your kick-off meeting request has been submitted successfully. You'll receive an email for appointment details soon.
                        </p>
                    </div>

                    <!-- Booking Details Card -->
                    <div class="p-6 mb-6 border-2 border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl">
                        <h3 class="mb-4 text-lg font-semibold text-gray-800">📅 Your Booking Details</h3>
                        <div class="grid grid-cols-1 gap-4 text-sm md:grid-cols-2">
                            <div class="text-left">
                                <div class="font-medium text-gray-600">Booking ID</div>
                                <div class="font-bold text-gray-800">#{{ $submittedBooking['id'] }}</div>
                            </div>
                            <div class="text-left">
                                <div class="font-medium text-gray-600">Submitted At</div>
                                <div class="font-bold text-gray-800">{{ $submittedBooking['submitted_at'] }}</div>
                            </div>
                            <div class="text-left">
                                <div class="font-medium text-gray-600">Date & Time</div>
                                <div class="font-bold text-gray-800">{{ $submittedBooking['date'] }}</div>
                                <div class="font-bold text-indigo-600">{{ $submittedBooking['time'] }}</div>
                            </div>
                            <div class="text-left">
                                <div class="font-medium text-gray-600">Session & Implementer</div>
                                <div class="font-bold text-gray-800">{{ $submittedBooking['session'] }}</div>
                                <div class="font-bold text-indigo-600">{{ $submittedBooking['implementer'] }}</div>
                            </div>
                        </div>

                        @if($submittedBooking['has_teams'])
                        <div class="p-3 mt-4 border border-green-200 rounded-lg bg-green-50">
                            <div class="flex items-center justify-center text-green-700">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="font-medium">Microsoft Teams meeting created</span>
                            </div>
                            <p class="mt-1 text-sm text-green-600">Meeting link will be included in your confirmation email</p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer bg-gray-50">
                    <div class="flex flex-col w-full gap-3 sm:flex-row">
                        <a href="{{ route('customer.dashboard') }}"
                        class="flex-1 px-6 py-3 font-semibold text-center text-white transition-all duration-200 transform rounded-lg bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 hover:scale-105">
                            🏠 Go to Dashboard
                        </a>
                        <button wire:click="closeSuccessModal"
                                class="flex-1 px-6 py-3 font-semibold text-white transition-colors bg-gray-500 rounded-lg hover:bg-gray-600">
                            📅 View Calendar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
