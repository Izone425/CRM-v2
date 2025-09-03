<style>
    .modal {
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.25);
    }

    .modal-content {
        background-color: #fff;
        margin: 15% auto;
        padding: 20px;
        border-radius: 10px;
        width: 80%;
        max-width: 500px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .close-button {
        float: right;
        font-size: 1.5rem;
        font-weight: bold;
        cursor: pointer;
    }

    .hover-text {
        color: #3b82f6;
        transition: color 0.3s;
    }

    .hover-text:hover {
        color: black;
    }

    .appointment-detail {
        padding-bottom: 8px;
        font-size: 14px;
        line-height: 1.5;
    }

    .appointment-detail:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: bold;
        color: #374151;
        display: inline;
    }

    .detail-value {
        display: inline;
    }

    .time-badge {
        display: inline-block;
        background-color: #e5e7eb;
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 500;
        margin-left: 4px;
    }

    .view-remarks-link {
        cursor: pointer;
        color: #3b82f6;
        text-decoration: underline;
        font-weight: bold;
        transition: color 0.2s ease;
    }

    .view-remarks-link:hover {
        color: #1d4ed8;
    }

    .remarks-modal {
        position: fixed;
        z-index: 1100;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .remarks-content {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        width: 80%;
        max-width: 600px;
        max-height: 80vh;
        overflow-y: auto;
    }
</style>

<div class="modal">
    <div class="modal-content">
        <span class="close-button" wire:click="$set('modalOpen',false)">&times;</span>
        <br><br>
        @foreach ($modalArray as $value)
            @if(isset($value['is_internal_task']) && $value['is_internal_task'])
                <div class="appointment-detail">
                    <div class="detail-label">DEMO TYPE:</div>
                    <div class="detail-value">{{ $value['type'] ?? 'INTERNAL TASK' }}</div>
                </div>

                <div class="appointment-detail">
                    <div class="detail-label">APPOINTMENT TYPE:</div>
                    <div class="detail-value">{{ $value['appointment_type'] ?? 'ONSITE' }}</div>
                </div>

                @if(isset($value['remarks']) && !empty($value['remarks']))
                <div class="appointment-detail" x-data="{ showRemarks: false }">
                    <div class="detail-label">REMARKS:</div>
                    <div class="detail-value">
                        <span class="view-remarks-link" @click="showRemarks = true">VIEW REMARK</span>

                        <template x-if="showRemarks">
                            <div class="remarks-modal" @click.self="showRemarks = false">
                                <div class="remarks-content">
                                    <h3 class="mb-4 text-lg font-bold">{{ $value['type'] }} Remarks</h3>
                                    <div class="whitespace-pre-line">{!! nl2br(e($value['remarks'])) !!}</div>
                                    <div class="mt-4 text-center">
                                        <button @click="showRemarks = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
                @endif

                <div class="appointment-detail">
                    <div class="detail-value time-badge">
                        <div class="detail-label">TIME:</div>
                        @php
                            $startTime = isset($value['start_time']) ? \Carbon\Carbon::parse($value['start_time'])->format('g:i A') : '00:00 AM';
                            $endTime = isset($value['end_time']) ? \Carbon\Carbon::parse($value['end_time'])->format('g:i A') : '00:00 AM';
                        @endphp
                        {{ $startTime }} - {{ $endTime }}
                    </div>
                </div>
            @else
                <div class="appointment-detail">
                    @if(count($modalArray) == 1)
                        <!-- Only one company - show "COMPANY:" label -->
                        <div class="detail-label">COMPANY:</div>
                        <div class="detail-value hover-text" style="display: inline;">
                            <a target="_blank" rel="noopener noreferrer" href={{ $modalArray[0]['url'] ?? '#' }}>
                                {{ $modalArray[0]['company_name'] ?? 'N/A' }}
                            </a>
                        </div>
                    @else
                        <!-- Multiple companies - use numbered companies -->
                        @foreach ($modalArray as $index => $company)
                            @if ($loop->first)
                                <div class="detail-value hover-text" style="display: inline;">
                                    <span style="color: #374151; font-weight: bold;">COMPANY 1:</span>
                                    <a target="_blank" rel="noopener noreferrer" href={{ $company['url'] ?? '#' }}>
                                        {{ $company['company_name'] ?? 'N/A' }}
                                    </a>
                                </div>
                            @else
                                <div style="margin-left: 0px; margin-top: 5px;" class="hover-text">
                                    <span style="color: #374151; font-weight: bold;">COMPANY {{ $loop->iteration }}:</span>
                                    <a target="_blank" rel="noopener noreferrer" href={{ $company['url'] ?? '#' }}>
                                        {{ $company['company_name'] ?? 'N/A' }}
                                    </a>
                                </div>
                            @endif
                        @endforeach
                    @endif
                </div>

                <div class="appointment-detail">
                    <div class="detail-label">DEMO TYPE:</div>
                    <div class="detail-value">{{ $value['type'] ?? 'NEW DEMO' }}</div>
                </div>

                <div class="appointment-detail">
                    <div class="detail-label">APPOINTMENT TYPE:</div>
                    <div class="detail-value">{{ $value['appointment_type'] ?? 'ONLINE' }}</div>
                </div>

                @if(isset($value['is_internal_task']) && $value['is_internal_task'])
                    <div class="appointment-detail" x-data="{ showRemarks: false }">
                        <div class="detail-label">REMARKS:</div>
                        <div class="detail-value">
                            <span class="view-remarks-link" @click="showRemarks = true">VIEW REMARK</span>

                            <template x-if="showRemarks">
                                <div class="remarks-modal" @click.self="showRemarks = false">
                                    <div class="remarks-content">
                                        <h3 class="mb-4 text-lg font-bold">{{ $value['type'] }} Remarks</h3>
                                        <div class="whitespace-pre-line">{!! nl2br(e($value['remarks'])) !!}</div>
                                        <div class="mt-4 text-center">
                                            <button @click="showRemarks = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                                Close
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                @endif

                <div class="appointment-detail">
                    <div class="detail-label">TIME:</div>
                    <div class="detail-value time-badge">
                        @php
                            $startTime = isset($value['start_time']) ? \Carbon\Carbon::parse($value['start_time'])->format('g:i A') : '00:00 AM';
                            $endTime = isset($value['end_time']) ? \Carbon\Carbon::parse($value['end_time'])->format('g:i A') : '00:00 AM';
                        @endphp
                         {{ $startTime }} - {{ $endTime }}
                    </div>
                </div>
            @endif
            @break
        @endforeach
    </div>
</div>
