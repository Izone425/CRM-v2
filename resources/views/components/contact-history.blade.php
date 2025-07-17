<div class="p-4 space-y-4">
    @php
        // Try to get the lead record - use different methods depending on context
        try {
            $lead = $this->getRecord();
        } catch (\Exception $e) {
            // Fallback if getRecord() doesn't work
            $lead = $lead ?? null;
        }

        // Get notes if we have a valid lead object
        $notes = $lead && method_exists($lead, 'implementerNotes')
            ? $lead->implementerNotes()->with('user')->orderBy('created_at', 'desc')->get()
            : collect();
    @endphp

    @if($notes->count() > 0)
        <div class="overflow-y-auto bg-white border border-gray-200 rounded-lg shadow max-h-96">
            <div class="space-y-0 divide-y divide-gray-200">
                @foreach($notes as $note)
                    <div class="p-4">
                        <div class="flex items-start justify-between">
                            <div class="space-y-1">
                                <p class="text-xs text-gray-500">
                                    {{ $note->created_at->format('Y-m-d H:i:s') }} by {{ $note->user ? $note->user->name : 'Unknown User' }}
                                </p>
                                <div class="text-sm prose max-w-none">
                                    {!! $note->content !!}
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="flex items-center justify-center p-6 text-gray-500 border border-gray-200 rounded-md bg-gray-50">
            <p>No notes available for this lead</p>
        </div>
    @endif
</div>
