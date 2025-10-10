{{-- filepath: /var/www/html/timeteccrm/resources/views/components/software-handover.blade.php --}}
@php
    $record = $extraAttributes['record'] ?? null;

    if (!$record) {
        echo 'No record found.';
        return;
    }

    // Format the company name with color highlight
    $companyName = $record->company_name ?? 'Software Handover';

    // Get PI details based on training type
    $productPIs = [];
    $softwareHardwarePIs = [];
    $nonHrdfPIs = [];
    $hrdfPIs = [];

    // Handle Product PI for Online Webinar Training
    if ($record->proforma_invoice_product) {
        $productPiIds = is_string($record->proforma_invoice_product)
            ? json_decode($record->proforma_invoice_product, true)
            : $record->proforma_invoice_product;

        if (is_array($productPiIds)) {
            $productPIs = App\Models\Quotation::whereIn('id', $productPiIds)->get();
        }
    }

    // Handle Software + Hardware PI for Online HRDF Training
    if ($record->software_hardware_pi) {
        $swHardwarePiIds = is_string($record->software_hardware_pi)
            ? json_decode($record->software_hardware_pi, true)
            : $record->software_hardware_pi;

        if (is_array($swHardwarePiIds)) {
            $softwareHardwarePIs = App\Models\Quotation::whereIn('id', $swHardwarePiIds)->get();
        }
    }

    // Handle Non-HRDF PI for Online HRDF Training
    if ($record->non_hrdf_pi) {
        $nonHrdfPiIds = is_string($record->non_hrdf_pi)
            ? json_decode($record->non_hrdf_pi, true)
            : $record->non_hrdf_pi;

        if (is_array($nonHrdfPiIds)) {
            $nonHrdfPIs = App\Models\Quotation::whereIn('id', $nonHrdfPiIds)->get();
        }
    }

    // Handle HRDF PI for both training types
    if ($record->proforma_invoice_hrdf) {
        $hrdfPiIds = is_string($record->proforma_invoice_hrdf)
            ? json_decode($record->proforma_invoice_hrdf, true)
            : $record->proforma_invoice_hrdf;

        if (is_array($hrdfPiIds)) {
            $hrdfPIs = App\Models\Quotation::whereIn('id', $hrdfPiIds)->get();
        }
    }
@endphp

<style>
    .sw-container {
        padding: 1.5rem;
        background-color: #ffffff;
        border-radius: 0.5rem;
    }

    .sw-title {
        text-align: center;
        margin-bottom: 1.5rem;
    }

    .sw-company-name {
        font-size: 1.125rem;
        font-weight: 600;
        color: #2563eb;
        margin-bottom: 0.5rem;
    }

    .sw-info-item {
        margin-bottom: 0.75rem;
        display: flex;
        flex-wrap: wrap;
        align-items: baseline;
    }

    .sw-label {
        font-weight: 600;
        color: #1f2937;
        margin-right: 0.5rem;
    }

    .sw-value {
        color: #374151;
    }

    .sw-separator {
        border: 0;
        border-top: 2px solid #e5e7eb;
        margin: 1rem 0;
    }

    .sw-status-approved {
        color: #059669;
        font-weight: 600;
    }

    .sw-status-rejected {
        color: #dc2626;
        font-weight: 600;
    }

    .sw-status-draft {
        color: #d97706;
        font-weight: 600;
    }

    .sw-status-new {
        color: #4f46e5;
        font-weight: 600;
    }

    .sw-link {
        color: #2563eb;
        text-decoration: none;
        font-weight: 500;
    }

    .sw-link:hover {
        text-decoration: underline;
    }

    .sw-pi-list {
        display: inline;
    }

    .sw-pi-item {
        display: inline;
    }

    .sw-export-container {
        text-align: center;
        margin-top: 1.5rem;
    }

    .sw-export-btn {
        display: inline-flex;
        align-items: center;
        color: #16a34a;
        text-decoration: none;
        font-weight: 500;
        padding: 0.5rem 0.75rem;
        border: 1px solid #16a34a;
        border-radius: 0.25rem;
        transition: background-color 0.2s;
    }

    .sw-export-btn:hover {
        background-color: #f0fdf4;
    }

    .sw-export-icon {
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.5rem;
    }

    .sw-not-available {
        color: #6b7280;
        font-style: italic;
    }
</style>

