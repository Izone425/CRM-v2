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
                                <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="sw-view-link">File {{ $index + 1 }}</a>
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
                                <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="sw-view-link">File {{ $index + 1 }}</a>
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
                                <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="sw-view-link">File {{ $index + 1 }}</a>
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
                                <a href="{{ url('proforma-invoice-v2/' . $pi->id) }}" target="_blank" class="sw-view-link">File {{ $index + 1 }}</a>
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

                <div class="sw-export-container">
                    <a href="{{ route('software-handover.export-customer', ['lead' => \App\Classes\Encryptor::encrypt($record->lead_id)]) }}"
                       target="_blank"
                       class="sw-export-btn">
                        <!-- Download Icon -->
                        <svg xmlns="http://www.w3.org/2000/svg" class="sw-export-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Export AutoCount Debtor
                    </a>
                    <a href="{{ route('invoice-data.export', ['softwareHandover' => \App\Classes\Encryptor::encrypt($record->id)]) }}"
                        target="_blank"
                        class="sw-export-btn"
                        style="background-color: #2563eb; color: white;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="sw-export-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Export AutoCount Invoice
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
