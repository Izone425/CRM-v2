<!-- filepath: /var/www/html/timeteccrm/resources/views/components/hardware-handover.blade.php -->
@php
    $record = $extraAttributes['record'] ?? null;

    if (!$record) {
        // If no record is found, show an error message or return
        echo 'No record found.';
        return;
    }

    // Format the company name with color highlight
    $companyName = $record->lead->companyDetail->company_name ?? 'Hardware Handover';

    // Get proforma invoice IDs
    $productPiIds = $record->proforma_invoice_product ?
        (is_string($record->proforma_invoice_product) ? json_decode($record->proforma_invoice_product, true) : $record->proforma_invoice_product) :
        [];

    $hrdfPiIds = $record->proforma_invoice_hrdf ?
        (is_string($record->proforma_invoice_hrdf) ? json_decode($record->proforma_invoice_hrdf, true) : $record->proforma_invoice_hrdf) :
        [];

    // Load the quotations to get the reference numbers (if needed)
    $productQuotations = \App\Models\Quotation::whereIn('id', $productPiIds)->get();
    $hrdfQuotations = \App\Models\Quotation::whereIn('id', $hrdfPiIds)->get();

    // Get attachment files
    $confirmationFiles = $record->confirmation_order_file ?
        (is_string($record->confirmation_order_file) ? json_decode($record->confirmation_order_file, true) : $record->confirmation_order_file) :
        [];
    $hrdfGrantFiles = $record->hrdf_grant_file ?
        (is_string($record->hrdf_grant_file) ? json_decode($record->hrdf_grant_file, true) : $record->hrdf_grant_file) :
        [];
    $paymentSlipFiles = $record->payment_slip_file ?
        (is_string($record->payment_slip_file) ? json_decode($record->payment_slip_file, true) : $record->payment_slip_file) :
        [];

@endphp

<div class="p-6 bg-white rounded-lg">
    <!-- Title -->
    <div class="mb-4 text-center">
        <h2 class="text-lg font-semibold text-gray-800">Hardware Handover Details</h2>
        <p class="text-blue-600">{{ $companyName }}</p>
    </div>

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
        <p class="mb-2"><span class="font-semibold">Hardware Handover ID:</span> {{ $record->id }}</p>
        <p class="mb-2"><span class="font-semibold">Hardware Handover Date:</span> {{ isset($record->created_at) ? $record->created_at->format('d M Y') : '-' }}</p>
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

    <!-- Category 1 Information -->
    <div class="mb-6">
        <p class="mb-2">
            <span class="font-semibold">Installation Type:</span>
            @if($record->installation_type === 'internal_installation')
                Internal Installation
            @elseif($record->installation_type === 'external_installation')
                External Installation
            @elseif($record->installation_type === 'courier')
                Courier
            @else
                {{ $record->installation_type ?? 'Not specified' }}
            @endif
        </p>
    </div>

    <!-- Separator Line -->
    {{-- <hr class="my-4 border-gray-300">

    <!-- Category 2 Information -->
    <div class="mb-6">
        <p class="mb-2"><span class="font-semibold">Name:</span> {{ $record->pic_name ?? '-' }}</p>
        <p class="mb-2"><span class="font-semibold">HP Number:</span> {{ $record->pic_phone ?? '-' }}</p>
        <p class="mb-2"><span class="font-semibold">Email:</span> {{ $record->email ?? '-' }}</p>
        <p class="mb-2"><span class="font-semibold">Courier Address:</span> {{ $record->courier_address ?? '-' }}</p>
        <p class="mb-2"><span class="font-semibold">Installer:</span> {{ $record->installer ?? '-' }}</p>
        <p class="mb-2"><span class="font-semibold">Reseller:</span> {{ $record->reseller ?? '-' }}</p>
    </div> --}}

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
                <span class="text-gray-500">No file uploaded</span>
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
                <span class="text-gray-500">No file uploaded</span>
            @endif
        </p>

        <p class="mb-2">
            <span class="font-semibold">HRDF Grant Approval Letter:</span>
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
            <span class="text-gray-500">No file uploaded</span>
        @endif
        </p>
    </div>
</div>
