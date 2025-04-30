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

            <div style="display: flex; gap: 10px; width: 800px;">
                @if (count($this->leadOwnerPickupCounts))
                    @foreach ($this->leadOwnerPickupCounts as $owner => $data)
                    <div
                        wire:click="openSlideOver('pickup', '{{ $owner }}')"
                        class="relative cursor-pointer group"
                        style="width: calc({{ $data['percentage'] }}%); min-width: 70px;"
                    >
                        <div style="
                            background-color: {{
                                $loop->index % 3 === 0 ? '#38b2ac' :
                                ($loop->index % 3 === 1 ? '#f6ad55' : '#7f9cf5')
                            }};
                            color: white;
                            padding: 10px 20px;
                            border-radius: 8px;
                            text-align: center;
                            overflow: hidden;
                            white-space: nowrap;
                            ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} leads ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
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

        {{-- Add Demo --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px;">Demo Assigned</div>

            <div style="display: flex; gap: 10px; width: 800px;">
                @if (count($this->demoStatsByLeadOwner))
                    @foreach ($this->demoStatsByLeadOwner as $owner => $data)
                        <div
                            wire:click="openSlideOver('demo', '{{ $owner }}')"
                            class="relative cursor-pointer group"
                            style="width: calc({{ $data['percentage'] }}%); min-width: 70px;"
                        >
                        <div style="
                            background-color: {{
                                $loop->index % 3 === 0 ? '#38b2ac' :
                                ($loop->index % 3 === 1 ? '#f6ad55' : '#7f9cf5')
                            }};
                            color: white;
                            padding: 10px 20px;
                            border-radius: 8px;
                            text-align: center;
                            overflow: hidden;
                            white-space: nowrap;
                            ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} leads ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
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

        {{-- Add RFQ --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px;">Add RFQs</div>

            <div style="display: flex; gap: 10px; width: 800px;">
                @if (count($this->rfqTransferStatsByLeadOwner))
                    @foreach ($this->rfqTransferStatsByLeadOwner as $owner => $data)
                    <div
                        wire:click="openSlideOver('rfq', '{{ $owner }}')"
                        class="relative cursor-pointer group"
                        style="width: calc({{ $data['percentage'] }}%); min-width: 70px;"
                    >
                        <div style="
                            background-color: {{
                                $loop->index % 3 === 0 ? '#38b2ac' :
                                ($loop->index % 3 === 1 ? '#f6ad55' : '#7f9cf5')
                            }};
                            color: white;
                            padding: 10px 20px;
                            border-radius: 8px;
                            text-align: center;
                            overflow: hidden;
                            white-space: nowrap;
                            ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} leads ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
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

        {{-- Add Automation --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px;">Automation Enabled</div>

            <div style="display: flex; gap: 10px; width: 800px;">
                @if (count($this->automationStatsByLeadOwner))
                    @foreach ($this->automationStatsByLeadOwner as $owner => $data)
                    <div
                        wire:click="openSlideOver('automation', '{{ $owner }}')"
                        class="relative cursor-pointer group"
                        style="width: calc({{ $data['percentage'] }}%); min-width: 70px;"
                    >
                        <div style="
                            background-color: {{
                                $loop->index % 3 === 0 ? '#38b2ac' :
                                ($loop->index % 3 === 1 ? '#f6ad55' : '#7f9cf5')
                            }};
                            color: white;
                            padding: 10px 20px;
                            border-radius: 8px;
                            text-align: center;
                            overflow: hidden;
                            white-space: nowrap;
                            ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} leads ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
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

        {{-- Archive --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px;">Archived Leads</div>

            <div style="display: flex; gap: 10px; width: 800px;">
                @if (count($this->archiveStatsByLeadOwner))
                    @foreach ($this->archiveStatsByLeadOwner as $owner => $data)
                    <div
                        wire:click="openSlideOver('archive', '{{ $owner }}')"
                        class="relative cursor-pointer group"
                        style="width: calc({{ $data['percentage'] }}%); min-width: 70px;"
                    >
                        <div style="
                            background-color: {{
                                $loop->index % 3 === 0 ? '#38b2ac' :
                                ($loop->index % 3 === 1 ? '#f6ad55' : '#7f9cf5')
                            }};
                            color: white;
                            padding: 10px 20px;
                            border-radius: 8px;
                            text-align: center;
                            overflow: hidden;
                            white-space: nowrap;
                            ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} leads ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
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

        {{-- Call Attempt --}}
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 150px;">Call Attempt</div>

            <div style="display: flex; gap: 10px; width: 800px;">
                @if (count($this->callAttemptStatsByLeadOwner))
                    @foreach ($this->callAttemptStatsByLeadOwner as $owner => $data)
                    <div
                        wire:click="openSlideOver('call', '{{ $owner }}')"
                        class="relative cursor-pointer group"
                        style="width: calc({{ $data['percentage'] }}%); min-width: 70px;"
                    >
                        <div style="
                            background-color: {{
                                $loop->index % 3 === 0 ? '#38b2ac' :
                                ($loop->index % 3 === 1 ? '#f6ad55' : '#7f9cf5')
                            }};
                            color: white;
                            padding: 10px 20px;
                            border-radius: 8px;
                            text-align: center;
                            overflow: hidden;
                            white-space: nowrap;
                            ">
                            {{ \Illuminate\Support\Str::of($owner)->after(' ')->before(' ') }} - {{ $data['count'] }}
                        </div>

                        <div class="hover-message">
                            {{ $data['count'] }} leads ({{ $data['percentage'] }}%)
                        </div>
                    </div>
                    @endforeach
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
            @click.away="open = false"  <!-- ✅ Close when clicking outside -->
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
