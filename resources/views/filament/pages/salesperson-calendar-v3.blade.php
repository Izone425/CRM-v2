<!-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/salesperson-calendar-v3.blade.php -->
<x-filament-panels::page>
    <div>
        <!-- Navigation and Legend -->
        <div style="display:flex; flex-direction: row; margin-bottom:1rem; margin-left:2rem; align-items: center;">
            <!-- Date Navigation -->
            <div style="margin-right: 2rem;">
                <button class="hover-button {{ $disablePrevDay ? 'disabled' : '' }}"
                        wire:click="prevDay"
                        style="margin-inline:1rem"
                        {{ $disablePrevDay ? 'disabled' : '' }}>
                    <i class="fa-solid fa-chevron-left"></i>
                </button>
                <span style="font-weight: bold; margin: 0 1rem;">
                    {{ Carbon\Carbon::parse($selectedDate)->format('l, d M Y') }}
                </span>
                <button class="hover-button" wire:click="nextDay" style="margin-inline:1rem">
                    <i class="fa-solid fa-chevron-right"></i>
                </button>
            </div>

            <!-- Legend -->
            <div style="display:flex;margin-inline: 1rem">
                <div style="width: 30px;background-color: #71eb71;margin-right:0.5rem"></div>
                <div>NEW DEMO</div>
            </div>

            <div style="display:flex;margin-inline: 1rem">
                <div style="width: 30px;background-color:#ffff5cbf;margin-right:0.5rem"></div>
                <div>WEBINAR DEMO</div>
            </div>

            <div style="display:flex;margin-inline: 1rem">
                <div style="width: 30px;background-color:#f86f6f;margin-right:0.5rem"></div>
                <div>OTHERS DEMO</div>
            </div>

            <div style="display:flex;margin-inline: 1rem">
                <div style="width: 30px;background-color:#3b82f6;margin-right:0.5rem"></div>
                <div>INTERNAL SALES TASK</div>
            </div>

            <div style="display:flex;margin-inline: 1rem">
                <div style="width:30px;height:100%;background-color:grey;margin-right:0.5rem"></div>
                <div>ON LEAVE & PUBLIC HOLIDAY</div>
            </div>
        </div>

        <!-- Daily Calendar Table -->
        <div style="margin: 2rem;">
            <style>
                .daily-table {
                    width: 100%;
                    border-collapse: collapse;
                    border: 2px solid #ddd;
                    font-family: Arial, sans-serif;
                }

                .daily-table th,
                .daily-table td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: center;
                    vertical-align: middle;
                }

                .daily-table th {
                    background-color: #f2f2f2;
                    font-weight: bold;
                }

                .daily-table .salesperson-name {
                    background-color: #dff0f7;
                    font-weight: bold;
                    text-align: left;
                    padding-left: 15px;
                }

                .session-cell {
                    min-width: 120px;
                    height: 40px;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .session-cell:hover {
                    filter: brightness(0.9);
                }

                .session-cell.new-demo {
                    background-color: #71eb71;
                }

                .session-cell.webinar-demo {
                    background-color: #ffff5cbf;
                }

                .session-cell.internal-sales-task {
                    background-color: #3b82f6;
                    color: white;
                }

                .session-cell.others {
                    background-color: #f86f6f;
                }

                .session-cell.leave {
                    background-color: #bfbbbb;
                    color: black;
                    cursor: default;
                }

                .session-cell.holiday {
                    background-color: #bfbbbb;
                    color: black;
                    font-size: 0.8rem;
                    cursor: default;
                }

                .session-cell.past {
                    background-color: #474747;
                    color: white;
                    cursor: default;
                }

                .session-cell.empty {
                    background-color: white;
                    cursor: default;
                }

                .hover-button {
                    color: black;
                    transition: color 0.3s;
                    background: none;
                    border: none;
                    font-size: 1.2rem;
                    padding: 0.5rem;
                }

                .hover-button:hover {
                    color: #3b82f6;
                }

                .hover-button.disabled {
                    color: #888888;
                    cursor: not-allowed;
                }

                @media screen and (max-width: 1400px) {
                    .daily-table {
                        font-size: 0.8rem;
                    }

                    .session-cell {
                        min-width: 100px;
                        height: 35px;
                    }
                }

                .summary-cell {
                    min-width: 120px;
                    height: 40px;
                    font-weight: bold;
                    text-align: center;
                    vertical-align: middle;
                }

                .summary-cell.has-demo {
                    background-color: #ef4444; /* Red */
                    color: white;
                }

                .summary-cell.all-free {
                    background-color: #22c55e; /* Green */
                    color: white;
                }

                .summary-cell.mixed {
                    background-color: #6b7280; /* Gray */
                    color: white;
                }
            </style>

            <table class="daily-table">
                <thead>
                    <tr>
                        <th style="width: 200px;">Date: {{ Carbon\Carbon::parse($selectedDate)->format('d M Y') }}</th>
                        <th>Session 1</th>
                        <th>Session 2</th>
                        <th>Session 3</th>
                        <th>Session 4</th>
                    </tr>
                    <tr>
                        <th class="salesperson-name">Summary</th>
                        @php $summary = $this->calculateSummary(); @endphp
                        @foreach([1, 2, 3, 4] as $sessionNumber)
                            @php $sessionSummary = $summary[$sessionNumber]; @endphp
                            <td class="summary-cell {{ $sessionSummary['type'] }}">
                                {{ $sessionSummary['label'] }}
                            </td>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($salespeopleData as $person)
                        <tr>
                            <td class="salesperson-name">{{ $person['name'] }}</td>
                            @foreach([1, 2, 3, 4] as $sessionNumber)
                                @php
                                    $session = $person['sessions'][$sessionNumber];
                                    $cssClass = $session['type'];
                                    $isClickable = isset($session['appointment']);
                                @endphp
                                <td class="session-cell {{ $cssClass }}"
                                    @if($isClickable)
                                        wire:click="openModal({{ $person['id'] }}, {{ json_encode($session) }})"
                                    @endif>
                                    {{ $session['label'] }}
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Modal (you can reuse your existing modal component) -->
        @if($modalOpen)
            <div class="modal-overlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;">
                <div class="modal-content" style="background: white; padding: 2rem; border-radius: 8px; max-width: 80%; max-height: 80%; overflow-y: auto;">
                    <div style="display: flex; justify-content: between; align-items: center; margin-bottom: 1rem;">
                        <h3>Appointment Details</h3>
                        <button wire:click="closeModal" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
                    </div>

                    @foreach($modalArray as $appointment)
                        <div style="border: 1px solid #ddd; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
                            <p><strong>Type:</strong> {{ $appointment['type'] ?? 'N/A' }}</p>
                            <p><strong>Status:</strong> {{ $appointment['status'] ?? 'N/A' }}</p>
                            <p><strong>Date:</strong> {{ $appointment['date'] ?? 'N/A' }}</p>
                            <p><strong>Time:</strong> {{ $appointment['start_time'] ?? 'N/A' }} - {{ $appointment['end_time'] ?? 'N/A' }}</p>

                            @if(!$appointment['is_internal_task'])
                                <p><strong>Company:</strong> {{ $appointment['company_name'] ?? 'N/A' }}</p>
                                @if(isset($appointment['url']))
                                    <a href="{{ $appointment['url'] }}" target="_blank" style="color: #3b82f6; text-decoration: underline;">View Lead Details</a>
                                @endif
                            @endif

                            @if(isset($appointment['remarks']) && $appointment['remarks'])
                                <p><strong>Remarks:</strong> {{ $appointment['remarks'] }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
