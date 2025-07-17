<div class="space-y-4">
    @php
        $lead = $this->getRecord();
        $notes = $lead->implementerNotes()->with('user')->orderBy('created_at', 'desc')->get();
    @endphp

    @if($notes->count() > 0)
        <div class="overflow-y-auto bg-white rounded-lg max-h-96">
            <div class="space-y-0 divide-y divide-gray-200">
                @foreach($notes as $note)
                    <div class="p-4 hover:bg-gray-50">
                        <div class="flex items-start justify-between">
                            <div class="w-full space-y-1">
                                <div class="flex items-center justify-between w-full">
                                    <p class="text-xs text-gray-500">
                                        {{ $note->created_at->format('d M Y, h:i A') }} by {{ $note->user ? $note->user->name : 'Unknown User' }}
                                    </p>
                                </div>
                                <div class="mt-2 text-sm prose max-w-none">
                                    {!! $note->content !!}
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
                <p>No notes available for this lead</p>
                <p class="mt-1 text-sm">Click 'Add Note' to create the first note</p>
            </div>
        </div>
    @endif
</div>
