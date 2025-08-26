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
                                        </p>

                                        @php
                                            // Check if there are any scheduled emails for this follow-up
                                            $scheduledEmails = DB::table('scheduled_emails')
                                                ->where('email_data', 'like', '%"implementer_log_id":' . $followUp->id . '%')
                                                ->get();
                                        @endphp

                                        @if($scheduledEmails && $scheduledEmails->count() > 0)
                                            @foreach($scheduledEmails as $email)
                                                @php
                                                    $emailData = json_decode($email->email_data, true);
                                                    $templateName = isset($emailData['template_name']) ? $emailData['template_name'] : 'Custom Email';

                                                    $badgeColor = 'bg-blue-100 text-blue-800';
                                                    $sendType = 'Unknown';

                                                    if ($email->status === 'Done') {
                                                        $badgeColor = 'bg-green-100 text-green-800';
                                                        $sendType = 'Sent';
                                                    } elseif ($email->status === 'New') {
                                                        if (strtotime($email->scheduled_date) > time()) {
                                                            $badgeColor = 'bg-yellow-100 text-yellow-800';
                                                            $sendType = 'Scheduled for ' . \Carbon\Carbon::parse($email->scheduled_date)->format('d M Y g:i A'). ' (Pending)';
                                                        }
                                                    }
                                                @endphp

                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeColor }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                    </svg>
                                                    {{ $templateName }} - {{ $sendType }}
                                                </span>
                                            @endforeach
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
                                                <span style="font-weight: bold; color: #eb911a;">Next Follow Up Date:  {{ \Carbon\Carbon::parse($followUpDate)->format('d M Y') }}</span>
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-3">
                                    <div class="p-3 mt-1 text-sm prose rounded max-w-none bg-gray-50">
                                        {!! strtoupper($followUp->remark) !!}
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
            <div class="text-center">
                <p>No follow-ups available for this project</p>
                <p class="mt-1 text-sm">Click 'Add Follow-up' to create the first follow-up record</p>
            </div>
        </div>
    @endif
</div>
