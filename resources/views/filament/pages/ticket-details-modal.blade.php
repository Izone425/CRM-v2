{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/ticket-details-modal.blade.php --}}
@php
    $record = $ticket ?? null;

    if (!$record) {
        echo 'No ticket found.';
        return;
    }

    // Get files arrays
    $attachmentFiles = $record->attachments ? (is_string($record->attachments) ? json_decode($record->attachments, true) : $record->attachments) : [];
    $adminAttachmentFiles = $record->admin_attachments ? (is_string($record->admin_attachments) ? json_decode($record->admin_attachments, true) : $record->admin_attachments) : [];
@endphp

<style>
    .ticket-container {
        border-radius: 0.5rem;
    }

    .ticket-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 768px) {
        .ticket-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .ticket-column {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .ticket-label {
        font-weight: 600;
        color: #1f2937;
    }

    .ticket-value {
        margin-left: 0.5rem;
        color: #374151;
    }

    .ticket-view-link {
        font-weight: 500;
        color: #2563eb;
        text-decoration: none;
        cursor: pointer;
    }

    .ticket-view-link:hover {
        text-decoration: underline;
    }

    .ticket-not-available {
        margin-left: 0.5rem;
        font-style: italic;
        color: #6b7280;
    }

    .ticket-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 0.5rem;
    }

    /* Modal Styles */
    .ticket-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
        overflow: auto;
        padding: 1rem;
    }

    .ticket-modal-content {
        position: relative;
        width: 100%;
        max-width: 35rem;
        padding: 1.5rem;
        margin: auto;
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        margin-top: 5rem;
        max-height: 80vh;
        overflow-y: auto;
    }

    .ticket-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .ticket-modal-title {
        font-size: 1.125rem;
        font-weight: 500;
        color: #111827;
    }

    .ticket-modal-close {
        color: #9ca3af;
        background-color: transparent;
        border: none;
        border-radius: 0.375rem;
        padding: 0.375rem;
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .ticket-modal-close:hover {
        background-color: #f3f4f6;
        color: #111827;
    }

    .ticket-modal-close svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .ticket-modal-body {
        padding: 1rem;
        border-radius: 0.5rem;
        background-color: #f9fafb;
        margin-bottom: 1rem;
    }

    .ticket-modal-text {
        color: #1f2937;
        line-height: 1.6;
    }
</style>

<div>
    <div class="ticket-info-item" style="margin-bottom: 1rem;">
        <span class="ticket-label">General Handover Details</span><br>
        <span class="ticket-label">Ticket ID:</span>
        <span class="ticket-value">{{ $record->formatted_ticket_id }}</span>
    </div>

    <div class="ticket-container" style="border: 0.1rem solid; padding: 1rem;">
        <div class="ticket-grid">
            <!-- Left Column -->
            <div class="ticket-column">
                <div class="ticket-info-item">
                    <span class="ticket-label">Created By:</span>
                    <span class="ticket-value">{{ $record->createdBy->name }}</span>
                </div>

                <div class="ticket-info-item">
                    <span class="ticket-label">Created Date & Time:</span>
                    <span class="ticket-value">{{ $record->created_at->format('d/m/Y H:i') }}</span>
                </div>

                <div class="ticket-info-item">
                    <span class="ticket-label">Attention To:</span>
                    <span class="ticket-value">{{ $record->attentionTo->name }}</span>
                </div>

                <div class="ticket-info-item">
                    <span class="ticket-label">Status:</span>
                    <span class="ticket-value">{{ ucfirst(str_replace('_', ' ', $record->status)) }}</span>
                </div>

                <hr class="my-6 border-t border-gray-300">

                <div class="ticket-info-item">
                    <span class="ticket-section-title">Details by Owner Ticket</span>
                </div>

                <!-- Remark for User -->
                @if($record->remark)
                <div class="ticket-remark-container" x-data="{ remarkOpen: false }">
                    <span class="ticket-label">Remark:</span>
                    <a href="#" @click.prevent="remarkOpen = true" class="ticket-view-link">View</a>

                    <div x-show="remarkOpen" x-cloak x-transition @click.outside="remarkOpen = false" class="ticket-modal">
                        <div class="ticket-modal-content" @click.away="remarkOpen = false">
                            <div class="ticket-modal-header">
                                <h3 class="ticket-modal-title">User Remark</h3>
                                <button type="button" @click="remarkOpen = false" class="ticket-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="ticket-modal-body">
                                <div class="ticket-modal-text" style="white-space: pre-wrap; word-wrap: break-word;">{{ $record->remark }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="ticket-info-item">
                    <span class="ticket-label">Remark:</span>
                    <span class="ticket-not-available">Not Available</span>
                </div>
                @endif

                <!-- User Attachments -->
                @if(count($attachmentFiles) > 0)
                    @foreach($attachmentFiles as $index => $attachment)
                    <div class="ticket-info-item">
                        <span class="ticket-label">Attachment {{ $index + 1 }}:</span>
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($attachment) }}" target="_blank" class="ticket-view-link">View / Download</a>
                    </div>
                    @endforeach
                @endif
            </div>

            <!-- Right Column -->
            <div class="ticket-column">
                <div class="ticket-info-item">
                    <span class="ticket-label">Completed By:</span>
                    <span class="ticket-value">{{ $record->completedBy->name ?? '' }}</span>
                </div>

                <div class="ticket-info-item">
                    <span class="ticket-label">Completed Date & Time:</span>
                    <span class="ticket-value">{{ $record->completed_at ? $record->completed_at->format('d/m/Y H:i') : '' }}</span>
                </div>

                <div class="ticket-info-item">
                    <span class="ticket-label">Status:</span>
                    <span class="ticket-value">{{ $record->status === 'completed' ? 'Completed' : 'Pending' }}</span>
                </div>

                <div class="ticket-info-item">
                    <span class="ticket-label">Duration to Complete:</span>
                    <span class="ticket-value">
                        @if($record->duration_minutes)
                            @php
                                $hours = intval($record->duration_minutes / 60);
                                $minutes = $record->duration_minutes % 60;
                                $days = intval($hours / 24);
                                $remainingHours = $hours % 24;

                                $durationStr = '';
                                if ($days > 0) {
                                    $durationStr .= "{$days} day" . ($days > 1 ? 's' : '') . ' ';
                                }
                                if ($remainingHours > 0) {
                                    $durationStr .= "{$remainingHours} hour" . ($remainingHours > 1 ? 's' : '') . ' ';
                                }
                                if ($minutes > 0) {
                                    $durationStr .= "{$minutes} minute" . ($minutes > 1 ? 's' : '');
                                }
                                echo trim($durationStr) ?: '0 minutes';
                            @endphp
                        @endif
                    </span>
                </div>

                <hr class="my-6 border-t border-gray-300">

                @if($record->status === 'completed')
                <div class="ticket-info-item">
                    <span class="ticket-section-title">Details by Admin</span>
                </div>

                <!-- Admin Remark -->
                @if($record->admin_remark)
                <div class="ticket-remark-container" x-data="{ adminRemarkOpen: false }">
                    <span class="ticket-label">Remark:</span>
                    <a href="#" @click.prevent="adminRemarkOpen = true" class="ticket-view-link">View</a>

                    <div x-show="adminRemarkOpen" x-cloak x-transition @click.outside="adminRemarkOpen = false" class="ticket-modal">
                        <div class="ticket-modal-content" @click.away="adminRemarkOpen = false">
                            <div class="ticket-modal-header">
                                <h3 class="ticket-modal-title">Admin Remark</h3>
                                <button type="button" @click="adminRemarkOpen = false" class="ticket-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="ticket-modal-body">
                                <div class="ticket-modal-text">{{ $record->admin_remark }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="ticket-info-item">
                    <span class="ticket-label">Remark:</span>
                    <span class="ticket-not-available">Not Available</span>
                </div>
                @endif

                <!-- Admin Attachments -->
                @if(count($adminAttachmentFiles) > 0)
                    @foreach($adminAttachmentFiles as $index => $attachment)
                    <div class="ticket-info-item">
                        <span class="ticket-label">Attachment {{ $index + 1 }}:</span>
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($attachment) }}" target="_blank" class="ticket-view-link">View / Download</a>
                    </div>
                    @endforeach
                @endif
                @endif
            </div>
        </div>
    </div>
</div>