<div class="sw-container">
    <!-- Title -->
    <div class="mb-4 text-center">
        <h2 class="text-lg font-semibold text-gray-800">Software Handover Details</h2>
        <p class="text-blue-600">{{ $companyName }}</p>
    </div>

    <!-- Software Handover Information -->
    <div class="sw-info-item">
        <span class="sw-label">Software Handover ID:</span>
        <span class="sw-value">{{ isset($record->id) ? 'SW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT) : '-' }}</span>
    </div>

    <div class="sw-info-item">
        <span class="sw-label">Software Handover Status:</span>
        @if($record->status == 'Approved')
            <span class="sw-status-approved">{{ $record->status }}</span>
        @elseif($record->status == 'Rejected')
            <span class="sw-status-rejected">{{ $record->status }}</span>
        @elseif($record->status == 'Draft')
            <span class="sw-status-draft">{{ $record->status }}</span>
        @elseif($record->status == 'New')
            <span class="sw-status-new">{{ $record->status }}</span>
        @else
            <span class="sw-value">{{ $record->status ?? '-' }}</span>
        @endif
    </div>

    <div class="sw-info-item">
        <span class="sw-label">Software Handover Form:</span>
        @if($record->handover_pdf)
            <a href="{{ asset('storage/' . $record->handover_pdf) }}" target="_blank" class="sw-link">Click Here</a>
        @elseif($record->status !== 'Draft' && $record->handover_pdf)
            <a href="{{ route('software-handover.pdf', $record->id) }}" target="_blank" class="sw-link">Click Here</a>
        @else
            <span class="sw-not-available">Click Here</span>
        @endif
    </div>

    <hr class="sw-separator">

    <div class="sw-info-item">
        <span class="sw-label">Date Submit:</span>
        <span class="sw-value">{{ $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at)->format('d F Y') : 'Not submitted' }}</span>
    </div>

    <div class="sw-info-item">
        <span class="sw-label">SalesPerson:</span>
        @php
            $salespersonName = "-";
            if (isset($record->lead) && isset($record->lead->salesperson)) {
                $salesperson = \App\Models\User::find($record->lead->salesperson);
                if ($salesperson) {
                    $salespersonName = $salesperson->name;
                }
            }
        @endphp
        <span class="sw-value">{{ $salespersonName }}</span>
    </div>

    <div class="sw-info-item">
        <span class="sw-label">Implementer:</span>
        <span class="sw-value">{{ $record->implementer ?? '-' }}</span>
    </div>

    <hr class="sw-separator">

    <!-- Product PI Section -->
    @if($record->training_type === 'online_webinar_training')
        <!-- Online Webinar Training - Show Product PI -->
        <div class="sw-info-item">
            <span class="sw-label">Product PI:</span>
            @if(count($productPIs) > 0)
                <div class="sw-pi-list">
                    @foreach($productPIs as $index => $pi)
                        <span class="sw-pi-item">
                            @if($index > 0), @endif
                            <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="sw-link">
                                {{ $pi->pi_reference_no }}
                            </a>
                        </span>
                    @endforeach
                </div>
            @else
                <span class="sw-not-available">No Product PI selected</span>
            @endif
        </div>
    @elseif($record->training_type === 'online_hrdf_training')
        <!-- Online HRDF Training - Show Software+Hardware and HRDF PI only -->
        <div class="sw-info-item">
            <span class="sw-label">Software + Hardware PI:</span>
            @if(count($softwareHardwarePIs) > 0)
                <div class="sw-pi-list">
                    @foreach($softwareHardwarePIs as $index => $pi)
                        <span class="sw-pi-item">
                            @if($index > 0), @endif
                            <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="sw-link">
                                {{ $pi->pi_reference_no }}
                            </a>
                        </span>
                    @endforeach
                </div>
            @else
                <span class="sw-not-available">No Software + Hardware PI selected</span>
            @endif
        </div>

        <!-- HRDF PI for Online HRDF Training -->
        <div class="sw-info-item">
            <span class="sw-label">HRDF PI:</span>
            @if(count($hrdfPIs) > 0)
                <div class="sw-pi-list">
                    @foreach($hrdfPIs as $index => $pi)
                        <span class="sw-pi-item">
                            @if($index > 0), @endif
                            <a href="{{ url('proforma-invoice/' . $pi->id) }}" target="_blank" class="sw-link">
                                {{ $pi->pi_reference_no }}
                            </a>
                        </span>
                    @endforeach
                </div>
            @else
                <span class="sw-not-available">No HRDF PI selected</span>
            @endif
        </div>

    @else
        <!-- Default behavior for other training types -->
        <div class="sw-info-item">
            <span class="sw-label">Product PI:</span>
            @if(count($productPIs) > 0)
                <div class="sw-pi-list">
                    @foreach($productPIs as $index => $pi)
                        <span class="sw-pi-item">
                            @if($index > 0), @endif
                            <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="sw-link">
                                {{ $pi->pi_reference_no }}
                            </a>
                        </span>
                    @endforeach
                </div>
            @else
                <span class="sw-not-available">No Product PI selected</span>
            @endif
        </div>
    @endif

    <div class="sw-info-item">
        <span class="sw-label">Invoice Attachment:</span>
        @php
            $invoiceFiles = $record->invoice_file ? (is_string($record->invoice_file) ? json_decode($record->invoice_file, true) : $record->invoice_file) : [];
        @endphp

        @if(is_array($invoiceFiles) && count($invoiceFiles) > 0)
            <span class="sw-value">
                @foreach($invoiceFiles as $index => $file)
                    @if($index > 0), @endif
                    <a href="{{ url('storage/' . $file) }}" target="_blank" class="sw-link">Invoice {{ $index + 1 }}</a>
                @endforeach
            </span>
        @else
            <span class="sw-not-available">Not Available</span>
        @endif
    </div>

    <hr class="sw-separator">

    <!-- Export Button -->
    <div class="sw-export-container">
        <a href="{{ route('software-handover.export-customer', ['lead' => \App\Classes\Encryptor::encrypt($record->lead_id)]) }}"
           target="_blank"
           class="sw-export-btn">
            <!-- Download Icon -->
            <svg xmlns="http://www.w3.org/2000/svg" class="sw-export-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Export Invoice Information to Excel
        </a>
    </div>
</div>
