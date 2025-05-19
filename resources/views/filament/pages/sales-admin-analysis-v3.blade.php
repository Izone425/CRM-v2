<x-filament-panels::page>
    <head>
        <style>
            .hover-message {
                position: absolute;
                bottom: 110%;
                left: 50%;
                transform: translateX(-50%);
                background-color: rgba(0, 0, 0, 0.75);
                color: white;
                padding: 5px 10px;
                font-size: 12px;
                border-radius: 5px;
                white-space: nowrap;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease-in-out;
                z-index: 10;
            }

            .group:hover .hover-message {
                opacity: 1;
                visibility: visible;
            }

            .cursor-pointer:hover {
                transform: scale(1.02);
                transition: all 0.2s;
            }

            .data-container {
                display: flex;
                gap: 10px;
                width: 800px;
                flex-wrap: wrap;
            }

            .data-item {
                flex-grow: 1;
                text-align: center;
            }

            .data-block {
                padding: 10px 15px;
                border-radius: 8px;
                color: white;
                overflow: hidden;
                white-space: nowrap;
                text-overflow: ellipsis;
                height: 100%;
            }

            /* When there's only one item */
            .data-container.items-1 .data-item {
                flex-basis: 100%;
            }

            /* When there are two items */
            .data-container.items-2 .data-item {
                flex-basis: calc(50% - 5px);
            }

            /* When there are three or more items */
            .data-container.items-3 .data-item,
            .data-container.items-many .data-item {
                flex-basis: calc(33.33% - 7px);
                min-width: 150px;
            }
        </style>
    </head>
    <div class="flex flex-col items-center justify-between mb-6 md:flex-row">
        <h1 class="text-2xl font-bold tracking-tight fi-header-heading text-gray-950 dark:text-white sm:text-3xl">Sales Admin Analysis V3</h1>
        <div>
            <input wire:model="startDate" type="date" id="startDate" class="mt-1 border-gray-300 rounded-md shadow-sm" />
            &nbsp;- &nbsp;
            <input wire:model="endDate" type="date" id="endDate" class="mt-1 border-gray-300 rounded-md shadow-sm" />
        </div>
    </div>
    <div style="display: flex; flex-direction: column; gap: 10px; background-color: white; align-items: center;"  wire:poll.1s>

        @php
            // Create a mapping of lead owners to consistent colors
            $ownerColors = [];
            $colorOptions = ['#38b2ac', '#f6ad55', '#7f9cf5', '#f56565', '#68d391', '#d69e2e'];

            // Get all unique lead owners from all data sets
            $allOwners = collect([])
                ->merge(array_keys($this->leadOwnerPickupCounts ?? []))
                ->merge(array_keys($this->demoStatsByLeadOwner ?? []))
                ->merge(array_keys($this->rfqTransferStatsByLeadOwner ?? []))
                ->merge(array_keys($this->automationStatsByLeadOwner ?? []))
                ->merge(array_keys($this->archiveStatsByLeadOwner ?? []))
                ->merge(array_keys($this->callAttemptStatsByLeadOwner ?? []))
                ->unique();

            $colorIndex = 0;
            foreach ($allOwners as $owner) {
                $ownerColors[$owner] = $colorOptions[$colorIndex % count($colorOptions)];
                $colorIndex++;
            }
        @endphp

        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px; font-weight: bold;">Leads Incoming</div>

            @if ($this->leadsIncoming > 0)
                <div style="
                    background-color: #4c51bf;
                    color: white;
                    padding: 10px 20px;
                    border-radius: 8px;
                    width: 800px;
                    text-align: center;
                ">
                    {{ $this->leadsIncoming }}
                </div>
            @else
                <div style="
                    background-color: #e2e8f0;
                    color: #4a5568;
                    padding: 10px 20px;
                    border-radius: 8px;
                    width: 800px;
                    text-align: center;
                ">
                    No Data Found
                </div>
            @endif
        </div>

        {{-- Leads Pickup --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px;">Leads Pickup</div>

            @if (count($this->leadOwnerPickupCounts))
                @php
                    $itemCount = count($this->leadOwnerPickupCounts);
                    $containerClass = 'data-container items-' . ($itemCount <= 3 ? $itemCount : 'many');
                @endphp

                <div class="{{ $containerClass }}">
                    @foreach ($this->leadOwnerPickupCounts as $owner => $data)
                    <div
                        wire:click="openSlideOver('pickup', '{{ $owner }}')"
                        class="relative cursor-pointer group data-item"
                    >
                        <div class="data-block" style="
                            background-color: {{ $ownerColors[$owner] ?? '#38b2ac' }};
                        ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} leads ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="
                    background-color: #e2e8f0;
                    color: #4a5568;
                    padding: 10px 20px;
                    border-radius: 8px;
                    width: 800px;
                    text-align: center;
                ">
                    No Data Found
                </div>
            @endif
        </div>

        <hr style="border: 0; height: 1px; background-color: #e2e8f0; width: 100%; margin: 15px 0;">

        {{-- Add Demo --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px;">Demo Assigned</div>

            @if (count($this->demoStatsByLeadOwner))
                @php
                    $itemCount = count($this->demoStatsByLeadOwner);
                    $containerClass = 'data-container items-' . ($itemCount <= 3 ? $itemCount : 'many');
                @endphp

                <div class="{{ $containerClass }}">
                    @foreach ($this->demoStatsByLeadOwner as $owner => $data)
                        <div
                            wire:click="openSlideOver('demo', '{{ $owner }}')"
                            class="relative cursor-pointer group data-item"
                        >
                        <div class="data-block" style="
                            background-color: {{ $ownerColors[$owner] ?? '#38b2ac' }};
                        ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} leads ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="
                    background-color: #e2e8f0;
                    color: #4a5568;
                    padding: 10px 20px;
                    border-radius: 8px;
                    width: 800px;
                    text-align: center;
                ">
                    No Data Found
                </div>
            @endif
        </div>

        {{-- Add RFQ --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px;">Add RFQs</div>

            @if (count($this->rfqTransferStatsByLeadOwner))
                @php
                    $itemCount = count($this->rfqTransferStatsByLeadOwner);
                    $containerClass = 'data-container items-' . ($itemCount <= 3 ? $itemCount : 'many');
                @endphp

                <div class="{{ $containerClass }}">
                    @foreach ($this->rfqTransferStatsByLeadOwner as $owner => $data)
                    <div
                        wire:click="openSlideOver('rfq', '{{ $owner }}')"
                        class="relative cursor-pointer group data-item"
                    >
                        <div class="data-block" style="
                            background-color: {{ $ownerColors[$owner] ?? '#38b2ac' }};
                        ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} leads ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="
                    background-color: #e2e8f0;
                    color: #4a5568;
                    padding: 10px 20px;
                    border-radius: 8px;
                    width: 800px;
                    text-align: center;
                ">
                    No Data Found
                </div>
            @endif
        </div>

        {{-- Add Automation --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px;">Automation Enabled</div>

            @if (count($this->automationStatsByLeadOwner))
                @php
                    $itemCount = count($this->automationStatsByLeadOwner);
                    $containerClass = 'data-container items-' . ($itemCount <= 3 ? $itemCount : 'many');
                @endphp

                <div class="{{ $containerClass }}">
                    @foreach ($this->automationStatsByLeadOwner as $owner => $data)
                    <div
                        wire:click="openSlideOver('automation', '{{ $owner }}')"
                        class="relative cursor-pointer group data-item"
                    >
                        <div class="data-block" style="
                            background-color: {{ $ownerColors[$owner] ?? '#38b2ac' }};
                        ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} leads ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="
                    background-color: #e2e8f0;
                    color: #4a5568;
                    padding: 10px 20px;
                    border-radius: 8px;
                    width: 800px;
                    text-align: center;
                ">
                    No Data Found
                </div>
            @endif
        </div>

        {{-- Archive --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px;">Archived Leads</div>

            @if (count($this->archiveStatsByLeadOwner))
                @php
                    $itemCount = count($this->archiveStatsByLeadOwner);
                    $containerClass = 'data-container items-' . ($itemCount <= 3 ? $itemCount : 'many');
                @endphp

                <div class="{{ $containerClass }}">
                    @foreach ($this->archiveStatsByLeadOwner as $owner => $data)
                    <div
                        wire:click="openSlideOver('archive', '{{ $owner }}')"
                        class="relative cursor-pointer group data-item"
                    >
                        <div class="data-block" style="
                            background-color: {{ $ownerColors[$owner] ?? '#38b2ac' }};
                        ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} leads ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="
                    background-color: #e2e8f0;
                    color: #4a5568;
                    padding: 10px 20px;
                    border-radius: 8px;
                    width: 800px;
                    text-align: center;
                ">
                    No Data Found
                </div>
            @endif
        </div>

        {{-- Call Attempt --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px;">Call Attempt (Active)</div>

            @if (count($this->callAttemptStatsByLeadOwner))
                @php
                    $itemCount = count($this->callAttemptStatsByLeadOwner);
                    $containerClass = 'data-container items-' . ($itemCount <= 3 ? $itemCount : 'many');
                @endphp

                <div class="{{ $containerClass }}">
                    @foreach ($this->callAttemptStatsByLeadOwner as $owner => $data)
                    <div
                        wire:click="openSlideOver('call', '{{ $owner }}')"
                        class="relative cursor-pointer group data-item"
                    >
                        <div class="data-block" style="
                            background-color: {{ $ownerColors[$owner] ?? '#38b2ac' }};
                        ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} leads ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="
                    background-color: #e2e8f0;
                    color: #4a5568;
                    padding: 10px 20px;
                    border-radius: 8px;
                    width: 800px;
                    text-align: center;
                ">
                    No Data Found
                </div>
            @endif
        </div>

        {{-- Inactive Call Attempt --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px;">Call Attempt (Inactive)</div>

            @if (count($this->inactiveCallAttemptStatsByLeadOwner))
                @php
                    $itemCount = count($this->inactiveCallAttemptStatsByLeadOwner);
                    $containerClass = 'data-container items-' . ($itemCount <= 3 ? $itemCount : 'many');
                @endphp

                <div class="{{ $containerClass }}">
                    @foreach ($this->inactiveCallAttemptStatsByLeadOwner as $owner => $data)
                    <div
                        wire:click="openSlideOver('inactivecall', '{{ $owner }}')"
                        class="relative cursor-pointer group data-item"
                    >
                        <div class="data-block" style="
                            background-color: {{ $ownerColors[$owner] ?? '#38b2ac' }};
                        ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} leads ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="
                    background-color: #e2e8f0;
                    color: #4a5568;
                    padding: 10px 20px;
                    border-radius: 8px;
                    width: 800px;
                    text-align: center;
                ">
                    No Data Found
                </div>
            @endif
        </div>

        <hr style="border: 0; height: 1px; background-color: #e2e8f0; width: 100%; margin: 15px 0;">

        {{-- Total Action Tasks --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px;">Total Action Tasks</div>

            @if (count($this->totalActionTasksByLeadOwner))
                @php
                    $itemCount = count($this->totalActionTasksByLeadOwner);
                    $containerClass = 'data-container items-' . ($itemCount <= 3 ? $itemCount : 'many');
                @endphp

                <div class="{{ $containerClass }}">
                    @foreach ($this->totalActionTasksByLeadOwner as $owner => $data)
                        <div
                            {{-- wire:click="openSlideOver('total', '{{ $owner }}')" --}}
                            class="relative group data-item"
                        >
                        <div class="data-block" style="
                            background-color: {{ $ownerColors[$owner] ?? '#4ade80' }}; {{-- Green color for totals --}}
                        ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} tasks ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div style="
                    background-color: #e2e8f0;
                    color: #4a5568;
                    padding: 10px 20px;
                    border-radius: 8px;
                    width: 800px;
                    text-align: center;
                ">
                    No Data Found
                </div>
            @endif
        </div>
    </div>

    <div
    x-data="{ open: @entangle('showSlideOver') }"
    x-show="open"
    @keydown.window.escape="open = false"
    class="fixed inset-0 z-[200] flex justify-end bg-black/40 backdrop-blur-sm transition-opacity duration-200"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    >
        <!-- Slide-over content -->
        <div
            class="w-full h-full max-w-md p-6 overflow-y-auto bg-white shadow-xl"
            @click.away="open = false"
        >
            <!-- Header -->
            <br><br>
            <div class="flex items-center justify-between p-4 border-b">
                <h2 class="text-lg font-bold text-gray-800">{{ $slideOverTitle }}</h2>
                <button @click="open = false" class="text-2xl leading-none text-gray-500 hover:text-gray-700">&times;</button>
            </div>

            <!-- Scrollable content -->
            <div class="flex-1 p-4 space-y-2 overflow-y-auto">
                @forelse ($leadList as $lead)
                    @php
                        $companyName = $lead->companyDetail->company_name ?? 'N/A';
                        $shortened = strtoupper(\Illuminate\Support\Str::limit($companyName, 20, '...'));
                        $encryptedId = \App\Classes\Encryptor::encrypt($lead->id);
                    @endphp

                    <a
                        href="{{ url('admin/leads/' . $encryptedId) }}"
                        target="_blank"
                        title="{{ $companyName }}"
                        class="block px-4 py-2 text-sm font-medium text-blue-600 transition border rounded bg-gray-50 hover:bg-blue-50 hover:text-blue-800"
                    >
                        {{ $shortened }}
                    </a>
                @empty
                    <div class="text-sm text-gray-500">No data found.</div>
                @endforelse
            </div>
        </div>
    </div>
</x-filament-panels::page>
