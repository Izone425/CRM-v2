<div class="space-y-4">
    @php
        $lead = $this->getRecord();

        // Get implementer logs that are follow-ups
        $followUps = $lead->adminRenewalLogs()->with('causer')->orderBy('created_at', 'desc')->get();

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
        <div x-show="showModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;" x-cloak>
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
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
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
                        <button @click="showModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>

        @if ($followUps->count() > 0)
            <div class="w-full overflow-hidden bg-white border border-gray-200 rounded-lg shadow">
                <div class="overflow-x-auto">
                    <table class="w-full border border-collapse border-gray-200 table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase border-b border-r border-gray-200">
                                    No
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase border-b border-r border-gray-200">
                                    Follow Up Date
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase border-b border-r border-gray-200">
                                    Admin Renewal
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase border-b border-r border-gray-200">
                                    Follow Up Remark
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-xs font-medium tracking-wider text-left text-gray-500 uppercase border-b border-gray-200">
                                    Next Follow Up Date
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @foreach ($followUps as $index => $followUp)
                                @php
                                    $followUpDate = $followUp->follow_up_date
                                        ? \Carbon\Carbon::parse($followUp->follow_up_date)->format('Y-m-d')
                                        : null;

                                    // Check if there are any scheduled emails for this follow-up
                                    $scheduledEmails = DB::table('scheduled_emails')
                                        ->where('email_data', 'like', '%"implementer_log_id":' . $followUp->id . '%')
                                        ->get();
                                @endphp
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <!-- FU Column -->
                                    <td
                                        class="px-6 py-4 text-sm font-medium text-gray-900 border-r border-gray-200 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-red-600">{{ $totalFollowUps - $index }}</span>
                                            @if ($followUp->manual_follow_up_count > 0)
                                                <span
                                                    class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-100 text-indigo-800">
                                                    Follow-up #{{ $followUp->manual_follow_up_count }}
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Added Date Column -->
                                    <td
                                        class="px-6 py-4 text-sm text-gray-900 border-r border-gray-200 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span
                                                class="font-medium">{{ $followUp->created_at->format('d M Y') }}</span>
                                            <span
                                                class="text-xs text-gray-500">{{ $followUp->created_at->format('h:i A') }}</span>
                                        </div>
                                    </td>

                                    <!-- Admin Renewal Column -->
                                    <td
                                        class="px-6 py-4 text-sm text-gray-900 border-r border-gray-200 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span
                                                class="font-medium">{{ $followUp->causer ? $followUp->causer->name : 'CRM System' }}</span>

                                        </div>
                                    </td>

                                    <!-- Remarks Column -->
                                    <td class="px-6 py-4 text-sm text-gray-900 border-r border-gray-200">
                                        <div class="w-full">
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

                                            <div class="prose-sm prose max-w-none">
                                                {!! $remark !!}
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Next Follow Up Date Column -->
                                    <td class="px-6 py-4 text-sm text-gray-900 whitespace-nowrap">
                                        @if ($followUpDate)
                                            <span class="font-medium text-orange-600">
                                                {{ \Carbon\Carbon::parse($followUpDate)->format('d M Y') }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
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
</div>
