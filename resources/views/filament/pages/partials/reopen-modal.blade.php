{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/partials/reopen-modal.blade.php --}}
<div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 60; display: flex; align-items: center; justify-content: center;"
     wire:click="closeReopenModal">
    <div style="background: white; border-radius: 16px; width: 100%; max-width: 700px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);"
         wire:click.stop>

        <!-- Modal Header -->
        <div style="padding: 24px; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #FEF3C7 0%, #FEF3C7 100%);">
            <div style="display: flex; align-items: center; gap: 16px;">
                <div style="background: white; padding: 12px; border-radius: 50%; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);">
                    <svg style="width: 24px; height: 24px; color: #D97706;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                </div>
                <div>
                    <h2 style="font-size: 20px; font-weight: 700; color: #111827; margin: 0;">
                        Reopen Ticket
                    </h2>
                    <p style="font-size: 14px; color: #6B7280; margin: 4px 0 0 0;">
                        {{ $selectedTicket->ticket_id }} - {{ $selectedTicket->title }}
                    </p>
                </div>
            </div>
            <button wire:click="closeReopenModal"
                    style="background: transparent; border: none; color: #9CA3AF; cursor: pointer; font-size: 24px; padding: 8px; border-radius: 8px; transition: all 0.2s;"
                    onmouseover="this.style.background='rgba(0,0,0,0.1)'"
                    onmouseout="this.style.background='transparent'">
                ✕
            </button>
        </div>

        <!-- Modal Body -->
        <div style="flex: 1; overflow-y: auto; padding: 24px;">

            <!-- Info Alert -->
            <div style="background: #FEF3C7; border: 1px solid #FDE047; border-radius: 8px; padding: 16px; margin-bottom: 24px; display: flex; align-items: start; gap: 12px;">
                <svg style="width: 20px; height: 20px; color: #D97706; flex-shrink: 0; margin-top: 2px;" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                </svg>
                <div>
                    <h4 style="font-size: 14px; font-weight: 600; color: #92400E; margin: 0 0 4px 0;">Reopening Ticket</h4>
                    <p style="font-size: 13px; color: #92400E; margin: 0; line-height: 1.5;">
                        This ticket will be reopened and its status will change from "{{ $selectedTicket->status }}" to "Reopen".
                        You can optionally add a comment and attachments to explain the reason for reopening.
                    </p>
                </div>
            </div>

            <!-- Comment Section -->
            <div style="margin-bottom: 24px;">
                <label style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                    Reason for Reopening (Optional)
                </label>

                <textarea
                    wire:model="reopenComment"
                    placeholder="Explain why you are reopening this ticket..."
                    rows="4"
                    style="width: 100%; padding: 12px; border: 1px solid #D1D5DB; border-radius: 8px; font-size: 14px; line-height: 1.5; resize: vertical; background: white;"
                ></textarea>

                @error('reopenComment')
                    <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>

            <!-- File Upload Section -->
            <div style="margin-bottom: 24px;">
                <label style="font-size: 14px; font-weight: 600; color: #374151; margin-bottom: 8px; display: block;">
                    Attachments (Optional)
                </label>

                <div style="border: 2px dashed #D1D5DB; border-radius: 8px; padding: 20px; text-align: center; background: #F9FAFB;">
                    <input type="file"
                           id="reopen-file-input"
                           wire:model="reopenAttachments"
                           multiple
                           style="display: none;"
                           accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.gif,.zip,.txt,.csv">

                    <label for="reopen-file-input"
                           style="display: inline-flex; flex-direction: column; align-items: center; gap: 8px; cursor: pointer; padding: 16px; border-radius: 8px; transition: all 0.2s;">
                        <svg style="width: 32px; height: 32px; color: #9CA3AF;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                        </svg>
                        <span style="font-size: 14px; font-weight: 600; color: #374151;">Choose Files</span>
                        <span style="font-size: 12px; color: #6B7280;">or drag and drop files here</span>
                        <span style="font-size: 11px; color: #9CA3AF;">Maximum file size: 10MB per file</span>
                    </label>
                </div>

                @if(!empty($reopenAttachments))
                    <div style="margin-top: 12px;">
                        <h4 style="font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 8px;">Selected Files:</h4>
                        @foreach($reopenAttachments as $index => $file)
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; background: #EEF2FF; border: 1px solid #C7D2FE; border-radius: 6px; margin-bottom: 4px;">
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <svg style="width: 16px; height: 16px; color: #6366F1;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <span style="font-size: 13px; color: #374151;">{{ $file->getClientOriginalName() }}</span>
                                    <span style="font-size: 11px; color: #6B7280;">({{ number_format($file->getSize() / 1024, 1) }} KB)</span>
                                </div>
                                <button type="button"
                                        wire:click="$set('reopenAttachments.{{ $index }}', null)"
                                        style="background: transparent; border: none; color: #EF4444; cursor: pointer; font-size: 18px; padding: 4px; border-radius: 4px;"
                                        title="Remove file">
                                    ✕
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                @error('reopenAttachments.*')
                    <div style="color: #DC2626; font-size: 12px; margin-top: 4px;">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Modal Footer -->
        <div style="padding: 24px; border-top: 1px solid #E5E7EB; background: #F9FAFB; display: flex; justify-content: space-between; align-items: center;">
            <button wire:click="closeReopenModal"
                    style="padding: 12px 24px; background: white; border: 1px solid #D1D5DB; border-radius: 8px; font-weight: 600; color: #374151; cursor: pointer; transition: all 0.2s;"
                    onmouseover="this.style.background='#F3F4F6'; this.style.borderColor='#9CA3AF'"
                    onmouseout="this.style.background='white'; this.style.borderColor='#D1D5DB'">
                Cancel
            </button>

            <button wire:click="reopenTicket"
                    style="padding: 12px 24px; background: #D97706; border: 1px solid #D97706; border-radius: 8px; font-weight: 600; color: white; cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 8px;"
                    onmouseover="this.style.background='#B45309'; this.style.borderColor='#B45309'"
                    onmouseout="this.style.background='#D97706'; this.style.borderColor='#D97706'">
                <svg style="width: 16px; height: 16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Reopen Ticket
            </button>
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* File upload hover effects */
    #reopen-file-input + label:hover {
        border-color: #6366F1 !important;
        background: #EEF2FF !important;
    }

    #reopen-file-input + label:hover svg {
        color: #6366F1 !important;
    }
</style>
