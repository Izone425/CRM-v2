{{-- filepath: /var/www/html/timeteccrm/resources/views/components/hardware-handover.blade.php --}}
@php
    $record = $extraAttributes['record'] ?? null;

    if (!$record) {
        echo 'No record found.';
        return;
    }

    // Format the handover ID
    $handoverId = 'HW_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);

    // Get company detail
    $companyDetail = $record->lead->companyDetail ?? null;
    $lead = $record->lead ?? null;

    $invoiceData = $record->invoice_data ? (is_string($record->invoice_data) ? json_decode($record->invoice_data, true) : $record->invoice_data) : [];

    // Get Product PI details
    $productPIs = [];
    if ($record->proforma_invoice_product) {
        $productPiIds = is_string($record->proforma_invoice_product)
            ? json_decode($record->proforma_invoice_product, true)
            : $record->proforma_invoice_product;

        if (is_array($productPiIds)) {
            $productPIs = App\Models\Quotation::whereIn('id', $productPiIds)->get();
        }
    }

    $hrdfPIs = [];
    if ($record->proforma_invoice_hrdf) {
        $hrdfPiIds = is_string($record->proforma_invoice_hrdf)
            ? json_decode($record->proforma_invoice_hrdf, true)
            : $record->proforma_invoice_hrdf;

        if (is_array($hrdfPiIds)) {
            $hrdfPIs = App\Models\Quotation::whereIn('id', $hrdfPiIds)->get();
        }
    }

    // Get files
    $invoiceFiles = $record->invoice_file ? (is_string($record->invoice_file) ? json_decode($record->invoice_file, true) : $record->invoice_file) : [];
    $salesOrderFiles = $record->sales_order_file ? (is_string($record->sales_order_file) ? json_decode($record->sales_order_file, true) : $record->sales_order_file) : [];
    $confirmationFiles = $record->confirmation_order_file ? (is_string($record->confirmation_order_file) ? json_decode($record->confirmation_order_file, true) : $record->confirmation_order_file) : [];
    $paymentFiles = $record->payment_slip_file ? (is_string($record->payment_slip_file) ? json_decode($record->payment_slip_file, true) : $record->payment_slip_file) : [];
    $hrdfFiles = $record->hrdf_grant_file ? (is_string($record->hrdf_grant_file) ? json_decode($record->hrdf_grant_file, true) : $record->hrdf_grant_file) : [];
    $resellerFiles = $record->reseller_quotation_file ? (is_string($record->reseller_quotation_file) ? json_decode($record->reseller_quotation_file, true) : $record->reseller_quotation_file) : [];

    // Get parsed data
    $contactDetails = is_string($record->contact_detail) ? json_decode($record->contact_detail, true) : $record->contact_detail;
    if (!is_array($contactDetails)) $contactDetails = [];

    $category2 = is_string($record->category2) ? json_decode($record->category2, true) : $record->category2;

    $remarks = $record->remarks;

    $adminRemarks = is_string($record->admin_remarks) ? json_decode($record->admin_remarks, true) : $record->admin_remarks;
    if (!is_array($adminRemarks)) $adminRemarks = [];

    $deviceSerials = $record->device_serials ? (is_string($record->device_serials) ? json_decode($record->device_serials, true) : $record->device_serials) : [];

    // Get related software handovers if combined invoice
    $relatedHandovers = [];
    if ($record->invoice_type === 'combined' && $record->related_software_handovers) {
        $handoverIds = is_string($record->related_software_handovers) ? json_decode($record->related_software_handovers, true) : $record->related_software_handovers;
        if (is_array($handoverIds)) {
            $relatedHandovers = \App\Models\SoftwareHandover::whereIn('id', $handoverIds)->get();
        }
    }

    // Get salesperson name
    $salespersonName = "-";
    if (isset($record->lead) && isset($record->lead->salesperson)) {
        $salesperson = \App\Models\User::find($record->lead->salesperson);
        if ($salesperson) {
            $salespersonName = $salesperson->name;
        }
    }
@endphp

