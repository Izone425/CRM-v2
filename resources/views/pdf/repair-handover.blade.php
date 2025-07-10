<!DOCTYPE html>
<!-- filepath: /var/www/html/timeteccrm/resources/views/pdf/repair-handover.blade.php -->
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
            padding-bottom: 5px;
            border-bottom: 0.5px solid #ccc;
        }
        .logo {
            max-width: 180px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 16px;
            margin: 5px 0;
            font-weight: bold;
            color: #003366;
        }
        .company-name {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .section {
            margin-bottom: 15px;
            clear: both;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #e6e6e6;
            padding: 5px 8px;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: bold;
            border-bottom: 0.5px solid #ccc;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        table, th, td {
            border: 0.5px solid #ccc;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: left;
            padding: 5px;
            font-size: 10px;
        }
        td {
            padding: 5px;
            font-size: 10px;
            vertical-align: top;
        }
        .info-grid {
            width: 100%;
            border-collapse: collapse;
        }
        .info-grid td {
            padding: 5px;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            width: 120px;
            background-color: #f9f9f9;
        }
        .status-approved {
            color: green;
            font-weight: bold;
        }
        .status-rejected {
            color: red;
            font-weight: bold;
        }
        .status-draft {
            color: orange;
            font-weight: bold;
        }
        .status-new {
            color: #003366;
            font-weight: bold;
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
        }
        .signature-box:last-child {
            margin-right: 0;
        }
        .signature-line {
            border-top: 0.5px solid #000;
            padding-top: 5px;
            margin-top: 40px;
            width: 80%;
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
            border-top: 0.5px solid #ccc;
            margin: 0 20px;
        }
        .stamp {
            margin-top: 5px;
        }
        .stamp img {
            max-width: 100px;
            max-height: 100px;
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
            background-color: #dc3545;
        }
        .status-Draft {
            background-color: #6c757d;
        }
        .status-InProgress {
            background-color: #ffc107;
            color: #333;
        }
        .status-AwaitingParts {
            background-color: #17a2b8;
        }
        .status-Resolved {
            background-color: #28a745;
        }
        .status-Closed {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $path_img ?? asset('img/logo-ttc.png') }}" alt="TIMETEC Logo" class="logo">
        <h1>REPAIR HANDOVER FORM</h1>
        <div class="company-name">{{ $repair->company_name ?? 'Unknown Company' }}</div>
    </div>

    <div class="section">
        <div class="section-title">1. TICKET DETAILS</div>
        <table class="info-grid">
            <tr>
                <td class="label" width="30%">Repair Ticket ID</td>
                <td width="70%">
                    <strong>{{ $repairId }}</strong>
                </td>
            </tr>
            <tr>
                <td class="label">Status</td>
                <td>
                    {{ $repair->status }}
                </td>
            </tr>
            <tr>
                <td class="label">Zoho Ticket</td>
                <td>{{ $repair->zoho_ticket ?? 'Not provided' }}</td>
            </tr>
            <tr>
                <td class="label">Submitted By</td>
                <td>{{ $creator->name ?? 'Unknown User' }}</td>
            </tr>
            <tr>
                <td class="label">Submission Date</td>
                <td>
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
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. COMPANY & CONTACT DETAILS</div>
        <table class="info-grid">
            <tr>
                <td class="label" width="30%">Company Name</td>
                <td width="70%">
                    <strong>{{ $repair->company_name ?? 'Unknown Company' }}</strong>
                </td>
            </tr>
            <tr>
                <td class="label">PIC Name</td>
                <td>{{ $repair->pic_name ?? 'Not provided' }}</td>
            </tr>
            <tr>
                <td class="label">PIC Phone</td>
                <td>{{ $repair->pic_phone ?? 'Not provided' }}</td>
            </tr>
            <tr>
                <td class="label">PIC Email</td>
                <td>{{ $repair->pic_email ?? 'Not provided' }}</td>
            </tr>
            <tr>
                <td class="label">Address</td>
                <td>{{ $repair->address ?? 'Not provided' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">3. DEVICE DETAILS</div>
        <table>
            <thead>
                <tr>
                    <th width="10%">No.</th>
                    <th width="40%">Device Model</th>
                    <th width="50%">Serial Number</th>
                </tr>
            </thead>
            <tbody>
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
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $device['device_model'] ?? 'N/A' }}</td>
                            <td>{{ $device['device_serial'] ?? 'N/A' }}</td>
                        </tr>
                    @endforeach
                @elseif($repair->device_model)
                    <!-- Legacy device data -->
                    <tr>
                        <td>1</td>
                        <td>{{ $repair->device_model }}</td>
                        <td>{{ $repair->device_serial }}</td>
                    </tr>
                @else
                    <tr>
                        <td colspan="3" style="text-align: center; font-style: italic;">No devices registered</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">4. REPAIR REMARKS</div>
        <table>
            <thead>
                <tr>
                    <th width="10%">No.</th>
                    <th width="60%">Remark</th>
                    <th width="30%">Attachments</th>
                </tr>
            </thead>
            <tbody>
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
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td style="white-space: pre-line;">{{ $remark['remark'] ?? 'N/A' }}</td>
                            <td>
                                @if(isset($remark['attachments']) && !empty($remark['attachments']))
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
                                        <div style="margin-bottom: 4px;">
                                            <a href="{{ $publicUrl }}" target="_blank" style="color: #0066cc; text-decoration: underline;">
                                                Attachment {{ $attIndex + 1 }}
                                            </a>
                                        </div>
                                    @endforeach

                                    @if(empty($attachments))
                                        <span style="font-style: italic; color: #777;">No attachments</span>
                                    @endif
                                @else
                                    <span style="font-style: italic; color: #777;">No attachments</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="3" style="text-align: center; font-style: italic;">No remarks provided</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">5. VIDEO FILES</div>
        <table>
            <thead>
                <tr>
                    <th width="10%">No.</th>
                    <th width="90%">Video File</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $videos = is_string($repair->video_files)
                        ? json_decode($repair->video_files, true)
                        : $repair->video_files;

                    if (!is_array($videos)) {
                        $videos = [];
                    }
                @endphp

                @if(count($videos) > 0)
                    @foreach($videos as $index => $video)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @php
                                    $fileName = basename($video);
                                    $publicUrl = url('storage/' . $video);
                                @endphp
                                <a href="{{ $publicUrl }}" target="_blank" style="color: #0066cc; text-decoration: underline;">
                                    {{ $fileName }}
                                </a>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="2" style="text-align: center; font-style: italic;">No video files provided</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">6. TECHNICIAN ASSESSMENT</div>
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
                <table style="margin-bottom: 15px; page-break-inside: avoid;">
                    <tr>
                        <th colspan="2" style="background-color: #f2f2f2; text-align: left; padding: 5px; font-size: 11px;">
                            Device Model: {{ $deviceRepair['device_model'] ?? 'N/A' }}
                            &nbsp;|&nbsp;
                            Serial Number: {{ $deviceRepair['device_serial'] ?? 'N/A' }}
                        </th>
                    </tr>
                    <tr>
                        <td width="70%" style="vertical-align: top; padding: 5px; border: 0.5px solid #ccc;">
                            <strong>Repair Remark:</strong><br/>
                            @if(!empty($deviceRepair['remarks']) && is_array($deviceRepair['remarks']))
                                @php
                                    $remark = $deviceRepair['remarks'][0] ?? null;
                                @endphp
                                @if($remark && !empty($remark['remark']))
                                    {{ $remark['remark'] }}
                                @else
                                    <span style="font-style: italic; color: #777;">No remarks provided</span>
                                @endif
                            @else
                                <span style="font-style: italic; color: #777;">No remarks provided</span>
                            @endif
                        </td>
                        <td width="30%" style="vertical-align: top; padding: 5px; border: 0.5px solid #ccc;">
                            <strong>Attachment:</strong><br/>
                            @if(!empty($deviceRepair['remarks']) && is_array($deviceRepair['remarks']))
                                @php
                                    $remark = $deviceRepair['remarks'][0] ?? null;
                                @endphp
                                @if($remark && !empty($remark['attachments']) && is_array($remark['attachments']) && count($remark['attachments']) > 0)
                                    @foreach($remark['attachments'] as $attIndex => $attachment)
                                        <div style="margin-bottom: 4px;">
                                            @php
                                                $fileName = basename($attachment);
                                                $publicUrl = url('storage/' . $attachment);
                                            @endphp
                                            <a href="{{ $publicUrl }}" target="_blank" style="color: #0066cc; text-decoration: underline;">
                                                Attachment {{ $attIndex + 1 }}
                                            </a>
                                        </div>
                                    @endforeach
                                @else
                                    <span style="font-style: italic; color: #777;">No attachments</span>
                                @endif
                            @else
                                <span style="font-style: italic; color: #777;">No attachments</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding: 5px; border: 0.5px solid #ccc;">
                            <strong>Spare Parts Required:</strong><br/>
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
                                <span style="font-style: italic; color: #777;">No spare parts required</span>
                            @endif
                        </td>
                    </tr>
                </table>
            @endforeach
        @else
            <p style="text-align: center; font-style: italic; color: #777; padding: 10px;">No technician assessment available</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">7. WARRANTY INFORMATION</div>
        @php
            $devicesWarranty = is_string($repair->devices_warranty)
                ? json_decode($repair->devices_warranty, true)
                : $repair->devices_warranty;

            if (!is_array($devicesWarranty)) {
                $devicesWarranty = [];
            }
        @endphp

        @if(count($devicesWarranty) > 0)
            <table>
                <thead>
                    <tr>
                        <th width="40%">Device Model</th>
                        <th width="20%">Serial Number</th>
                        <th width="20%">Invoice Date</th>
                        <th width="20%">Warranty Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($devicesWarranty as $device)
                        <tr>
                            <td>{{ $device['device_model'] ?? 'N/A' }}</td>
                            <td>{{ $device['device_serial'] ?? 'N/A' }}</td>
                            <td>
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
                            </td>
                            <td style="font-weight: bold; {{ $device['warranty_status'] === 'In Warranty' ? 'color: green;' : 'color: red;' }}">
                                {{ $device['warranty_status'] ?? 'Unknown' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p style="text-align: center; font-style: italic; color: #777; padding: 10px;">No warranty information available</p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">8. QUOTATION INFORMATION</div>

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
        <table style="width: 100%; margin-bottom: 15px;">
            <thead>
                <tr>
                    <th style="background-color: #e6e6e6; font-weight: bold;">Product Quotations</th>
                </tr>
                <tr>
                    <th style="background-color: #f2f2f2;">Reference Number</th>
                </tr>
            </thead>
            <tbody>
                @if(count($productQuotations) > 0)
                    @foreach($productQuotations as $quoteId)
                        @php
                            $quotation = \App\Models\Quotation::find($quoteId);
                        @endphp
                        @if($quotation)
                            <tr>
                                <td>{{ $quotation->quotation_reference_no }}</td>
                            </tr>
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td style="text-align: center; font-style: italic;">No product quotations linked</td>
                    </tr>
                @endif
            </tbody>
        </table>

        <!-- HRDF Quotations -->
        <table style="width: 100%;">
            <thead>
                <tr>
                    <th style="background-color: #e6e6e6; font-weight: bold;">HRDF Quotations</th>
                </tr>
                <tr>
                    <th style="background-color: #f2f2f2;">Reference Number</th>
                </tr>
            </thead>
            <tbody>
                @if(count($hrdfQuotations) > 0)
                    @foreach($hrdfQuotations as $quoteId)
                        @php
                            $quotation = \App\Models\Quotation::find($quoteId);
                        @endphp
                        @if($quotation)
                            <tr>
                                <td>{{ $quotation->quotation_reference_no }}</td>
                            </tr>
                        @endif
                    @endforeach
                @else
                    <tr>
                        <td style="text-align: center; font-style: italic;">No HRDF quotations linked</td>
                    </tr>
                @endif
            </tbody>
        </table>

        @if(!$hasQuotations)
            <p style="text-align: center; font-style: italic; color: #777; padding: 10px;">No quotation information available</p>
        @endif
    </div>
</body>
</html>
