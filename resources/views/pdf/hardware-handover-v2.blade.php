<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hardware Handover Form</title>
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
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $path_img ?? asset('img/logo-ttc.png') }}" alt="TIMETEC Logo" class="logo">
        <h1>HARDWARE HANDOVER FORM</h1>
        <div class="company-name">{{ $hardwareHandover->company_name ?? $lead->companyDetail->company_name ?? 'Unknown Company' }}</div>
    </div>

    <div class="section">
        <div class="section-title">1. INVOICE TYPE</div>
        <table class="info-grid">
            <tr>
                <td class="label" width="30%">Invoice Type</td>
                <td width="70%">
                    @if($hardwareHandover->invoice_type === 'combined')
                        <strong>Combined Invoice</strong> (Hardware + Software)
                    @else
                        <strong>Single Invoice</strong> (Hardware Only)
                    @endif
                </td>
            </tr>

            @if($hardwareHandover->invoice_type === 'combined' && $hardwareHandover->related_software_handovers)
                <tr>
                    <td class="label">Software Handovers</td>
                    <td>
                        @php
                            $relatedHandovers = is_string($hardwareHandover->related_software_handovers)
                                ? json_decode($hardwareHandover->related_software_handovers, true)
                                : $hardwareHandover->related_software_handovers;

                            if (!is_array($relatedHandovers)) {
                                $relatedHandovers = [];
                            }
                        @endphp

                        @if(count($relatedHandovers) > 0)
                            @foreach($relatedHandovers as $handoverId)
                                @php
                                    $softwareHandover = \App\Models\SoftwareHandover::find($handoverId);
                                    if ($softwareHandover) {
                                        $formattedId = 'SW_250' . str_pad($softwareHandover->id, 3, '0', STR_PAD_LEFT);
                                        $pdfUrl = $softwareHandover->handover_pdf
                                            ? url('storage/' . $softwareHandover->handover_pdf)
                                            : null;
                                    }
                                @endphp

                                @if(isset($softwareHandover))
                                    @if($pdfUrl)
                                        <a href="{{ $pdfUrl }}" target="_blank" style="color: #0066cc; text-decoration: underline;">
                                            {{ $formattedId }}
                                        </a>
                                    @else
                                        {{ $formattedId }}
                                    @endif
                                    ({{ $softwareHandover->created_at ? $softwareHandover->created_at->format('d M Y') : 'N/A' }})
                                @else
                                    <li style="margin-bottom: 3px;">Software Handover #{{ $handoverId }} (Not found)</li>
                                @endif
                            @endforeach
                        @else
                            <span style="font-style: italic; color: #777;">No related software handovers</span>
                        @endif
                    </td>
                </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. INVOICE DETAILS</div>
    </div>

    <div class="section">
        <div class="section-title">3. CONTACT DETAILS</div>
        <table class="info-grid">
            <thead>
                <tr>
                    <th width="10%">No.</th>
                    <th width="30%">Name</th>
                    <th width="30%">HP Number</th>
                    <th width="30%">Email Address</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $contactDetails = is_string($hardwareHandover->contact_detail)
                        ? json_decode($hardwareHandover->contact_detail, true)
                        : $hardwareHandover->contact_detail;

                    if (!is_array($contactDetails)) {
                        $contactDetails = [];
                    }
                @endphp

                @if(count($contactDetails) > 0)
                    @foreach($contactDetails as $index => $contact)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $contact['pic_name'] ?? '-' }}</td>
                            <td>{{ $contact['pic_phone'] ?? '-' }}</td>
                            <td>{{ $contact['pic_email'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="4" style="text-align: center; font-style: italic;">No contact details available</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">4. CATEGORY 1</div>
        <table class="info-grid">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tr>
                <td class="label">Installation Type</td>
                <td>
                    @if($hardwareHandover->installation_type === 'internal_installation')
                        Internal Installation
                    @elseif($hardwareHandover->installation_type === 'external_installation')
                        External Installation
                    @elseif($hardwareHandover->installation_type === 'courier')
                        Courier
                    @elseif($hardwareHandover->installation_type === 'self_pick_up')
                        Self Pick-Up
                    @else
                        {{ $hardwareHandover->installation_type ?? 'Not specified' }}
                    @endif
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">5. CATEGORY 2</div>
        <table class="info-grid">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
            </thead>
            @php
                $category2 = is_string($hardwareHandover->category2) ? json_decode($hardwareHandover->category2, true) : $hardwareHandover->category2;
            @endphp

            @if($hardwareHandover->installation_type === 'courier')
                @php
                    $courierAddresses = [];
                    if (isset($category2['courier_addresses'])) {
                        $courierAddresses = is_string($category2['courier_addresses'])
                            ? json_decode($category2['courier_addresses'], true)
                            : $category2['courier_addresses'];
                    }

                    if (!is_array($courierAddresses)) {
                        $courierAddresses = [];
                    }
                @endphp

                <tr>
                    <td class="label" width="30%">Courier Addresses</td>
                    <td width="70%">
                        @if(count($courierAddresses) > 0)
                            @foreach($courierAddresses as $index => $courierData)
                                @php
                                    $addressText = $courierData['address'] ?? '';
                                @endphp
                                <div style="margin-bottom: 15px; padding: 8px; border: 1px solid #ddd; background-color: #f9f9f9;">
                                    <strong>Courier Address {{ $index + 1 }}:</strong><br>
                                    <div style="white-space: pre-line; font-family: monospace; font-size: 9px; margin-top: 5px;">{{ $addressText }}</div>
                                </div>
                            @endforeach
                        @else
                            <span style="font-style: italic; color: #777;">No courier addresses specified</span>
                        @endif
                    </td>
                </tr>
            @elseif($hardwareHandover->installation_type === 'self_pick_up')
                <tr>
                    <td class="label">Pickup Address</td>
                    <td>{{ $category2['pickup_address'] ?? '-' }}</td>
                </tr>
            @elseif($hardwareHandover->installation_type === 'internal_installation')
                @php
                    $installerId = $category2['installer'] ?? null;
                    $installer = null;
                    if ($installerId) {
                        $installer = \App\Models\Installer::find($installerId);
                    }
                @endphp
                <tr>
                    <td class="label" width="30%">Installer</td>
                    <td width="70%">{{ $installer ? $installer->company_name : 'Unknown Installer' }}</td>
                </tr>
            @elseif($hardwareHandover->installation_type === 'external_installation')
                @php
                    $resellerId = $category2['reseller'] ?? null;
                    $reseller = null;
                    if ($resellerId) {
                        $reseller = \App\Models\Reseller::find($resellerId);
                    }
                @endphp
                <tr>
                    <td class="label" width="30%">Reseller</td>
                    <td width="70%">{{ $reseller ? $reseller->company_name : 'Unknown Reseller' }}</td>
                </tr>
                <tr>
                    <td class="label">Name</td>
                    <td>{{ $category2['pic_name'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">HP Number</td>
                    <td>{{ $category2['pic_phone'] ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="label">Email</td>
                    <td>{{ $category2['email'] ?? '-' }}</td>
                </tr>
            @else
                <tr>
                    <td class="label" width="30%">Installation Type</td>
                    <td width="70%">Unknown or not specified</td>
                </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">6. REMARK DETAILS</div>
        <table>
            <thead>
                <tr>
                    <th width="15%">No.</th>
                    <th width="60%">Description</th>
                    <th width="25%">Attachments</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($hardwareHandover->remarks) && !empty($hardwareHandover->remarks))
                    @php
                        $remarks = is_string($hardwareHandover->remarks)
                            ? json_decode($hardwareHandover->remarks, true)
                            : $hardwareHandover->remarks;

                        if (!is_array($remarks)) {
                            $remarks = [];
                        }
                    @endphp

                    @foreach($remarks as $index => $rmk)
                        <tr>
                            <td>Remark {{ $index + 1 }}</td>
                            <td>{{ is_array($rmk) ? ($rmk['remark'] ?? '') : $rmk }}</td>
                            <td>
                                @if(isset($rmk['attachments']) && !empty($rmk['attachments']))
                                    @php
                                        $attachments = is_string($rmk['attachments'])
                                            ? json_decode($rmk['attachments'], true)
                                            : $rmk['attachments'];

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
                        <td colspan="3" style="text-align: center; font-style: italic;">No remarks</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">7. VIDEO FILES</div>
        <table>
            <thead>
                <tr>
                    <th width="15%">No.</th>
                    <th width="85%">Video File</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($hardwareHandover->video_files) && !empty($hardwareHandover->video_files))
                    @php
                        $videoFiles = is_string($hardwareHandover->video_files)
                            ? json_decode($hardwareHandover->video_files, true)
                            : $hardwareHandover->video_files;

                        if (!is_array($videoFiles)) {
                            $videoFiles = [];
                        }
                    @endphp

                    @if(count($videoFiles) > 0)
                        @foreach($videoFiles as $index => $file)
                            <tr>
                                <td>Video {{ $index + 1 }}</td>
                                <td>
                                    @php
                                        $fileName = basename($file);
                                        $publicUrl = url('storage/' . $file);
                                    @endphp
                                    <a href="{{ $publicUrl }}" target="_blank" style="color: #0066cc; text-decoration: underline;">
                                        {{ $fileName }}
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    @else
                        <tr>
                            <td colspan="2" style="text-align: center; font-style: italic;">No video files uploaded</td>
                        </tr>
                    @endif
                @else
                    <tr>
                        <td colspan="2" style="text-align: center; font-style: italic;">No video files uploaded</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">8. PROFORMA INVOICE</div>
        <table>
            <thead>
                <tr>
                    <th width="30%">Type</th>
                    <th width="70%">Files</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="label">Proforma Invoice (Product)</td>
                    <td>
                        @if(isset($hardwareHandover->proforma_invoice_product) && !empty($hardwareHandover->proforma_invoice_product))
                            @php
                                $productInvoiceFiles = is_string($hardwareHandover->proforma_invoice_product)
                                    ? json_decode($hardwareHandover->proforma_invoice_product, true)
                                    : $hardwareHandover->proforma_invoice_product;

                                if (!is_array($productInvoiceFiles)) {
                                    $productInvoiceFiles = [];
                                }
                            @endphp

                            @foreach($productInvoiceFiles as $index => $file)
                                @php
                                    $fileName = basename($file);
                                    $publicUrl = url('proforma-invoice-v2/' . $file);
                                @endphp
                                <div style="margin-bottom: 4px;">
                                    <a href="{{ $publicUrl }}" target="_blank" style="color: #0066cc; text-decoration: underline;">
                                        Product Invoice {{ $index + 1 }}
                                    </a>
                                </div>
                            @endforeach

                            @if(empty($productInvoiceFiles))
                                <span style="font-style: italic; color: #777;">No files</span>
                            @endif
                        @else
                            <span style="font-style: italic; color: #777;">No files</span>
                        @endif
                    </td>
                </tr>

                <!-- Proforma Invoice Files (HRDF) -->
                <tr>
                    <td class="label">Proforma Invoice (HRDF)</td>
                    <td>
                        @if(isset($hardwareHandover->proforma_invoice_hrdf) && !empty($hardwareHandover->proforma_invoice_hrdf))
                            @php
                                $hrdfInvoiceFiles = is_string($hardwareHandover->proforma_invoice_hrdf)
                                    ? json_decode($hardwareHandover->proforma_invoice_hrdf, true)
                                    : $hardwareHandover->proforma_invoice_hrdf;

                                if (!is_array($hrdfInvoiceFiles)) {
                                    $hrdfInvoiceFiles = [];
                                }
                            @endphp

                            @foreach($hrdfInvoiceFiles as $index => $file)
                                @php
                                    $fileName = basename($file);
                                    $publicUrl = url('proforma-invoice-v2/' . $file);
                                @endphp
                                <div style="margin-bottom: 4px;">
                                    <a href="{{ $publicUrl }}" target="_blank" style="color: #0066cc; text-decoration: underline;">
                                        HRDF Invoice {{ $index + 1 }}
                                    </a>
                                </div>
                            @endforeach

                            @if(empty($hrdfInvoiceFiles))
                                <span style="font-style: italic; color: #777;">No files</span>
                            @endif
                        @else
                            <span style="font-style: italic; color: #777;">No files</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">9. ATTACHMENT</div>
        <table>
            <thead>
                <tr>
                    <th width="30%">Document Type</th>
                    <th width="70%">Files</th>
                </tr>
            </thead>
            <tbody>
                <!-- Confirmation Order Files -->
                <tr>
                    <td class="label">Confirmation Order</td>
                    <td>
                        @if(isset($hardwareHandover->confirmation_order_file) && !empty($hardwareHandover->confirmation_order_file))
                            @php
                                $confirmationFiles = is_string($hardwareHandover->confirmation_order_file)
                                    ? json_decode($hardwareHandover->confirmation_order_file, true)
                                    : $hardwareHandover->confirmation_order_file;

                                if (!is_array($confirmationFiles)) {
                                    $confirmationFiles = [];
                                }
                            @endphp

                            @foreach($confirmationFiles as $index => $file)
                                @php
                                    $fileName = basename($file);
                                    $publicUrl = url('storage/' . $file);
                                @endphp
                                <div style="margin-bottom: 4px;">
                                    <a href="{{ $publicUrl }}" target="_blank" style="color: #0066cc; text-decoration: underline;">
                                        Confirmation Order {{ $index + 1 }}
                                    </a>
                                </div>
                            @endforeach

                            @if(empty($confirmationFiles))
                                <span style="font-style: italic; color: #777;">No files</span>
                            @endif
                        @else
                            <span style="font-style: italic; color: #777;">No files</span>
                        @endif
                    </td>
                </tr>

                <!-- Payment Slip Files -->
                <tr>
                    <td class="label">Payment Slip</td>
                    <td>
                        @if(isset($hardwareHandover->payment_slip_file) && !empty($hardwareHandover->payment_slip_file))
                            @php
                                $paymentFiles = is_string($hardwareHandover->payment_slip_file)
                                    ? json_decode($hardwareHandover->payment_slip_file, true)
                                    : $hardwareHandover->payment_slip_file;

                                if (!is_array($paymentFiles)) {
                                    $paymentFiles = [];
                                }
                            @endphp

                            @foreach($paymentFiles as $index => $file)
                                @php
                                    $fileName = basename($file);
                                    $publicUrl = url('storage/' . $file);
                                @endphp
                                <div style="margin-bottom: 4px;">
                                    <a href="{{ $publicUrl }}" target="_blank" style="color: #0066cc; text-decoration: underline;">
                                        Payment Slip {{ $index + 1 }}
                                    </a>
                                </div>
                            @endforeach

                            @if(empty($paymentFiles))
                                <span style="font-style: italic; color: #777;">No files</span>
                            @endif
                        @else
                            <span style="font-style: italic; color: #777;">No files</span>
                        @endif
                    </td>
                </tr>

                <!-- HRDF Grant Files -->
                <tr>
                    <td class="label">HRDF Grant</td>
                    <td>
                        @if(isset($hardwareHandover->hrdf_grant_file) && !empty($hardwareHandover->hrdf_grant_file))
                            @php
                                $hrdfFiles = is_string($hardwareHandover->hrdf_grant_file)
                                    ? json_decode($hardwareHandover->hrdf_grant_file, true)
                                    : $hardwareHandover->hrdf_grant_file;

                                if (!is_array($hrdfFiles)) {
                                    $hrdfFiles = [];
                                }
                            @endphp

                            @foreach($hrdfFiles as $index => $file)
                                @php
                                    $fileName = basename($file);
                                    $publicUrl = url('storage/' . $file);
                                @endphp
                                <div style="margin-bottom: 4px;">
                                    <a href="{{ $publicUrl }}" target="_blank" style="color: #0066cc; text-decoration: underline;">
                                        HRDF Grant {{ $index + 1 }}
                                    </a>
                                </div>
                            @endforeach

                            @if(empty($hrdfFiles))
                                <span style="font-style: italic; color: #777;">No files</span>
                            @endif
                        @else
                            <span style="font-style: italic; color: #777;">No files</span>
                        @endif
                    </td>
                </tr>

                <!-- Sales Order Files -->
                <tr>
                    <td class="label">Invoice</td>
                    <td>
                        @if(isset($hardwareHandover->invoice_file) && !empty($hardwareHandover->invoice_file))
                            @php
                                $invoiceFiles = is_string($hardwareHandover->invoice_file)
                                    ? json_decode($hardwareHandover->invoice_file, true)
                                    : $hardwareHandover->invoice_file;

                                if (!is_array($invoiceFiles)) {
                                    $invoiceFiles = [];
                                }
                            @endphp

                            @foreach($invoiceFiles as $index => $file)
                                @php
                                    $fileName = basename($file);
                                    $publicUrl = url('storage/' . $file);
                                @endphp
                                <div style="margin-bottom: 4px;">
                                    <a href="{{ $publicUrl }}" target="_blank" style="color: #0066cc; text-decoration: underline;">
                                        Invoice {{ $index + 1 }}
                                    </a>
                                </div>
                            @endforeach

                            @if(empty($invoiceFiles))
                                <span style="font-style: italic; color: #777;">No files</span>
                            @endif
                        @else
                            <span style="font-style: italic; color: #777;">No files</span>
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">10. IMPLEMENTER DEPARTMENT - JOB DESCRIPTION</div>
        <table>
            <tr>
                <td style="font-size: 10px; line-height: 1.5;">
                    <ol style="margin: 0; padding-left: 15px;">
                        <li>Implementer will need to raise ticket for any customization details under Hardware Handover Form from the date received.</li>
                        <li>Implementer will need to raise ticket for any enhancement details under Hardware Handover Form from the date received.</li>
                        <li>Implementer will need to take note any special remark under Hardware Handover Form from the date received.</li>
                    </ol>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
