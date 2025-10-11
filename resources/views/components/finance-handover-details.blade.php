{{-- filepath: /var/www/html/timeteccrm/resources/views/components/finance-handover-details.blade.php --}}
<div class="finance-handover-details">
    <style>
        .finance-handover-details {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .handover-header {
            background-color: #dbeafe;
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .handover-title {
            font-size: 1.125rem;
            font-weight: 600;
            color: #1e3a8a;
            margin-bottom: 0.5rem;
        }

        .handover-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            font-size: 0.875rem;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: 500;
            margin-bottom: 0.25rem;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
            max-width: fit-content;
        }

        .status-new {
            background-color: #dcfce7;
            color: #166534;
        }

        .status-processing {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-completed {
            background-color: #dbeafe;
            color: #1e40af;
        }

        .status-rejected {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .reseller-section {
            background-color: #f9fafb;
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .section-title {
            font-weight: 600;
            color: #111827;
            margin-bottom: 0.75rem;
        }

        .reseller-details {
            display: grid;
            grid-template-columns: 1fr;
            gap: 0.5rem;
            font-size: 0.875rem;
        }

        .detail-item {
            display: flex;
            gap: 0.5rem;
        }

        .detail-label {
            font-weight: 500;
            min-width: 80px;
        }

        .attachments-section {
            background-color: #fff7ed;
            padding: 1rem;
            border-radius: 0.5rem;
        }

        .attachments-title {
            font-weight: 600;
            color: #ea580c;
            margin-bottom: 0.75rem;
        }

        .attachments-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .attachment-group {
            margin: 0;
        }

        .attachment-group-title {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .file-list {
            list-style-type: disc;
            padding-left: 1.25rem;
            margin: 0;
        }

        .file-item {
            margin-bottom: 0.25rem;
        }

        .file-link {
            color: #2563eb;
            font-size: 0.875rem;
            text-decoration: none;
        }

        .file-link:hover {
            text-decoration: underline;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .handover-info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    @php
        $formattedId = 'FN_250' . str_pad($record->id, 3, '0', STR_PAD_LEFT);
    @endphp

    <div class="handover-header">
        <h3 class="handover-title">Finance Handover: {{ $formattedId }}</h3>
        <div class="handover-info-grid">
            <div class="info-item">
                <span class="info-label">Submitted Date:</span>
                <span>{{ $record->submitted_at?->format('d M Y H:i') }}</span>
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                <span class="status-badge
                    @if($record->status === 'New') status-new
                    @elseif($record->status === 'Processing') status-processing
                    @elseif($record->status === 'Completed') status-completed
                    @elseif($record->status === 'Rejected') status-rejected
                    @endif">
                    {{ $record->status }}
                </span>
            </div>
        </div>
    </div>

    <div class="reseller-section">
        <h4 class="section-title">Reseller Details</h4>
        <div class="reseller-details">
            <div class="detail-item">
                <span class="detail-label">Company:</span>
                <span>{{ $record->reseller->company_name ?? 'N/A' }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">PIC Name:</span>
                <span>{{ $record->pic_name }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">HP Number:</span>
                <span>{{ $record->pic_phone }}</span>
            </div>
            <div class="detail-item">
                <span class="detail-label">Email:</span>
                <span>{{ $record->pic_email }}</span>
            </div>
        </div>
    </div>

    <div class="attachments-section">
        <h4 class="attachments-title">Attachment Details</h4>

        @php
            $invoiceCustomer = $record->invoice_by_customer ? (is_string($record->invoice_by_customer) ? json_decode($record->invoice_by_customer, true) : $record->invoice_by_customer) : [];
            $paymentCustomer = $record->payment_by_customer ? (is_string($record->payment_by_customer) ? json_decode($record->payment_by_customer, true) : $record->payment_by_customer) : [];
            $invoiceReseller = $record->invoice_by_reseller ? (is_string($record->invoice_by_reseller) ? json_decode($record->invoice_by_reseller, true) : $record->invoice_by_reseller) : [];
        @endphp

        <div class="attachments-content">
            @if(!empty($invoiceCustomer))
                <div class="attachment-group">
                    <h5 class="attachment-group-title">Invoice by Customer</h5>
                    <ul class="file-list">
                        @foreach($invoiceCustomer as $index => $file)
                            <li class="file-item">
                                <a href="{{ asset('storage/' . $file) }}" target="_blank" class="file-link">
                                    File {{ $index + 1 }} (View/Download)
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($paymentCustomer))
                <div class="attachment-group">
                    <h5 class="attachment-group-title">Payment by Customer</h5>
                    <ul class="file-list">
                        @foreach($paymentCustomer as $index => $file)
                            <li class="file-item">
                                <a href="{{ asset('storage/' . $file) }}" target="_blank" class="file-link">
                                    File {{ $index + 1 }} (View/Download)
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if(!empty($invoiceReseller))
                <div class="attachment-group">
                    <h5 class="attachment-group-title">Invoice by Reseller</h5>
                    <ul class="file-list">
                        @foreach($invoiceReseller as $index => $file)
                            <li class="file-item">
                                <a href="{{ asset('storage/' . $file) }}" target="_blank" class="file-link">
                                    File {{ $index + 1 }} (View/Download)
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
