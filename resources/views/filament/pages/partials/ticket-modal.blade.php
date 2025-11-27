{{-- filepath: /var/www/html/timeteccrm/resources/views/filament/pages/partials/ticket-modal.blade.php --}}
<div style="position: fixed; inset: 0; background: rgba(0, 0, 0, 0.5); z-index: 50; display: flex; align-items: center; justify-content: center;"
    wire:click="closeTicketModal">
    <div style="background: white; border-radius: 16px; width: 100%; max-width: 1150px; max-height: 90vh; overflow: hidden; display: flex; flex-direction: column;"
        wire:click.stop>

        <!-- Modal Header -->
        <div style="padding: 24px; border-bottom: 1px solid #E5E7EB; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="background: #FEF3C7; padding: 8px 12px; border-radius: 6px;">
                    <span style="color: #F59E0B; font-size: 14px; font-weight: 600;">📋 TICKET</span>
                </div>
                <h2 style="font-size: 18px; font-weight: 700; color: #111827; margin: 0;">
                    {{ $selectedTicket->ticket_id }}
                </h2>
            </div>
            <button wire:click="closeTicketModal" style="background: transparent; border: none; color: #9CA3AF; cursor: pointer; font-size: 24px;">
                ✕
            </button>
        </div>

        <!-- Modal Body -->
        <div style="flex: 1; overflow-y: auto; display: grid; grid-template-columns: 1fr 350px;">

            <!-- Left Side - Main Content -->
            <div style="padding: 24px; border-right: 1px solid #E5E7EB; overflow-y: auto;">
                <!-- Title -->
                <h1 style="font-size: 24px; font-weight: 700; color: #111827; margin: 0 0 24px 0;">
                    {{ $selectedTicket->title }}
                </h1>

                <!-- Description Section -->
                <div style="margin-bottom: 24px;">
                    <div style="font-size: 14px; font-weight: 600; color: #6B7280; margin-bottom: 12px;">Description</div>
                    <div style="background: #f7f7fe; padding: 16px; border-radius: 8px; border: 1px solid #E5E7EB; line-height: 1.6; color: #374151;">
                        {!! $selectedTicket->description ?? 'No description provided.' !!}
                    </div>
                </div>

                <!-- Tabs -->
                <div x-data="{ activeTab: 'comments' }" style="margin-bottom: 24px;">
                    <div style="display: flex; gap: 24px; border-bottom: 2px solid #F3F4F6;">
                        <button @click="activeTab = 'comments'"
                                :style="activeTab === 'comments' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="padding: 12px 0; font-weight: 600; font-size: 14px; background: transparent; border: none; cursor: pointer; margin-bottom: -2px;">
                            Comments
                        </button>
                        <button @click="activeTab = 'attachments'"
                                :style="activeTab === 'attachments' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="padding: 12px 0; font-weight: 600; font-size: 14px; background: transparent; border: none; cursor: pointer; margin-bottom: -2px;">
                            Attachments ({{ $selectedTicket->attachments->count() }})
                        </button>
                        <button @click="activeTab = 'status'"
                                :style="activeTab === 'status' ? 'border-bottom: 2px solid #6366F1; color: #6366F1;' : 'color: #9CA3AF;'"
                                style="padding: 12px 0; font-weight: 600; font-size: 14px; background: transparent; border: none; cursor: pointer; margin-bottom: -2px;">
                            Status Log
                        </button>
                    </div>

                    <!-- Comments Tab -->
                    <div x-show="activeTab === 'comments'" style="padding: 24px 0;">
                        <div style="margin-bottom: 24px;">
                            <form wire:submit.prevent="addComment">
                                {{ $this->form }}

                                <div style="margin-top: 12px;">
                                    <button type="submit"
                                            style="padding: 8px 20px; background: #6366F1; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; transition: all 0.2s;"
                                            onmouseover="this.style.background='#4F46E5'"
                                            onmouseout="this.style.background='#6366F1'">
                                        Add Comment
                                    </button>
                                </div>
                            </form>
                        </div>

                        @if($selectedTicket->comments->count() > 0)
                            <div style="margin-top: 24px;">
                                <h4 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #E5E7EB;">
                                    Previous Comments
                                </h4>
                                @foreach($selectedTicket->comments as $comment)
                                    <div style="margin-bottom: 20px; padding: 16px; background: #F9FAFB; border-radius: 8px; border-left: 3px solid #6366F1;">
                                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                                            <div style="width: 36px; height: 36px; border-radius: 50%; background: #6366F1; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 14px;">
                                                {{ strtoupper(substr($comment->user->name ?? 'U', 0, 1)) }}
                                            </div>
                                            <div style="flex: 1; display: flex; align-items: center; justify-content: space-between;">
                                                <div style="display: flex; align-items: center; gap: 8px;">
                                                    <span style="font-weight: 600; font-size: 14px; color: #111827;">
                                                        {{ $comment->user->name ?? 'Unknown User' }}
                                                    </span>
                                                    <span style="padding: 2px 8px; background: #E0E7FF; color: #4F46E5; border-radius: 4px; font-size: 11px; font-weight: 600;">
                                                        {{ $comment->user->role ?? 'HRcrm User' }}
                                                    </span>
                                                </div>
                                                <div style="font-size: 12px; color: #9CA3AF;">
                                                    {{ $comment->created_at->diffForHumans() }}
                                                </div>
                                            </div>
                                        </div>
                                        <div style="color: #374151; margin: 0; font-size: 14px; line-height: 1.6;">
                                            {!! $comment->comment !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="text-align: center; padding: 60px 20px;">
                                <div style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;">💬</div>
                                <p style="color: #9CA3AF; font-size: 14px;">No comments yet</p>
                            </div>
                        @endif
                    </div>

                    <!-- Attachments Tab -->
                    <div x-show="activeTab === 'attachments'" style="padding: 24px 0;">
                        @if($selectedTicket->device_type === 'Mobile' && $selectedTicket->version_screenshot)
                            <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px;">Version Screenshot</h3>

                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #F9FAFB; border-radius: 8px; margin-bottom: 24px; border-left: 3px solid #6366F1;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; font-size: 14px; color: #111827;">App Version Screenshot</div>
                                    <div style="font-size: 12px; color: #6B7280; margin-top: 4px;">Mobile App Version</div>
                                </div>
                                <div style="display: flex; gap: 8px;">
                                    <a href="{{ \Illuminate\Support\Facades\Storage::disk('s3-ticketing')->temporaryUrl($selectedTicket->version_screenshot, now()->addMinutes(60)) }}"
                                       target="_blank"
                                       style="padding: 6px 12px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; cursor: pointer; text-decoration: none; color: #374151; display: flex; align-items: center; gap: 4px;">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                        View
                                    </a>
                                </div>
                            </div>

                            <div style="border-top: 1px solid #E5E7EB; margin: 24px 0;"></div>
                        @endif

                        @if($selectedTicket->attachments->count() > 0)
                            <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px;">Current Attachments</h3>

                            @foreach($selectedTicket->attachments as $attachment)
                                <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px; background: #F9FAFB; border-radius: 8px; margin-bottom: 8px;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; font-size: 14px; color: #111827;">{{ $attachment->original_filename }}</div>
                                        <div style="font-size: 12px; color: #6B7280; margin-top: 4px;">
                                            {{ number_format($attachment->file_size / 1024, 2) }} KB • {{ $attachment->created_at->format('d M Y, H:i A') }}
                                            <br>
                                            <span style="font-style: italic;">by {{ $attachment->uploader->name ?? 'Unknown' }}</span>
                                        </div>
                                    </div>
                                    <div style="display: flex; gap: 8px;">
                                        <a href="{{ \Illuminate\Support\Facades\Storage::disk('s3-ticketing')->temporaryUrl($attachment->file_path, now()->addMinutes(60)) }}"
                                           target="_blank"
                                           style="padding: 6px 12px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; cursor: pointer; text-decoration: none; color: #374151; display: flex; align-items: center; gap: 4px;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            View
                                        </a>
                                    </div>
                                </div>
                            @endforeach

                            <div style="border-top: 1px solid #E5E7EB; margin: 24px 0;"></div>
                        @else
                            <div style="text-align: center; padding: 40px 20px;">
                                <div style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;">📎</div>
                                <p style="color: #9CA3AF; font-size: 14px;">No attachments yet</p>
                            </div>
                        @endif

                        <!-- Upload Section -->
                        <h3 style="font-size: 14px; font-weight: 600; color: #111827; margin-bottom: 16px;">Upload New Attachments</h3>

                        <div style="margin-bottom: 16px;">
                            <input type="file"
                                   wire:model="attachments"
                                   multiple
                                   style="width: 100%; padding: 12px; border: 2px dashed #E5E7EB; border-radius: 8px; cursor: pointer;"
                                   accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt">

                            @error('attachments.*')
                                <div style="color: #DC2626; font-size: 12px; margin-top: 8px;">{{ $message }}</div>
                            @enderror

                            <div wire:loading wire:target="attachments" style="margin-top: 12px; color: #6366F1; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation: spin 1s linear infinite;">
                                    <circle cx="12" cy="12" r="10" stroke-opacity="0.25"></circle>
                                    <path d="M12 2a10 10 0 0 1 10 10" stroke-opacity="0.75"></path>
                                </svg>
                                Uploading files...
                            </div>
                        </div>

                        @if(!empty($attachments))
                            <div style="margin-bottom: 16px; padding: 12px; background: #F9FAFB; border-radius: 8px;">
                                <div style="font-size: 13px; font-weight: 600; color: #6B7280; margin-bottom: 8px;">
                                    Selected Files ({{ count($attachments) }})
                                </div>
                                @foreach($attachments as $file)
                                    <div style="font-size: 12px; color: #374151; padding: 4px 0;">
                                        • {{ $file->getClientOriginalName() }} ({{ number_format($file->getSize() / 1024, 2) }} KB)
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <button wire:click="uploadAttachments"
                                @if(empty($attachments)) disabled @endif
                                style="width: 100%; padding: 12px; background: {{ empty($attachments) ? '#D1D5DB' : '#6366F1' }}; color: white; border: none; border-radius: 8px; font-weight: 600; cursor: {{ empty($attachments) ? 'not-allowed' : 'pointer' }}; transition: all 0.2s;">
                            Upload Files
                        </button>
                    </div>

                    <!-- Status Log Tab (copy from ticket-dashboard.blade.php) -->
                    <div x-show="activeTab === 'status'" style="padding: 24px 0;">
                        @if($selectedTicket->logs->count() > 0)
                            <div style="position: relative;">
                                @foreach($selectedTicket->logs->sortByDesc('created_at') as $index => $log)
                                    <div style="display: flex; gap: 16px; margin-bottom: {{ $index < $selectedTicket->logs->count() - 1 ? '24px' : '0' }};">
                                        {{-- Timeline Connector --}}
                                        <div style="display: flex; flex-direction: column; align-items: center; position: relative;">
                                            {{-- Timeline Dot --}}
                                            <div style="width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; background:
                                                @if($log->new_status === 'Completed') #F97316
                                                @elseif($log->new_status === 'Reopen') white
                                                @elseif($log->new_status === 'Closed' || $log->new_status === 'Closed System Configuration') #F97316
                                                @elseif($log->new_status === 'In Progress') white
                                                @elseif($log->new_status === 'New') white
                                                @else white
                                                @endif;
                                                border: 2px solid
                                                @if($log->new_status === 'Completed') #F97316
                                                @elseif($log->new_status === 'Reopen') #9CA3AF
                                                @elseif($log->new_status === 'Closed' || $log->new_status === 'Closed System Configuration') #F97316
                                                @elseif($log->new_status === 'In Progress') #9CA3AF
                                                @elseif($log->new_status === 'New') #9CA3AF
                                                @else #9CA3AF
                                                @endif;"></div>

                                            {{-- Timeline Line --}}
                                            @if($index < $selectedTicket->logs->count() - 1)
                                                <div style="width: 2px; background: #E5E7EB; flex: 1; margin-top: 4px; min-height: 40px;"></div>
                                            @endif
                                        </div>

                                        {{-- Status Content --}}
                                        <div style="flex: 1; padding-bottom: 8px;">
                                            <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 8px;">
                                                <div class="status-badge-wrapper" style="position: relative; display: inline-block;">
                                                    <span style="padding: 4px 12px; background:
                                                        @if($log->new_status === 'Completed') #FEF3C7
                                                        @elseif($log->new_status === 'Reopen') #F3F4F6
                                                        @elseif($log->new_status === 'Closed System Configuration') #FEF3C7
                                                        @elseif($log->new_status === 'In Progress') #FEF3C7
                                                        @elseif($log->new_status === 'New') #F3F4F6
                                                        @else #F3F4F6
                                                        @endif;
                                                        color:
                                                        @if($log->new_status === 'Completed') #D97706
                                                        @elseif($log->new_status === 'Reopen') #6B7280
                                                        @elseif($log->new_status === 'Closed System Configuration') #D97706
                                                        @elseif($log->new_status === 'In Progress') #D97706
                                                        @elseif($log->new_status === 'New') #6B7280
                                                        @else #6B7280
                                                        @endif;
                                                        border-radius: 6px; font-size: 13px; font-weight: 500; border: 1px solid
                                                        @if($log->new_status === 'Completed') #FDE047
                                                        @elseif($log->new_status === 'Reopen') #E5E7EB
                                                        @elseif($log->new_status === 'Closed System Configuration') #FDE047
                                                        @elseif($log->new_status === 'In Progress') #FDE047
                                                        @elseif($log->new_status === 'New') #E5E7EB
                                                        @else #E5E7EB
                                                        @endif;">
                                                        {{ $log->new_status }}
                                                    </span>
                                                    <div class="status-tooltip" style="position: absolute; bottom: 100%; left: 0; margin-bottom: 8px; padding: 6px 10px; background: #1F2937; color: white; font-size: 11px; border-radius: 6px; white-space: nowrap; opacity: 0; pointer-events: none; transition: opacity 0.2s; z-index: 10;">
                                                        {{ $log->created_at->format('M d') }} • {{ $log->created_at->format('Y, g:i A') }}
                                                    </div>
                                                </div>

                                                @if($index === 0)
                                                    @php
                                                        $createdAt = $log->created_at;
                                                        $now = now();
                                                        $diff = $createdAt->diff($now);
                                                        $elapsed = '';
                                                        if ($diff->d > 0) $elapsed .= $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ';
                                                        if ($diff->h > 0) $elapsed .= $diff->h . ' hr' . ($diff->h > 1 ? 's' : '') . ' ';
                                                        if ($diff->i > 0) $elapsed .= $diff->i . ' min' . ($diff->i > 1 ? 's' : '') . ' ';
                                                        $elapsed .= $diff->s . ' sec' . ($diff->s > 1 ? 's' : '');
                                                    @endphp
                                                    <div style="display: flex; align-items: center; gap: 4px; color: #3B82F6; font-size: 12px;">
                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                            <circle cx="12" cy="12" r="10"></circle>
                                                            <polyline points="12 6 12 12 16 14"></polyline>
                                                        </svg>
                                                        <span>Elapsed: {{ trim($elapsed) }}</span>
                                                    </div>
                                                @endif
                                            </div>
                                            <div style="font-size: 13px; color: #6B7280;">
                                                Updated by {{ $log->user_name ?? 'Unknown User' }} - {{ $log->user_role ?? 'User' }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div style="text-align: center; padding: 60px 20px;">
                                <div style="font-size: 48px; opacity: 0.3; margin-bottom: 16px;">📋</div>
                                <p style="color: #9CA3AF; font-size: 14px;">No status changes recorded</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Side - Other Information (copy from ticket-dashboard.blade.php) -->
            <div style="padding: 24px; background: #F9FAFB;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;">
                    <h3 style="font-size: 14px; font-weight: 600; color: #6B7280; margin: 0;">Other Information</h3>
                </div>

                <!-- Priority -->
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Priority</div>
                    <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->priority->name ?? $selectedTicket->priority ?? '-' }}</div>
                </div>

                <!-- Status -->
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Status</div>
                    <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->status ?? '-' }}</div>
                </div>

                <!-- Due Date -->
                <div style="margin-bottom: 16px;">
                    <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Due Date</div>
                    <div style="font-weight: 500; color: #111827; font-size: 14px;">-</div>
                </div>

                <!-- ETA Release Date & Live Release Date -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">ETA Release Date</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->eta_release ? $selectedTicket->eta_release->format('M d, Y') : '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Live Release Date</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->live_release ? $selectedTicket->live_release->format('M d, Y') : '-' }}</div>
                    </div>
                </div>

                <!-- Divider -->
                <div style="border-top: 1px solid #E5E7EB; margin: 20px 0;"></div>

                <!-- Product & Module -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Product</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->product->name ?? $selectedTicket->product ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Module</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->module->name ?? $selectedTicket->module ?? '-' }}</div>
                    </div>
                </div>

                <!-- Company Name & Requester -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Company Name</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->company_name ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Requester</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->requestor->name ?? $selectedTicket->created_by ?? '-' }}</div>
                    </div>
                </div>

                <!-- Zoho Ticket Number & Created Date -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Zoho Ticket Number</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->zoho_ticket_number ?? '-' }}</div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Created Date</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->created_at ? $selectedTicket->created_at->format('M d, Y') : '-' }}</div>
                    </div>
                </div>

                <!-- Divider -->
                <div style="border-top: 1px solid #E5E7EB; margin: 20px 0;"></div>

                <!-- Device Type, Browser Type & Windows Version -->
                @if($selectedTicket->device_type === 'Browser')
                    <!-- ✅ BROWSER SPECIFIC FIELDS -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Device Type</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->device_type ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Browser Type</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->browser_type ?? '-' }}</div>
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Windows Version</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->windows_version ?? '-' }}</div>
                    </div>
                @elseif($selectedTicket->device_type === 'Mobile')
                    <!-- ✅ MOBILE SPECIFIC FIELDS -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Device Type</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->device_type ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Mobile Type</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->mobile_type ?? '-' }}</div>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Device ID</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->device_id ?? '-' }}</div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">OS Version</div>
                            <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->os_version ?? '-' }}</div>
                        </div>
                    </div>

                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">App Version</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">{{ $selectedTicket->app_version ?? '-' }}</div>
                    </div>

                    @if($selectedTicket->version_screenshot)
                        <div style="margin-bottom: 16px;">
                            <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Version Screenshot</div>
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('s3-ticketing')->temporaryUrl($selectedTicket->version_screenshot, now()->addMinutes(60)) }}" target="_blank"
                            style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; background: white; border: 1px solid #E5E7EB; border-radius: 6px; text-decoration: none; color: #6366F1; font-size: 13px; font-weight: 500; transition: all 0.2s;"
                            onmouseover="this.style.background='#F3F4F6'"
                            onmouseout="this.style.background='white'">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                    <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                    <polyline points="21 15 16 10 5 21"></polyline>
                                </svg>
                                View Screenshot
                            </a>
                        </div>
                    @endif
                @else
                    <!-- ✅ FALLBACK if device_type is not set -->
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 11px; color: #9CA3AF; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 500;">Device Type</div>
                        <div style="font-weight: 500; color: #111827; font-size: 14px;">-</div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .status-badge-wrapper:hover .status-tooltip {
        opacity: 1 !important;
    }
</style>
