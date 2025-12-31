<x-filament-panels::page>
    <div class="training-request-container">
        {{-- STEP 1 & 2: Choose Trainer and Year --}}
        <div class="selection-header">
            <div class="selection-section">
                <h3 class="section-title">🎓 Step 1: Choose Trainer</h3>
                <div class="trainer-selection">
                    @foreach($trainers as $key => $label)
                        <label class="trainer-option {{ $selectedTrainer === $key ? 'selected' : '' }}">
                            <input type="radio" wire:model.live="selectedTrainer" value="{{ $key }}" class="trainer-input">
                            <span class="trainer-text">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <div class="selection-section">
                <h3 class="section-title">📅 Step 2: Choose Year</h3>
                <div class="year-selection">
                    @foreach($years as $year)
                        <label class="year-option {{ $selectedYear === $year ? 'selected' : '' }}">
                            <input type="radio" wire:model.live="selectedYear" value="{{ $year }}" class="year-input">
                            <span class="year-text">{{ $year }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- STEP 3: Training Sessions Display --}}
        @if($showSessions)
            <div class="sessions-container">
                <h2 class="sessions-title">🎯 Step 3: Choose Training Session</h2>

                <div class="legend">
                    <h4>Color Legend:</h4>
                    <div class="legend-items">
                        <div class="legend-item">
                            <div class="color-indicator past"></div>
                            <span>Grey - Past Date (Not Available)</span>
                        </div>
                        <div class="legend-item">
                            <div class="color-indicator available"></div>
                            <span>Green - Future Date (Available for Request)</span>
                        </div>
                    </div>
                </div>

                <div class="sessions-grid">
                    @foreach($this->trainingSessions as $sessionData)
                        @php $session = $sessionData['session']; @endphp
                        <div class="session-card status-{{ $sessionData['status'] }}">
                            <div class="session-header" wire:click="toggleSession({{ $session->id }})">
                                <div class="session-info">
                                    <h4 class="session-title">{{ $session->session_number }}</h4>
                                    <div class="session-dates">
                                        <div class="day-info">
                                            <span class="day-label">Day 1</span>
                                            <span class="day-detail">{{ \Carbon\Carbon::parse($session->day1_date)->format('j F Y') }} / {{ \Carbon\Carbon::parse($session->day1_date)->format('l') }}</span>
                                        </div>
                                        <div class="day-info">
                                            <span class="day-label">Day 2</span>
                                            <span class="day-detail">{{ \Carbon\Carbon::parse($session->day2_date)->format('j F Y') }} / {{ \Carbon\Carbon::parse($session->day2_date)->format('l') }}</span>
                                        </div>
                                        <div class="day-info">
                                            <span class="day-label">Day 3</span>
                                            <span class="day-detail">{{ \Carbon\Carbon::parse($session->day3_date)->format('j F Y') }} / {{ \Carbon\Carbon::parse($session->day3_date)->format('l') }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="session-counts">
                                    @if($sessionData['training_category'] === 'HRDF_WEBINAR')
                                        <div class="count-item">
                                            <span class="count-label">Combined Slot:</span>
                                            <span class="count-value">{{ $sessionData['hrdf_count'] + $sessionData['webinar_count'] }}/100 (50 HRDF + 50 WEBINAR)</span>
                                        </div>
                                        <div class="count-item">
                                            <span class="count-label">HRDF Slot:</span>
                                            <span class="count-value">{{ $sessionData['hrdf_count'] }}/{{ $sessionData['hrdf_limit'] }}</span>
                                        </div>
                                        <div class="count-item">
                                            <span class="count-label">Webinar Slot:</span>
                                            <span class="count-value">{{ $sessionData['webinar_count'] }}/{{ $sessionData['webinar_limit'] }}</span>
                                        </div>
                                    @else
                                        <div class="count-item">
                                            <span class="count-label">HRDF Slot:</span>
                                            <span class="count-value">{{ $sessionData['hrdf_count'] }}/{{ $sessionData['hrdf_limit'] }}</span>
                                        </div>
                                        <div class="count-item">
                                            <span class="count-label">Webinar Slot:</span>
                                            <span class="count-value">{{ $sessionData['webinar_count'] }}/{{ $sessionData['webinar_limit'] }}</span>
                                        </div>
                                    @endif
                                </div>

                                <div class="session-actions">
                                    @if($sessionData['status'] === 'available' && $this->hasCompleteMeetingLinks($session))
                                        <button wire:click.stop="showAddRequestModal({{ $session->id }})" class="btn btn-request">
                                            ➕ Add Training Request
                                        </button>
                                    @elseif($sessionData['status'] === 'available' && !$this->hasCompleteMeetingLinks($session))
                                        <div class="no-meeting-link">
                                            <span>⚠️ Missing Meeting Links</span>
                                        </div>
                                    @endif

                                    <div class="expand-icon {{ $sessionData['is_expanded'] ? 'expanded' : '' }}">
                                        <i>▼</i>
                                    </div>
                                </div>
                            </div>

                            {{-- STEP 4: Expanded Session Details --}}
                            @if($sessionData['is_expanded'])
                                <div class="session-details">
                                    @php $bookings = $this->getSessionBookings($session->id); @endphp

                                    @if($bookings->has('HRDF'))
                                        <div class="booking-section">
                                            <h5 class="booking-title">📚 Online HRDF Training Handover ID</h5>
                                            <div class="booking-table">
                                                <div class="table-header">
                                                    <div>Handover ID</div>
                                                    <div>Submitted By</div>
                                                    <div>Submitted Date & Time</div>
                                                    <div>Lead ID</div>
                                                    <div>Company Name</div>
                                                    <div>Participant Count</div>
                                                    <div>HRDF Status</div>
                                                    <div>Actions</div>
                                                </div>
                                                @foreach($bookings['HRDF'] as $booking)
                                                    <div class="table-row">
                                                        <div>{{ $booking->handover_id }}</div>
                                                        <div>{{ $booking->submitted_by }}</div>
                                                        <div>{{ $booking->submitted_at->format('j M Y h:i A') }}</div>
                                                        <div>{{ str_pad($booking->lead_id, 5, '0', STR_PAD_LEFT) }}</div>
                                                        <div>{{ $booking->company_name }}</div>
                                                        <div>{{ $booking->attendees->count() }} attendees</div>
                                                        <div>
                                                            <span class="status-badge status-{{ strtolower($booking->status) }}">
                                                                {{ $booking->status }}
                                                            </span>
                                                        </div>
                                                        <div>
                                                            @if($booking->submitted_by === auth()->user()->name || auth()->user()->role_id === 1)
                                                                <button wire:click="cancelRequest({{ $booking->id }})" class="btn-cancel">Cancel</button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if($bookings->has('WEBINAR'))
                                        <div class="booking-section">
                                            <h5 class="booking-title">🌐 Online Webinar Training Handover ID</h5>
                                            <div class="booking-table">
                                                <div class="table-header">
                                                    <div>Handover ID</div>
                                                    <div>Submitted By</div>
                                                    <div>Submitted Date & Time</div>
                                                    <div>Lead ID</div>
                                                    <div>Company Name</div>
                                                    <div>Participant Count</div>
                                                    <div>Actions</div>
                                                </div>
                                                @foreach($bookings['WEBINAR'] as $booking)
                                                    <div class="table-row">
                                                        <div>{{ $booking->handover_id }}</div>
                                                        <div>{{ $booking->submitted_by }}</div>
                                                        <div>{{ $booking->submitted_at->format('j M Y h:i A') }}</div>
                                                        <div>{{ str_pad($booking->lead_id, 5, '0', STR_PAD_LEFT) }}</div>
                                                        <div>{{ $booking->company_name }}</div>
                                                        <div>{{ $booking->attendees->count() }} attendees</div>
                                                        <div>
                                                            @if($booking->submitted_by === auth()->user()->name || auth()->user()->role_id === 1)
                                                                <button wire:click="cancelRequest({{ $booking->id }})" class="btn-cancel">Cancel</button>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if(!$bookings->count())
                                        <div class="no-bookings">
                                            <p>No training requests for this session yet.</p>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- STEP 5-10: Add Training Request Modal --}}
        @if($showRequestModal)
            <div class="modal-overlay">
                <div class="modal-container request-modal">
                    <div class="modal-header">
                        <h3>🎯 Add Training Request</h3>
                        <button wire:click="closeRequestModal" class="modal-close">✕</button>
                    </div>

                    <div class="modal-body">
                        {{-- STEP 6: Choose Training Type --}}
                        @if(!$selectedTrainingType)
                            <div class="step-section">
                                <h4 class="step-title">Step 6: Choose Training Type</h4>
                                <div class="training-type-selection">
                                    @foreach($trainingTypes as $key => $label)
                                        <button wire:click="selectTrainingType('{{ $key }}')" class="training-type-btn">
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- STEP 7-9: Training Request Form --}}
                            <div class="step-section">
                                <h4 class="step-title">Step 7-9: {{ $trainingTypes[$selectedTrainingType] }} Request</h4>

                                {{-- Common Fields --}}
                                <div class="form-grid">
                                    <div class="form-group" style="display: none;">
                                        <label class="form-label">Submitted By</label>
                                        <input type="text" value="{{ auth()->user()->name }}" disabled class="form-input disabled">
                                    </div>

                                    {{-- HRDF Specific Fields --}}
                                    @if($selectedTrainingType === 'HRDF')
                                        <div class="form-group">
                                            <label class="form-label">HRDF Training Application Status</label>
                                            <select wire:model="hrdfStatus" class="form-select">
                                                @foreach($hrdfStatuses as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif

                                    {{-- Company Search --}}
                                    <div class="form-group full-width">
                                        <label class="form-label">Company Name / Lead ID Search</label>
                                        <input type="text" wire:model.live.debounce.500ms="companySearchTerm"
                                               placeholder="Search by Lead ID or Company Name..." class="form-input">

                                        @if($companySearchTerm && !$selectedLeadId)
                                            <div class="search-results">
                                                @foreach($this->searchCompanies() as $lead)
                                                    <div wire:click="selectLead({{ $lead->id }})" class="search-result-item">
                                                        <span class="lead-id">{{ str_pad($lead->id, 5, '0', STR_PAD_LEFT) }}</span>
                                                        <span class="company-name">{{ $lead->companyDetail->company_name ?? 'No Company' }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Webinar Specific Fields --}}
                                    @if($selectedTrainingType === 'WEBINAR')
                                        <div class="form-group">
                                            <label class="form-label">Training Category</label>
                                            <select wire:model="trainingCategory" class="form-select">
                                                <option value="">Select Category</option>
                                                @foreach($trainingCategories as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @endif
                                </div>

                                {{-- Attendees Section --}}
                                <div class="attendees-section">
                                    <div class="attendees-header">
                                        <h5 class="attendees-title">Training Attendees</h5>
                                        <button wire:click="addAttendee" type="button" class="btn-add-attendee">
                                            ➕ Add Attendee
                                        </button>
                                    </div>

                                    @foreach($attendees as $index => $attendee)
                                        <div class="attendee-card">
                                            <div class="attendee-header">
                                                <h6 class="attendee-number">Attendee {{ $index + 1 }}</h6>
                                                @if(count($attendees) > 1)
                                                    <button wire:click="removeAttendee({{ $index }})" type="button" class="btn-remove-attendee">
                                                        ❌ Remove
                                                    </button>
                                                @endif
                                            </div>

                                            <div class="attendee-grid">
                                                <div class="form-group">
                                                    <label class="form-label">Full Name *</label>
                                                    <input type="text" wire:model="attendees.{{ $index }}.name" class="form-input" placeholder="Attendee Full Name">
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Email Address *</label>
                                                    <input type="email" wire:model="attendees.{{ $index }}.email" class="form-input" placeholder="Attendee Email">
                                                </div>

                                                <div class="form-group">
                                                    <label class="form-label">Phone Number</label>
                                                    <input type="text" wire:model="attendees.{{ $index }}.phone" class="form-input" placeholder="Phone Number">
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button wire:click="closeRequestModal" class="btn btn-secondary">Cancel</button>
                        @if($selectedTrainingType)
                            <button wire:click="submitRequest" class="btn btn-primary">
                                🚀 Submit Request
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    </div>

    <style>
        .training-request-container {
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        /* Selection Header */
        .selection-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .selection-section {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .trainer-selection,
        .year-selection {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .trainer-option,
        .year-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            background: white;
        }

        .trainer-option:hover,
        .year-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .trainer-option.selected,
        .year-option.selected {
            border-color: #667eea;
            background: #667eea;
            color: white;
        }

        .trainer-input,
        .year-input {
            margin: 0;
        }

        /* Sessions Container */
        .sessions-container {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .sessions-title {
            font-size: 24px;
            font-weight: 800;
            color: #333;
            margin: 0 0 20px 0;
        }

        /* Legend */
        .legend {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
        }

        .legend h4 {
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: 600;
        }

        .legend-items {
            display: flex;
            gap: 25px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 13px;
        }

        .color-indicator {
            width: 20px;
            height: 20px;
            border-radius: 4px;
        }

        .color-indicator.past {
            background: linear-gradient(45deg, #6c757d, #5a6268);
        }

        .color-indicator.available {
            background: linear-gradient(45deg, #28a745, #20c997);
        }

        /* Sessions Grid */
        .sessions-grid {
            display: grid;
            gap: 20px;
        }

        .session-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .session-card.status-past {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-left: 5px solid #6c757d;
        }

        .session-card.status-available {
            background: linear-gradient(135deg, #f0fdf4, #dcfce7);
            border-left: 5px solid #16a34a;
        }

        .session-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.15);
        }

        .session-header {
            display: grid;
            grid-template-columns: 1fr auto auto;
            gap: 20px;
            padding: 20px;
            cursor: pointer;
            align-items: start;
        }

        .session-info {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .session-title {
            font-size: 20px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .session-dates {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .day-info {
            display: flex;
            gap: 10px;
            font-size: 14px;
        }

        .day-label {
            font-weight: 600;
            color: #666;
            min-width: 50px;
        }

        .day-detail {
            color: #333;
        }

        .session-counts {
            display: flex;
            flex-direction: column;
            gap: 8px;
            text-align: right;
        }

        .count-item {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .count-label {
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }

        .count-value {
            font-size: 16px;
            font-weight: 700;
            color: #333;
        }

        .session-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .btn-request {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-request:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }

        .no-meeting-link {
            background: #fef3c7;
            color: #92400e;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 600;
            text-align: center;
            border: 1px solid #fbbf24;
        }

        .expand-icon {
            font-size: 18px;
            color: #666;
            transition: transform 0.3s ease;
        }

        .expand-icon.expanded {
            transform: rotate(180deg);
        }

        /* Session Details */
        .session-details {
            border-top: 1px solid #e1e5e9;
            padding: 20px;
            background: rgba(255, 255, 255, 0.8);
        }

        .booking-section {
            margin-bottom: 25px;
        }

        .booking-section:last-child {
            margin-bottom: 0;
        }

        .booking-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin: 0 0 15px 0;
        }

        .booking-table {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            border: 1px solid #e1e5e9;
        }

        .table-header {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 10px;
            padding: 12px;
            background: #f8f9fa;
            font-weight: 600;
            font-size: 13px;
            border-bottom: 1px solid #e1e5e9;
        }

        .table-row {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 10px;
            padding: 12px;
            border-bottom: 1px solid #f1f3f4;
            font-size: 13px;
            align-items: center;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.status-booked,
        .status-badge.status-active {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.status-apply {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.status-cancelled {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
        }

        .no-bookings {
            text-align: center;
            padding: 30px;
            color: #666;
            font-style: italic;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .request-modal {
            width: 95%;
            max-width: 800px;
            max-height: 90vh;
            overflow: auto;
        }

        .modal-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px;
            border-bottom: 1px solid #e1e5e9;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }

        .modal-body {
            padding: 25px;
        }

        .step-section {
            margin-bottom: 25px;
        }

        .step-title {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin: 0 0 20px 0;
        }

        .training-type-selection {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .training-type-btn {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 15px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .training-type-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.3);
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }

        .form-input,
        .form-select {
            padding: 12px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-input.disabled {
            background: #f8f9fa;
            color: #666;
        }

        .search-results {
            border: 1px solid #e1e5e9;
            border-top: none;
            border-radius: 0 0 8px 8px;
            background: white;
            max-height: 200px;
            overflow-y: auto;
        }

        .search-result-item {
            display: flex;
            justify-content: space-between;
            padding: 12px;
            cursor: pointer;
            border-bottom: 1px solid #f1f3f4;
        }

        .search-result-item:hover {
            background: #f8f9fa;
        }

        .lead-id {
            font-weight: 600;
            color: #667eea;
        }

        .company-name {
            color: #333;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            padding: 20px 25px;
            border-top: 1px solid #e1e5e9;
        }

        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        /* Attendees Section */
        .attendees-section {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }

        .attendees-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .attendees-title {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .btn-add-attendee {
            background: linear-gradient(45deg, #28a745, #20c997);
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-add-attendee:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }

        .attendee-card {
            background: #f8f9fa;
            border: 1px solid #e1e5e9;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .attendee-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .attendee-number {
            font-size: 14px;
            font-weight: 700;
            color: #667eea;
            margin: 0;
        }

        .btn-remove-attendee {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border: none;
            border-radius: 4px;
            font-size: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-remove-attendee:hover {
            background: #c82333;
            transform: translateY(-1px);
        }

        .attendee-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .attendee-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .selection-header {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .session-header {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .session-counts {
                flex-direction: row;
                justify-content: space-between;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .table-header,
            .table-row {
                grid-template-columns: repeat(4, 1fr);
                font-size: 12px;
            }

            .table-header div:nth-child(n+5),
            .table-row div:nth-child(n+5) {
                display: none;
            }
        }
    </style>
</x-filament-panels::page>
