<div class="space-y-4">
    @php
        $lead = $this->getRecord();
        // Get the latest software handover instead of the first one
        $softwareHandover = $lead->softwareHandover()
            ->orderBy('created_at', 'desc')
            ->first();
    @endphp

    @if($softwareHandover)
        <div class="grid grid-cols-1 gap-3">
            <!-- Implementer -->
            <div class="flex items-start">
                <div class="w-1/3 text-sm font-medium text-gray-950 dark:text-white">Implementer:</div>&nbsp;
                <div class="w-2/3 text-sm text-gray-900 dark:text-white">
                    @if($softwareHandover->implementer)
                        {{ $softwareHandover->implementer }}
                    @else
                        <span class="italic text-gray-500">Not assigned</span>
                    @endif
                </div>
            </div>

            <!-- Project Status -->
            <div class="flex items-start">
                <div class="w-1/3 text-sm font-medium text-gray-950 dark:text-white">Project Status:</div>&nbsp;
                <div class="w-2/3 text-sm text-gray-900 dark:text-white">
                    <span>
                        {{ $softwareHandover->status_handover ?? 'Open' }}
                    </span>
                </div>
            </div>

            <!-- Go Live Date -->
            {{-- @if($softwareHandover->go_live_date)
                <div class="flex items-start">
                    <div class="w-1/3 text-sm font-medium text-gray-950 dark:text-white">Go Live Date:</div>&nbsp;
                    <div class="w-2/3 text-sm text-gray-900 dark:text-white">
                        {{ \Carbon\Carbon::parse($softwareHandover->go_live_date)->format('d M Y') }}
                    </div>
                </div>
            @endif --}}
        </div>
    @else
        <div class="p-4 text-center text-gray-500 border border-gray-200 rounded-md bg-gray-50">
            No project information available yet. Click the Edit button to add project details.
        </div>
    @endif
</div>
