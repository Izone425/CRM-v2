<!-- filepath: /var/www/html/timeteccrm/resources/views/pdf/repair-handover.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Repair Handover Form</title>
    <style>
        @page {
            margin: 2cm;
        }
        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e0e0e0;
        }
        .logo {
            max-width: 180px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 18px;
            margin: 5px 0;
            font-weight: bold;
            color: #003366;
        }
        .company-name {
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .repair-id {
            display: inline-block;
            padding: 4px 10px;
            background-color: #003366;
            color: white;
            border-radius: 15px;
            font-size: 12px;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }
        .section {
            margin-bottom: 20px;
            clear: both;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #003366;
            color: white;
            padding: 6px 10px;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 4px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table, th, td {
            border: 0.5px solid #e0e0e0;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
        }
        td {
            padding: 6px 8px;
            font-size: 10px;
            vertical-align: top;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 6px 8px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            width: 120px;
            background-color: #f9f9f9;
        }
        .signature-area {
            margin-top: 30px;
            width: 100%;
            page-break-inside: avoid;
        }
        .signature-box {
            width: 45%;
            float: left;
            margin-right: 5%;
            padding: 10px;
            border: 0.5px solid #e0e0e0;
            background-color: #fcfcfc;
            min-height: 120px;
        }
        .signature-box:last-child {
            margin-right: 0;
        }
        .signature-line {
            border-top: 0.5px solid #000;
            padding-top: 5px;
            margin-top: 70px;
            width: 80%;
        }
        .signature-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #003366;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #666;
            padding: 5px 0;
            border-top: 0.5px solid #e0e0e0;
            margin: 0 20px;
        }
        .stamp {
            margin-top: 10px;
        }
        .stamp img {
            max-width: 100px;
            max-height: 100px;
            opacity: 0.7;
        }
        .col-6 {
            width: 50%;
            float: left;
        }
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }
        .status-tag {
            display: inline-block;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: bold;
            color: white;
            border-radius: 3px;
        }
        .status-New {
            background-color: #2563eb;
        }
        .status-Accepted {
            background-color: #f59e0b;
        }
        .status-Pending {
            background-color: #8b5cf6;
        }
        .status-Onsite {
            background-color: #ec4899;
        }
        .status-Completed {
            background-color: #10b981;
        }
        .status-Inactive {
            background-color: #6c757d;
        }
        .key-value {
            padding: 5px 10px;
            margin-bottom: 5px;
            border-radius: 3px;
            background-color: #f9f9f9;
        }
        .key-value .key {
            font-weight: bold;
            color: #003366;
            display: inline-block;
            width: 120px;
        }
        .key-value .value {
            display: inline-block;
        }
        .device-card {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #fcfcfc;
        }
        .device-header {
            padding-bottom: 5px;
            border-bottom: 1px solid #e0e0e0;
            margin-bottom: 5px;
            font-weight: bold;
            color: #003366;
        }
        .video-link {
            color: #0066cc;
            text-decoration: underline;
        }
        .no-data {
            text-align: center;
            font-style: italic;
            color: #777;
            padding: 10px;
            background-color: #f9f9f9;
            border-radius: 3px;
        }
        .warranty-in {
            color: #10b981;
            font-weight: bold;
        }
        .warranty-out {
            color: #ef4444;
            font-weight: bold;
        }
        .quotation-section {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
            margin-bottom: 10px;
            padding: 0;
            overflow: hidden;
        }
        .quotation-header {
            padding: 6px 10px;
            background-color: #f2f2f2;
            font-weight: bold;
            border-bottom: 1px solid #e0e0e0;
        }
        .quotation-body {
            padding: 6px 10px;
        }
        .attachment-link {
            color: #0066cc;
            text-decoration: underline;
            display: inline-block;
            margin: 2px 0;
        }
        .zebra-stripe:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $path_img ?? asset('img/logo-ttc.png') }}" alt="TIMETEC Logo" class="logo">
        <h1>REPAIR HANDOVER FORM</h1>
        <div class="repair-id">{{ $repairId }}</div>
        <div class="company-name">{{ $repair->company_name ?? 'Unknown Company' }}</div>
    </div>

    <div class="section">
        <div class="section-title">1. TICKET DETAILS</div>

        <div class="key-value">
            <span class="key">Status:</span>
            <span class="value">
                <span class="status-tag status-{{ str_replace(' ', '', $repair->status) }}">
                    {{ $repair->status }}
                </span>
            </span>
        </div>

        <div class="key-value">
            <span class="key">Zoho Ticket:</span>
            <span class="value">{{ $repair->zoho_ticket ?? 'Not provided' }}</span>
        </div>

        <div class="key-value">
            <span class="key">Submitted By:</span>
            <span class="value">{{ $creator->name ?? 'Unknown User' }}</span>
        </div>

        <div class="key-value">
            <span class="key">Submission Date:</span>
            <span class="value">
                @php
                    try {
                        $submittedAt = $repair->submitted_at instanceof \Carbon\Carbon
                            ? $repair->submitted_at->format('d M Y, h:i A')
                            : ($repair->submitted_at ? \Carbon\Carbon::parse($repair->submitted_at)->format('d M Y, h:i A') : 'Not submitted yet');
                    } catch (\Exception $e) {
                        $submittedAt = is_string($repair->submitted_at) ? $repair->submitted_at : 'Not submitted yet';
                    }
                @endphp
                {{ $submittedAt }}
            </span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">2. COMPANY & CONTACT DETAILS</div>

        <div class="key-value">
            <span class="key">PIC Name:</span>
            <span class="value">{{ $repair->pic_name ?? 'Not provided' }}</span>
        </div>

        <div class="key-value">
            <span class="key">PIC Phone:</span>
            <span class="value">{{ $repair->pic_phone ?? 'Not provided' }}</span>
        </div>

        <div class="key-value">
            <span class="key">PIC Email:</span>
            <span class="value">{{ $repair->pic_email ?? 'Not provided' }}</span>
        </div>

        <div class="key-value">
            <span class="key">Address:</span>
            <span class="value">{{ $repair->address ?? 'Not provided' }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">3. DEVICE DETAILS</div>

        @php
            $devices = is_string($repair->devices)
                ? json_decode($repair->devices, true)
                : $repair->devices;

            if (!is_array($devices)) {
                $devices = [];
            }
        @endphp

        @if(count($devices) > 0)
            @foreach($devices as $index => $device)
                <div class="device-card">
                    <div class="device-header">Device #{{ $index + 1 }}</div>

                    <div class="key-value">
                        <span class="key">Model:</span>
                        <span class="value">{{ $device['device_model'] ?? 'N/A' }}</span>
                    </div>

                    <div class="key-value">
                        <span class="key">Serial Number:</span>
                        <span class="value">{{ $device['device_serial'] ?? 'N/A' }}</span>
                    </div>

                    <div class="key-value">
                        <span class="key">Category:</span>
                        <span class="value">{{ $device['device_category'] ?? 'N/A' }}</span>
                    </div>

                    <div class="key-value">
                        <span class="key">Remark:</span>
                        <span class="value">{{ $device['remark'] ?? 'N/A' }}</span>
                    </div>

                    @if(!empty($device['video_link_1']) || !empty($device['video_link_2']) || !empty($device['video_link_3']))
                        <div class="key-value">
                            <span class="key">Video Links:</span>
                            <span class="value">
                                @if(!empty($device['video_link_1']))
                                    <div><a href="{{ $device['video_link_1'] }}" class="video-link">Video 1</a></div>
                                @endif
                                @if(!empty($device['video_link_2']))
                                    <div><a href="{{ $device['video_link_2'] }}" class="video-link">Video 2</a></div>
                                @endif
                                @if(!empty($device['video_link_3']))
                                    <div><a href="{{ $device['video_link_3'] }}" class="video-link">Video 3</a></div>
                                @endif
                            </span>
                        </div>
                    @endif

                    @if(isset($device['attachments']) && !empty($device['attachments']))
                        <div class="key-value">
                            <span class="key">Attachments:</span>
                            <span class="value">
                                @php
                                    $attachments = is_string($device['attachments'])
                                        ? json_decode($device['attachments'], true)
                                        : $device['attachments'];

                                    if (!is_array($attachments)) {
                                        $attachments = [];
                                    }
                                @endphp

                                @foreach($attachments as $attIndex => $attachment)
                                    @php
                                        $fileName = basename($attachment);
                                        $publicUrl = url('storage/' . $attachment);
                                    @endphp
                                    <div>
                                        <a href="{{ $publicUrl }}" class="attachment-link">Attachment {{ $attIndex + 1 }}</a>
                                    </div>
                                @endforeach

                                @if(empty($attachments))
                                    <span class="no-data">No attachments</span>
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            @endforeach
        @elseif($repair->device_model)
            <!-- Legacy device data -->
            <div class="device-card">
                <div class="device-header">Device #1</div>

                <div class="key-value">
                    <span class="key">Model:</span>
                    <span class="value">{{ $repair->device_model }}</span>
                </div>

                <div class="key-value">
                    <span class="key">Serial Number:</span>
                    <span class="value">{{ $repair->device_serial }}</span>
                </div>

                <div class="key-value">
                    <span class="key">Category:</span>
                    <span class="value">{{ $repair->device_category ?? 'N/A' }}</span>
                </div>

                @if(!empty($repair->remark))
                    <div class="key-value">
                        <span class="key">Remark:</span>
                        <span class="value">{{ strtoupper($repair->remark ?? '') }}</span>
                    </div>
                @endif
            </div>
        @else
            <div class="no-data">No devices registered</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">4. REPAIR REMARKS</div>

        @php
            $remarks = is_string($repair->remarks)
                ? json_decode($repair->remarks, true)
                : $repair->remarks;

            if (!is_array($remarks)) {
                $remarks = [];
            }
        @endphp

        @if(count($remarks) > 0)
            @foreach($remarks as $index => $remark)
                <div class="device-card">
                    <div class="device-header">Remark #{{ $index + 1 }}</div>

                    <div class="key-value">
                        <span class="key">Notes:</span>
                        <span class="value" style="white-space: pre-line;">{{ $remark['remark'] ?? 'N/A' }}</span>
                    </div>

                    @if(isset($remark['attachments']) && !empty($remark['attachments']))
                        <div class="key-value">
                            <span class="key">Attachments:</span>
                            <span class="value">
                                @php
                                    $attachments = is_string($remark['attachments'])
                                        ? json_decode($remark['attachments'], true)
                                        : $remark['attachments'];

                                    if (!is_array($attachments)) {
                                        $attachments = [];
                                    }
                                @endphp

                                @foreach($attachments as $attIndex => $attachment)
                                    @php
                                        $fileName = basename($attachment);
                                        $publicUrl = url('storage/' . $attachment);
                                    @endphp
                                    <div>
                                        <a href="{{ $publicUrl }}" class="attachment-link">Attachment {{ $attIndex + 1 }}</a>
                                    </div>
                                @endforeach

                                @if(empty($attachments))
                                    <span class="no-data">No attachments</span>
                                @endif
                            </span>
                        </div>
                    @endif
                </div>
            @endforeach
        @else
            <div class="no-data">No remarks provided</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">5. TECHNICIAN ASSESSMENT</div>
        @php
            $technicianRemarks = is_string($repair->repair_remark)
                ? json_decode($repair->repair_remark, true)
                : $repair->repair_remark;

            if (!is_array($technicianRemarks)) {
                $technicianRemarks = [];
            }
        @endphp

        @if(count($technicianRemarks) > 0)
            @foreach($technicianRemarks as $deviceRepair)
                <div class="device-card">
                    <div class="device-header">
                        {{ $deviceRepair['device_model'] ?? 'N/A' }} | S/N: {{ $deviceRepair['device_serial'] ?? 'N/A' }}
                    </div>

                    <div class="key-value">
                        <span class="key">Assessment:</span>
                        <span class="value">
                            @if(!empty($deviceRepair['remarks']) && is_array($deviceRepair['remarks']))
                                @php
                                    $remark = $deviceRepair['remarks'][0] ?? null;
                                @endphp
                                @if($remark && !empty($remark['remark']))
                                    <div style="white-space: pre-line;">{{ $remark['remark'] }}</div>
                                @else
                                    <span class="no-data">No remarks provided</span>
                                @endif
                            @else
                                <span class="no-data">No remarks provided</span>
                            @endif
                        </span>
                    </div>

                    @if(!empty($deviceRepair['remarks']) && is_array($deviceRepair['remarks']))
                        @php
                            $remark = $deviceRepair['remarks'][0] ?? null;
                        @endphp
                        @if($remark && !empty($remark['attachments']) && is_array($remark['attachments']) && count($remark['attachments']) > 0)
                            <div class="key-value">
                                <span class="key">Attachments:</span>
                                <span class="value">
                                    @foreach($remark['attachments'] as $attIndex => $attachment)
                                        @php
                                            $fileName = basename($attachment);
                                            $publicUrl = url('storage/' . $attachment);
                                        @endphp
                                        <div>
                                            <a href="{{ $publicUrl }}" class="attachment-link">Attachment {{ $attIndex + 1 }}</a>
                                        </div>
                                    @endforeach
                                </span>
                            </div>
                        @endif
                    @endif

                    <div class="key-value">
                        <span class="key">Spare Parts:</span>
                        <span class="value">
                            @if(!empty($deviceRepair['spare_parts']) && is_array($deviceRepair['spare_parts']) && count($deviceRepair['spare_parts']) > 0)
                                <ul style="margin-top: 5px; padding-left: 20px;">
                                    @foreach($deviceRepair['spare_parts'] as $part)
                                        <li>
                                            {{ $part['name'] ?? 'Unknown Part' }}
                                            @if(!empty($part['code']))
                                                ({{ $part['code'] }})
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <span class="no-data">No spare parts required</span>
                            @endif
                        </span>
                    </div>
                </div>
            @endforeach
        @else
            <div class="no-data">No technician assessment available</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">6. WARRANTY INFORMATION</div>
        @php
            $devicesWarranty = is_string($repair->devices_warranty)
                ? json_decode($repair->devices_warranty, true)
                : $repair->devices_warranty;

            if (!is_array($devicesWarranty)) {
                $devicesWarranty = [];
            }
        @endphp

        @if(count($devicesWarranty) > 0)
            @foreach($devicesWarranty as $device)
                <div class="device-card">
                    <div class="device-header">
                        {{ $device['device_model'] ?? 'N/A' }} | S/N: {{ $device['device_serial'] ?? 'N/A' }}
                    </div>

                    <div class="key-value">
                        <span class="key">Invoice Date:</span>
                        <span class="value">
                            @if(!empty($device['invoice_date']))
                                @php
                                    try {
                                        // First check if it's already a Carbon instance
                                        if ($device['invoice_date'] instanceof \Carbon\Carbon) {
                                            $formattedDate = $device['invoice_date']->format('d M Y');
                                        }
                                        // Check if it's a valid date string format
                                        elseif (is_string($device['invoice_date']) && strtotime($device['invoice_date']) !== false) {
                                            $formattedDate = \Carbon\Carbon::parse($device['invoice_date'])->format('d M Y');
                                        }
                                        // If all else fails, just display as is
                                        else {
                                            $formattedDate = $device['invoice_date'];
                                        }
                                    } catch (\Exception $e) {
                                        $formattedDate = $device['invoice_date'];
                                    }
                                @endphp
                                {{ $formattedDate }}
                            @else
                                Not provided
                            @endif
                        </span>
                    </div>

                    <div class="key-value">
                        <span class="key">Warranty Status:</span>
                        <span class="value {{ $device['warranty_status'] === 'In Warranty' ? 'warranty-in' : 'warranty-out' }}">
                            {{ $device['warranty_status'] ?? 'Unknown' }}
                        </span>
                    </div>
                </div>
            @endforeach
        @else
            <div class="no-data">No warranty information available</div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">7. QUOTATION INFORMATION</div>

        @php
            // Product Quotations
            $productQuotations = is_string($repair->quotation_product)
                ? json_decode($repair->quotation_product, true)
                : $repair->quotation_product;

            if (!is_array($productQuotations)) {
                $productQuotations = [];
            }

            // HRDF Quotations
            $hrdfQuotations = is_string($repair->quotation_hrdf)
                ? json_decode($repair->quotation_hrdf, true)
                : $repair->quotation_hrdf;

            if (!is_array($hrdfQuotations)) {
                $hrdfQuotations = [];
            }

            $hasQuotations = (count($productQuotations) > 0 || count($hrdfQuotations) > 0);
        @endphp

        <!-- Product Quotations -->
        <div class="quotation-section">
            <div class="quotation-header">Product Quotations</div>
            <div class="quotation-body">
                @if(count($productQuotations) > 0)
                    @foreach($productQuotations as $index => $quoteId)
                        @php
                            $quotation = \App\Models\Quotation::find($quoteId);
                        @endphp
                        @if($quotation)
                            <div class="key-value zebra-stripe">
                                <span class="key">Reference:</span>
                                <span class="value">{{ $quotation->quotation_reference_no }}</span>
                            </div>
                        @endif
                    @endforeach
                @else
                    <div class="no-data">No product quotations linked</div>
                @endif
            </div>
        </div>

        <!-- HRDF Quotations -->
        <div class="quotation-section">
            <div class="quotation-header">HRDF Quotations</div>
            <div class="quotation-body">
                @if(count($hrdfQuotations) > 0)
                    @foreach($hrdfQuotations as $index => $quoteId)
                        @php
                            $quotation = \App\Models\Quotation::find($quoteId);
                        @endphp
                        @if($quotation)
                            <div class="key-value zebra-stripe">
                                <span class="key">Reference:</span>
                                <span class="value">{{ $quotation->quotation_reference_no }}</span>
                            </div>
                        @endif
                    @endforeach
                @else
                    <div class="no-data">No HRDF quotations linked</div>
                @endif
            </div>
        </div>
    </div>
    <div class="footer">
        Generated on {{ now()->format('d M Y, h:i A') }} | Repair Handover Form #{{ $repairId }} | TimeTec Cloud Sdn Bhd
    </div>
</body>
</html>
