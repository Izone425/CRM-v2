{{-- filepath: /var/www/html/timeteccrm/resources/views/components/software-handover.blade.php --}}
@php
    $record = $extraAttributes['record'] ?? null;

    if (!$record) {
        // If no record is found, show an error message or return
        echo 'No record found.';
        return;
    }

    // Format the company name with color highlight
    $companyName = $record->company_name ?? 'Software Handover';
@endphp

<div class="p-6 bg-white rounded-lg">
    <!-- Title -->
    <div class="mb-4 text-center">
        <h2 class="text-lg font-semibold text-gray-800">Software Handover Details</h2>
        <p class="text-blue-600">{{ $companyName }}</p>
    </div>

    <!-- Main Information - Single Column -->
    <div class="mb-6">
        <p class="mb-2">
            <span class="font-semibold">Software Handover ID:</span>
            {{ isset($record->id) ? 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT) : '-' }}
        </p>

        <p class="mb-4">
            <span class="font-semibold">Software Handover Form:</span>
            @if($record->handover_pdf)
                <a href="{{ asset('storage/' . $record->handover_pdf) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Click Here</a>
            @elseif($record->status !== 'Draft' && $record->handover_pdf)
                <a href="{{ route('software-handover.pdf', $record->id) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Click Here</a>
            @else
                <span style="color: #6B7280;">Click Here</span>
            @endif
        </p>

        <p class="flex mb-2">
            <span class="mr-2 font-semibold">Software Handover Status:</span>&nbsp;
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
            <span class="font-semibold">Date Submit:</span>
            {{ $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at)->format('d F Y') : 'Not submitted' }}
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
            <span class="font-semibold">Implementer:</span>
            {{ $record->implementer ?? '' }}
        </p>

        <p class="mb-6">
            <span class="font-semibold">Invoice Attachment:</span>

            @php
                $invoiceFiles = $record->invoice_file ? (is_string($record->invoice_file) ? json_decode($record->invoice_file, true) : $record->invoice_file) : [];
            @endphp

            @if(is_array($invoiceFiles) && count($invoiceFiles) > 0)
                @foreach($invoiceFiles as $index => $file)
                    @if($index > 0), @endif
                    <a href="{{ url('storage/' . $file) }}" target="_blank" style="color: #2563EB; text-decoration: none; font-weight: 500;" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'">Invoice {{ $index + 1 }}</a>
                @endforeach
            @else
                Not Available
            @endif
        </p>

        <div class="text-center">
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
</div>
