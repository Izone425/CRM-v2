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

    // Get attachment files
    $confirmationFiles = $record->confirmation_order_file ? (is_string($record->confirmation_order_file) ? json_decode($record->confirmation_order_file, true) : $record->confirmation_order_file) : [];
    $paymentSlipFiles = $record->payment_slip_file ? (is_string($record->payment_slip_file) ? json_decode($record->payment_slip_file, true) : $record->payment_slip_file) : [];
    $hrdfGrantFiles = $record->hrdf_grant_file ? (is_string($record->hrdf_grant_file) ? json_decode($record->hrdf_grant_file, true) : $record->hrdf_grant_file) : [];
@endphp

<div class="p-6 bg-white rounded-lg">
    <!-- Title -->
    <div class="mb-4 text-center">
        <h2 class="text-lg font-semibold text-gray-800">Software Handover Details</h2>
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
                    <span class="font-semibold">Software Handover ID:</span>
                    {{ isset($record->id) ? 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT) : '-' }}
                </p>
                <p class="mb-2"><span class="font-semibold">Software Handover Date:</span>
                    @if(isset($record->completed_at))
                        @php
                            // Check if it's already a Carbon instance
                            if (!($record->completed_at instanceof \Carbon\Carbon)) {
                                $date = \Carbon\Carbon::parse($record->completed_at);
                            } else {
                                $date = $record->completed_at;
                            }
                        @endphp
                        {{ $date->format('d M Y') }}
                    @else
                        -
                    @endif
                </p>
                <p class="mb-4">
                    <span class="font-semibold">Software Handover Form:</span>
                    @if($record->handover_pdf)
                        <a href="{{ asset('storage/' . $record->handover_pdf) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Click Here</a>
                    @elseif($record->status !== 'Draft')
                        <a href="{{ route('software-handover.pdf', $record->id) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Click Here</a>
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
                            <a href="{{ url('proforma-invoice/' . $quotation->id) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">{{ $quotation->pi_reference_no }}</a>
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
                            <a href="{{ url('proforma-invoice/' . $quotation->id) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">{{ $quotation->pi_reference_no }}</a>
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
                    <span class="font-semibold">Kick Off Meeting Date:</span>
                    {{ $record->kick_off_meeting ? \Carbon\Carbon::parse($record->kick_off_meeting)->format('d M Y') : 'Not set' }}
                </p>

                <p class="mb-2">
                    <span class="font-semibold">Online Webinar Training Date:</span>
                    {{ $record->webinar_training ? \Carbon\Carbon::parse($record->webinar_training)->format('d M Y') : 'Not set' }}
                </p>

                <p class="mb-2">
                    <span class="font-semibold">Implementer:</span>
                    {{ $record->implementer ?? 'Not assigned' }}
                </p>

                <p class="mb-2">
                    <span class="font-semibold">Date Submit:</span>
                    {{ $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at)->format('d M Y') : 'Not submitted' }}
                </p>

                <p class="mb-2">
                    <span class="font-semibold">Date Completed:</span>
                    {{ $record->completed_at ? \Carbon\Carbon::parse($record->completed_at)->format('d M Y') : 'Not completed' }}
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
        </div>
</div>
