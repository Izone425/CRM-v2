{{-- filepath: /var/www/html/timeteccrm/resources/views/components/software-handover.blade.php --}}
@php
    $record = $extraAttributes['record'] ?? null;

    if (!$record) {
        echo 'No record found.';
        return;
    }

    // Format the handover ID
    $handoverId = $record->formatted_handover_id;

    // Get company detail
    $companyDetail = $record->lead->companyDetail ?? null;
    $lead = $record->lead ?? null;

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
            $productPIs = App\Models\Quotation::whereIn('id', $productPiIds)->with(['items.product'])->get();
        }
    }

    // Handle Software + Hardware PI for Online HRDF Training
    if ($record->software_hardware_pi) {
        $swHardwarePiIds = is_string($record->software_hardware_pi)
            ? json_decode($record->software_hardware_pi, true)
            : $record->software_hardware_pi;

        if (is_array($swHardwarePiIds)) {
            $softwareHardwarePIs = App\Models\Quotation::whereIn('id', $swHardwarePiIds)->with(['items.product'])->get();
        }
    }

    // Handle Non-HRDF PI for Online HRDF Training
    if ($record->non_hrdf_pi) {
        $nonHrdfPiIds = is_string($record->non_hrdf_pi)
            ? json_decode($record->non_hrdf_pi, true)
            : $record->non_hrdf_pi;

        if (is_array($nonHrdfPiIds)) {
            $nonHrdfPIs = App\Models\Quotation::whereIn('id', $nonHrdfPiIds)->with(['items.product'])->get();
        }
    }

    // Handle HRDF PI for both training types
    if ($record->proforma_invoice_hrdf) {
        $hrdfPiIds = is_string($record->proforma_invoice_hrdf)
            ? json_decode($record->proforma_invoice_hrdf, true)
            : $record->proforma_invoice_hrdf;

        if (is_array($hrdfPiIds)) {
            $hrdfPIs = App\Models\Quotation::whereIn('id', $hrdfPiIds)->with(['items.product'])->get();
        }
    }

    // Parse PI tracking data to get invoice numbers
    $type1InvoiceNumbers = [];
    $type2InvoiceNumbers = [];
    $type3InvoiceNumbers = [];

    // Parse Type 1 PI Invoice Data
    if ($record->type_1_pi_invoice_data) {
        $type1Data = is_string($record->type_1_pi_invoice_data)
            ? json_decode($record->type_1_pi_invoice_data, true)
            : $record->type_1_pi_invoice_data;

        if (is_array($type1Data)) {
            foreach ($type1Data as $item) {
                if (isset($item['invoice_number']) && !empty($item['invoice_number'])) {
                    $type1InvoiceNumbers[] = $item['invoice_number'];
                }
            }
        }
    }

    // Parse Type 2 PI Invoice Data
    if ($record->type_2_pi_invoice_data) {
        $type2Data = is_string($record->type_2_pi_invoice_data)
            ? json_decode($record->type_2_pi_invoice_data, true)
            : $record->type_2_pi_invoice_data;

        if (is_array($type2Data)) {
            foreach ($type2Data as $item) {
                if (isset($item['invoice_number']) && !empty($item['invoice_number'])) {
                    $type2InvoiceNumbers[] = $item['invoice_number'];
                }
            }
        }
    }

    // Parse Type 3 PI Invoice Data
    if ($record->type_3_pi_invoice_data) {
        $type3Data = is_string($record->type_3_pi_invoice_data)
            ? json_decode($record->type_3_pi_invoice_data, true)
            : $record->type_3_pi_invoice_data;

        if (is_array($type3Data)) {
            foreach ($type3Data as $item) {
                if (isset($item['invoice_number']) && !empty($item['invoice_number'])) {
                    $type3InvoiceNumbers[] = $item['invoice_number'];
                }
            }
        }
    }

    // Get files
    $confirmationFiles = $record->confirmation_order_file ? (is_string($record->confirmation_order_file) ? json_decode($record->confirmation_order_file, true) : $record->confirmation_order_file) : [];
    $paymentFiles = $record->payment_slip_file ? (is_string($record->payment_slip_file) ? json_decode($record->payment_slip_file, true) : $record->payment_slip_file) : [];
    $hrdfGrantFiles = $record->hrdf_grant_file ? (is_string($record->hrdf_grant_file) ? json_decode($record->hrdf_grant_file, true) : $record->hrdf_grant_file) : [];
    $invoiceFiles = $record->invoice_file ? (is_string($record->invoice_file) ? json_decode($record->invoice_file, true) : $record->invoice_file) : [];

    // Get parsed data
    $implementationPics = is_string($record->implementation_pics) ? json_decode($record->implementation_pics, true) : $record->implementation_pics;
    if (!is_array($implementationPics)) $implementationPics = [];

    $remarks = is_string($record->remarks) ? json_decode($record->remarks, true) : $record->remarks;
    if (!is_array($remarks)) $remarks = [];

    // Get salesperson name
    $salespersonName = "-";
    if (isset($record->lead) && isset($record->lead->salesperson)) {
        $salesperson = \App\Models\User::find($record->lead->salesperson);
        if ($salesperson) {
            $salespersonName = $salesperson->name;
        }
    }

    // Calculate company size based on all PI items
    $companySize = $record->headcount ?? 0;
    $allPIs = collect()
        ->merge($productPIs)
        ->merge($softwareHardwarePIs)
        ->merge($nonHrdfPIs)
        ->merge($hrdfPIs);

    foreach ($allPIs as $pi) {
        if ($pi && $pi->items) {
            foreach ($pi->items as $item) {
                // Extract quantity from description or item details
                if (isset($item->quantity)) {
                    $companySize += (int)$item->quantity;
                }
            }
        }
    }

    // Generate database email
    $databaseEmail = 'sw_' . str_pad($record->id, 6, '0', STR_PAD_LEFT) . '@timeteccloud.com';

    // Get primary contact from implementation PICs
    $primaryContact = $implementationPics[0] ?? null;
