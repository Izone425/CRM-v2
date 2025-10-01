<div class="space-y-4">
    @php
        $lead = $this->getRecord();

        // Get implementer logs that are follow-ups
        $followUps = $lead->implementerLogs()
            ->with('causer')
            ->orderBy('created_at', 'desc')
            ->get();

        $totalFollowUps = $followUps->count();
    @endphp

    <div x-data="{
        showModal: false,
        emailSubject: '',
        emailContent: '',
        emailSender: '',
        emailDate: '',
        emailRecipients: '',
        openEmail(subject, content, sender, date, recipients) {
            this.emailSubject = subject;
            this.emailContent = content;
            this.emailSender = sender;
            this.emailDate = date;
            this.emailRecipients = recipients;
            this.showModal = true;
        }
    }" @keydown.escape.window="showModal = false">
        <!-- Modal -->
        <div x-show="showModal"
             class="fixed inset-0 z-50 overflow-y-auto"
             style="display: none;"
             x-cloak>
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="showModal = false"></div>

            <!-- Modal content -->
            <div class="relative flex items-center justify-center min-h-screen p-4">
                <div class="relative w-full max-w-3xl p-6 mx-auto bg-white rounded-lg shadow-xl">
                    <!-- Header -->
                    <div class="pb-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-medium" x-text="emailSubject"></h3>
                            <button @click="showModal = false" class="text-gray-400 hover:text-gray-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="mt-2 text-sm text-gray-500">
                            <div><strong>From:</strong> <span x-text="emailSender"></span></div>
                            <div><strong>To:</strong> <span x-text="emailRecipients"></span></div>
                            <div><strong>Date:</strong> <span x-text="emailDate"></span></div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="py-4 max-h-[60vh] overflow-y-auto">
                        <div class="prose max-w-none" x-html="emailContent"></div>
                    </div>

                    <!-- Footer -->
                    <div class="flex justify-end pt-4 border-t border-gray-200">
                        <button @click="showModal = false" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if($followUps->count() > 0)
            <div class="overflow-y-auto bg-white rounded-lg max-h-96">
                <div class="space-y-0 divide-y divide-gray-200">
                    @foreach($followUps as $index => $followUp)
                        <div class="p-4 hover:bg-gray-50">
                            <div class="flex items-start justify-between">
                                <div class="w-full space-y-1">
                                    <div class="flex flex-col w-full">
                                        <div class="flex items-center justify-between">
                                            <p class="text-gray-500" style="font-weight:bold; font-size: 1rem; color: #eb321a; text-decoration: underline;">
                                                Implementer Follow Up {{ $totalFollowUps - $index }}
                                                @if($followUp->manual_follow_up_count > 0)
                                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                        Follow-up #{{ $followUp->manual_follow_up_count }}
                                                    </span>
                                                @endif
                                            </p>

                                            @php
                                                // Check if there are any scheduled emails for this follow-up
                                                $scheduledEmails = DB::table('scheduled_emails')
                                                    ->where('email_data', 'like', '%"implementer_log_id":' . $followUp->id . '%')
                                                    ->get();
                                            @endphp

                                            @if($scheduledEmails && $scheduledEmails->count() > 0)
                                                <div class="flex flex-wrap gap-2">
                                                    @foreach($scheduledEmails as $email)
                                                        @php
                                                            $emailData = json_decode($email->email_data, true);
                                                            $templateName = isset($emailData['template_name']) ? $emailData['template_name'] : 'Custom Email';
                                                            $emailSubject = isset($emailData['subject']) ? $emailData['subject'] : 'No Subject';
                                                            $emailContent = isset($emailData['content']) ? $emailData['content'] : 'No Content';
                                                            $senderName = isset($emailData['sender_name']) ? $emailData['sender_name'] : 'Unknown Sender';
                                                            $senderEmail = isset($emailData['sender_email']) ? $emailData['sender_email'] : '';
                                                            $recipients = isset($emailData['recipients']) ? implode(', ', $emailData['recipients']) : 'No Recipients';
                                                            $emailDate = \Carbon\Carbon::parse($email->created_at)->format('d M Y g:i A');

                                                            $badgeColor = 'bg-blue-100 text-blue-800';
                                                            $sendType = 'Unknown';

                                                            if ($email->status === 'Done' && $email->scheduled_date === null) {
                                                                $badgeColor = 'bg-green-100 text-green-800';
                                                                $sendType = 'Sent Instantly';
                                                            } elseif ($email->status === 'Done') {
                                                                $badgeColor = 'bg-green-100 text-green-800';
                                                                $sendType = 'Sent';
                                                            } elseif ($email->status === 'New') {
                                                                if (strtotime($email->scheduled_date) > time()) {
                                                                    $badgeColor = 'bg-yellow-100 text-yellow-800';
                                                                    $sendType = 'Scheduled for ' . \Carbon\Carbon::parse($email->scheduled_date)->format('d M Y g:i A'). ' (Pending)';
                                                                }
                                                            }
                                                        @endphp

                                                        <button type="button"
                                                            @click="openEmail(
                                                                '{{ str_replace("'", "\\'", $emailSubject) }}',
                                                                `{!! str_replace('`', '\\`', $emailContent) !!}`,
                                                                '{{ str_replace("'", "\\'", $senderName) }} <{{ str_replace("'", "\\'", $senderEmail) }}>',
                                                                '{{ str_replace("'", "\\'", $emailDate) }}',
                                                                '{{ str_replace("'", "\\'", $recipients) }}'
                                                            )"
                                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }} cursor-pointer">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                            </svg>
                                                            {{ $templateName }} - {{ $sendType }}
                                                        </button>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>

                                        <div class="flex flex-col mt-2">
                                            <p class="text-xs font-medium">
                                                Added {{ $followUp->created_at->format('d M Y, h:i A') }} by {{ $followUp->causer ? $followUp->causer->name : 'CRM System' }}
                                            </p>

                                            @php
                                                $softwareHandover = \App\Models\SoftwareHandover::where('id', $followUp->subject_id)->first();
                                                $followUpDate = $followUp->follow_up_date ? \Carbon\Carbon::parse($followUp->follow_up_date)->format('Y-m-d') : null;
                                            @endphp

                                            @if($followUpDate)
                                                <p class="mt-1 text-xs font-medium">
                                                    <span style="font-weight: bold; color: #eb911a;">Next Follow Up Date: {{ \Carbon\Carbon::parse($followUpDate)->format('d M Y') }}</span>
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <div class="p-3 mt-1 text-sm prose rounded max-w-none bg-gray-50">
                                            @php
                                                $remark = $followUp->remark;

                                                // Convert literal "&nbsp;" text to actual non-breaking spaces
                                                $remark = str_replace('&nbsp;', ' ', $remark);
                                                $remark = str_replace('&amp;nbsp;', ' ', $remark);

                                                // Handle any other common HTML entities that might be causing issues
                                                $remark = str_replace('&amp;', '&', $remark);

                                                // Final decode of any remaining entities
                                                $remark = html_entity_decode($remark, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                                                // Convert to uppercase after all decoding is done
                                                $remark = strtoupper($remark);
                                            @endphp

                                            {!! $remark !!}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="flex items-center justify-center p-6 text-gray-500 rounded-lg bg-gray-50">

            </div>
        @endif
    </div>
</div>