<style>
    .hw-container {
        border-radius: 0.5rem;
    }

    .hw-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 768px) {
        .hw-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .hw-column {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .hw-column-right {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .hw-label {
        font-weight: 600;
        color: #1f2937;
    }

    .hw-value {
        margin-left: 0.5rem;
        color: #374151;
    }

    .hw-view-link {
        font-weight: 500;
        color: #2563eb;
        text-decoration: none;
        cursor: pointer;
    }

    .hw-view-link:hover {
        text-decoration: underline;
    }

    .hw-not-available {
        margin-left: 0.5rem;
        font-style: italic;
        color: #6b7280;
    }

    .hw-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 0.5rem;
    }

    .hw-file-list {
        display: flex;
        flex-direction: column;
    }

    .hw-file-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.25rem 0;
    }

    .hw-file-label {
        font-weight: 500;
        color: #4b5563;
    }

    .hw-file-actions {
        display: flex;
        gap: 0.5rem;
    }

    .hw-btn {
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        color: white;
        text-decoration: none;
        border-radius: 0.25rem;
        border: none;
        cursor: pointer;
        transition: background-color 0.2s ease;
    }

    .hw-btn-view {
        background-color: #2563eb;
    }

    .hw-btn-view:hover {
        background-color: #1d4ed8;
    }

    .hw-btn-download {
        background-color: #16a34a;
    }

    .hw-btn-download:hover {
        background-color: #15803d;
    }

    .hw-status-approved { color: #059669; font-weight: 600; }
    .hw-status-rejected { color: #dc2626; font-weight: 600; }
    .hw-status-draft { color: #d97706; font-weight: 600; }
    .hw-status-new { color: #4f46e5; font-weight: 600; }

    /* Modal Styles */
    .hw-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 50;
        overflow: auto;
        padding: 1rem;
    }

    .hw-modal-content {
        position: relative;
        width: 100%;
        max-width: 55rem;
        padding: 1.5rem;
        margin: auto;
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        margin-top: 5rem;
        max-height: 80vh;
        overflow-y: auto;
    }

    .hw-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .hw-modal-title {
        font-size: 1.125rem;
        font-weight: 500;
        color: #111827;
    }

    .hw-modal-close {
        color: #9ca3af;
        background-color: transparent;
        border: none;
        border-radius: 0.375rem;
        padding: 0.375rem;
        margin-left: auto;
        display: inline-flex;
        align-items: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .hw-modal-close:hover {
        background-color: #f3f4f6;
        color: #111827;
    }

    .hw-modal-close svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .hw-modal-body {
        padding: 1rem;
        border-radius: 0.5rem;
        background-color: #f9fafb;
        margin-bottom: 1rem;
    }

    .hw-modal-text {
        color: #1f2937;
        line-height: 1.6;
    }

    .hw-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.5rem;
    }

    .hw-table th,
    .hw-table td {
        border: 1px solid #d1d5db;
        padding: 0.5rem;
        text-align: left;
        font-size: 0.875rem;
    }

    .hw-table th {
        background-color: #f3f4f6;
        font-weight: 600;
    }

    .hw-table tbody tr:nth-child(even) {
        background-color: #f9fafb;
    }

    .hw-device-list {
        display: block;
        margin: 0;
        padding-left: 1rem;
        list-style-type: disc;
    }

    .hw-device-item {
        margin-bottom: 0.25rem;
    }

    .hw-export-container {
        text-align: center;
        margin-top: 1.5rem;
    }

    .hw-export-btn {
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

    .hw-export-btn:hover {
        background-color: #f0fdf4;
    }

    .hw-export-icon {
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.5rem;
    }

    .hw-not-available {
        color: #6b7280;
        font-style: italic;
    }

    /* Responsive adjustments */
    @media (max-width: 767px) {
        .hw-container {
            padding: 1rem;
        }

        .hw-modal-content {
            margin-top: 2rem;
            padding: 1rem;
            max-width: 95%;
        }

        .hw-file-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        .hw-file-actions {
            width: 100%;
            justify-content: flex-start;
        }

        .hw-grid {
            grid-template-columns: 1fr;
        }
    }

    .hw-status-red {
        color: #dc2626;
        font-weight: 600;
    }
</style>

<div>
    <div class="hw-info-item" style="margin-bottom: 1rem;">
        <span class="hw-label">Hardware Handover Details</span><br>
        <span class="hw-label">Company Name:</span>
        <span class="hw-value">{{ $companyDetail->company_name ?? 'N/A' }}</span>
    </div>

    <div class="hw-container" style="border: 0.1rem solid; padding: 1rem;">
        <div class="hw-grid">
            <!-- Left Column -->
            <div class="hw-column">
                <div class="hw-info-item">
                    <span class="hw-label">Hardware Handover ID:</span>
                    <span class="hw-value">{{ $handoverId }}</span>
                </div>

                <hr class="my-6 border-t border-gray-300">

                <div class="hw-info-item">
                    <span class="hw-label">Status:</span>
                    <span class="hw-status-red hw-value">{{ $record->status ?? '-' }}</span>
                </div>

                <div class="hw-info-item">
                    <span class="hw-label">Date Submit:</span>
                    <span class="hw-value">{{ $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at)->format('d F Y') : 'Not submitted' }}</span>
                </div>

                <div class="hw-info-item">
                    <span class="hw-label">SalesPerson:</span>
                    <span class="hw-value">{{ $salespersonName }}</span>
                </div>

                <div class="hw-info-item">
                    <span class="hw-label">Invoice Type:</span>
                    <span class="hw-value">
                        @if($record->invoice_type === 'combined')
                            Combined Invoice
                        @else
                            Single Invoice
                        @endif
                    </span>
                </div>

                <!-- Contact Details -->
                <div class="hw-remark-container" x-data="{ contactOpen: false }">
                    <span class="hw-label">Contact Details:</span>
                    @if(count($contactDetails) > 0)
                        <a href="#" @click.prevent="contactOpen = true" class="hw-view-link">View</a>
                    @else
                        <span class="hw-not-available">Not Available</span>
                    @endif

                    @if(count($contactDetails) > 0)
                    <div x-show="contactOpen" x-cloak x-transition @click.outside="contactOpen = false" class="hw-modal">
                        <div class="hw-modal-content" @click.away="contactOpen = false">
                            <div class="hw-modal-header">
                                <h3 class="hw-modal-title">Contact Details</h3>
                                <button type="button" @click="contactOpen = false" class="hw-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="hw-modal-body">
                                <table class="hw-table">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Name</th>
                                            <th>HP Number</th>
                                            <th>Email Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($contactDetails as $index => $contact)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $contact['pic_name'] ?? '-' }}</td>
                                                <td>{{ $contact['pic_phone'] ?? '-' }}</td>
                                                <td>{{ $contact['pic_email'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                @if($record->invoice_type === 'combined' && count($relatedHandovers) > 0)
                    <div class="hw-info-item">
                        <span class="hw-label">Related Software Handovers:</span>
                        <span class="hw-value">
                            @foreach($relatedHandovers as $index => $softwareHandover)
                                @php
                                    $formattedId = 'SW_250' . str_pad($softwareHandover->id, 3, '0', STR_PAD_LEFT);
                                @endphp
                                @if($index > 0), @endif
                                {{ $formattedId }}
                            @endforeach
                        </span>
                    </div>
                @endif

                <hr class="my-6 border-t border-gray-300">

                <div class="hw-info-item">
                    <span class="hw-label">Installation Type:</span>
                    <span class="hw-value">
                        @if($record->installation_type === 'internal_installation')
                            Internal Installation
                        @elseif($record->installation_type === 'external_installation')
                            External Installation
                        @elseif($record->installation_type === 'courier')
                            Courier
                        @elseif($record->installation_type === 'self_pick_up')
                            Self Pick-Up
                        @else
                            {{ $record->installation_type ?? 'Not specified' }}
                        @endif
                    </span>
                </div>

                @if($record->installation_type === 'courier' && isset($category2['courier_addresses']))
                    @php
                        $courierAddresses = is_string($category2['courier_addresses']) ? json_decode($category2['courier_addresses'], true) : $category2['courier_addresses'];
                        if (!is_array($courierAddresses)) $courierAddresses = [];
                    @endphp

                    @if(count($courierAddresses) > 0)
                        <div class="hw-remark-container" x-data="{ courierOpen: false }">
                            <span class="hw-label">Courier Address:</span>
                            <a href="#" @click.prevent="courierOpen = true" class="hw-view-link">View</a>

                            <div x-show="courierOpen" x-cloak x-transition @click.outside="courierOpen = false" class="hw-modal">
                                <div class="hw-modal-content" @click.away="courierOpen = false">
                                    <div class="hw-modal-header">
                                        <h3 class="hw-modal-title">Courier Address</h3>
                                        <button type="button" @click="courierOpen = false" class="hw-modal-close">
                                            <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="hw-modal-body">
                                        <table class="hw-table">
                                            <thead>
                                                <tr>
                                                    <th>No.</th>
                                                    <th>Address</th>
                                                    <th>Courier Date</th>
                                                    <th>GDEX Tracking Number</th>
                                                    @php
                                                        $hasRemarksOrDocuments = false;
                                                        foreach($courierAddresses as $courierData) {
                                                            if (!empty($courierData['courier_remark']) || !empty($courierData['courier_document'])) {
                                                                $hasRemarksOrDocuments = true;
                                                                break;
                                                            }
                                                        }
                                                    @endphp
                                                    @if($hasRemarksOrDocuments)
                                                        <th>Remark</th>
                                                        <th>Document</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($courierAddresses as $index => $courierData)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{!! nl2br(e($courierData['address'] ?? '-')) !!}</td>
                                                        <td>
                                                            @if(!empty($courierData['courier_date']))
                                                                {{ \Carbon\Carbon::parse($courierData['courier_date'])->format('d F Y') }}
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td>{{ $courierData['courier_tracking'] ?? '-' }}</td>
                                                        @if($hasRemarksOrDocuments)
                                                            <td>{{ $courierData['courier_remark'] ?? '-' }}</td>
                                                            <td>
                                                                @if(!empty($courierData['courier_document']))
                                                                    <a href="{{ url('storage/' . $courierData['courier_document']) }}" target="_blank" class="hw-view-link">
                                                                        View Document
                                                                    </a>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @elseif($record->installation_type === 'self_pick_up')
                    <div class="hw-remark-container" x-data="{ pickupAddressOpen: false }">
                        <span class="hw-label">Pickup Details:</span>
                        <a href="#" @click.prevent="pickupAddressOpen = true" class="hw-view-link">View</a>

                        <div x-show="pickupAddressOpen" x-cloak x-transition @click.outside="pickupAddressOpen = false" class="hw-modal">
                            <div class="hw-modal-content" @click.away="pickupAddressOpen = false">
                                <div class="hw-modal-header">
                                    <button type="button" @click="pickupAddressOpen = false" class="hw-modal-close">
                                        <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="hw-modal-body">
                                    <!-- Pickup Address -->
                                    @if(!empty($category2['pickup_address']))
                                        <div style="margin-bottom: 1.5rem; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; background-color: #f8fafc;">
                                            <h4 style="margin: 0 0 0.5rem 0; font-weight: 600; color: #1f2937;">Pickup Address:</h4>
                                            <div style="white-space: pre-line; color: #374151;">{{ $category2['pickup_address'] }}</div>
                                        </div>
                                    @endif

                                    <!-- Pickup Information Table -->
                                    <table class="hw-table">
                                        <thead>
                                            <tr>
                                                <th>Information</th>
                                                <th>Details</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <!-- Estimation Pick-Up Date -->
                                            <tr>
                                                <td><strong>Estimation Pick-Up Date:</strong></td>
                                                <td>
                                                    @if(!empty($category2['customer_forecast_pickup_date']))
                                                        {{ \Carbon\Carbon::parse($category2['customer_forecast_pickup_date'])->format('d F Y') }}
                                                    @else
                                                        <span class="hw-not-available">Not Available</span>
                                                    @endif
                                                </td>
                                            </tr>

                                            <!-- Completed Pick-Up Date -->
                                            <tr>
                                                <td><strong>Completed Pick-Up Date:</strong></td>
                                                <td>
                                                    @if(!empty($category2['self_pickup_date']))
                                                        {{ \Carbon\Carbon::parse($category2['self_pickup_date'])->format('d F Y') }}
                                                    @else
                                                        <span class="hw-not-available">Not Available</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @elseif($record->installation_type === 'internal_installation')
                    @php
                        $installer = isset($category2['installer']) ? \App\Models\Installer::find($category2['installer']) : null;
                    @endphp
                    <div class="hw-info-item">
                        <span class="hw-label">Installer:</span>
                        <span class="hw-value">{{ $installer ? $installer->company_name : 'Unknown Installer' }}</span>
                    </div>

                    @if(isset($category2['installation_appointments']) && is_array($category2['installation_appointments']) && count($category2['installation_appointments']) > 0)
                        {{-- <div class="hw-info-item">
                            <span class="hw-label">Installation Appointments:</span>
                            <div class="hw-value">
                                @foreach($category2['installation_appointments'] as $index => $appointment)
                                    <div style="margin-bottom: 1rem; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; background-color: #f9fafb;">
                                        <div style="margin-bottom: 0.5rem;">
                                            <strong>Appointment {{ $index + 1 }} (ID: {{ $appointment['appointment_id'] ?? 'N/A' }}):</strong>
                                        </div>

                                        @if(!empty($appointment['appointment_details']))
                                            @php $details = $appointment['appointment_details']; @endphp

                                            @if(!empty($details['demo_type']))
                                                <div style="margin-bottom: 0.25rem;">
                                                    <strong>Demo Type:</strong> {{ $details['demo_type'] }}
                                                </div>
                                            @endif

                                            @if(!empty($details['appointment_type']))
                                                <div style="margin-bottom: 0.25rem;">
                                                    <strong>Appointment Type:</strong> {{ $details['appointment_type'] }}
                                                </div>
                                            @endif

                                            @if(!empty($details['technician']))
                                                <div style="margin-bottom: 0.25rem;">
                                                    <strong>Technician:</strong> {{ $details['technician'] }}
                                                </div>
                                            @endif

                                            @if(!empty($details['date']))
                                                <div style="margin-bottom: 0.25rem;">
                                                    <strong>Date:</strong> {{ \Carbon\Carbon::parse($details['date'])->format('d M Y') }}
                                                </div>
                                            @endif

                                            @if(!empty($details['start_time']) && !empty($details['end_time']))
                                                <div style="margin-bottom: 0.25rem;">
                                                    <strong>Time:</strong> {{ $details['start_time'] }} - {{ $details['end_time'] }}
                                                </div>
                                            @endif

                                            @if(!empty($details['pic_name']))
                                                <div style="margin-bottom: 0.25rem;">
                                                    <strong>PIC Name:</strong> {{ $details['pic_name'] }}
                                                </div>
                                            @endif

                                            @if(!empty($details['pic_phone']))
                                                <div style="margin-bottom: 0.25rem;">
                                                    <strong>PIC Phone:</strong> {{ $details['pic_phone'] }}
                                                </div>
                                            @endif

                                            @if(!empty($details['pic_email']))
                                                <div style="margin-bottom: 0.25rem;">
                                                    <strong>PIC Email:</strong> {{ $details['pic_email'] }}
                                                </div>
                                            @endif

                                            @if(!empty($details['installation_address']))
                                                <div style="margin-bottom: 0.25rem;">
                                                    <strong>Installation Address:</strong><br>
                                                    {{ $details['installation_address'] }}
                                                </div>
                                            @endif

                                            @if(!empty($details['installation_remark']))
                                                <div style="margin-bottom: 0.25rem;">
                                                    <strong>Remark:</strong> {{ $details['installation_remark'] }}
                                                </div>
                                            @endif
                                        @endif

                                        @if(!empty($appointment['device_allocation']))
                                            @php $allocation = $appointment['device_allocation']; @endphp
                                            <div style="margin-bottom: 0.25rem;">
                                                <strong>Device Allocation:</strong>
                                                <ul style="margin-left: 1rem; margin-top: 0.25rem;">
                                                    @if(!empty($allocation['tc10_units']) && $allocation['tc10_units'] > 0)
                                                        <li>TC10: {{ $allocation['tc10_units'] }} units</li>
                                                    @endif
                                                    @if(!empty($allocation['tc20_units']) && $allocation['tc20_units'] > 0)
                                                        <li>TC20: {{ $allocation['tc20_units'] }} units</li>
                                                    @endif
                                                    @if(!empty($allocation['face_id5_units']) && $allocation['face_id5_units'] > 0)
                                                        <li>FACE ID5: {{ $allocation['face_id5_units'] }} units</li>
                                                    @endif
                                                    @if(!empty($allocation['face_id6_units']) && $allocation['face_id6_units'] > 0)
                                                        <li>FACE ID6: {{ $allocation['face_id6_units'] }} units</li>
                                                    @endif
                                                </ul>
                                            </div>
                                        @endif

                                        @if(!empty($appointment['appointment_status']))
                                            <div style="margin-bottom: 0.25rem;">
                                                <strong>Status:</strong>
                                                <span style="color: {{ $appointment['appointment_status'] === 'Scheduled' ? '#d97706' : ($appointment['appointment_status'] === 'Completed' ? '#059669' : '#6b7280') }}; font-weight: 600;">
                                                    {{ $appointment['appointment_status'] }}
                                                </span>
                                            </div>
                                        @endif

                                        @if(!empty($appointment['created_at']))
                                            <div style="margin-bottom: 0.25rem;">
                                                <strong>Created At:</strong> {{ \Carbon\Carbon::parse($appointment['created_at'])->format('d M Y H:i') }}
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        @if(isset($category2['all_devices_allocated']) && $category2['all_devices_allocated'])
                            <div class="hw-info-item">
                                <span class="hw-label">Device Allocation Status:</span>
                                <span class="hw-value" style="color: #059669; font-weight: 600;">All Devices Allocated</span>
                            </div>
                        @endif

                        @if(isset($category2['all_appointments_scheduled']) && $category2['all_appointments_scheduled'])
                            <div class="hw-info-item">
                                <span class="hw-label">Appointment Status:</span>
                                <span class="hw-value" style="color: #059669; font-weight: 600;">All Appointments Scheduled</span>
                            </div>
                        @endif --}}
                    @endif
                @elseif($record->installation_type === 'external_installation')
                    @php
                        $reseller = isset($category2['reseller']) ? \App\Models\Reseller::find($category2['reseller']) : null;
                    @endphp
                    <div class="hw-info-item">
                        <span class="hw-label">Reseller:</span>
                        <span class="hw-value">{{ $reseller ? $reseller->company_name : 'Unknown Reseller' }}</span>
                    </div>

                    @if(isset($category2['external_courier_addresses']) && is_array($category2['external_courier_addresses']) && count($category2['external_courier_addresses']) > 0)
                        <div class="hw-remark-container" x-data="{ externalCourierOpen: false }">
                            <span class="hw-label">External Courier Address:</span>
                            <a href="#" @click.prevent="externalCourierOpen = true" class="hw-view-link">View</a>

                            <div x-show="externalCourierOpen" x-cloak x-transition @click.outside="externalCourierOpen = false" class="hw-modal">
                                <div class="hw-modal-content" @click.away="externalCourierOpen = false">
                                    <div class="hw-modal-header">
                                        <h3 class="hw-modal-title">External Courier Address</h3>
                                        <button type="button" @click="externalCourierOpen = false" class="hw-modal-close">
                                            <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="hw-modal-body">
                                        <table class="hw-table">
                                            <thead>
                                                <tr>
                                                    <th>No.</th>
                                                    <th>Address</th>
                                                    <th>Courier Date</th>
                                                    <th>GDEX Tracking Number</th>
                                                    @php
                                                        $hasRemarksOrDocuments = false;
                                                        foreach($category2['external_courier_addresses'] as $courierData) {  // ✅ Correct variable
                                                            if (!empty($courierData['external_courier_remark']) || !empty($courierData['external_courier_document'])) {
                                                                $hasRemarksOrDocuments = true;
                                                                break;
                                                            }
                                                        }
                                                    @endphp
                                                    @if($hasRemarksOrDocuments)
                                                        <th>Remark</th>
                                                        <th>Document</th>
                                                    @endif
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($category2['external_courier_addresses'] as $index => $courierData)
                                                    <tr>
                                                        <td>{{ $index + 1 }}</td>
                                                        <td>{!! nl2br(e($courierData['address'] ?? '-')) !!}</td>
                                                        <td>
                                                            @if(!empty($courierData['external_courier_date']))
                                                                {{ \Carbon\Carbon::parse($courierData['external_courier_date'])->format('d F Y') }}
                                                            @else
                                                                -
                                                            @endif
                                                        </td>
                                                        <td>{{ $courierData['external_courier_tracking'] ?? '-' }}</td>
                                                        @if($hasRemarksOrDocuments)
                                                            <td>{{ $courierData['external_courier_remark'] ?? '-' }}</td>
                                                            <td>
                                                                @if(!empty($courierData['external_courier_document']))
                                                                    <a href="{{ url('storage/' . $courierData['external_courier_document']) }}" target="_blank" class="hw-view-link">
                                                                        View Document
                                                                    </a>
                                                                @else
                                                                    -
                                                                @endif
                                                            </td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

                <!-- Remarks -->
                <div class="hw-remark-container" x-data="{ remarkOpen: false }">
                    <span class="hw-label">Remark Details:</span>
                    @php
                        // Handle both old JSON format and new simple string format
                        $hasRemarks = false;
                        $parsedRemarks = [];

                        if ($remarks) {
                            // Check if it's already an array (from database casting)
                            if (is_array($remarks)) {
                                if (count($remarks) > 0) {
                                    // Already decoded array format
                                    $parsedRemarks = $remarks;
                                    $hasRemarks = true;
                                }
                            } elseif (is_string($remarks)) {
                                // Try to decode as JSON first (old format)
                                $jsonDecoded = json_decode($remarks, true);
                                if (json_last_error() === JSON_ERROR_NONE && is_array($jsonDecoded) && count($jsonDecoded) > 0) {
                                    // Old format: JSON array of remarks
                                    $parsedRemarks = $jsonDecoded;
                                    $hasRemarks = true;
                                } elseif (trim($remarks) !== '') {
                                    // New format: simple string
                                    $parsedRemarks = [['remark' => $remarks, 'user_name' => null, 'created_at' => null, 'attachments' => null]];
                                    $hasRemarks = true;
                                }
                            }
                        }
                    @endphp

                    @if($hasRemarks)
                        <a href="#" @click.prevent="remarkOpen = true" class="hw-view-link">View</a>
                    @else
                        <span class="hw-not-available">Not Available</span>
                    @endif

                    @if($hasRemarks)
                    <div x-show="remarkOpen" x-cloak x-transition @click.outside="remarkOpen = false" class="hw-modal">
                        <div class="hw-modal-content" @click.away="remarkOpen = false">
                            <div class="hw-modal-header">
                                <h3 class="hw-modal-title">Remarks</h3>
                                <button type="button" @click="remarkOpen = false" class="hw-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="hw-modal-body">
                                <div class="hw-modal-text">
                                    @foreach($parsedRemarks as $index => $remarkData)
                                        <div style="margin-bottom: 1.5rem; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; background-color: #f9fafb;">
                                            @if(count($parsedRemarks) > 1)
                                                <div style="margin-bottom: 0.75rem;">
                                                    <strong>Remark {{ $index + 1 }}:</strong>
                                                </div>
                                            @endif

                                            <!-- Remark Content -->
                                            <div style="margin-bottom: 0.75rem; white-space: pre-line; line-height: 1.6;">
                                                {{ $remarkData['remark'] ?? '' }}
                                            </div>

                                            <!-- Metadata (only for old format) -->
                                            @if(isset($remarkData['user_name']) || isset($remarkData['created_at']))
                                                <div style="margin-bottom: 0.75rem; padding-top: 0.5rem; border-top: 1px solid #e5e7eb; font-size: 0.875rem; color: #6b7280;">
                                                    @if(!empty($remarkData['user_name']))
                                                        <div><strong>Created by:</strong> {{ $remarkData['user_name'] }}</div>
                                                    @endif
                                                    @if(!empty($remarkData['created_at']))
                                                        <div><strong>Created at:</strong> {{ \Carbon\Carbon::parse($remarkData['created_at'])->format('d M Y H:i') }}</div>
                                                    @endif
                                                </div>
                                            @endif

                                            <!-- Attachments (only for old format) -->
                                            @if(!empty($remarkData['attachments']))
                                                <div style="margin-top: 0.75rem;">
                                                    <strong style="font-size: 0.875rem; color: #374151;">Attachments:</strong>
                                                    <div style="margin-top: 0.25rem;">
                                                        @php
                                                            $attachments = is_string($remarkData['attachments'])
                                                                ? json_decode($remarkData['attachments'], true)
                                                                : (is_array($remarkData['attachments']) ? $remarkData['attachments'] : []);
                                                        @endphp
                                                        @if(is_array($attachments) && count($attachments) > 0)
                                                            @foreach($attachments as $attachmentIndex => $attachment)
                                                                <a href="{{ url('storage/' . $attachment) }}"
                                                                   target="_blank"
                                                                   class="hw-btn hw-btn-view"
                                                                   style="margin-right: 0.5rem; margin-bottom: 0.25rem; display: inline-block;">
                                                                    <svg style="width: 1rem; height: 1rem; margin-right: 0.25rem; display: inline;" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                                    </svg>
                                                                    File {{ $attachmentIndex + 1 }}
                                                                </a>
                                                            @endforeach
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Admin Remarks -->
                @if(count($adminRemarks) > 0)
                <div class="hw-remark-container" x-data="{ adminRemarkOpen: false }">
                    <span class="hw-label">Admin Remarks:</span>
                    <a href="#" @click.prevent="adminRemarkOpen = true" class="hw-view-link">View</a>

                    <div x-show="adminRemarkOpen" x-cloak x-transition @click.outside="adminRemarkOpen = false" class="hw-modal">
                        <div class="hw-modal-content" @click.away="adminRemarkOpen = false">
                            <div class="hw-modal-header">
                                <h3 class="hw-modal-title">Admin Remarks</h3>
                                <button type="button" @click="adminRemarkOpen = false" class="hw-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="hw-modal-body">
                                <div class="hw-modal-text">
                                    @foreach($adminRemarks as $index => $remark)
                                        <div style="margin-bottom: 1rem; padding-left: 10px; border-left: 3px solid #10b981;">
                                            <strong>Admin Remark {{ $index + 1 }}:</strong><br>
                                            {{ $remark['remark'] ?? '' }}
                                            @if(!empty($remark['attachments']))
                                                <div style="margin-top: 0.5rem;">
                                                    @php
                                                        $attachments = is_string($remark['attachments']) ? json_decode($remark['attachments'], true) : $remark['attachments'];
                                                    @endphp
                                                    @if(is_array($attachments))
                                                        @foreach($attachments as $attachment)
                                                            <a href="{{ url('storage/' . $attachment) }}" target="_blank" class="hw-btn hw-btn-view" style="margin-right: 0.25rem;">
                                                                {{ pathinfo($attachment, PATHINFO_FILENAME) }}
                                                            </a>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column -->
            <div class="hw-column-right">
                <div class="hw-column-right">

                <!-- Product PI -->
                <div class="hw-info-item">
                    <span class="hw-label">Product PI:</span>
                    @if(count($productPIs) > 0)
                        <span class="hw-value">
                            @foreach($productPIs as $index => $pi)
                                @if($index > 0), @endif
                                <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="hw-view-link">
                                    {{ $pi->pi_reference_no }}
                                </a>
                            @endforeach
                        </span>
                    @else
                        <span class="hw-not-available">No Available</span>
                    @endif
                </div>

                <!-- HRDF PI -->
                <div class="hw-info-item">
                    <span class="hw-label">HRDF PI:</span>
                    @if(count($hrdfPIs) > 0)
                        <span class="hw-value">
                            @foreach($hrdfPIs as $index => $pi)
                                @if($index > 0), @endif
                                <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="hw-view-link">
                                    {{ $pi->pi_reference_no }}
                                </a>
                            @endforeach
                        </span>
                    @else
                        <span class="hw-not-available">No Available</span>
                    @endif
                </div>

                <hr class="my-6 border-t border-gray-300">
                <!-- Confirmation Order Files Section -->
                <div class="hw-section">
                    <div class="hw-info-item">
                        <span class="hw-label">Confirmation Order:</span>
                        @php $hasConfirmationFiles = false; @endphp
                        @for($i = 1; $i <= 4; $i++)
                            @if(isset($confirmationFiles[$i-1]))
                                @if($hasConfirmationFiles) / @endif
                                <a href="{{ url('storage/' . $confirmationFiles[$i-1]) }}" target="_blank" class="hw-view-link">
                                    File {{ $i }}
                                </a>
                                @php $hasConfirmationFiles = true; @endphp
                            @endif
                        @endfor
                        @if(!$hasConfirmationFiles)
                            <span class="hw-not-available">Not Available</span>
                        @endif
                    </div>
                </div>

                <!-- Payment Slip Files Section -->
                <div class="hw-section">
                    <div class="hw-info-item">
                        <span class="hw-label">Payment Slip:</span>
                        @php $hasPaymentFiles = false; @endphp
                        @for($i = 1; $i <= 4; $i++)
                            @if(isset($paymentFiles[$i-1]))
                                @if($hasPaymentFiles) / @endif
                                <a href="{{ url('storage/' . $paymentFiles[$i-1]) }}" target="_blank" class="hw-view-link">
                                    File {{ $i }}
                                </a>
                                @php $hasPaymentFiles = true; @endphp
                            @endif
                        @endfor
                        @if(!$hasPaymentFiles)
                            <span class="hw-not-available">Not Available</span>
                        @endif
                    </div>
                </div>

                <!-- HRDF Grant Files Section -->
                <div class="hw-section">
                    <div class="hw-info-item">
                        <span class="hw-label">HRDF Grant:</span>
                        @php $hasHrdfFiles = false; @endphp
                        @for($i = 1; $i <= 4; $i++)
                            @if(isset($hrdfFiles[$i-1]))
                                @if($hasHrdfFiles) / @endif
                                <a href="{{ url('storage/' . $hrdfFiles[$i-1]) }}" target="_blank" class="hw-view-link">
                                    File {{ $i }}
                                </a>
                                @php $hasHrdfFiles = true; @endphp
                            @endif
                        @endfor
                        @if(!$hasHrdfFiles)
                            <span class="hw-not-available">Not Available</span>
                        @endif
                    </div>
                </div>

                <hr class="my-6 border-t border-gray-300">

                <!-- Reseller Quotation Section -->
                <div class="hw-section">
                    <div class="hw-info-item">
                        <span class="hw-label">Reseller Quotation:</span>
                        @php $hasResellerFiles = false; @endphp
                        @for($i = 1; $i <= 4; $i++)
                            @if(isset($resellerFiles[$i-1]))
                                @if($hasResellerFiles) / @endif
                                <a href="{{ url('storage/' . $resellerFiles[$i-1]) }}" target="_blank" class="hw-view-link">
                                    File {{ $i }}
                                </a>
                                @php $hasResellerFiles = true; @endphp
                            @endif
                        @endfor
                        @if(!$hasResellerFiles)
                            <span class="hw-not-available">Not Available</span>
                        @endif
                    </div>
                </div>

                <hr class="my-6 border-t border-gray-300">

                @if(!empty($record->sales_order_number))
                    <!-- Device Inventory -->
                    <div class="hw-remark-container" x-data="{ deviceOpen: false }">
                        <span class="hw-label">Sales Order:</span>
                        <a href="#" @click.prevent="deviceOpen = true" class="hw-view-link">View</a>

                        <div x-show="deviceOpen" x-cloak x-transition @click.outside="deviceOpen = false" class="hw-modal">
                            <div class="hw-modal-content" @click.away="deviceOpen = false">
                                <div class="hw-modal-header">
                                    <button type="button" @click="deviceOpen = false" class="hw-modal-close">
                                        <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="hw-modal-body">
                                    <!-- Sales Order Number -->
                                    @if(!empty($record->sales_order_number))
                                        <div style="margin-bottom: 1.5rem; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; background-color: #f8fafc;">
                                            <strong>Sales Order Number:</strong> {{ $record->sales_order_number }}
                                        </div>
                                    @endif

                                    <table class="hw-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $hasDevices = false;
                                                $deviceTypes = [
                                                    'tc10' => ['quantity' => $record->tc10_quantity ?? 0, 'name' => 'TC10'],
                                                    'tc20' => ['quantity' => $record->tc20_quantity ?? 0, 'name' => 'TC20'],
                                                    'face_id5' => ['quantity' => $record->face_id5_quantity ?? 0, 'name' => 'FACE ID5'],
                                                    'face_id6' => ['quantity' => $record->face_id6_quantity ?? 0, 'name' => 'FACE ID6'],
                                                    'time_beacon' => ['quantity' => $record->time_beacon_quantity ?? 0, 'name' => 'TIME BEACON'],
                                                    'nfc_tag' => ['quantity' => $record->nfc_tag_quantity ?? 0, 'name' => 'NFC TAG']
                                                ];
                                            @endphp

                                            @foreach($deviceTypes as $deviceKey => $deviceInfo)
                                                @if($deviceInfo['quantity'] > 0)
                                                    @php $hasDevices = true; @endphp
                                                    <tr>
                                                        <td>{{ $deviceInfo['name'] }}</td>
                                                        <td>{{ $deviceInfo['quantity'] }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach

                                            @if(!$hasDevices)
                                                <tr>
                                                    <td colspan="2" style="text-align: center; font-style: italic; color: #6b7280;">No devices available</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="hw-remark-container" x-data="{ deviceOpen: false }">
                        <span class="hw-label">Device Inventory:</span>
                        <a href="#" @click.prevent="deviceOpen = true" class="hw-view-link">View</a>

                        <div x-show="deviceOpen" x-cloak x-transition @click.outside="deviceOpen = false" class="hw-modal">
                            <div class="hw-modal-content" @click.away="deviceOpen = false">
                                <div class="hw-modal-header">
                                    <button type="button" @click="deviceOpen = false" class="hw-modal-close">
                                        <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                        </svg>
                                    </button>
                                </div>
                                <div class="hw-modal-body">
                                    <!-- Sales Order Number -->
                                    @if(!empty($record->sales_order_number))
                                        <div style="margin-bottom: 1.5rem; padding: 0.75rem; border: 1px solid #e5e7eb; border-radius: 0.375rem; background-color: #f8fafc;">
                                            <strong>Sales Order Number:</strong> {{ $record->sales_order_number }}
                                        </div>
                                    @endif

                                    <table class="hw-table">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @php
                                                $hasDevices = false;
                                                $deviceTypes = [
                                                    'tc10' => ['quantity' => $record->tc10_quantity ?? 0, 'name' => 'TC10'],
                                                    'tc20' => ['quantity' => $record->tc20_quantity ?? 0, 'name' => 'TC20'],
                                                    'face_id5' => ['quantity' => $record->face_id5_quantity ?? 0, 'name' => 'FACE ID5'],
                                                    'face_id6' => ['quantity' => $record->face_id6_quantity ?? 0, 'name' => 'FACE ID6'],
                                                    'time_beacon' => ['quantity' => $record->time_beacon_quantity ?? 0, 'name' => 'TIME BEACON'],
                                                    'nfc_tag' => ['quantity' => $record->nfc_tag_quantity ?? 0, 'name' => 'NFC TAG']
                                                ];
                                            @endphp

                                            @foreach($deviceTypes as $deviceKey => $deviceInfo)
                                                @if($deviceInfo['quantity'] > 0)
                                                    @php $hasDevices = true; @endphp
                                                    <tr>
                                                        <td>{{ $deviceInfo['name'] }}</td>
                                                        <td>{{ $deviceInfo['quantity'] }}</td>
                                                    </tr>
                                                @endif
                                            @endforeach

                                            @if(!$hasDevices)
                                                <tr>
                                                    <td colspan="2" style="text-align: center; font-style: italic; color: #6b7280;">No devices available</td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-6 border-t border-gray-300">
                @endif

                @if(is_array($invoiceData) && count($invoiceData) > 0)
                <div class="hw-remark-container" x-data="{ invoiceDataOpen: false }">
                    <span class="hw-label">Invoice Details:</span>
                    <a href="#" @click.prevent="invoiceDataOpen = true" class="hw-view-link">View</a>

                    <div x-show="invoiceDataOpen" x-cloak x-transition @click.outside="invoiceDataOpen = false" class="hw-modal">
                        <div class="hw-modal-content" @click.away="invoiceDataOpen = false">
                            <div class="hw-modal-header">
                                <h3 class="hw-modal-title">Invoice Details</h3>
                                <button type="button" @click="invoiceDataOpen = false" class="hw-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="hw-modal-body">
                                <table class="hw-table">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Invoice Number</th>
                                            <th>Payment Status</th>
                                            <th>Invoice File</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($invoiceData as $index => $invoice)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $invoice['invoice_no'] ?? '-' }}</td>
                                                <td>
                                                    @if(isset($invoice['payment_status']))
                                                        <span style="color: {{ $invoice['payment_status'] === 'Full Payment' ? '#059669' : ($invoice['payment_status'] === 'Partial Payment' ? '#d97706' : '#dc2626') }}; font-weight: 600;">
                                                            {{ $invoice['payment_status'] }}
                                                        </span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    @if(!empty($invoice['invoice_file']))
                                                        <a href="{{ url('storage/' . $invoice['invoice_file']) }}" target="_blank" class="hw-view-link">
                                                            View Invoice
                                                        </a>
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="my-6 border-t border-gray-300">
                @endif

                <div class="hw-export-container">
                    <a href="{{ route('software-handover.export-customer', ['lead' => \App\Classes\Encryptor::encrypt($record->lead_id)]) }}"
                    target="_blank"
                    class="hw-export-btn">
                        <!-- Download Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="hw-export-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export AutoCount Debtor
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
