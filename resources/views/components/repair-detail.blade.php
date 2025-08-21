<div class="p-6 bg-white rounded-lg">
    <!-- Title -->
    <div class="mb-4 text-center">
        <h2 class="text-lg font-semibold text-gray-800">Repair Handover Details</h2>
        <p class="text-blue-600">{{ $record->company_name ?? 'Repair Ticket' }}</p>
    </div>

    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2">
        <div>
            <!-- Company Information -->
            <div class="mb-6">
                <p class="mb-2">
                    <span class="font-semibold">PIC Name:</span>
                    {{ $record->pic_name }}
                </p>
                <p class="mb-2">
                    <span class="font-semibold">PIC HP Number:</span>
                    {{ $record->pic_phone }}
                </p>
                <p class="mb-2">
                    <span class="font-semibold">PIC Email Address:</span>
                    <a href="#"
                    x-data
                    @click.prevent="$dispatch('open-email-modal')"
                    style="color: #2563EB; text-decoration: none; font-weight: 500;"
                    onmouseover="this.style.textDecoration='underline'"
                    onmouseout="this.style.textDecoration='none'">
                        View Email
                    </a>
                </p>

                <!-- Email Modal -->
                <div
                    x-data="{ emailModalOpen: false }"
                    @open-email-modal.window="emailModalOpen = true"
                    x-show="emailModalOpen"
                    x-transition
                    @click.outside="emailModalOpen = false"
                    class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50"
                    style="display: none;">
                    <div class="relative w-full max-w-md p-6 mx-auto mt-20 bg-white rounded-lg shadow-xl" @click.away="emailModalOpen = false">
                        <div class="flex items-start justify-between mb-4">
                            <button type="button" @click="emailModalOpen = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1.5 ml-auto inline-flex items-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="p-4 mb-4 bg-gray-100 rounded-lg">
                            @if($record->pic_email)
                                <div class="flex items-center mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mr-2 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    <span class="font-medium">Email Address</span>
                                </div>
                                <p class="break-words">
                                    <a href="mailto:{{ $record->pic_email }}" class="text-blue-600 hover:underline">
                                        {{ $record->pic_email }}
                                    </a>
                                </p>
                            @else
                                <p class="italic text-gray-500">No email address available</p>
                            @endif
                        </div>

                        <div class="flex justify-center">
                            @if($record->pic_email)
                                <a href="mailto:{{ $record->pic_email }}" class="inline-flex items-center px-4 py-2 mr-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                    </svg>
                                    Send Email
                                </a>
                            @endif
                            <button @click="emailModalOpen = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
                <p class="mb-2">
                    <span class="font-semibold">Company Address:</span>
                    <a href="#"
                       x-data
                       @click.prevent="$dispatch('open-address-modal')"
                       style="color: #2563EB; text-decoration: none; font-weight: 500;"
                       onmouseover="this.style.textDecoration='underline'"
                       onmouseout="this.style.textDecoration='none'">
                        View Address
                    </a>
                </p>

                <!-- Address Modal -->
                <div
                     x-data="{ addressModalOpen: false }"
                     @open-address-modal.window="addressModalOpen = true"
                     x-show="addressModalOpen"
                     x-transition
                     @click.outside="addressModalOpen = false"
                     class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50"
                     style="display: none;">
                    <div class="relative w-full max-w-md p-6 mx-auto mt-20 bg-white rounded-lg shadow-xl" @click.away="addressModalOpen = false">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Company Address</h3>
                            <button type="button" @click="addressModalOpen = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1.5 ml-auto inline-flex items-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="p-4 mb-4 bg-gray-100 rounded-lg">
                            <p class="whitespace-pre-line">{{ $record->address ?: 'No address available' }}</p>
                        </div>

                        <!-- Google Maps Link (if address is available) -->
                        @if($record->address)
                            <div class="mb-4 text-center">
                                <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($record->address) }}"
                                   target="_blank"
                                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                    Open in Google Maps
                                </a>
                            </div>
                        @endif

                        <div class="text-center">
                            <button @click="addressModalOpen = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
                <p class="mb-2">
                    <span class="font-semibold">Zoho Ticket Number:</span>
                    {{ $record->zoho_ticket ?? 'N/A' }}
                </p>
                <p class="mb-4">
                    <span class="font-semibold">Repair Handover Form:</span>
                    @if($record->handover_pdf)
                        <a href="{{ asset('storage/' . $record->handover_pdf) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Click Here</a>
                    @else
                        <span style="color: #6B7280;">Click Here</span>
                    @endif
                </p>
            </div>

            <!-- Separator Line -->
            <hr class="my-4 border-gray-300">

            <!-- Ticket Information -->
            <div class="mb-6">
                <p class="flex mb-2">
                    <span class="mr-2 font-semibold">Status:</span>&nbsp;
                    <span class="
                        @if($record->status == 'Draft') bg-gray-200 text-gray-800
                        @elseif($record->status == 'New') bg-red-100 text-red-800
                        @elseif($record->status == 'In Progress') bg-yellow-100 text-yellow-800
                        @elseif($record->status == 'Awaiting Parts') bg-blue-100 text-blue-800
                        @elseif($record->status == 'Resolved') bg-green-100 text-green-800
                        @elseif($record->status == 'Closed') bg-gray-100 text-gray-800
                        @else bg-gray-100 text-gray-800 @endif
                    ">
                        {{ $record->status }}
                    </span>
                </p>
                <p class="mb-2">
                    <span class="font-semibold">Repair Handover ID:</span>
                    OR_250{{ str_pad($record->id, 3, '0', STR_PAD_LEFT) }}
                </p>
                <p class="mb-2">
                    <span class="font-semibold">Submitted Date:</span>
                    {{ $record->created_at->format('d M Y, h:i A') }}
                </p>
            </div>
        </div>

        <div>
            <!-- Remarks Section -->
            <div x-data="{ remarkOpen: false }">
                <p class="mb-2">
                    <span class="font-semibold">Repair Remarks:</span>
                    <a href="#"
                       @click.prevent="remarkOpen = true"
                       style="color: #2563EB; text-decoration: none; font-weight: 500;"
                       onmouseover="this.style.textDecoration='underline'"
                       onmouseout="this.style.textDecoration='none'">
                        View Remarks
                    </a>
                </p>

                <!-- Remarks Modal -->
                <div x-show="remarkOpen"
                     x-transition
                     @click.outside="remarkOpen = false"
                     class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50">
                    <div class="relative w-full max-w-2xl p-6 mx-auto mt-20 bg-white rounded-lg shadow-xl" @click.away="remarkOpen = false">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Repair Remarks</h3>
                            <button type="button" @click="remarkOpen = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1.5 ml-auto inline-flex items-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="max-h-[60vh] overflow-y-auto">
                            @if($record->remarks)
                                @php
                                    $remarks = is_string($record->remarks) ? json_decode($record->remarks, true) : $record->remarks;
                                @endphp

                                @if(is_array($remarks) && count($remarks) > 0)
                                    <div class="space-y-4">
                                        @foreach($remarks as $index => $remark)
                                            <div class="p-4 border border-gray-200 rounded-lg">
                                                <h4 class="mb-2 font-semibold text-gray-700 text-md">Remark {{ $index + 1 }}</h4>

                                                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                                    <!-- Remark (Left Column) -->
                                                    <div class="p-3 text-gray-800 bg-gray-100 rounded">
                                                        <h5 class="mb-2 font-medium">Remark:</h5>
                                                        <div style='white-space: pre-line'>{{ strtoupper($remark['remark'] ?? 'No remarks provided') }}</div>
                                                    </div>

                                                    <!-- Attachments (Right Column) -->
                                                    <div>
                                                        @if(!empty($remark['attachments']))
                                                            @php
                                                                $attachments = is_string($remark['attachments'])
                                                                    ? json_decode($remark['attachments'], true)
                                                                    : $remark['attachments'];
                                                            @endphp

                                                            @if(is_array($attachments) && count($attachments) > 0)
                                                                <div class="h-full p-3 rounded bg-gray-50">
                                                                    <h5 class="mb-3 font-medium">Attachments:</h5>
                                                                    <div class="space-y-2">
                                                                        @foreach($attachments as $attIndex => $attachment)
                                                                            <a
                                                                                href="{{ asset('storage/' . $attachment) }}"
                                                                                target="_blank"
                                                                                class="flex items-center px-3 py-2 text-sm text-blue-600 border border-blue-200 rounded-md bg-blue-50 hover:bg-blue-100"
                                                                            >
                                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-2" viewBox="0 0 20 20" fill="currentColor">
                                                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                                                                                </svg>
                                                                                Attachment {{ $attIndex + 1 }}
                                                                            </a>
                                                                        @endforeach
                                                                    </div>
                                                                </div>
                                                            @else
                                                                <div class="flex items-center justify-center h-full p-3 rounded bg-gray-50">
                                                                    <p class="italic text-gray-500">No attachments available</p>
                                                                </div>
                                                            @endif
                                                        @else
                                                            <div class="flex items-center justify-center h-full p-3 rounded bg-gray-50">
                                                                <p class="italic text-gray-500">No attachments available</p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-center text-gray-500">No remarks available</p>
                                @endif
                            @else
                                <p class="text-center text-gray-500">No remarks available</p>
                            @endif
                        </div>

                        <div class="mt-4 text-center">
                            <button @click="remarkOpen = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>


            {{-- Invoice Attachment --}}
            <div class="mb-2">
                @php
                    $invoiceFiles = $record->invoice_file
                        ? (is_string($record->invoice_file) ? json_decode($record->invoice_file, true) : $record->invoice_file)
                        : [];
                @endphp

                <p>
                    <span class="font-semibold">Invoice Attachment:</span>
                    @if(is_array($invoiceFiles) && count($invoiceFiles) > 0)
                        @foreach($invoiceFiles as $index => $file)
                            <a href="{{ url('storage/' . $file) }}"
                            target="_blank"
                            class="ml-2 font-medium text-blue-600"
                            style="color: #2563EB; text-decoration: none; font-weight: 500;"
                            onmouseover="this.style.textDecoration='underline'"
                            onmouseout="this.style.textDecoration='none'">
                                Invoice {{ $index + 1 }}
                            </a>
                            @if(!$loop->last)
                                <span class="text-gray-400">/</span>
                            @endif
                        @endforeach
                    @else
                        <span class="ml-2">Not Available</span>
                    @endif
                </p>
            </div>

            {{-- Sales Order Attachment --}}
            <div class="mb-2">
                @php
                    $salesOrderFiles = $record->sales_order_file
                        ? (is_string($record->sales_order_file) ? json_decode($record->sales_order_file, true) : $record->sales_order_file)
                        : [];
                @endphp

                <p>
                    <span class="font-semibold">Sales Order Attachment:</span>
                    @if(is_array($salesOrderFiles) && count($salesOrderFiles) > 0)
                        @foreach($salesOrderFiles as $index => $file)
                            <a href="{{ url('storage/' . $file) }}"
                               target="_blank"
                               class="ml-2 font-medium text-blue-600"
                               style="color: #2563EB; text-decoration: none; font-weight: 500;"
                               onmouseover="this.style.textDecoration='underline'"
                               onmouseout="this.style.textDecoration='none'">
                                Sales Order {{ $index + 1 }}
                            </a>
                            @if(!$loop->last)
                                <span class="text-gray-400">/</span>
                            @endif
                        @endforeach
                    @else
                        <span class="ml-2">Not Available</span>
                    @endif
                </p>
            </div>

            {{-- Payment Slip Attachment --}}
            <div class="mb-2">
                @php
                    $paymentSlipFiles = $record->payment_slip_file
                        ? (is_string($record->payment_slip_file) ? json_decode($record->payment_slip_file, true) : $record->payment_slip_file)
                        : [];
                @endphp

                <p>
                    <span class="font-semibold">Payment Slip Attachment:</span>
                    @if(is_array($paymentSlipFiles) && count($paymentSlipFiles) > 0)
                        @foreach($paymentSlipFiles as $index => $file)
                            <a href="{{ url('storage/' . $file) }}"
                            target="_blank"
                            class="ml-2 font-medium text-blue-600"
                            style="color: #2563EB; text-decoration: none; font-weight: 500;"
                            onmouseover="this.style.textDecoration='underline'"
                            onmouseout="this.style.textDecoration='none'">
                                Payment Slip {{ $index + 1 }}
                            </a>
                            @if(!$loop->last)
                                <span class="text-gray-400">/</span>
                            @endif
                        @endforeach
                    @else
                        <span class="ml-2">Not Available</span>
                    @endif
                </p>
            </div>

            <!-- Separator Line -->
            <hr class="my-4 border-gray-300">

            <!-- Devices Section -->
            <div x-data="{ deviceModalOpen: false }">
                <p class="mb-2">
                    <span class="font-semibold">Devices:</span>
                    <a href="#"
                    @click.prevent="deviceModalOpen = true"
                    style="color: #2563EB; text-decoration: none; font-weight: 500;"
                    onmouseover="this.style.textDecoration='underline'"
                    onmouseout="this.style.textDecoration='none'">
                        View Devices
                    </a>
                </p>

                <!-- Devices Modal -->
                <div x-show="deviceModalOpen"
                    x-transition
                    @click.outside="deviceModalOpen = false"
                    class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50">
                    <div class="relative w-auto p-6 mx-auto mt-20 bg-white rounded-lg shadow-xl" @click.away="deviceModalOpen = false">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Device Details</h3>
                            <button type="button" @click="deviceModalOpen = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1.5 ml-auto inline-flex items-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                        <div>
                            <table class="min-w-full border border-collapse border-gray-300">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-4 py-2 text-left border border-gray-300">Device Model</th>
                                        <th class="px-4 py-2 text-left border border-gray-300">Serial Number</th>
                                        <th class="px-4 py-2 text-left border border-gray-300">Invoice Date</th>
                                        <th class="px-4 py-2 text-left border border-gray-300">Warranty Status</th>
                                        <th class="px-4 py-2 text-left border border-gray-300">CSO</th>
                                        <th class="px-4 py-2 text-left border border-gray-300">Quotation</th>
                                        <th class="px-4 py-2 text-left border border-gray-300">Spare Parts Invoice</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @if($record->devices)
                                        @php
                                            $devices = is_string($record->devices)
                                                ? json_decode($record->devices, true)
                                                : $record->devices;

                                            // Get warranty information
                                            $devicesWarranty = !empty($record->devices_warranty)
                                                ? (is_string($record->devices_warranty)
                                                    ? json_decode($record->devices_warranty, true)
                                                    : $record->devices_warranty)
                                                : [];

                                            // Create lookup by serial number
                                            $warrantyBySerial = [];
                                            if (is_array($devicesWarranty)) {
                                                foreach ($devicesWarranty as $warranty) {
                                                    if (!empty($warranty['device_serial'])) {
                                                        $warrantyBySerial[$warranty['device_serial']] = $warranty;
                                                    }
                                                }
                                            }
                                        @endphp

                                        @if(is_array($devices) && count($devices) > 0)
                                            @foreach($devices as $index => $device)
                                                @php
                                                    $serial = $device['device_serial'] ?? '';
                                                    $hasWarranty = !empty($warrantyBySerial[$serial]);
                                                    $warrantyStatus = $hasWarranty ? ($warrantyBySerial[$serial]['warranty_status'] ?? null) : null;
                                                    $invoiceDate = $hasWarranty ? ($warrantyBySerial[$serial]['invoice_date'] ?? null) : null;

                                                    // Get CSO file path if available
                                                    $csoFile = $hasWarranty && isset($warrantyBySerial[$serial]['cso_file'])
                                                        ? $warrantyBySerial[$serial]['cso_file']
                                                        : null;

                                                    // Get quotation file path or ID if available
                                                    $quotationFile = null;
                                                    $quotationId = null;

                                                    if ($hasWarranty && $warrantyStatus === 'Out of Warranty') {
                                                        // Check if quotation is stored directly
                                                        if (isset($warrantyBySerial[$serial]['quotation_file'])) {
                                                            $quotationFile = $warrantyBySerial[$serial]['quotation_file'];
                                                        }

                                                        // Check if quotation ID is stored
                                                        if (isset($warrantyBySerial[$serial]['quotation_id'])) {
                                                            $quotationId = $warrantyBySerial[$serial]['quotation_id'];
                                                        }
                                                    }
                                                @endphp
                                                <tr class="{{ $index % 2 == 0 ? 'bg-white' : 'bg-gray-50' }}">
                                                    <td class="px-4 py-2 border border-gray-300">{{ $device['device_model'] }}</td>
                                                    <td class="px-4 py-2 border border-gray-300">{{ $device['device_serial'] }}</td>
                                                    <td class="px-4 py-2 border border-gray-300">
                                                        {{ $invoiceDate ? date('d M Y', strtotime($invoiceDate)) : 'N/A' }}
                                                    </td>
                                                    <td class="px-4 py-2 border border-gray-300">
                                                        @if($warrantyStatus == 'In Warranty')
                                                            <span style="display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 500; color: #166534; background-color: #dcfce7; border-radius: 9999px;">
                                                                In Warranty
                                                            </span>
                                                        @elseif($warrantyStatus == 'Out of Warranty')
                                                            <span style="display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 500; color: #991b1b; background-color: #fee2e2; border-radius: 9999px;">
                                                                Out of Warranty
                                                            </span>
                                                        @else
                                                            <span style="display: inline-block; padding: 0.25rem 0.5rem; font-size: 0.75rem; font-weight: 500; color: #1f2937; background-color: #f3f4f6; border-radius: 9999px;">
                                                                Unknown
                                                            </span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 border border-gray-300">
                                                        @if($csoFile)
                                                            <a href="{{ asset('storage/' . $csoFile) }}"
                                                            target="_blank"
                                                            class="flex items-center justify-center px-2 py-1 text-xs text-blue-600 border border-blue-200 rounded-md bg-blue-50 hover:bg-blue-100">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                                View CSO
                                                            </a>
                                                        @else
                                                            <span class="text-xs text-gray-500">Not available</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 border border-gray-300">
                                                        @if($warrantyStatus == 'Out of Warranty')
                                                            @if($quotationFile)
                                                                <a href="{{ asset('storage/' . $quotationFile) }}"
                                                                target="_blank"
                                                                class="flex items-center justify-center px-2 py-1 text-xs text-green-600 border border-green-200 rounded-md bg-green-50 hover:bg-green-100">
                                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                    </svg>
                                                                    View Quotation
                                                                </a>
                                                            @elseif($quotationId && $quotationId != 'none')
                                                                @php
                                                                    // Try to load quotation details if we have just the ID
                                                                    $quotation = \App\Models\Quotation::find($quotationId);

                                                                    // Generate the quotation URL using the proper route
                                                                    $quotationUrl = $quotation ? route('pdf.print-quotation-v2', $quotation) : null;

                                                                    $quotationRef = $quotation ? ($quotation->quotation_reference_no ?? $quotation->quotation_no ?? "#{$quotationId}") : "#{$quotationId}";
                                                                @endphp

                                                                @if($quotation)
                                                                    <a href="{{ $quotationUrl }}"
                                                                    target="_blank"
                                                                    class="flex items-center justify-center px-2 py-1 text-xs text-green-600 border border-green-200 rounded-md bg-green-50 hover:bg-green-100">
                                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                        </svg>
                                                                        {{ $quotationRef }}
                                                                    </a>
                                                                @else
                                                                    <span class="text-xs text-gray-500">Quotation {{ $quotationRef }}</span>
                                                                @endif
                                                            @else
                                                                <span class="text-xs text-gray-500">Not available</span>
                                                            @endif
                                                        @else
                                                            <span class="text-xs italic text-gray-400">Not required</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2 border border-gray-300">
                                                        @php
                                                            // Get spare parts invoice if available
                                                            $sparePartsInvoice = $hasWarranty && isset($warrantyBySerial[$serial]['invoice_sparepart'])
                                                                ? $warrantyBySerial[$serial]['invoice_sparepart']
                                                                : null;
                                                        @endphp

                                                        @if($sparePartsInvoice)
                                                            <a href="{{ asset('storage/' . $sparePartsInvoice) }}"
                                                            target="_blank"
                                                            class="flex items-center justify-center px-2 py-1 text-xs text-orange-600 border border-orange-200 rounded-md bg-orange-50 hover:bg-orange-100">
                                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                                View Invoice
                                                            </a>
                                                        @else
                                                            <span class="text-xs text-gray-500">Not available</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="6" class="px-4 py-2 text-center border border-gray-300">No device information available</td>
                                            </tr>
                                        @endif
                                    @elseif($record->device_model)
                                        <tr>
                                            <td class="px-4 py-2 border border-gray-300">{{ $record->device_model }}</td>
                                            <td class="px-4 py-2 border border-gray-300">{{ $record->device_serial }}</td>
                                            <td class="px-4 py-2 border border-gray-300">
                                                {{ $record->invoice_date ? date('d M Y', strtotime($record->invoice_date)) : 'N/A' }}
                                            </td>
                                            <td class="px-4 py-2 border border-gray-300">
                                                @if($record->warranty_status == 'In Warranty')
                                                    <span class="px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                                                        In Warranty
                                                    </span>
                                                @elseif($record->warranty_status == 'Out of Warranty')
                                                    <span class="px-2 py-1 text-xs font-medium text-red-800 bg-red-100 rounded-full">
                                                        Out of Warranty
                                                    </span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-medium text-gray-800 bg-gray-100 rounded-full">
                                                        Unknown
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2 border border-gray-300">
                                                <span class="text-xs text-gray-500">Not available</span>
                                            </td>
                                            <td class="px-4 py-2 border border-gray-300">
                                                <span class="text-xs text-gray-500">Not available</span>
                                            </td>
                                        </tr>
                                    @else
                                        <tr>
                                            <td colspan="6" class="px-4 py-2 text-center border border-gray-300">No device information available</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>

                            <div class="mt-4 text-center">
                                <button @click="deviceModalOpen = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div x-data="{ remarkOpen: false }">
                <p class="mb-2">
                    <span class="font-semibold">Technician Remarks:</span>
                    <a href="#"
                       @click.prevent="remarkOpen = true"
                       style="color: #2563EB; text-decoration: none; font-weight: 500;"
                       onmouseover="this.style.textDecoration='underline'"
                       onmouseout="this.style.textDecoration='none'">
                        View Remarks
                    </a>
                </p>

                <!-- Technician Remarks Modal -->
                <div x-show="remarkOpen"
                     x-transition
                     @click.outside="remarkOpen = false"
                     class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50">
                    <div class="relative w-full max-w-2xl p-6 mx-auto mt-20 bg-white rounded-lg shadow-xl" @click.away="remarkOpen = false">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Technician Remarks</h3>
                            <button type="button" @click="remarkOpen = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1.5 ml-auto inline-flex items-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="max-h-[60vh] overflow-y-auto">
                            @if($record->repair_remark)
                                @php
                                    $deviceRepairs = is_string($record->repair_remark)
                                        ? json_decode($record->repair_remark, true)
                                        : $record->repair_remark;
                                @endphp

                                @if(is_array($deviceRepairs) && count($deviceRepairs) > 0)
                                    @foreach($deviceRepairs as $deviceRepair)
                                        <div class="mb-4">
                                            <table class="w-full mb-4 border border-collapse border-gray-300">
                                                <tr>
                                                    <th colspan="2" class="px-4 py-2 text-left bg-gray-100 border border-gray-300">
                                                        Device Model: {{ $deviceRepair['device_model'] ?? 'N/A' }}
                                                        &nbsp;|&nbsp;
                                                        Serial Number: {{ $deviceRepair['device_serial'] ?? 'N/A' }}
                                                    </th>
                                                </tr>

                                                @if(!empty($deviceRepair['remarks']) && is_array($deviceRepair['remarks']))
                                                    @php
                                                        // Just get the first remark for simplicity
                                                        $remark = $deviceRepair['remarks'][0] ?? null;
                                                    @endphp
                                                    @if($remark)
                                                        <tr>
                                                            <td class="px-4 py-2 border border-gray-300" style ='width:70%'>
                                                                <strong>Repair Remark:</strong><br>
                                                                <div style='white-space: pre-line'>{{ strtoupper(str_replace('\n', PHP_EOL, $remark['remark'] ?? 'No remarks provided')) }}</div>
                                                            </td>
                                                            <td class="px-4 py-2 border border-gray-300" style ='width:30%'>
                                                                <strong>Attachment:</strong><br>
                                                                @if(!empty($remark['attachments']) && is_array($remark['attachments']) && count($remark['attachments']) > 0)
                                                                    <div>
                                                                        @foreach($remark['attachments'] as $attIndex => $attachment)
                                                                            <div class="mb-1">
                                                                                <a href="{{ asset('storage/' . $attachment) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">
                                                                                    Attachment {{ $attIndex + 1 }}
                                                                                </a>
                                                                            </div>
                                                                        @endforeach
                                                                    </div>
                                                                @else
                                                                    <div class="text-gray-500">No attachments</div>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @else
                                                        <tr>
                                                            <td class="px-4 py-2 border border-gray-300">
                                                                <div class="text-gray-500">No remarks provided</div>
                                                            </td>
                                                            <td class="px-4 py-2 border border-gray-300">
                                                                <div class="text-gray-500">No attachments</div>
                                                            </td>
                                                        </tr>
                                                    @endif
                                                @else
                                                    <tr>
                                                        <td class="px-4 py-2 border border-gray-300">
                                                            <div class="text-gray-500">No remarks provided</div>
                                                        </td>
                                                        <td class="px-4 py-2 border border-gray-300">
                                                            <div class="text-gray-500">No attachments</div>
                                                        </td>
                                                    </tr>
                                                @endif

                                                <tr>
                                                    <td colspan="2" class="px-4 py-2 border border-gray-300">
                                                        <strong>Spare Parts Required:</strong><br>
                                                        @if(!empty($deviceRepair['spare_parts']) && is_array($deviceRepair['spare_parts']) && count($deviceRepair['spare_parts']) > 0)
                                                            <ul class="pl-5 mt-1 list-disc">
                                                                @foreach($deviceRepair['spare_parts'] as $part)
                                                                    {{ $part['name'] ?? 'Unknown Part' }}
                                                                    @if(!empty($part['code']))
                                                                        ({{ $part['code'] }})
                                                                    @endif
                                                                    @if(!$loop->last)
                                                                        <span class="text-gray-400">/</span>
                                                                    @endif
                                                                @endforeach
                                                            </ul>
                                                        @else
                                                            <div class="text-gray-500">No spare parts required</div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    @endforeach
                                @else
                                    <p class="text-center text-gray-500">No technician repair assessments available</p>
                                @endif
                            @else
                                <p class="text-center text-gray-500">No technician repair assessments available</p>
                            @endif
                        </div>

                        <div class="mt-4 text-center">
                            <button @click="remarkOpen = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div x-data="{ doneRepairModalOpen: false }">
                <p class="mb-2">
                    <span class="font-semibold">Completed Repair Details:</span>
                    <a href="#"
                    @click.prevent="doneRepairModalOpen = true"
                    style="color: #2563EB; text-decoration: none; font-weight: 500;"
                    onmouseover="this.style.textDecoration='underline'"
                    onmouseout="this.style.textDecoration='none'">
                        View Details
                    </a>
                </p>

                <!-- Done Repair Modal -->
                <div x-show="doneRepairModalOpen"
                    x-transition
                    @click.outside="doneRepairModalOpen = false"
                    class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50">
                    <div class="relative w-full max-w-4xl p-6 mx-auto mt-10 bg-white rounded-lg shadow-xl" @click.away="doneRepairModalOpen = false">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Completed Repair Details</h3>
                            <button type="button" @click="doneRepairModalOpen = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1.5 ml-auto inline-flex items-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>

                        <div class="p-6 overflow-y-auto" style="max-height: 70vh;">
                            <!-- Onsite Repair Remark -->
                            <div class="mb-6">
                                <h4 class="pb-2 mb-2 font-semibold text-gray-700 border-b text-md">Repair Remarks</h4>
                                <div class="p-4 rounded-lg bg-gray-50">
                                    @if(!empty($record->onsite_repair_remark))
                                        <div class="text-gray-800 whitespace-pre-line">{{ $record->onsite_repair_remark }}</div>
                                    @else
                                        <p class="italic text-gray-500">No onsite repair remarks provided</p>
                                    @endif
                                </div>
                            </div>
                            <br>

                            <!-- Spare Parts Used -->
                            <div class="mb-6">
                                <h4 class="pb-2 mb-2 font-semibold text-gray-700 border-b text-md" style='color: green;'>Spare Parts Used</h4>
                                @php
                                    // Get all spare parts from repair_remark
                                    $allParts = [];
                                    $deviceInfo = [];

                                    // First collect all parts and device info from repair_remark
                                    if(!empty($record->repair_remark)) {
                                        $deviceRepairs = is_string($record->repair_remark)
                                            ? json_decode($record->repair_remark, true)
                                            : $record->repair_remark;

                                        if(is_array($deviceRepairs)) {
                                            foreach($deviceRepairs as $repair) {
                                                if(!empty($repair['device_model']) && !empty($repair['spare_parts'])) {
                                                    foreach($repair['spare_parts'] as $part) {
                                                        if(!empty($part['part_id'])) {
                                                            $partId = $part['part_id'];
                                                            $partName = $part['name'] ?? 'Unknown Part';

                                                            // Store part in allParts
                                                            $allParts[$partId] = [
                                                                'part_id' => $partId,
                                                                'part_name' => $partName,
                                                                'device_model' => $repair['device_model'],
                                                                'device_serial' => $repair['device_serial'] ?? 'N/A'
                                                            ];

                                                            // Also store in deviceInfo for lookup
                                                            $deviceInfo[$partId] = [
                                                                'device_model' => $repair['device_model'],
                                                                'device_serial' => $repair['device_serial'] ?? 'N/A'
                                                            ];
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    // Get unused parts
                                    $unusedParts = !empty($record->spare_parts_unused)
                                        ? (is_string($record->spare_parts_unused)
                                            ? json_decode($record->spare_parts_unused, true)
                                            : $record->spare_parts_unused)
                                        : [];

                                    // Build a lookup for unused parts by part_id
                                    $unusedPartIds = [];
                                    if(is_array($unusedParts)) {
                                        foreach($unusedParts as $part) {
                                            if(isset($part['part_id'])) {
                                                $unusedPartIds[$part['part_id']] = true;
                                            }
                                        }
                                    }

                                    // Calculate actually used parts (all parts minus unused parts)
                                    $spareParts = [];
                                    foreach($allParts as $partId => $part) {
                                        // Only include if not in unused parts
                                        if(!isset($unusedPartIds[$partId])) {
                                            $spareParts[] = $part;
                                        }
                                    }

                                    // If we have specific spare_parts_used data, override with that
                                    $explicitUsedParts = !empty($record->spare_parts_used)
                                        ? (is_string($record->spare_parts_used)
                                            ? json_decode($record->spare_parts_used, true)
                                            : $record->spare_parts_used)
                                        : [];

                                    if(!empty($explicitUsedParts) && is_array($explicitUsedParts)) {
                                        $spareParts = $explicitUsedParts;
                                    }

                                    // Group spare parts by device serial number
                                    $sparePartsBySerial = [];
                                    foreach($spareParts as $part) {
                                        $serial = $part['device_serial'] ?? 'N/A';
                                        if(!isset($sparePartsBySerial[$serial])) {
                                            $sparePartsBySerial[$serial] = [
                                                'device_model' => $part['device_model'] ?? 'N/A',
                                                'device_serial' => $serial,
                                                'parts' => []
                                            ];
                                        }
                                        $sparePartsBySerial[$serial]['parts'][] = $part;
                                    }
                                @endphp

                                @if(!empty($spareParts) && is_array($spareParts) && count($spareParts) > 0)
                                    <div class="space-y-4">
                                        @foreach($sparePartsBySerial as $serial => $deviceGroup)
                                            <div class="mb-3">
                                                <div class="px-4 py-2 font-medium text-white bg-green-600 rounded-t-lg" style='background-color: green;'>
                                                    Device: {{ $deviceGroup['device_model'] }} (S/N: {{ $serial }})
                                                </div>

                                                <!-- Left-right grid for spare parts -->
                                                <div class="grid grid-cols-1 gap-2 p-4 border border-gray-300 md:grid-cols-2" style='background-color: #0080001c;'>
                                                    <div>
                                                        <!-- Left column parts -->
                                                        @foreach($deviceGroup['parts'] as $index => $part)
                                                            @if($index % 2 == 0)
                                                                <div class="items-start mb-2">
                                                                    <span class="mr-2 text-lg">•</span>
                                                                    <span>{{ $part['part_name'] ?? 'N/A' }}</span>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                    <div>
                                                        <!-- Right column parts -->
                                                        @foreach($deviceGroup['parts'] as $index => $part)
                                                            @if($index % 2 == 1)
                                                                <div class="items-start mb-2">
                                                                    <span class="mr-2 text-lg">•</span>
                                                                    <span>{{ $part['part_name'] ?? 'N/A' }}</span>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="italic text-gray-500">No spare parts were used</p>
                                @endif
                            </div>
                            <br>
                            <!-- Spare Parts Not Used -->
                            <div class="mb-6">
                                <h4 class="pb-2 mb-2 font-semibold text-gray-700 border-b text-md" style='color: crimson;'>Spare Parts Not Used</h4>
                                @php
                                    $unusedParts = !empty($record->spare_parts_unused)
                                        ? (is_string($record->spare_parts_unused)
                                            ? json_decode($record->spare_parts_unused, true)
                                            : $record->spare_parts_unused)
                                        : [];

                                    // Prepare a collection of device information for lookup
                                    $deviceInfo = [];

                                    // First try to get device info from repair_remark
                                    if(!empty($record->repair_remark)) {
                                        $deviceRepairs = is_string($record->repair_remark)
                                            ? json_decode($record->repair_remark, true)
                                            : $record->repair_remark;

                                        if(is_array($deviceRepairs)) {
                                            foreach($deviceRepairs as $repair) {
                                                if(!empty($repair['device_model']) && !empty($repair['spare_parts'])) {
                                                    foreach($repair['spare_parts'] as $part) {
                                                        if(!empty($part['part_id'])) {
                                                            $deviceInfo[$part['part_id']] = [
                                                                'device_model' => $repair['device_model'],
                                                                'device_serial' => $repair['device_serial'] ?? 'N/A'
                                                            ];
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    // Then try to get from devices array as backup
                                    if(!empty($record->devices)) {
                                        $devices = is_string($record->devices)
                                            ? json_decode($record->devices, true)
                                            : $record->devices;

                                        if(is_array($devices)) {
                                            foreach($devices as $device) {
                                                if(!empty($device['device_model'])) {
                                                    // If we have spare parts in the device structure
                                                    if(!empty($device['spare_parts'])) {
                                                        foreach($device['spare_parts'] as $part) {
                                                            if(!empty($part['id'])) {
                                                                $deviceInfo[$part['id']] = [
                                                                    'device_model' => $device['device_model'],
                                                                    'device_serial' => $device['device_serial'] ?? 'N/A'
                                                                ];
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    // Group unused parts by device model and serial
                                    $unusedPartsByDevice = [];
                                    foreach($unusedParts as $part) {
                                        $partId = $part['part_id'] ?? ($part['id'] ?? null);
                                        $deviceData = !empty($partId) && isset($deviceInfo[$partId])
                                            ? $deviceInfo[$partId]
                                            : null;

                                        // Fall back to data directly in the part if lookup fails
                                        $deviceModel = $deviceData['device_model'] ?? ($part['device_model'] ?? 'N/A');
                                        $deviceSerial = $deviceData['device_serial'] ?? ($part['device_serial'] ?? 'N/A');

                                        $key = $deviceModel . '|' . $deviceSerial;
                                        if (!isset($unusedPartsByDevice[$key])) {
                                            $unusedPartsByDevice[$key] = [
                                                'device_model' => $deviceModel,
                                                'device_serial' => $deviceSerial,
                                                'parts' => []
                                            ];
                                        }

                                        $unusedPartsByDevice[$key]['parts'][] = [
                                            'part_id' => $partId,
                                            'part_name' => $part['part_name'] ?? 'N/A'
                                        ];
                                    }
                                @endphp

                                @if(!empty($unusedParts) && is_array($unusedParts) && count($unusedParts) > 0)
                                    <div class="space-y-4">
                                        @foreach($unusedPartsByDevice as $deviceKey => $deviceGroup)
                                            <div class="mb-3">
                                                <div class="px-4 py-2 font-medium text-white bg-red-600 rounded-t-lg" style='background-color: crimson;'>
                                                    Device: {{ $deviceGroup['device_model'] }} (S/N: {{ $deviceGroup['device_serial'] }})
                                                </div>

                                                <!-- Left-right grid for spare parts -->
                                                <div class="grid grid-cols-1 gap-2 p-4 border border-gray-300 md:grid-cols-2" style='background-color: #ed143d12;'>
                                                    <div>
                                                        <!-- Left column parts -->
                                                        @foreach($deviceGroup['parts'] as $index => $part)
                                                            @if($index % 2 == 0)
                                                                <div class="items-start mb-2">
                                                                    <span class="mr-2 text-lg">•</span>
                                                                    <span>{{ $part['part_name'] }}</span>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                    <div>
                                                        <!-- Right column parts -->
                                                        @foreach($deviceGroup['parts'] as $index => $part)
                                                            @if($index % 2 == 1)
                                                                <div class="items-start mb-2">
                                                                    <span class="mr-2 text-lg">•</span>
                                                                    <span>{{ $part['part_name'] }}</span>
                                                                </div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="italic text-gray-500">No unused spare parts reported</p>
                                @endif
                            </div>
                            <br>
                            <!-- Repair Documents -->
                            <div class="grid grid-cols-1 gap-4 mb-6">
                                <!-- Delivery Order Files -->
                                <div>
                                    <p>
                                        <span class="font-semibold">Delivery Order Files:</span>
                                        @php
                                            $deliveryOrderFiles = !empty($record->delivery_order_files)
                                                ? (is_string($record->delivery_order_files)
                                                    ? json_decode($record->delivery_order_files, true)
                                                    : $record->delivery_order_files)
                                                : [];
                                        @endphp

                                        @if(is_array($deliveryOrderFiles) && count($deliveryOrderFiles) > 0)
                                            @foreach($deliveryOrderFiles as $index => $file)
                                                <a href="{{ asset('storage/' . $file) }}"
                                                target="_blank"
                                                class="ml-2 font-medium text-blue-600"
                                                style="color: #2563EB; text-decoration: none; font-weight: 500;"
                                                onmouseover="this.style.textDecoration='underline'"
                                                onmouseout="this.style.textDecoration='none'">
                                                    File {{ $index + 1 }}
                                                </a>
                                                @if(!$loop->last)
                                                    <span class="text-gray-400">/</span>
                                                @endif
                                            @endforeach
                                        @else
                                            <span class="ml-2">Not Available</span>
                                        @endif
                                    </p>
                                </div>

                                <!-- Repair Form Files -->
                                <div>
                                    <p class="border-b">
                                        <span class="font-semibold">Repair Form Files:</span>
                                        @php
                                            $repairFormFiles = !empty($record->repair_form_files)
                                                ? (is_string($record->repair_form_files)
                                                    ? json_decode($record->repair_form_files, true)
                                                    : $record->repair_form_files)
                                                : [];
                                        @endphp

                                        @if(is_array($repairFormFiles) && count($repairFormFiles) > 0)
                                            @foreach($repairFormFiles as $index => $file)
                                                <a href="{{ asset('storage/' . $file) }}"
                                                target="_blank"
                                                class="ml-2 font-medium text-blue-600"
                                                style="color: #2563EB; text-decoration: none; font-weight: 500;"
                                                onmouseover="this.style.textDecoration='underline'"
                                                onmouseout="this.style.textDecoration='none'">
                                                    File {{ $index + 1 }}
                                                </a>
                                                @if(!$loop->last)
                                                    <span class="text-gray-400">/</span>
                                                @endif
                                            @endforeach
                                        @else
                                            <span class="ml-2">Not Available</span>
                                        @endif
                                    </p>
                                </div>
                                <!-- Repair Image Files -->
                                <div class="mb-4">
                                    <p>
                                        <span class="font-semibold">Repair Images:</span>
                                        @php
                                            $repairImages = !empty($record->repair_image_files)
                                                ? (is_string($record->repair_image_files)
                                                    ? json_decode($record->repair_image_files, true)
                                                    : $record->repair_image_files)
                                                : [];
                                        @endphp

                                        @if(is_array($repairImages) && count($repairImages) > 0)
                                            @foreach($repairImages as $index => $image)
                                                <a href="{{ asset('storage/' . $image) }}"
                                                target="_blank"
                                                class="ml-2 font-medium text-blue-600"
                                                style="color: #2563EB; text-decoration: none; font-weight: 500;"
                                                onmouseover="this.style.textDecoration='underline'"
                                                onmouseout="this.style.textDecoration='none'">
                                                    Image {{ $index + 1 }}
                                                </a>
                                                @if(!$loop->last)
                                                    <span class="text-gray-400">/</span>
                                                @endif
                                            @endforeach
                                        @else
                                            <span class="ml-2">Not Available</span>
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 text-center">
                            <button @click="doneRepairModalOpen = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            {{-- <div class="mb-6">
                <p class="mb-2">
                    <span class="font-semibold">Additional Attachments:</span>
                </p>

                @php
                    $newAttachmentFiles = $record->new_attachment_file ? (is_string($record->new_attachment_file) ? json_decode($record->new_attachment_file, true) : $record->new_attachment_file) : [];
                @endphp

                @if(is_array($newAttachmentFiles) && count($newAttachmentFiles) > 0)
                    <ul class="pl-6 list-none">
                        @foreach($newAttachmentFiles as $index => $file)
                            <li class="mb-1">
                                <span class="mr-2">➤</span>
                                <a href="{{ url('storage/' . $file) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Attachment {{ $index + 1 }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <span>No additional attachments uploaded</span>
                @endif
            </div> --}}
        </div>
    </div>
</div>
