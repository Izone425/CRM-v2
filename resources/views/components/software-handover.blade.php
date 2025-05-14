@php
    $record = $extraAttributes['record'] ?? null;

    if (!$record) {
        // If no record is found, show an error message or return
        echo 'No record found.';
        return;
    }

    // Format the company name with color highlight
    $companyName = $record->company_name ?? 'Software Handover';

    // Get product proforma invoice IDs
    $productPiIds = $record->proforma_invoice_product ?
        (is_string($record->proforma_invoice_product) ? json_decode($record->proforma_invoice_product, true) : $record->proforma_invoice_product) :
        [];

    // Get HRDF proforma invoice IDs
    $hrdfPiIds = $record->proforma_invoice_hrdf ?
        (is_string($record->proforma_invoice_hrdf) ? json_decode($record->proforma_invoice_hrdf, true) : $record->proforma_invoice_hrdf) :
        [];

    // Load the quotations to get the reference numbers
    $productQuotations = \App\Models\Quotation::whereIn('id', $productPiIds)->get();
    $hrdfQuotations = \App\Models\Quotation::whereIn('id', $hrdfPiIds)->get();

    // Define key-value pairs for the main information
    $mainInfo = [
        [
            'label' => 'Software Handover ID',
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
    $paymentSlipFiles = $record->payment_slip_file ? (is_string($record->payment_slip_file) ? json_decode($record->payment_slip_file, true) : $record->payment_slip_file) : [];
    $hrdfGrantFiles = $record->hrdf_grant_file ? (is_string($record->hrdf_grant_file) ? json_decode($record->hrdf_grant_file, true) : $record->hrdf_grant_file) : [];
@endphp

<div class="p-2">
    <!-- Header with company name -->
    <div class="mb-6 text-center">
        <h2 class="text-lg font-semibold text-gray-800">Software Handover Details</h2>
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
            <!-- Software Handover Form PDF -->
            <div class="flex items-center">
                <span class="mr-2 text-blue-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                </span>
                @if($record->handover_pdf)
                    <a href="{{ asset('storage/' . $record->handover_pdf) }}" target="_blank" class="text-blue-500 hover:underline">Software Handover Form</a>
                @elseif($record->status !== 'Draft')
                    <a href="{{ route('software-handover.pdf', $record->id) }}" target="_blank" class="text-blue-500 hover:underline">Software Handover Form</a>
                @else
                    <span class="text-gray-500">Software Handover Form (Generated after submission)</span>
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
                        <a href="{{ url('storage/' . $file) }}" target="_blank" class="text-blue-500 hover:underline">
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

            <!-- Product Proforma Invoices -->
            @if($productQuotations->count() > 0)
                @foreach($productQuotations as $index => $quotation)
                    <div class="flex items-center">
                        <span class="mr-2 text-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </span>
                        <a href="{{ url('proforma-invoice/' . $quotation->id) }}" target="_blank" class="text-blue-500 hover:underline">
                            Product PI: {{ $quotation->pi_reference_no }}
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
                    <span class="text-gray-400">No Product Proforma Invoice attached</span>
                </div>
            @endif

            <!-- HRDF Proforma Invoices -->
            @if($hrdfQuotations->count() > 0)
                @foreach($hrdfQuotations as $index => $quotation)
                    <div class="flex items-center">
                        <span class="mr-2 text-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </span>
                        <a href="{{ url('proforma-invoice/' . $quotation->id) }}" target="_blank" class="text-blue-500 hover:underline">
                            HRDF PI: {{ $quotation->pi_reference_no }}
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
                    <span class="text-gray-400">No HRDF Proforma Invoice attached</span>
                </div>
            @endif

            <!-- Payment Files / HRDF Approval Letter -->
            @if(is_array($paymentSlipFiles) && count($paymentSlipFiles) > 0)
                @foreach($paymentSlipFiles as $index => $file)
                    <div class="flex items-center">
                        <span class="mr-2 text-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </span>
                        <a href="{{ url('storage/' . $file) }}" target="_blank" class="text-blue-500 hover:underline">
                            Payment Slip{{ $index > 0 ? ' ' . ($index + 1) : '' }}
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
                    <span class="text-gray-400">No Payment Slip attached</span>
                </div>
            @endif

            <!-- HRDF Grant Approval Letter Files -->
            @if(is_array($hrdfGrantFiles) && count($hrdfGrantFiles) > 0)
                @foreach($hrdfGrantFiles as $index => $file)
                    <div class="flex items-center">
                        <span class="mr-2 text-blue-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </span>
                        <a href="{{ url('storage/' . $file) }}" target="_blank" class="text-blue-500 hover:underline">
                            HRDF Grant Approval Letter{{ $index > 0 ? ' ' . ($index + 1) : '' }}
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
                    <span class="text-gray-400">No HRDF Grant Approval Letter attached</span>
                </div>
            @endif
        </div>
    </div>
</div>
