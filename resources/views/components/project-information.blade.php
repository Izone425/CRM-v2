<div class="space-y-4">
    @php
        $lead = $this->getRecord();
        // Get the latest software handover instead of the first one
        $softwareHandover = $lead->softwareHandover()
            ->orderBy('created_at', 'desc')
            ->first();
    @endphp

    <div class="grid grid-cols-1 gap-2">
        <!-- Implementer -->
        <div class="flex items-start">
            <div class="w-1/3 text-sm font-medium text-gray-950 dark:text-white">Implementer:</div>&nbsp;
            <div class="w-2/3 text-sm text-gray-900 dark:text-white">
                @if($softwareHandover && $softwareHandover->implementer)
                    {{ $softwareHandover->implementer }}
                @else
                    Not Available
                @endif
            </div>
        </div>

        <!-- Project Status -->
        <div class="flex items-start">
            <div class="w-1/3 text-sm font-medium text-gray-950 dark:text-white">Project Status:</div>&nbsp;
            <div class="w-2/3 text-sm text-gray-900 dark:text-white">
                <span>
                    {{ $softwareHandover->status_handover ?? 'Not Available' }}
                </span>
            </div>
        </div>
    </div>
</div>
