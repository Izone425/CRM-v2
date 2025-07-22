@php
    $record = $extraAttributes['record'] ?? null;

    if (!$record) {
        // If no record is found, show an error message or return
        echo 'No record found.';
        return;
    }

    // Format the company name with color highlight
    $companyName = $record->lead->companyDetail->company_name ?? 'Hardware Handover';

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

    // Get attachment files
    $confirmationFiles = $record->confirmation_order_file ? (is_string($record->confirmation_order_file) ? json_decode($record->confirmation_order_file, true) : $record->confirmation_order_file) : [];
    $paymentSlipFiles = $record->payment_slip_file ? (is_string($record->payment_slip_file) ? json_decode($record->payment_slip_file, true) : $record->payment_slip_file) : [];
    $hrdfGrantFiles = $record->hrdf_grant_file ? (is_string($record->hrdf_grant_file) ? json_decode($record->hrdf_grant_file, true) : $record->hrdf_grant_file) : [];
@endphp

<div class="p-6 bg-white rounded-lg">
    <!-- Title -->
    <div class="mb-4 text-center">
        <h2 class="text-lg font-semibold text-gray-800">Hardware Handover Details</h2>
        <p class="text-blue-600">{{ $companyName }}</p>
    </div>
    <div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-2">
        <div>
            <!-- Main Information -->
            <div class="mb-6">
                <p class="flex mb-2">
                    <span class="mr-2 font-semibold">Status:</span>&nbsp;
                    @if($record->status == 'Approved')
                        <span class="text-green-600">{{ $record->status }}</span>
                    @elseif($record->status == 'Rejected')
                        <span class="text-red-600">{{ $record->status }}</span>
                    @elseif($record->status == 'Draft')
                        <span class="text-yellow-500">{{ $record->status }}</span>
                    @elseif($record->status == 'New')
                        <span class="text-indigo-600">{{ $record->status }}</span>
                    @else
                        <span>{{ $record->status ?? '-' }}</span>
                    @endif
                </p>
                <p class="mb-2">
                    <span class="font-semibold">SalesPerson:</span>
                    @php
                        $salespersonName = "-";
                        if (isset($record->lead) && isset($record->lead->salesperson)) {
                            $salesperson = \App\Models\User::find($record->lead->salesperson);
                            if ($salesperson) {
                                $salespersonName = $salesperson->name;
                            }
                        }
                    @endphp
                    {{ $salespersonName }}
                </p>
                <p class="mb-2">
                    <span class="font-semibold">Hardware Handover ID:</span>
                    {{ isset($record->id) ? 'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT) : '-' }}
                </p>
                <p class="mb-4">
                    <span class="font-semibold">Hardware Handover Form:</span>
                    @if($record->handover_pdf)
                        <a href="{{ asset('storage/' . $record->handover_pdf) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Click Here</a>
                    @elseif($record->status !== 'Draft')
                        <a href="{{ route('hardware-handover.pdf', $record->id) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Click Here</a>
                    @else
                        <span style="color: #6B7280;">Click Here</span>
                    @endif
                </p>
            </div>

            <!-- Separator Line -->
            <hr class="my-4 border-gray-300">

            <!-- Proforma Invoice Information -->
            <div class="mb-6">
                <p class="mb-2">
                    <span class="font-semibold">Product PI:</span>
                    @if($productQuotations->count() > 0)
                        @foreach($productQuotations as $index => $quotation)
                            <a href="{{ url('proforma-invoice-v2/' . $quotation->id) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">{{ $quotation->pi_reference_no }}</a>
                            @if(!$loop->last) / @endif
                        @endforeach
                    @else
                        <span class="text-gray-500">-</span>
                    @endif
                </p>
                <p class="mb-4">
                    <span class="font-semibold">HRDF PI:</span>
                    @if($hrdfQuotations->count() > 0)
                        @foreach($hrdfQuotations as $index => $quotation)
                            <a href="{{ url('proforma-invoice-v2/' . $quotation->id) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">{{ $quotation->pi_reference_no }}</a>
                            @if(!$loop->last) / @endif
                        @endforeach
                    @else
                        <span class="text-gray-500">-</span>
                    @endif
                </p>
            </div>

            <!-- Separator Line -->
            <hr class="my-4 border-gray-300">

            <!-- Attachment Files -->
            <div class="mb-6">
                <p class="mb-2">
                    <span class="font-semibold">Confirmation Order:</span>
                    @if(is_array($confirmationFiles) && count($confirmationFiles) > 0)
                        @foreach($confirmationFiles as $index => $file)
                            <a href="{{ url('storage/' . $file) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Click Here</a>
                            @if(!$loop->last) / @endif
                        @endforeach
                    @else
                        <span class="text-gray-500">Click Here</span>
                    @endif
                </p>

                <p class="mb-2">
                    <span class="font-semibold">Payment Slip:</span>
                    @if(is_array($paymentSlipFiles) && count($paymentSlipFiles) > 0)
                        @foreach($paymentSlipFiles as $index => $file)
                            <a href="{{ url('storage/' . $file) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Click Here</a>
                            @if(!$loop->last) / @endif
                        @endforeach
                    @else
                        <span class="text-gray-500">Click Here</span>
                    @endif
                </p>

                <p class="mb-2">
                    <span class="font-semibold">HRDF Grant Approval Letter:</span>
                </p>

                @if(is_array($hrdfGrantFiles) && count($hrdfGrantFiles) > 0)
                    <ul class="pl-6 list-none">
                        @foreach($hrdfGrantFiles as $index => $file)
                            <li class="mb-1">
                                <span class="mr-2">➤</span>
                                <a href="{{ url('storage/' . $file) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Approval {{ $index + 1 }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <span>No Record found</span>
                @endif
            </div>

            <!-- Separator Line -->
            <hr class="my-4 border-gray-300">

            <div class="mb-2 text-center">
                <a href="{{ route('software-handover.export-customer', ['lead' => \App\Classes\Encryptor::encrypt($record->lead_id)]) }}"
                target="_blank"
                style="display: inline-flex; align-items: center; color: #16a34a; text-decoration: none; font-weight: 500; padding: 6px 12px; border: 1px solid #16a34a; border-radius: 4px;"
                onmouseover="this.style.backgroundColor='#f0fdf4'"
                onmouseout="this.style.backgroundColor='transparent'">
                    <!-- Download Icon -->
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Export Invoice Information to Excel
                </a>
            </div>
        </div>

        <div>
            <div class="mb-6">
                <p class="mb-2">
                    <span class="font-semibold">Implementer:</span>
                    {{ $record->implementer ?? 'Not assigned' }}
                </p>

                <p class="mb-2">
                    <span class="font-semibold">Date Submit:</span>
                    {{ $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at)->format('d M Y') : 'Not Available' }}
                </p>

                <p class="mb-2">
                    <span class="font-semibold">Date Pending Stock:</span>
                    {{ $record->pending_stock_at ? \Carbon\Carbon::parse($record->pending_stock_at)->format('d M Y') : 'Not Available' }}
                </p>

                <p class="mb-2">
                    <span class="font-semibold">Date Pending Migration:</span>
                    {{ $record->pending_migration_at ? \Carbon\Carbon::parse($record->pending_migration_at)->format('d M Y') : 'Not Available' }}
                </p>

                <p class="mb-2">
                    <span class="font-semibold">Date Completed:</span>
                    {{ $record->completed_at ? \Carbon\Carbon::parse($record->completed_at)->format('d M Y') : 'Not Available' }}
                </p>
            </div>

            <!-- Separator Line -->
            <hr class="my-4 border-gray-300">
            <div class="mb-6">
                <p class="mb-2">
                    <span class="font-semibold">Invoice Attachment:</span>
                </p>

                @php
                    $invoiceFiles = $record->invoice_file ? (is_string($record->invoice_file) ? json_decode($record->invoice_file, true) : $record->invoice_file) : [];
                @endphp

                @if(is_array($invoiceFiles) && count($invoiceFiles) > 0)
                    <ul class="pl-6 list-none">
                        @foreach($invoiceFiles as $index => $file)
                            <li class="mb-1">
                                <span class="mr-2">➤</span>
                                <a href="{{ url('storage/' . $file) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Invoice {{ $index + 1 }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <span>No invoices uploaded</span>
                @endif
            </div>

            <br>
            <div class="mb-6">
                <p class="mb-2">
                    <span class="font-semibold">Sales Order Attachment:</span>
                </p>

                @php
                    $salesOrderFiles = $record->sales_order_file ? (is_string($record->sales_order_file) ? json_decode($record->sales_order_file, true) : $record->sales_order_file) : [];
                @endphp

                @if(is_array($salesOrderFiles) && count($salesOrderFiles) > 0)
                    <ul class="pl-6 list-none">
                        @foreach($salesOrderFiles as $index => $file)
                            <li class="mb-1">
                                <span class="mr-2">➤</span>
                                <a href="{{ url('storage/' . $file) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Sales Order {{ $index + 1 }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <span>No sales order uploaded</span>
                @endif
            </div>

            <!-- Separator Line -->
            <hr class="my-4 border-gray-300">
            <div class="mb-6">
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
            </div>

            <hr class="my-4 border-gray-300">
            <div x-data="{ open: false }">
                <p class="mb-2">
                    <span class="font-semibold">Device Inventory:</span>
                    <a href="#"
                       @click.prevent="open = true"
                       style="color: #2563EB; text-decoration: none; font-weight: 500;"
                       onmouseover="this.style.textDecoration='underline'"
                       onmouseout="this.style.textDecoration='none'">
                        View Devices
                    </a>
                </p>

                <!-- Modal -->
                <div x-show="open"
                     x-transition
                     @click.outside="open = false"
                     class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50">
                    <div class="relative w-auto p-6 mx-auto mt-20 bg-white rounded-lg shadow-xl" @click.away="open = false">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Device Inventory</h3>
                            <button type="button" @click="open = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1.5 ml-auto inline-flex items-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                        <div>
                            <table class="min-w-full border border-collapse border-gray-300">
                                <thead>
                                    <tr class="bg-gray-100">
                                        <th class="px-4 py-2 text-left border border-gray-300">Product</th>
                                        <th class="px-4 py-2 text-center border border-gray-300">Quantity</th>
                                        <th class="px-4 py-2 text-left border border-gray-300">Serial Numbers</th>
                                        <th class="px-4 py-2 text-left border border-gray-300">Installation Address</th>
                                        <th class="px-4 py-2 text-left border border-gray-300">Attachments</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $deviceSerials = $record->device_serials ?
                                            (is_string($record->device_serials) ? json_decode($record->device_serials, true) : $record->device_serials) :
                                            [];
                                    @endphp

                                    @if(isset($record->tc10_quantity) && $record->tc10_quantity > 0)
                                        <tr>
                                            <td class="px-6 py-3 border border-gray-300">TC10</td>
                                            <td class="px-6 py-3 text-center border border-gray-300">{{ $record->tc10_quantity }}</td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['tc10_serials']) && count($deviceSerials['tc10_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['tc10_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>{{ $serialData['serial'] }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No serials recorded</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['tc10_serials']) && count($deviceSerials['tc10_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['tc10_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>{{ $serialData['installation_address'] ?? 'Not recorded' }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No address recorded</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['tc10_serials']) && count($deviceSerials['tc10_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['tc10_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>
                                                                    @if(!empty($serialData['attachments']))
                                                                        @foreach((array)$serialData['attachments'] as $index => $attachment)
                                                                            <a href="{{ asset('storage/' . $attachment) }}" target="_blank" class="text-blue-600 hover:underline">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" class="inline w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                                </svg>
                                                                                File {{ $index + 1 }}
                                                                            </a>
                                                                            @if(!$loop->last) | @endif
                                                                        @endforeach
                                                                    @else
                                                                        <span class="text-gray-500">No attachments</span>
                                                                    @endif
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No attachments</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif

                                    @if(isset($record->tc20_quantity) && $record->tc20_quantity > 0)
                                        <tr>
                                            <td class="px-6 py-3 border border-gray-300">TC20</td>
                                            <td class="px-6 py-3 text-center border border-gray-300">{{ $record->tc20_quantity }}</td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['tc20_serials']) && count($deviceSerials['tc20_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['tc20_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>{{ $serialData['serial'] }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No serials recorded</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['tc20_serials']) && count($deviceSerials['tc20_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['tc20_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>{{ $serialData['installation_address'] ?? 'Not recorded' }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No address recorded</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['tc20_serials']) && count($deviceSerials['tc20_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['tc20_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>
                                                                    @if(!empty($serialData['attachments']))
                                                                        @foreach((array)$serialData['attachments'] as $index => $attachment)
                                                                            <a href="{{ asset('storage/' . $attachment) }}" target="_blank" class="text-blue-600 hover:underline">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" class="inline w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                                </svg>
                                                                                File {{ $index + 1 }}
                                                                            </a>
                                                                            @if(!$loop->last) | @endif
                                                                        @endforeach
                                                                    @else
                                                                        <span class="text-gray-500">No attachments</span>
                                                                    @endif
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No attachments</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif

                                    @if(isset($record->face_id5_quantity) && $record->face_id5_quantity > 0)
                                        <tr>
                                            <td class="px-6 py-3 border border-gray-300">FACE ID5</td>
                                            <td class="px-6 py-3 text-center border border-gray-300">{{ $record->face_id5_quantity }}</td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['face_id5_serials']) && count($deviceSerials['face_id5_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['face_id5_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>{{ $serialData['serial'] }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No serials recorded</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['face_id5_serials']) && count($deviceSerials['face_id5_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['face_id5_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>{{ $serialData['installation_address'] ?? 'Not recorded' }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No address recorded</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['face_id5_serials']) && count($deviceSerials['face_id5_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['face_id5_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>
                                                                    @if(!empty($serialData['attachments']))
                                                                        @foreach((array)$serialData['attachments'] as $index => $attachment)
                                                                            <a href="{{ asset('storage/' . $attachment) }}" target="_blank" class="text-blue-600 hover:underline">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" class="inline w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                                </svg>
                                                                                File {{ $index + 1 }}
                                                                            </a>
                                                                            @if(!$loop->last) | @endif
                                                                        @endforeach
                                                                    @else
                                                                        <span class="text-gray-500">No attachments</span>
                                                                    @endif
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No attachments</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif

                                    @if(isset($record->face_id6_quantity) && $record->face_id6_quantity > 0)
                                        <tr>
                                            <td class="px-6 py-3 border border-gray-300">FACE ID6</td>
                                            <td class="px-6 py-3 text-center border border-gray-300">{{ $record->face_id6_quantity }}</td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['face_id6_serials']) && count($deviceSerials['face_id6_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['face_id6_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>{{ $serialData['serial'] }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No serials recorded</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['face_id6_serials']) && count($deviceSerials['face_id6_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['face_id6_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>{{ $serialData['installation_address'] ?? 'Not recorded' }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No address recorded</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['face_id6_serials']) && count($deviceSerials['face_id6_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['face_id6_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>
                                                                    @if(!empty($serialData['attachments']))
                                                                        @foreach((array)$serialData['attachments'] as $index => $attachment)
                                                                            <a href="{{ asset('storage/' . $attachment) }}" target="_blank" class="text-blue-600 hover:underline">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" class="inline w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                                </svg>
                                                                                File {{ $index + 1 }}
                                                                            </a>
                                                                            @if(!$loop->last) | @endif
                                                                        @endforeach
                                                                    @else
                                                                        <span class="text-gray-500">No attachments</span>
                                                                    @endif
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No attachments</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif

                                    @if(isset($record->time_beacon_quantity) && $record->time_beacon_quantity > 0)
                                        <tr>
                                            <td class="px-6 py-3 border border-gray-300">TIME BEACON</td>
                                            <td class="px-6 py-3 text-center border border-gray-300">{{ $record->time_beacon_quantity }}</td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['time_beacon_serials']) && count($deviceSerials['time_beacon_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['time_beacon_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>{{ $serialData['serial'] }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No serials recorded</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['time_beacon_serials']) && count($deviceSerials['time_beacon_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['time_beacon_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>{{ $serialData['installation_address'] ?? 'Not recorded' }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No address recorded</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['time_beacon_serials']) && count($deviceSerials['time_beacon_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['time_beacon_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>
                                                                    @if(!empty($serialData['attachments']))
                                                                        @foreach((array)$serialData['attachments'] as $index => $attachment)
                                                                            <a href="{{ asset('storage/' . $attachment) }}" target="_blank" class="text-blue-600 hover:underline">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" class="inline w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                                </svg>
                                                                                File {{ $index + 1 }}
                                                                            </a>
                                                                            @if(!$loop->last) | @endif
                                                                        @endforeach
                                                                    @else
                                                                        <span class="text-gray-500">No attachments</span>
                                                                    @endif
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No attachments</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif

                                    @if(isset($record->nfc_tag_quantity) && $record->nfc_tag_quantity > 0)
                                        <tr>
                                            <td class="px-6 py-3 border border-gray-300">NFC TAG</td>
                                            <td class="px-6 py-3 text-center border border-gray-300">{{ $record->nfc_tag_quantity }}</td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['nfc_tag_serials']) && count($deviceSerials['nfc_tag_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['nfc_tag_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>{{ $serialData['serial'] }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No serials recorded</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['nfc_tag_serials']) && count($deviceSerials['nfc_tag_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['nfc_tag_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>{{ $serialData['installation_address'] ?? 'Not recorded' }}</li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No address recorded</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-3 border border-gray-300">
                                                @if(isset($deviceSerials['nfc_tag_serials']) && count($deviceSerials['nfc_tag_serials']) > 0)
                                                    <ul class="pl-4 list-disc">
                                                        @foreach($deviceSerials['nfc_tag_serials'] as $serialData)
                                                            @if(!empty($serialData['serial']))
                                                                <li>
                                                                    @if(!empty($serialData['attachments']))
                                                                        @foreach((array)$serialData['attachments'] as $index => $attachment)
                                                                            <a href="{{ asset('storage/' . $attachment) }}" target="_blank" class="text-blue-600 hover:underline">
                                                                                <svg xmlns="http://www.w3.org/2000/svg" class="inline w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                                                </svg>
                                                                                File {{ $index + 1 }}
                                                                            </a>
                                                                            @if(!$loop->last) | @endif
                                                                        @endforeach
                                                                    @else
                                                                        <span class="text-gray-500">No attachments</span>
                                                                    @endif
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-gray-500">No attachments</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif

                                    @if(
                                        (!isset($record->tc10_quantity) || $record->tc10_quantity <= 0) &&
                                        (!isset($record->tc20_quantity) || $record->tc20_quantity <= 0) &&
                                        (!isset($record->face_id5_quantity) || $record->face_id5_quantity <= 0) &&
                                        (!isset($record->face_id6_quantity) || $record->face_id6_quantity <= 0) &&
                                        (!isset($record->time_beacon_quantity) || $record->time_beacon_quantity <= 0) &&
                                        (!isset($record->nfc_tag_quantity) || $record->nfc_tag_quantity <= 0)
                                    )
                                        <tr>
                                            <td colspan="5" class="px-4 py-2 text-center border border-gray-300">No devices available</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>

                            <div class="mt-4 text-center">
                                <button @click="open = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div x-data="{ remarkOpen: false }">
                <p class="mb-2">
                    <span class="font-semibold">Admin Remarks:</span>
                    <a href="#"
                    @click.prevent="remarkOpen = true"
                    style="color: #2563EB; text-decoration: none; font-weight: 500;"
                    onmouseover="this.style.textDecoration='underline'"
                    onmouseout="this.style.textDecoration='none'">
                        View Remarks
                    </a>
                </p>

                <!-- Admin Remarks Modal -->
                <div x-show="remarkOpen"
                    x-transition
                    @click.outside="remarkOpen = false"
                    class="fixed inset-0 z-50 flex items-center justify-center overflow-auto bg-black bg-opacity-50">
                    <div class="relative w-full max-w-lg p-6 mx-auto mt-20 bg-white rounded-lg shadow-xl" @click.away="remarkOpen = false">
                        <div class="flex items-start justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Admin Remarks</h3>
                            <button type="button" @click="remarkOpen = false" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg p-1.5 ml-auto inline-flex items-center">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                </svg>
                            </button>
                        </div>
                        <div>
                            @php
                                $adminRemarks = $record->admin_remarks ?
                                    (is_string($record->admin_remarks) ? json_decode($record->admin_remarks, true) : $record->admin_remarks) :
                                    [];
                            @endphp

                            @if(!empty($adminRemarks) && is_array($adminRemarks))
                                <table class="min-w-full border border-collapse border-gray-300">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th class="px-4 py-2 border border-gray-300">Remark</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($adminRemarks as $index => $remark)
                                            <tr>
                                                <td style="border: 1px solid #e0e0e0; padding: 12px; background-color: {{ $index % 2 == 0 ? '#f9f9f9' : '#ffffff' }};">
                                                    <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                                        <div style="font-weight: bold; color: #10b981;">Remark {{ $index + 1 }}</div>
                                                    </div>

                                                    <div style="margin-bottom: 10px; padding-left: 10px; border-left: 3px solid #10b981;">
                                                        {{ $remark['remark'] ?? '' }}
                                                    </div>

                                                    @if(!empty($remark['attachments']))
                                                        <div style="margin-top: 10px;">
                                                            <div style="font-weight: bold; margin-bottom: 5px;">Attachments:</div>
                                                            <div>
                                                                @php
                                                                    $attachments = is_string($remark['attachments']) ?
                                                                        json_decode($remark['attachments'], true) :
                                                                        $remark['attachments'];
                                                                @endphp

                                                                @if(is_array($attachments))
                                                                    @foreach($attachments as $attachment)
                                                                        <a href="{{ url('storage/' . $attachment) }}" target="_blank" style="background-color: #10b981; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; display: inline-block; margin-right: 5px; margin-bottom: 5px;">
                                                                            {{ pathinfo($attachment, PATHINFO_FILENAME) }}
                                                                        </a>
                                                                    @endforeach
                                                                @endif
                                                            </div>
                                                        </div>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @else
                                <div class="p-4 text-center text-gray-500">
                                    No admin remarks available
                                </div>
                            @endif

                            <div class="mt-4 text-center">
                                <button @click="remarkOpen = false" class="px-4 py-2 text-white bg-gray-500 rounded hover:bg-gray-600">
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</div>
