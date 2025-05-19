@php
    $record = $extraAttributes['record'] ?? null;

    if (!$record) {
        // If no record is found, show an error message or return
        echo 'No record found.';
        return;
    }

    // Format the company name with color highlight
    $companyName = $record->company_name ?? 'Hardware Handover';

    // Define key-value pairs for the main information
    $mainInfo = [
        [
            'label' => 'Hardware Handover ID',
            'value' => isset($record->id) ? 'SH' . $record->id : '-'
        ],
        [
            'label' => 'Date',
            'value' => isset($record->created_at) ? $record->created_at->format('d F Y') : '-'
        ],
        [
            'label' => 'Payment Type',
            'value' => isset($record->payment_term) ? Str::title(str_replace('_', ' ', $record->payment_term)) : '-'
        ],
        [
            'label' => 'Invoice Value',
            'value' => isset($record->value) ? 'MYR ' . number_format($record->value, 2) : 'MYR 0.00'
        ],
        [
            'label' => 'Status',
            'value' => $record->status ?? '-',
            'is_status' => true
        ],
    ];

    // Get attachment files
    $confirmationFiles = $record->confirmation_order_file ? (is_string($record->confirmation_order_file) ? json_decode($record->confirmation_order_file, true) : $record->confirmation_order_file) : [];
    $paymentFiles = $record->payment_slip_file ? (is_string($record->payment_slip_file) ? json_decode($record->payment_slip_file, true) : $record->payment_slip_file) : [];
@endphp

<div class="p-2">
    <!-- Header with company name -->
    <div class="mb-6 text-center">
        <h2 class="text-lg font-semibold text-gray-800">Hardware Handover Details</h2>
        <p class="text-blue-500">{{ $companyName }}</p>
    </div>
    <br>
    <!-- Main information in a 2-column grid -->
    <div style="display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 24px;"
    class="grid grid-cols-2 gap-6">
        @foreach ($mainInfo as $info)
            <div>
                <p class="text-sm font-medium text-gray-600">{{ $info['label'] }}</p>
                @if (isset($info['is_status']) && $info['is_status'])
                    @if($info['value'] == 'Approved')
                        <p class="font-medium" style='color: rgb(35, 189, 35);'>{{ $info['value'] }}</p>
                    @elseif($info['value'] == 'Rejected')
                        <p class="font-medium" style='color: rgb(189, 32, 32);'>{{ $info['value'] }}</p>
                    @elseif($info['value'] == 'Draft')
                        <p class="font-medium" style='color: rgb(235, 202, 14);'>{{ $info['value'] }}</p>
                    @elseif($info['value'] == 'New')
                        <p class="font-medium" style='color: rgb(57, 32, 172);'>{{ $info['value'] }}</p>
                    @else
                        <p class="font-medium">{{ $info['value'] }}</p>
                    @endif
                @else
                    <p class="font-medium">{{ $info['value'] }}</p>
                @endif
            </div>
        @endforeach
    </div>

    <!-- Separator line -->
    <hr class="my-4 border-gray-200">

    <!-- Attachments section -->
    <div>
        <h3 class="mb-3 font-medium text-gray-700">Attachments</h3>

        <div class="space-y-2">
            <!-- Hardware Handover Form PDF -->
            <div class="flex items-center">
                <span class="mr-2 text-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </span>
                @if($record->handover_pdf)
                    <a href="{{ asset('storage/' . $record->handover_pdf) }}" target="_blank" class="text-blue-500 hover:underline">Hardware Handover Form</a>
                @elseif($record->status !== 'Draft')
                    <a href="{{ route('hardware-handover.pdf', $record->id) }}" target="_blank" class="text-blue-500 hover:underline">Hardware Handover Form</a>
                @else
                    <span class="text-gray-500">Hardware Handover Form (Generated after submission)</span>
                @endif
            </div>

            <!-- Confirmation Order Files -->
            @if(is_array($confirmationFiles) && count($confirmationFiles) > 0)
            @foreach($confirmationFiles as $index => $file)
                <div class="flex items-center">
                    <span class="mr-2 text-blue-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </span>
                    <a href="{{ asset('storage/' . $file) }}" target="_blank" class="text-blue-500 hover:underline">
                        Confirmation Order {{ $index > 0 ? $index + 1 : '' }}
                    </a>
                </div>
            @endforeach
            @else
            <div class="flex items-center">
                <span class="mr-2 text-gray-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </span>
                <span class="text-gray-400">No Confirmation Order attached</span>
            </div>
            @endif

            <!-- Proforma Invoice -->
            @if(isset($record->proforma_invoice_number) && $record->proforma_invoice_number)
                @php
                    $piNumbers = is_string($record->proforma_invoice_number) ?
                        json_decode($record->proforma_invoice_number, true) :
                        (is_array($record->proforma_invoice_number) ? $record->proforma_invoice_number : [$record->proforma_invoice_number]);
                @endphp

                @foreach($piNumbers as $index => $piNumber)
                    @if($piNumber)
                        <div class="flex items-center">
                            <span class="mr-2 text-blue-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                </svg>
                            </span>
                            <a href="{{ url('proforma-invoice/' . $piNumber) }}" target="_blank" class="text-blue-500 hover:underline">
                                Proforma Invoice {{ $index > 0 ? $index + 1 : '' }}
                            </a>
                        </div>
                    @endif
                @endforeach
                @else
                <div class="flex items-center">
                    <span class="mr-2 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </span>
                    <span class="text-gray-400">No Proforma Invoice attached</span>
                </div>
            @endif

            <!-- Payment Files / HRDF Approval Letter -->
            @if(is_array($paymentFiles) && count($paymentFiles) > 0)
                @foreach($paymentFiles as $index => $file)
                    <div class="flex items-center">
                        <span class="mr-2 text-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </span>
                        <a href="{{ url('storage/' . $file) }}" target="_blank" class="text-blue-500 hover:underline">
                            {{ $record->payment_term === 'payment_via_hrdf' ? 'HRDF Grant Approval Letter' : 'Payment Slip' }}{{ $index > 0 ? ' ' . ($index + 1) : '' }}
                        </a>
                    </div>
                @endforeach
                @else
                <div class="flex items-center">
                    <span class="mr-2 text-gray-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                        </svg>
                    </span>
                    <span class="text-gray-400">No {{ $record->payment_term === 'payment_via_hrdf' ? 'HRDF Grant Approval Letter' : 'Payment Slip' }} attached</span>
                </div>
            @endif
        </div>
    </div>

    <!-- Separator line -->
    <hr class="my-4 border-gray-200">

    <!-- Installation Details Section -->
    <div>
        <h3 class="mb-3 font-medium text-gray-700">Installation Details</h3>

        <div class="space-y-3">
            <div>
                <p class="text-sm font-medium text-gray-600">Special Remark</p>
                <p class="mt-1">{{ $record->installation_special_remark ?? '-' }}</p>
            </div>

            @if(isset($record->installation_media) && !empty($installation_media))
                <div>
                    <p class="mb-2 text-sm font-medium text-gray-600">Photo/Video</p>
                    <div class="grid grid-cols-3 gap-2">
                        @php
                            $photos = is_string($record->photos) ? json_decode($record->photos, true) : $record->photos;
                        @endphp

                        @foreach($photos as $photo)
                            <div class="relative overflow-hidden rounded-md aspect-square">
                                <img src="{{ Storage::disk('public')->url($photo) }}" alt="Installation photo"
                                    class="object-cover w-full h-full" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
