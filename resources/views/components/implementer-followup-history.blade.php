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
                                    <p class="text-gray-500" style="font-weight:bold; font-size: 1rem; color: #eb321a; text-decoration: underline;">
                                        Implementer Follow Up {{ $totalFollowUps - $index }}
                                    </p>
                                    <div class="flex flex-col mt-2">
                                        <p class="text-xs font-medium">
                                            Added {{ $followUp->created_at->format('d M Y, h:i A') }} by {{ $followUp->causer ? $followUp->causer->name : 'CRM System' }}
                                        </p>

                                        @php
                                            $softwareHandover = \App\Models\SoftwareHandover::where('id', $followUp->subject_id)->first();
                                            $followUpDate = $softwareHandover ? $softwareHandover->follow_up_date : null;
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