@endphp

<style>
    .sw-container {
        border-radius: 0.5rem;
    }

    .sw-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 768px) {
        .sw-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .sw-column {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .sw-column-right {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .sw-label {
        font-weight: 600;
        color: #1f2937;
    }

    .sw-value {
        margin-left: 0.5rem;
        color: #374151;
    }

    .sw-view-link {
        font-weight: 500;
        color: #2563eb;
        text-decoration: none;
        cursor: pointer;
    }

    .sw-view-link:hover {
        text-decoration: underline;
    }

    .sw-not-available {
        margin-left: 0.5rem;
        font-style: italic;
        color: #6b7280;
    }

    .sw-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 0.5rem;
    }

    .sw-status-approved { color: #059669; font-weight: 600; }
    .sw-status-rejected { color: #dc2626; font-weight: 600; }
    .sw-status-draft { color: #d97706; font-weight: 600; }
    .sw-status-new { color: #4f46e5; font-weight: 600; }

    /* Modal Styles */
    .sw-modal {
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

    .sw-modal-content {
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

    .sw-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .sw-modal-title {
        font-size: 1.125rem;
        font-weight: 500;
        color: #111827;
    }

    .sw-modal-close {
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

    .sw-modal-close:hover {
        background-color: #f3f4f6;
        color: #111827;
    }

    .sw-modal-close svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .sw-modal-body {
        padding: 1rem;
        border-radius: 0.5rem;
        background-color: #f9fafb;
        margin-bottom: 1rem;
    }

    .sw-modal-text {
        color: #1f2937;
        line-height: 1.6;
    }

    .sw-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.5rem;
    }

    .sw-table th,
    .sw-table td {
        border: 1px solid #d1d5db;
        padding: 0.5rem;
        text-align: left;
        font-size: 0.875rem;
    }

    .sw-table th {
        background-color: #f3f4f6;
        font-weight: 600;
    }

    .sw-table tbody tr:nth-child(even) {
        background-color: #f9fafb;
    }

    .sw-export-container {
        text-align: center;
        margin-top: 0.5rem;
        display: flex;
        justify-content: center;
        gap: 1rem; /* Add gap between buttons */
        flex-wrap: wrap; /* Allow wrapping on smaller screens */
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

    /* Responsive adjustments */
    @media (max-width: 767px) {
        .sw-container {
            padding: 1rem;
        }

        .sw-modal-content {
            margin-top: 2rem;
            padding: 1rem;
            max-width: 95%;
        }

        .sw-grid {
            grid-template-columns: 1fr;
        }
    }

    .sw-status-red {
        color: #dc2626;
        font-weight: 600;
    }
</style>

<div>
    <div class="sw-info-item" style = 'margin-bottom: 1rem;'>
        <span class="sw-label">Software Handover Details</span><br>
        <span class="sw-label">Company Name:</span>
        <span class="sw-value">{{ $companyDetail->company_name ?? $record->company_name ?? 'N/A' }}</span>
    </div>

    <div class="sw-container" style="border: 0.1rem solid; padding: 1rem;">
        <div class="sw-grid">
            <!-- Left Column -->
            <div class="sw-column">
                <div class="sw-info-item">
                    <span class="sw-label">Software Handover ID:</span>
                    <span class="sw-value">{{ $handoverId }}</span>
                </div>

                <hr class="my-6 border-t border-gray-300">

                <div class="sw-info-item">
                    <span class="sw-label">Status:</span>
                    <span class="sw-status-red sw-value">{{ $record->status ?? '-' }}</span>
                </div>

                <div class="sw-info-item">
                    <span class="sw-label">Date Submit:</span>
                    <span class="sw-value">{{ $record->submitted_at ? \Carbon\Carbon::parse($record->submitted_at)->format('d F Y') : 'Not submitted' }}</span>
                </div>

                <div class="sw-info-item">
                    <span class="sw-label">SalesPerson:</span>
                    <span class="sw-value">{{ $salespersonName }}</span>
                </div>

                <hr class="my-6 border-t border-gray-300">

                <div class="sw-info-item">
                    <span class="sw-label">Implementer:</span>
                    <span class="sw-value">{{ $record->implementer ?? '-' }}</span>
                </div>

                <div class="sw-remark-container" x-data="{ databaseOpen: false }">
                    <span class="sw-label">Database Details:</span>
                    <a href="#" @click.prevent="databaseOpen = true" class="sw-view-link">View</a>

                    <div x-show="databaseOpen" x-cloak x-transition @click.outside="databaseOpen = false" class="sw-modal">
                        <div class="sw-modal-content" @click.away="databaseOpen = false">
                            <div class="sw-modal-header">
                                <h3 class="sw-modal-title">Database Details</h3>
                                <button type="button" @click="databaseOpen = false" class="sw-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="sw-modal-body">
                                <table class="sw-table">
                                    <tbody>
                                        <tr>
                                            <td style="font-weight: 600; width: 40%; background-color: #f3f4f6;">Company Name</td>
                                            <td>{{ $companyDetail->company_name ?? $record->company_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; width: 40%; background-color: #f3f4f6;">Company Size</td>
                                            <td>{{ $record->headcount_company_size_label }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; width: 40%; background-color: #f3f4f6;">SalesPerson Name</td>
                                            <td>{{ $salespersonName }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; width: 40%; background-color: #f3f4f6;">Contact Name</td>
                                            <td>{{ $record->pic_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; width: 40%; background-color: #f3f4f6;">HP Number</td>
                                            <td>{{ $record->pic_phone ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; width: 40%; background-color: #f3f4f6;">Email</td>
                                            <td>{{ $record->customer->email ?? 'N/A' }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Implementation Details -->
                @if(count($implementationPics) > 0)
                <div class="sw-remark-container" x-data="{ implementationOpen: false }">
                    <span class="sw-label">Implementation Details:</span>
                    <a href="#" @click.prevent="implementationOpen = true" class="sw-view-link">View</a>

                    <div x-show="implementationOpen" x-cloak x-transition @click.outside="implementationOpen = false" class="sw-modal">
                        <div class="sw-modal-content" @click.away="implementationOpen = false">
                            <div class="sw-modal-header">
                                <h3 class="sw-modal-title">Implementation Details</h3>
                                <button type="button" @click="implementationOpen = false" class="sw-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="sw-modal-body">
                                <table class="sw-table">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Name</th>
                                            <th>Position</th>
                                            <th>HP Number</th>
                                            <th>Email Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($implementationPics as $index => $pic)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $pic['pic_name_impl'] ?? '-' }}</td>
                                                <td>{{ $pic['position'] ?? '-' }}</td>
                                                <td>{{ $pic['pic_phone_impl'] ?? '-' }}</td>
                                                <td>{{ $pic['pic_email_impl'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="sw-info-item">
                    <span class="sw-label">Implementation Details:</span>
                    <span class="sw-not-available">Not Available</span>
                </div>
                @endif

                <!-- Remark Details -->
                @if(count($remarks) > 0)
                <div class="sw-remark-container" x-data="{ remarkOpen: false }">
                    <span class="sw-label">Remark Details:</span>
                    <a href="#" @click.prevent="remarkOpen = true" class="sw-view-link">View</a>

                    <div x-show="remarkOpen" x-cloak x-transition @click.outside="remarkOpen = false" class="sw-modal">
                        <div class="sw-modal-content" @click.away="remarkOpen = false">
                            <div class="sw-modal-header">
                                <h3 class="sw-modal-title">Remark Details</h3>
                                <button type="button" @click="remarkOpen = false" class="sw-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="sw-modal-body">
                                @foreach($remarks as $index => $remark)
                                    <div style="margin-bottom: 1rem; padding-left: 10px; border-left: 3px solid #10b981;">
                                        <strong>Remark {{ $index + 1 }}:</strong><br>
                                        <div class="sw-modal-text">
                                            {{ $remark['remark'] ?? '' }}
                                        </div>
                                        @if(!empty($remark['attachments']))
                                            <div style="margin-top: 0.5rem;">
                                                @php
                                                    $attachments = is_string($remark['attachments']) ? json_decode($remark['attachments'], true) : $remark['attachments'];
                                                @endphp
                                                @if(is_array($attachments))
                                                    @foreach($attachments as $attachment)
                                                        <a href="{{ url('storage/' . $attachment) }}" target="_blank" class="sw-view-link" style="margin-right: 0.25rem;">
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
                @else
                <div class="sw-info-item">
                    <span class="sw-label">Remark Details:</span>
                    <span class="sw-not-available">Not Available</span>
                </div>
                @endif

                <!-- Training Category -->
                <div class="sw-info-item">
                    <span class="sw-label">Training Category:</span>
                    <span class="sw-value">
                        @if($record->training_type === 'online_webinar_training')
                            Webinar
                        @elseif($record->training_type === 'online_hrdf_training')
                            HRDF
                        @else
                            {{ $record->training_type ?? 'Not specified' }}
                        @endif
                    </span>
                </div>

                <!-- Speaker Category -->
                <div class="sw-info-item">
                    <span class="sw-label">Speaker Category:</span>
                    <span class="sw-value">
                        @if($record->speaker_category === 'english / malay')
                            English / Malay
                        @elseif($record->speaker_category === 'mandarin')
                            Mandarin
                        @else
                            {{ $record->speaker_category ?? 'Not specified' }}
                        @endif
                    </span>
                </div>

                <div class="sw-info-item">
                    <span class="sw-label">Company Size:</span>
                    <span class="sw-value">
                        {{ $record->headcount_company_size_label }}
                    </span>
                </div>

                <hr class="my-6 border-t border-gray-300">

                <div class="sw-info-item">
                    <span class="sw-label" style="color: #000000;">Invoice to Reseller: </span>
                    @if($record->reseller && $record->reseller->company_name)
                        <span class="sw-value" style="color: #dc2626; font-weight: bold;">{{ $record->reseller->company_name }}</span>
                    @else
                        <span class="sw-not-available">Not Available</span>
                    @endif
                </div>
            </div>

            <!-- Right Column -->
            <div class="sw-column-right">
                <!-- Proforma Invoice Section -->
                @if($record->training_type === 'online_webinar_training')
                    <!-- Type 1: SW+HW Proforma Invoice -->
                    <div class="sw-info-item">
                        <span class="sw-label">Type 1: SW+HW Proforma Invoice: </span>
                        @if(count($productPIs) > 0)
                            @foreach($productPIs as $index => $pi)
                                @php
                                    $invoiceNumber = isset($type1InvoiceNumbers[$index]) ? $type1InvoiceNumbers[$index] : null;
                                @endphp
                                <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="sw-view-link">
                                    File {{ $index + 1 }}@if($invoiceNumber) <small>({{ $invoiceNumber }})</small>@endif
                                </a>
                                @if(!$loop->last) / @endif
                            @endforeach
                        @else
                            <span class="sw-not-available">Not Available</span>
                        @endif
                    </div>
                @elseif($record->training_type === 'online_hrdf_training')
                    <!-- Type 1: SW+HW Proforma Invoice -->
                    <div class="sw-info-item">
                        <span class="sw-label">Type 1: SW+HW Proforma Invoice: </span>
                        @if(count($softwareHardwarePIs) > 0)
                            @foreach($softwareHardwarePIs as $index => $pi)
                                @php
                                    $invoiceNumber = isset($type1InvoiceNumbers[$index]) ? $type1InvoiceNumbers[$index] : null;
                                @endphp
                                <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="sw-view-link">
                                    File {{ $index + 1 }}@if($invoiceNumber) <small>({{ $invoiceNumber }})</small>@endif
                                </a>
                                @if(!$loop->last) / @endif
                            @endforeach
                        @else
                            <span class="sw-not-available">Not Available</span>
                        @endif
                    </div>

                    <!-- Type 2: NON-HRDF Invoice -->
                    <div class="sw-info-item">
                        <span class="sw-label">Type 2: NON-HRDF Invoice: </span>
                        @if(count($nonHrdfPIs) > 0)
                            @foreach($nonHrdfPIs as $index => $pi)
                                @php
                                    $invoiceNumber = isset($type2InvoiceNumbers[$index]) ? $type2InvoiceNumbers[$index] : null;
                                @endphp
                                <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="sw-view-link">
                                    File {{ $index + 1 }}@if($invoiceNumber) <small>({{ $invoiceNumber }})</small>@endif
                                </a>
                                @if(!$loop->last) / @endif
                            @endforeach
                        @else
                            <span class="sw-not-available">Not Available</span>
                        @endif
                    </div>

                    <!-- Type 3: HRDF Invoice -->
                    <div class="sw-info-item">
                        <span class="sw-label">Type 3: HRDF Invoice: </span>
                        @if(count($hrdfPIs) > 0)
                            @foreach($hrdfPIs as $index => $pi)
                                @php
                                    $invoiceNumber = isset($type3InvoiceNumbers[$index]) ? $type3InvoiceNumbers[$index] : null;
                                @endphp
                                <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="sw-view-link">
                                    File {{ $index + 1 }}@if($invoiceNumber) <small>({{ $invoiceNumber }})</small>@endif
                                </a>
                                @if(!$loop->last) / @endif
                            @endforeach
                        @else
                            <span class="sw-not-available">Not Available</span>
                        @endif
                    </div>
                @else
                    <!-- Default - Product PI -->
                    <div class="sw-info-item">
                        <span class="sw-label">Product PI: </span>
                        @if(count($productPIs) > 0)
                            <span class="sw-value">
                                @foreach($productPIs as $index => $pi)
                                    @if($index > 0), @endif
                                    <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="sw-view-link">
                                        {{ $pi->pi_reference_no }}
                                    </a>
                                @endforeach
                            </span>
                        @else
                            <span class="sw-not-available">Not Available</span>
                        @endif
                    </div>
                @endif

                <!-- Confirmation Order -->
                <div class="sw-info-item">
                    <span class="sw-label">Confirmation Order: </span>
                    @if(count($confirmationFiles) > 0)
                        @foreach($confirmationFiles as $index => $file)
                            <a href="{{ url('storage/' . $file) }}" target="_blank" class="sw-view-link">File {{ $index + 1 }}</a>
                            @if(!$loop->last) / @endif
                        @endforeach
                    @else
                        <span class="sw-not-available">Not Available</span>
                    @endif
                </div>

                <!-- Payment Slip -->
                <div class="sw-info-item">
                    <span class="sw-label">Payment Slip: </span>
                    @if(count($paymentFiles) > 0)
                        @foreach($paymentFiles as $index => $file)
                            <a href="{{ url('storage/' . $file) }}" target="_blank" class="sw-view-link">File {{ $index + 1 }}</a>
                            @if(!$loop->last) / @endif
                        @endforeach
                    @else
                        <span class="sw-not-available">Not Available</span>
                    @endif
                </div>

                <!-- HRDF Grant -->
                <div class="sw-info-item">
                    <span class="sw-label">HRDF Grant: </span>
                    @if(count($hrdfGrantFiles) > 0)
                        @foreach($hrdfGrantFiles as $index => $file)
                            <a href="{{ url('storage/' . $file) }}" target="_blank" class="sw-view-link">File {{ $index + 1 }}</a>
                            @if(!$loop->last) / @endif
                        @endforeach
                    @else
                        <span class="sw-not-available">Not Available</span>
                    @endif
                </div>

                <hr class="my-6 border-t border-gray-300">

                <!-- Invoice by Winson -->
                <div class="sw-info-item">
                    <span class="sw-label">Invoice by Admin: </span>
                    @if(count($invoiceFiles) > 0)
                        @foreach($invoiceFiles as $index => $file)
                            <a href="{{ url('storage/' . $file) }}" target="_blank" class="sw-view-link">File {{ $index + 1 }}</a>
                            @if(!$loop->last) / @endif
                        @endforeach
                    @else
                        <span class="sw-not-available">Not Available</span>
                    @endif
                </div>

                <hr class="my-6 border-t border-gray-300">

                <!-- Invoice by Admin -->
                <div class="sw-info-item">
                    <span class="sw-label">Invoice by Wirson: </span>
                    <span class="sw-not-available">Not Available</span>
                </div>

                <hr class="my-6 border-t border-gray-300">

                @php
                    // Determine Type 1 PI collection based on training type
                    $type1PIs = ($record->training_type === 'online_webinar_training')
                        ? $productPIs
                        : (($record->training_type === 'online_hrdf_training') ? $softwareHardwarePIs : $productPIs);
                @endphp

                <div class="sw-export-container">
                    {{-- Create Database button + drawer --}}
                    @php
                        $confirmDbUrl = '/software-handover/confirm-db/' . \App\Classes\Encryptor::encrypt($record->id);
                        $existingDbDate = $record->db_creation ? $record->db_creation->format('Y-m-d') : null;

                        // Available products for dropdown (exclude ONBOARD)
                        $dbProducts = \App\Models\Product::where('code', 'LIKE', 'TIMETEC-%')
                            ->where('code', 'NOT LIKE', '%ONBOARD%')
                            ->orderBy('code')
                            ->get(['id', 'code', 'description']);

                        // Build initial rows from PI items (excluding ONBOARD)
                        $dbInitialRows = [];
                        if (is_countable($type1PIs) && count($type1PIs) > 0) {
                            foreach ($type1PIs as $pi) {
                                foreach ($pi->items as $item) {
                                    if ($item->product && str_contains(strtoupper($item->product->code ?? ''), 'ONBOARD')) continue;
                                    $dbInitialRows[] = [
                                        'product_code' => $item->product->code ?? '',
                                        'description' => $item->product->description ?? $item->description ?? '',
                                        'qty' => $item->quantity ?? 0,
                                    ];
                                }
                            }
                        }

                        // If saved data exists, use that instead
                        $savedBufferMonth = 1;
                        if (!empty($record->type_1_pi_invoice_data)) {
                            $savedData = $record->type_1_pi_invoice_data;
                            if (isset($savedData['items'])) {
                                $dbInitialRows = $savedData['items'];
                                $savedBufferMonth = $savedData['buffer_month'] ?? 1;
                            } else {
                                // Backward compat: old format was flat array of items
                                $dbInitialRows = $savedData;
                            }
                        }
                    @endphp
                    <div x-data="{
                        showDatabaseDrawer: false,
                        confirming: false,
                        confirmed: {{ $existingDbDate ? 'true' : 'false' }},
                        dbDate: '{{ $existingDbDate ?? now()->format('Y-m-d') }}',
                        bufferMonth: {{ $savedBufferMonth }},
                        rows: {{ Js::from($dbInitialRows) }},
                        products: {{ Js::from($dbProducts) }},
                        updateDescription(index) {
                            const p = this.products.find(p => p.code === this.rows[index].product_code);
                            this.rows[index].description = p ? p.description : '-';
                        },
                        get trialStartDate() {
                            if (!this.dbDate) return '-';
                            const d = new Date(this.dbDate);
                            return d.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        },
                        get trialEndDate() {
                            if (!this.dbDate) return '-';
                            const d = new Date(this.dbDate);
                            d.setMonth(d.getMonth() + parseInt(this.bufferMonth));
                            d.setDate(d.getDate() - 1);
                            return d.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        }
                    }">
                        <button
                            x-on:click.stop.prevent="showDatabaseDrawer = true"
                            class="sw-export-btn"
                            style="background-color: #16a34a; color: white; border-color: #16a34a; cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="sw-export-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                            </svg>
                            Create DB + Trial License
                        </button>

                        <div x-show="showDatabaseDrawer" x-cloak x-on:click.stop style="position: fixed; inset: 0; z-index: 9999;">
                            <div x-on:click.self="showDatabaseDrawer = false" style="position: absolute; inset: 0; background-color: rgba(0,0,0,0.4);"></div>
                            <div x-on:click.stop style="position: absolute; top: 0; right: 0; bottom: 0; width: 500px; background: white; box-shadow: -4px 0 15px rgba(0,0,0,0.1); display: flex; flex-direction: column;">
                                <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #111827;">Create DB + Trial License</h3>
                                    <button x-on:click.stop="showDatabaseDrawer = false" style="background: none; border: none; cursor: pointer; padding: 4px; color: #6b7280;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div style="padding: 20px; flex: 1; overflow-y: auto;">
                                    <p style="margin: 0 0 12px; font-size: 0.85rem; color: #6b7280;">SW+HW Proforma Invoice items</p>

                                    {{-- PI reference info --}}
                                    @if(is_countable($type1PIs) && count($type1PIs) > 0)
                                        @foreach($type1PIs as $piIndex => $pi)
                                            <div style="font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                                PI #{{ $piIndex + 1 }}
                                                @if($pi->pi_reference_no) — {{ $pi->pi_reference_no }} @endif
                                            </div>
                                        @endforeach
                                    @endif

                                    {{-- Editable items table --}}
                                    <template x-if="rows.length > 0">
                                        <table style="width: 100%; border-collapse: collapse; font-size: 0.75rem;">
                                            <thead>
                                                <tr style="background: #f3f4f6;">
                                                    <th style="padding: 6px 8px; text-align: left; border: 1px solid #e5e7eb; font-weight: 600;">Product Code</th>
                                                    <th style="padding: 6px 8px; text-align: left; border: 1px solid #e5e7eb; font-weight: 600;">Description</th>
                                                    <th style="padding: 6px 8px; text-align: center; border: 1px solid #e5e7eb; font-weight: 600; width: 70px;">Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="(row, index) in rows" :key="index">
                                                    <tr>
                                                        <td style="padding: 4px 6px; border: 1px solid #e5e7eb;">
                                                            <select x-model="row.product_code" x-on:change="updateDescription(index)" x-bind:disabled="confirmed" style="width: 100%; padding: 4px 6px; font-size: 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; background: white;">
                                                                <template x-for="product in products" :key="product.code">
                                                                    <option :value="product.code" x-text="product.code" :selected="product.code === row.product_code"></option>
                                                                </template>
                                                            </select>
                                                        </td>
                                                        <td style="padding: 5px 8px; border: 1px solid #e5e7eb; color: #6b7280;" x-text="row.description"></td>
                                                        <td style="padding: 4px 6px; border: 1px solid #e5e7eb; text-align: center;">
                                                            <input type="number" x-model.number="row.qty" min="1" x-bind:disabled="confirmed" style="width: 100%; padding: 4px 6px; font-size: 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; text-align: center;" />
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </template>
                                    <template x-if="rows.length === 0">
                                        <div style="padding: 20px; text-align: center; color: #9ca3af; font-size: 0.85rem;">No SW+HW Proforma Invoice data available</div>
                                    </template>

                                    {{-- Buffer Month dropdown --}}
                                    <div style="margin-top: 16px;">
                                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 4px;">Buffer Month</label>
                                        <select x-model="bufferMonth" x-bind:disabled="confirmed" style="width: 100%; padding: 8px 12px; font-size: 0.85rem; border: 1px solid #d1d5db; border-radius: 6px; background: white; color: #374151; box-sizing: border-box;">
                                            <option value="1">1 Month</option>
                                            <option value="2">2 Months</option>
                                            <option value="3">3 Months</option>
                                            <option value="4">4 Months</option>
                                            <option value="5">5 Months</option>
                                            <option value="6">6 Months</option>
                                        </select>
                                    </div>

                                    {{-- Create DB date field (editable) --}}
                                    <div style="margin-top: 16px;">
                                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 4px;">Create DB Date</label>
                                        <input type="date" x-model="dbDate" x-bind:disabled="confirmed" style="width: 100%; padding: 8px 12px; font-size: 0.85rem; border: 1px solid #d1d5db; border-radius: 6px; background: white; color: #374151; box-sizing: border-box;" />
                                    </div>

                                    {{-- Trial period summary --}}
                                    <p style="margin: 16px 0 0; font-size: 0.85rem; color: #374151;">Trial License Period : <span x-text="trialStartDate" style="font-weight: 600;"></span> to <span x-text="trialEndDate" style="font-weight: 600;"></span></p>
                                </div>
                                <div style="padding: 16px 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end;">
                                    <button x-on:click.stop="showDatabaseDrawer = false" style="padding: 8px 16px; font-size: 0.85rem; font-weight: 500; color: #374151; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">Cancel</button>
                                    <button
                                        x-on:click.stop="
                                            if (confirmed) return;
                                            confirming = true;
                                            fetch('{{ $confirmDbUrl }}', {
                                                method: 'POST',
                                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                                body: JSON.stringify({ items: rows, db_date: dbDate, buffer_month: bufferMonth })
                                            })
                                            .then(r => r.json())
                                            .then(() => { confirmed = true; confirming = false; })
                                            .catch(() => { confirming = false; });
                                        "
                                        x-bind:disabled="confirming || confirmed"
                                        x-bind:style="'padding: 8px 16px; font-size: 0.85rem; font-weight: 500; color: white; border: none; border-radius: 6px; cursor: ' + (confirmed ? 'default' : 'pointer') + '; background-color: ' + (confirmed ? '#9ca3af' : '#16a34a') + ';'"
                                    >
                                        <template x-if="!confirming && !confirmed"><span>Confirm Create DB</span></template>
                                        <template x-if="confirming"><span>Saving...</span></template>
                                        <template x-if="confirmed"><span>Confirmed</span></template>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Create Pending License button + drawer --}}
                    @php
                        $confirmPendingUrl = '/software-handover/confirm-pending-license/' . \App\Classes\Encryptor::encrypt($record->id);

                        // Build initial rows from PI items (same source as Create DB)
                        $pendingInitialRows = [];
                        if (is_countable($type1PIs) && count($type1PIs) > 0) {
                            foreach ($type1PIs as $pi) {
                                foreach ($pi->items as $item) {
                                    if ($item->product && str_contains(strtoupper($item->product->code ?? ''), 'ONBOARD')) continue;
                                    $pendingInitialRows[] = [
                                        'product_code' => $item->product->code ?? '',
                                        'description' => $item->product->description ?? $item->description ?? '',
                                        'qty' => $item->quantity ?? 0,
                                    ];
                                }
                            }
                        }

                        // Default pending date = day after trial license end (db_creation + buffer months)
                        $defaultPendingDate = null;
                        if ($record->db_creation) {
                            $bufferMonths = $record->type_1_pi_invoice_data['buffer_month'] ?? 1;
                            $defaultPendingDate = $record->db_creation->copy()->addMonths($bufferMonths)->format('Y-m-d');
                        }

                        // If saved data exists, use that instead
                        $savedPendingBufferMonth = 1;
                        $existingPendingDate = null;
                        if (!empty($record->type_2_pi_invoice_data)) {
                            $savedPendingData = $record->type_2_pi_invoice_data;
                            if (isset($savedPendingData['items'])) {
                                $pendingInitialRows = $savedPendingData['items'];
                                $savedPendingBufferMonth = $savedPendingData['buffer_month'] ?? 1;
                                $savedPendingBillingCycle = $savedPendingData['billing_cycle'] ?? 12;
                                $existingPendingDate = $savedPendingData['pending_date'] ?? null;
                            } else {
                                $pendingInitialRows = $savedPendingData;
                            }
                        }
                    @endphp
                    <div style="display: contents;" x-data="{
                        showPendingDrawer: false,
                        plConfirming: false,
                        plConfirmed: {{ $existingPendingDate ? 'true' : 'false' }},
                        plDate: '{{ $existingPendingDate ?? $defaultPendingDate ?? now()->format('Y-m-d') }}',
                        plBufferMonth: {{ $savedPendingBufferMonth }},
                        plBillingCycle: {{ $savedPendingBillingCycle ?? 12 }},
                        plRows: {{ Js::from($pendingInitialRows) }},
                        plProducts: {{ Js::from($dbProducts) }},
                        plUpdateDescription(index) {
                            const p = this.plProducts.find(p => p.code === this.plRows[index].product_code);
                            this.plRows[index].description = p ? p.description : '-';
                        },
                        get plStartDate() {
                            if (!this.plDate) return '-';
                            const d = new Date(this.plDate);
                            return d.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        },
                        get plEndDate() {
                            if (!this.plDate) return '-';
                            const d = new Date(this.plDate);
                            d.setMonth(d.getMonth() + parseInt(this.plBillingCycle));
                            d.setDate(d.getDate() - 1);
                            return d.toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        }
                    }">
                        <button
                            x-on:click.stop.prevent="showPendingDrawer = true"
                            class="sw-export-btn"
                            style="background-color: #16a34a; color: white; border-color: #16a34a; cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="sw-export-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            Create Pending License
                        </button>

                        <div x-show="showPendingDrawer" x-cloak x-on:click.stop style="position: fixed; inset: 0; z-index: 9999;">
                            <div x-on:click.self="showPendingDrawer = false" style="position: absolute; inset: 0; background-color: rgba(0,0,0,0.4);"></div>
                            <div x-on:click.stop style="position: absolute; top: 0; right: 0; bottom: 0; width: 500px; background: white; box-shadow: -4px 0 15px rgba(0,0,0,0.1); display: flex; flex-direction: column;">
                                <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #111827;">Create Pending License</h3>
                                    <button x-on:click.stop="showPendingDrawer = false" style="background: none; border: none; cursor: pointer; padding: 4px; color: #6b7280;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                <div style="padding: 20px; flex: 1; overflow-y: auto;">
                                    <p style="margin: 0 0 12px; font-size: 0.85rem; color: #6b7280;">SW+HW Proforma Invoice items</p>

                                    {{-- PI reference info --}}
                                    @if(is_countable($type1PIs) && count($type1PIs) > 0)
                                        @foreach($type1PIs as $piIndex => $pi)
                                            <div style="font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 6px;">
                                                PI #{{ $piIndex + 1 }}
                                                @if($pi->pi_reference_no) — {{ $pi->pi_reference_no }} @endif
                                            </div>
                                        @endforeach
                                    @endif

                                    {{-- Editable items table --}}
                                    <template x-if="plRows.length > 0">
                                        <table style="width: 100%; border-collapse: collapse; font-size: 0.75rem;">
                                            <thead>
                                                <tr style="background: #f3f4f6;">
                                                    <th style="padding: 6px 8px; text-align: left; border: 1px solid #e5e7eb; font-weight: 600;">Product Code</th>
                                                    <th style="padding: 6px 8px; text-align: left; border: 1px solid #e5e7eb; font-weight: 600;">Description</th>
                                                    <th style="padding: 6px 8px; text-align: center; border: 1px solid #e5e7eb; font-weight: 600; width: 70px;">Qty</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <template x-for="(row, index) in plRows" :key="index">
                                                    <tr>
                                                        <td style="padding: 4px 6px; border: 1px solid #e5e7eb;">
                                                            <select x-model="row.product_code" x-on:change="plUpdateDescription(index)" x-bind:disabled="plConfirmed" style="width: 100%; padding: 4px 6px; font-size: 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; background: white;">
                                                                <template x-for="product in plProducts" :key="product.code">
                                                                    <option :value="product.code" x-text="product.code" :selected="product.code === row.product_code"></option>
                                                                </template>
                                                            </select>
                                                        </td>
                                                        <td style="padding: 5px 8px; border: 1px solid #e5e7eb; color: #6b7280;" x-text="row.description"></td>
                                                        <td style="padding: 4px 6px; border: 1px solid #e5e7eb; text-align: center;">
                                                            <input type="number" x-model.number="row.qty" min="1" x-bind:disabled="plConfirmed" style="width: 100%; padding: 4px 6px; font-size: 0.75rem; border: 1px solid #d1d5db; border-radius: 4px; text-align: center;" />
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </template>
                                    <template x-if="plRows.length === 0">
                                        <div style="padding: 20px; text-align: center; color: #9ca3af; font-size: 0.85rem;">No SW+HW Proforma Invoice data available</div>
                                    </template>

                                    {{-- Billing Cycle dropdown --}}
                                    <div style="margin-top: 16px;">
                                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 4px;">Billing Cycle</label>
                                        <select x-model="plBillingCycle" x-bind:disabled="plConfirmed" style="width: 100%; padding: 8px 12px; font-size: 0.85rem; border: 1px solid #d1d5db; border-radius: 6px; background: white; color: #374151; box-sizing: border-box;">
                                            <option value="12">12 Months</option>
                                            <option value="24">24 Months</option>
                                            <option value="36">36 Months</option>
                                            <option value="48">48 Months</option>
                                            <option value="60">60 Months</option>
                                        </select>
                                    </div>

                                    {{-- Pending License date field (editable) --}}
                                    <div style="margin-top: 16px;">
                                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 4px;">Pending License Date</label>
                                        <input type="date" x-model="plDate" x-bind:disabled="plConfirmed" style="width: 100%; padding: 8px 12px; font-size: 0.85rem; border: 1px solid #d1d5db; border-radius: 6px; background: white; color: #374151; box-sizing: border-box;" />
                                    </div>

                                    {{-- Pending license period summary --}}
                                    <p style="margin: 16px 0 0; font-size: 0.85rem; color: #374151;">Pending License Period : <span x-text="plStartDate" style="font-weight: 600;"></span> to <span x-text="plEndDate" style="font-weight: 600;"></span></p>
                                </div>
                                <div style="padding: 16px 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end;">
                                    <button x-on:click.stop="showPendingDrawer = false" style="padding: 8px 16px; font-size: 0.85rem; font-weight: 500; color: #374151; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">Cancel</button>
                                    <button
                                        x-on:click.stop="
                                            if (plConfirmed) return;
                                            plConfirming = true;
                                            fetch('{{ $confirmPendingUrl }}', {
                                                method: 'POST',
                                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/json', 'Accept': 'application/json' },
                                                body: JSON.stringify({ items: plRows, pending_date: plDate, buffer_month: plBufferMonth, billing_cycle: plBillingCycle })
                                            })
                                            .then(r => r.json())
                                            .then(() => { plConfirmed = true; plConfirming = false; })
                                            .catch(() => { plConfirming = false; });
                                        "
                                        x-bind:disabled="plConfirming || plConfirmed"
                                        x-bind:style="'padding: 8px 16px; font-size: 0.85rem; font-weight: 500; color: white; border: none; border-radius: 6px; cursor: ' + (plConfirmed ? 'default' : 'pointer') + '; background-color: ' + (plConfirmed ? '#9ca3af' : '#16a34a') + ';'"
                                    >
                                        <template x-if="!plConfirming && !plConfirmed"><span>Confirm Pending License</span></template>
                                        <template x-if="plConfirming"><span>Saving...</span></template>
                                        <template x-if="plConfirmed"><span>Confirmed</span></template>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <a href="{{ route('software-handover.export-customer', ['lead' => \App\Classes\Encryptor::encrypt($record->lead_id)]) }}"
                       target="_blank"
                       class="sw-export-btn">
                        <!-- Download Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="sw-export-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export AutoCount Debtor
                    </a>
                    @php
                        $docNoPrefix = match($record->training_type) {
                            'online_webinar_training' => 'EPIN',
                            'online_hrdf_training' => 'EHIN',
                            default => 'EGIN',
                        };
                        $exportBaseUrl = route('invoice-data.export', ['softwareHandover' => \App\Classes\Encryptor::encrypt($record->id)]);
                    @endphp
                    <div x-data="{ showInvoiceDrawer: false, docNo: '{{ $docNoPrefix }}', debtorCode: '', licenseNumber: '' }">
                        <button
                            x-on:click.stop.prevent="showInvoiceDrawer = true"
                            class="sw-export-btn"
                            style="background-color: #2563eb; color: white; border-color: #2563eb; cursor: pointer;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="sw-export-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export AutoCount Invoice
                        </button>

                        {{-- Drawer overlay --}}
                        <div x-show="showInvoiceDrawer" x-cloak x-on:click.stop style="position: fixed; inset: 0; z-index: 9999;">
                            {{-- Backdrop --}}
                            <div x-on:click.self="showInvoiceDrawer = false" style="position: absolute; inset: 0; background-color: rgba(0,0,0,0.4);"></div>
                            {{-- Drawer panel --}}
                            <div x-on:click.stop style="position: absolute; top: 0; right: 0; bottom: 0; width: 400px; background: white; box-shadow: -4px 0 15px rgba(0,0,0,0.1); display: flex; flex-direction: column;">
                                {{-- Header --}}
                                <div style="padding: 16px 20px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; justify-content: space-between;">
                                    <h3 style="margin: 0; font-size: 1.1rem; font-weight: 600; color: #111827;">Export AutoCount Invoice</h3>
                                    <button x-on:click.stop="showInvoiceDrawer = false" style="background: none; border: none; cursor: pointer; padding: 4px; color: #6b7280;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                {{-- Body --}}
                                <div style="padding: 20px; flex: 1; overflow-y: auto;">
                                    <p style="margin: 0 0 16px; font-size: 0.85rem; color: #6b7280;">Enter the following details before generating the Excel file.</p>
                                    <div style="margin-bottom: 16px;">
                                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 4px;">DocNo</label>
                                        <input type="text" x-model="docNo" style="width: 100%; padding: 8px 12px; font-size: 0.85rem; border: 1px solid #d1d5db; border-radius: 6px; outline: none; box-sizing: border-box;" placeholder="e.g. EPIN001" />
                                    </div>
                                    <div style="margin-bottom: 16px;">
                                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 4px;">DebtorCode</label>
                                        <input type="text" x-model="debtorCode" style="width: 100%; padding: 8px 12px; font-size: 0.85rem; border: 1px solid #d1d5db; border-radius: 6px; outline: none; box-sizing: border-box;" placeholder="Enter debtor code" />
                                    </div>
                                    <div style="margin-bottom: 16px;">
                                        <label style="display: block; font-size: 0.8rem; font-weight: 600; color: #374151; margin-bottom: 4px;">UDF_IV_LicenseNumber</label>
                                        <input type="text" x-model="licenseNumber" style="width: 100%; padding: 8px 12px; font-size: 0.85rem; border: 1px solid #d1d5db; border-radius: 6px; outline: none; box-sizing: border-box;" placeholder="Enter license number" />
                                    </div>
                                </div>
                                {{-- Footer --}}
                                <div style="padding: 16px 20px; border-top: 1px solid #e5e7eb; display: flex; gap: 10px; justify-content: flex-end;">
                                    <button x-on:click.stop="showInvoiceDrawer = false" style="padding: 8px 16px; font-size: 0.85rem; font-weight: 500; color: #374151; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">Cancel</button>
                                    <button x-on:click.stop="
                                        let url = '{{ $exportBaseUrl }}' + '?doc_no=' + encodeURIComponent(docNo) + '&debtor_code=' + encodeURIComponent(debtorCode) + '&license_number=' + encodeURIComponent(licenseNumber);
                                        window.open(url, '_blank');
                                        showInvoiceDrawer = false;
                                    " style="padding: 8px 16px; font-size: 0.85rem; font-weight: 500; color: white; background-color: #2563eb; border: none; border-radius: 6px; cursor: pointer;">Generate Excel</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
