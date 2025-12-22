{{-- filepath: /var/www/html/timeteccrm/resources/views/components/renewal-handover.blade.php --}}
@php
    $record = $extraAttributes['record'] ?? null;

    if (!$record) {
        echo 'No handover record found.';
        return;
    }

    // Get renewal-specific data
    $renewalDetails = $record->renewalDetail ?? null;
    $quotations = $record->quotations()->with('items')->get(); // Load quotations with details
    $implementationPics = $record->implementationPics ?? collect();
    $primaryContact = $implementationPics->first();

    // Format the handover ID
    $handoverId = $record->formatted_handover_id ?? 'RW_' . str_pad($record->id, 6, '0', STR_PAD_LEFT);

    // Get company detail
    $companyDetail = $record->lead->companyDetail ?? null;
    $lead = $record->lead ?? null;

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
    .rw-container {
        border-radius: 0.5rem;
    }

    .rw-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 768px) {
        .rw-grid {
            grid-template-columns: 1fr 1fr;
        }
    }

    .rw-column {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .rw-column-right {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .rw-label {
        font-weight: 600;
        color: #1f2937;
    }

    .rw-value {
        margin-left: 0.5rem;
        color: #374151;
    }

    .rw-view-link {
        font-weight: 500;
        color: #2563eb;
        text-decoration: none;
        cursor: pointer;
    }

    .rw-view-link:hover {
        text-decoration: underline;
    }

    .rw-not-available {
        margin-left: 0.5rem;
        font-style: italic;
        color: #6b7280;
    }

    .rw-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #1f2937;
        border-bottom: 2px solid #e5e7eb;
        padding-bottom: 0.5rem;
    }

    .rw-status-completed { color: #059669; font-weight: 600; }
    .rw-status-pending { color: #dc2626; font-weight: 600; }
    .rw-status-draft { color: #d97706; font-weight: 600; }
    .rw-status-new { color: #4f46e5; font-weight: 600; }

    /* Modal Styles */
    .rw-modal {
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

    .rw-modal-content {
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

    .rw-modal-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .rw-modal-title {
        font-size: 1.125rem;
        font-weight: 500;
        color: #111827;
    }

    .rw-modal-close {
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

    .rw-modal-close:hover {
        background-color: #f3f4f6;
        color: #111827;
    }

    .rw-modal-close svg {
        width: 1.25rem;
        height: 1.25rem;
    }

    .rw-modal-body {
        padding: 1rem;
        border-radius: 0.5rem;
        background-color: #f9fafb;
        margin-bottom: 1rem;
    }

    .rw-modal-text {
        color: #1f2937;
        line-height: 1.6;
    }

    .rw-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 0.5rem;
    }

    .rw-table th,
    .rw-table td {
        border: 1px solid #d1d5db;
        padding: 0.5rem;
        text-align: left;
        font-size: 0.875rem;
    }

    .rw-table th {
        background-color: #f3f4f6;
        font-weight: 600;
    }

    .rw-table tbody tr:nth-child(even) {
        background-color: #f9fafb;
    }

    .rw-export-container {
        text-align: center;
        margin-top: 0.5rem;
        display: flex;
        justify-content: center;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .rw-export-btn {
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

    .rw-export-btn:hover {
        background-color: #f0fdf4;
    }

    .rw-export-icon {
        width: 1.25rem;
        height: 1.25rem;
        margin-right: 0.5rem;
    }

    .rw-info-item {
        margin-bottom: 0.5rem;
    }

    /* Responsive adjustments */
    @media (max-width: 767px) {
        .rw-container {
            padding: 1rem;
        }

        .rw-modal-content {
            margin-top: 2rem;
            padding: 1rem;
            max-width: 95%;
        }

        .rw-grid {
            grid-template-columns: 1fr;
        }
    }

    .rw-status-red {
        color: #dc2626;
        font-weight: 600;
    }

    .rw-quotation-item {
        padding: 0.75rem;
        background-color: white;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        margin-bottom: 0.5rem;
    }
</style>

<div>
    <div class="rw-info-item" style='margin-bottom: 1rem;'>
        <span class="rw-label">Renewal Handover Details</span><br>
        <span class="rw-label">Company Name:</span>
        <span class="rw-value">{{ $companyDetail->company_name ?? $record->company_name ?? 'N/A' }}</span>
    </div>

    <div class="rw-container" style="border: 0.1rem solid; padding: 1rem;">
        <div class="rw-grid">
            <!-- Left Column -->
            <div class="rw-column">
                <div class="rw-info-item">
                    <span class="rw-label">Renewal Handover ID:</span>
                    <span class="rw-value">{{ $handoverId }}</span>
                </div>

                <hr class="my-6 border-t border-gray-300">

                <div class="rw-info-item">
                    <span class="rw-label">Status:</span>
                    <span class="rw-status-red rw-value">{{ $record->status ?? 'Pending' }}</span>
                </div>

                <div class="rw-info-item">
                    <span class="rw-label">Renewal Date:</span>
                    <span class="rw-value">{{ $record->created_at ? $record->created_at->format('d F Y') : 'N/A' }}</span>
                </div>

                <div class="rw-info-item">
                    <span class="rw-label">SalesPerson:</span>
                    <span class="rw-value">{{ $salespersonName }}</span>
                </div>

                <hr class="my-6 border-t border-gray-300">

                <!-- Company Details Modal -->
                <div class="rw-remark-container" x-data="{ companyOpen: false }">
                    <span class="rw-label">Company Details:</span>
                    <a href="#" @click.prevent="companyOpen = true" class="rw-view-link">View</a>

                    <div x-show="companyOpen" x-cloak x-transition @click.outside="companyOpen = false" class="rw-modal">
                        <div class="rw-modal-content" @click.away="companyOpen = false">
                            <div class="rw-modal-header">
                                <h3 class="rw-modal-title">Company Details</h3>
                                <button type="button" @click="companyOpen = false" class="rw-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="rw-modal-body">
                                <table class="rw-table">
                                    <tbody>
                                        <tr>
                                            <td style="font-weight: 600; width: 40%; background-color: #f3f4f6;">Company Name</td>
                                            <td>{{ $companyDetail->company_name ?? $record->company_name ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; width: 40%; background-color: #f3f4f6;">Debtor Code</td>
                                            <td>{{ $record->debtor_code ?? 'N/A' }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; width: 40%; background-color: #f3f4f6;">Total Amount</td>
                                            <td>RM {{ number_format($record->total_amount ?? 0, 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td style="font-weight: 600; width: 40%; background-color: #f3f4f6;">SalesPerson Name</td>
                                            <td>{{ $salespersonName }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quotations Modal -->
                @if($quotations && $quotations->count() > 0)
                <div class="rw-remark-container" x-data="{ quotationsOpen: false }">
                    <span class="rw-label">Renewal Quotations:</span>
                    <a href="#" @click.prevent="quotationsOpen = true" class="rw-view-link">View ({{ $quotations->count() }})</a>

                    <div x-show="quotationsOpen" x-cloak x-transition @click.outside="quotationsOpen = false" class="rw-modal">
                        <div class="rw-modal-content" @click.away="quotationsOpen = false">
                            <div class="rw-modal-header">
                                <h3 class="rw-modal-title">Renewal Quotations</h3>
                                <button type="button" @click="quotationsOpen = false" class="rw-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="rw-modal-body">
                                @foreach($quotations as $quotation)
                                    <div class="rw-quotation-item">
                                        <div style="display: flex; justify-content: space-between; align-items: start;">
                                            <div>
                                                <div style="font-weight: 600; color: #2563eb;">{{ $quotation->quotation_reference_no ?? 'N/A' }}</div>
                                                <div style="font-size: 0.875rem; color: #6b7280;">Created: {{ $quotation->created_at ? $quotation->created_at->format('d M Y') : 'N/A' }}</div>
                                                <div style="font-size: 0.875rem; color: #6b7280;">Items: {{ $quotation->items ? $quotation->items->count() : 0 }}</div>
                                            </div>
                                            <div style="text-align: right;">
                                                <div style="font-size: 1.125rem; font-weight: 700;">RM {{ number_format($quotation->total_after_tax ?? 0, 2) }}</div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="rw-info-item">
                    <span class="rw-label">Renewal Quotations:</span>
                    <span class="rw-not-available">Not Available</span>
                </div>
                @endif

                <!-- Notes -->
                @if($record->notes)
                <div class="rw-remark-container" x-data="{ notesOpen: false }">
                    <span class="rw-label">Renewal Notes:</span>
                    <a href="#" @click.prevent="notesOpen = true" class="rw-view-link">View</a>

                    <div x-show="notesOpen" x-cloak x-transition @click.outside="notesOpen = false" class="rw-modal">
                        <div class="rw-modal-content" @click.away="notesOpen = false">
                            <div class="rw-modal-header">
                                <h3 class="rw-modal-title">Renewal Notes</h3>
                                <button type="button" @click="notesOpen = false" class="rw-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="rw-modal-body">
                                <div class="rw-modal-text">
                                    {{ $record->notes }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="rw-info-item">
                    <span class="rw-label">Renewal Notes:</span>
                    <span class="rw-not-available">Not Available</span>
                </div>
                @endif

                <hr class="my-6 border-t border-gray-300">

                <div class="rw-info-item">
                    <span class="rw-label">Total Quotations:</span>
                    <span class="rw-value">{{ $record->total_quotations ?? 0 }}</span>
                </div>

                <div class="rw-info-item">
                    <span class="rw-label">Total Amount:</span>
                    <span class="rw-value">RM {{ number_format($record->total_amount ?? 0, 2) }}</span>
                </div>
            </div>

            <!-- Right Column -->
            <div class="rw-column-right">
                <!-- Invoice Information -->
                <div class="rw-info-item">
                    <span class="rw-label">TT Invoice Number:</span>
                    <span class="rw-value">{{ $record->tt_invoice_number ?? 'Not Generated' }}</span>
                </div>

                <div class="rw-info-item">
                    <span class="rw-label">Invoice Numbers:</span>
                    @if($record->invoice_numbers && is_array($record->invoice_numbers) && count($record->invoice_numbers) > 0)
                        <span class="rw-value">{{ implode(', ', $record->invoice_numbers) }}</span>
                    @else
                        <span class="rw-not-available">Not Generated</span>
                    @endif
                </div>

                <div class="rw-info-item">
                    <span class="rw-label">Processing Status:</span>
                    <span class="rw-value {{ $record->processed_at ? 'rw-status-completed' : 'rw-status-pending' }}">
                        {{ $record->processed_at ? 'Processed' : 'Pending Processing' }}
                    </span>
                </div>

                @if($record->processed_at)
                <div class="rw-info-item">
                    <span class="rw-label">Processed Date:</span>
                    <span class="rw-value">{{ $record->processed_at->format('d F Y, H:i') }}</span>
                </div>
                @endif

                <hr class="my-6 border-t border-gray-300">

                <!-- AutoCount Response -->
                {{-- @if($record->autocount_response)
                <div class="rw-remark-container" x-data="{ responseOpen: false }">
                    <span class="rw-label">AutoCount Response:</span>
                    <a href="#" @click.prevent="responseOpen = true" class="rw-view-link">View</a>

                    <div x-show="responseOpen" x-cloak x-transition @click.outside="responseOpen = false" class="rw-modal">
                        <div class="rw-modal-content" @click.away="responseOpen = false">
                            <div class="rw-modal-header">
                                <h3 class="rw-modal-title">AutoCount Response</h3>
                                <button type="button" @click="responseOpen = false" class="rw-modal-close">
                                    <svg fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="rw-modal-body">
                                <pre class="rw-modal-text" style="white-space: pre-wrap; font-family: monospace;">{{ json_encode($record->autocount_response, JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="rw-info-item">
                    <span class="rw-label">AutoCount Response:</span>
                    <span class="rw-not-available">Not Available</span>
                </div>
                @endif --}}
            </div>
        </div>
    </div>
</div>
