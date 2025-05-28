<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Software Handover Form</title>
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
        <h1>SOFTWARE HANDOVER FORM</h1>
        <div class="company-name">{{ $softwareHandover->company_name ?? $lead->companyDetail->company_name ?? 'Unknown Company' }}</div>
    </div>

    <div class="section">
        <div class="section-title">1. DATABASE</div>
        <table class="info-grid">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tr>
                <td class="label" width="30%">Company Name</td>
                <td width="70%">{{ $softwareHandover->company_name }}</td>
            </tr>
            <tr>
                <td class="label">Salesperson</td>
                <td>{{ $softwareHandover->salesperson }}</td>
            </tr>
            <tr>
                <td class="label" width="30%">Name</td>
                <td width="70%">{{ $softwareHandover->pic_name }}</td>
            </tr>
            <tr>
                <td class="label">HP Number</td>
                <td>{{ $softwareHandover->pic_phone }}</td>
            </tr>
            <tr>
                <td class="label">Headcount</td>
                <td>{{ $softwareHandover->headcount }}</td>
            </tr>
            <tr>
                <td class="label">Company Size</td>
                <td>
                    @php
                        // Use the same logic from CategoryService to ensure consistency
                        $headcount = $softwareHandover->headcount;
                        $category = '';

                        if ($headcount) {
                            if ($headcount > 0 && $headcount < 25) {
                                $category = 'SMALL';
                            }
                            if ($headcount >= 25 && $headcount < 100) {
                                $category = 'MEDIUM';
                            }
                            if ($headcount >= 100 && $headcount < 500) {
                                $category = 'LARGE';
                            }
                            if ($headcount >= 500) {
                                $category = 'ENTERPRISE';
                            }
                        }
                    @endphp
                    {{ $category ?: ($softwareHandover->category ?? 'Not specified') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. INVOICE DETAILS</div>
    </div>

    {{-- <div class="section">
        <div class="section-title">3. INVOICE DETAILS</div>
        <table class="info-grid">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tr>
                <td class="label" width="30%">Company Name</td>
                <td width="70%">{{ $softwareHandover->company_name }}</td>
            </tr>
            <tr>
                <td class="label">Company Address</td>
                <td>
                    @php
                        $address = $softwareHandover->company_address ?? $lead->companyDetail->address ?? 'Not specified';

                        if ($softwareHandover->state || $softwareHandover->country) {
                            $address .= ', ';

                            if ($softwareHandover->state) {
                                $address .= strtoupper($softwareHandover->state);
                            }

                            if ($softwareHandover->state && $softwareHandover->country) {
                                $address .= ', ';
                            }

                            if ($softwareHandover->country) {
                                $address .= strtoupper($softwareHandover->country);
                            }
                        }
                    @endphp
                    {{ $address }}
                </td>
            </tr>
            <tr>
                <td class="label">Salesperson</td>
                <td>{{ $softwareHandover->salesperson }}</td>
            </tr>
            <tr>
                <td class="label">PIC Name</td>
                <td>{{ $softwareHandover->pic_name }}</td>
            </tr>
            <tr>
                <td class="label">PIC Email</td>
                <td>{{ $softwareHandover->email }}</td>
            </tr>
            <tr>
                <td class="label">PIC HP No.</td>
                <td>{{ $softwareHandover->pic_phone }}</td>
            </tr>
        </table>
    </div> --}}

    <div class="section">
        <div class="section-title">3. IMPLEMENTATION DETAILS</div>
        <table>
            <thead>
                <tr>
                    <th width="10%">No.</th>
                    <th width="20%">Name</th>
                    <th width="20%">Position</th>
                    <th width="20%">HP Number</th>
                    <th width="30%">Email Address</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($softwareHandover->implementation_pics) && !empty($softwareHandover->implementation_pics))
                    @php
                        $implementationPics = is_string($softwareHandover->implementation_pics)
                            ? json_decode($softwareHandover->implementation_pics, true)
                            : $softwareHandover->implementation_pics;

                        if (!is_array($implementationPics)) {
                            $implementationPics = [];
                        }
                    @endphp

                    @foreach($implementationPics as $index => $pic)
                        <tr>
                            <td>PIC {{ $index + 1 }}</td>
                            <td>{{ $pic['pic_name_impl'] ?? '' }}</td>
                            <td>{{ $pic['position'] ?? '' }}</td>
                            <td>{{ $pic['pic_phone_impl'] ?? '' }}</td>
                            <td>{{ $pic['pic_email_impl'] ?? '' }}</td>
                        </tr>
                    @endforeach
                @endif

                @if(!isset($softwareHandover->implementation_pics) || empty($softwareHandover->implementation_pics))
                    <tr>
                        <td colspan="5" style="text-align: center; font-style: italic;">No additional implementation PICs</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- <div class="section">
        <div class="section-title">5. TIMETEC HR MODULE SUBSCRIPTION</div>
        <table>
            <thead>
                <tr>
                    <th width="40%">Module</th>
                    <th width="20%">Headcount</th>
                    <th width="20%">Subscription Months</th>
                    <th width="20%">Purchase / Free</th>
                </tr>
            </thead>
            <tbody>
                @php
                    // Process modules data from JSON if it exists
                    $moduleData = [];
                    if (isset($softwareHandover->modules) && !empty($softwareHandover->modules)) {
                        $modules = is_string($softwareHandover->modules)
                            ? json_decode($softwareHandover->modules, true)
                            : $softwareHandover->modules;

                        if (is_array($modules)) {
                            foreach ($modules as $module) {
                                if (isset($module['module_name'])) {
                                    $moduleData[$module['module_name']] = [
                                        'headcount' => $module['headcount'] ?? '-',
                                        'subscription_months' => $module['subscription_months'] ?? '-',
                                        'purchase_type' => $module['purchase_type'] ?? '-'
                                    ];
                                }
                            }
                        }
                    }

                    // Create a list of all TimeTec modules we want to display
                    $allModules = [
                        'Attendance' => [
                            'legacy_headcount' => $softwareHandover->attendance_module_headcount ?? null,
                            'legacy_months' => $softwareHandover->attendance_subscription_months ?? null,
                            'legacy_type' => $softwareHandover->attendance_purchase_type ?? null
                        ],
                        'Leave' => [
                            'legacy_headcount' => $softwareHandover->leave_module_headcount ?? null,
                            'legacy_months' => $softwareHandover->leave_subscription_months ?? null,
                            'legacy_type' => $softwareHandover->leave_purchase_type ?? null
                        ],
                        'Claim' => [
                            'legacy_headcount' => $softwareHandover->claim_module_headcount ?? null,
                            'legacy_months' => $softwareHandover->claim_subscription_months ?? null,
                            'legacy_type' => $softwareHandover->claim_purchase_type ?? null
                        ],
                        'Payroll' => [
                            'legacy_headcount' => $softwareHandover->payroll_module_headcount ?? null,
                            'legacy_months' => $softwareHandover->payroll_subscription_months ?? null,
                            'legacy_type' => $softwareHandover->payroll_purchase_type ?? null
                        ],
                        'Appraisal' => [
                            'legacy_headcount' => $softwareHandover->appraisal_module_headcount ?? null,
                            'legacy_months' => $softwareHandover->appraisal_subscription_months ?? null,
                            'legacy_type' => $softwareHandover->appraisal_purchase_type ?? null
                        ],
                        'Recruitment' => [
                            'legacy_headcount' => $softwareHandover->recruitment_module_headcount ?? null,
                            'legacy_months' => $softwareHandover->recruitment_subscription_months ?? null,
                            'legacy_type' => $softwareHandover->recruitment_purchase_type ?? null
                        ],
                        'Power BI' => [
                            'legacy_headcount' => $softwareHandover->power_bi_headcount ?? null,
                            'legacy_months' => $softwareHandover->power_bi_subscription_months ?? null,
                            'legacy_type' => $softwareHandover->power_bi_purchase_type ?? null
                        ]
                    ];
                @endphp

                @foreach($allModules as $moduleName => $legacyData)
                    <tr>
                        <td>TimeTec {{ $moduleName }}</td>
                        @if(isset($moduleData[$moduleName]))
                            <td>{{ $moduleData[$moduleName]['headcount'] }}</td>
                            <td>{{ $moduleData[$moduleName]['subscription_months'] }}</td>
                            <td>{{ $moduleData[$moduleName]['purchase_type'] == '0' ? 'Free' : 'Purchase' }}</td>
                        @else
                            <td>{{ $legacyData['legacy_headcount'] ?: '' }}</td>
                            <td>{{ $legacyData['legacy_months'] ?: '' }}</td>
                            <td>{{ $legacyData['legacy_type'] ?: '' }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div> --}}

    <div class="section">
        <div class="section-title">4. REMARK DETAILS</div>
        <table>
            <thead>
                <tr>
                    <th width="15%">No.</th>
                    <th width="60%">Description</th>
                    <th width="25%">Attachments</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($softwareHandover->remarks) && !empty($softwareHandover->remarks))
                    @php
                        $remarks = is_string($softwareHandover->remarks)
                            ? json_decode($softwareHandover->remarks, true)
                            : $softwareHandover->remarks;

                        if (!is_array($remarks)) {
                            $remarks = [];
                        }
                    @endphp

                    @foreach($remarks as $index => $rmk)
                        <tr>
                            <td>Remark {{ $index + 1 }}</td>
                            <td>{{ $rmk['remark'] ?? $rmk ?? '' }}</td>
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
        <div class="section-title">5. TRAINING</div>
        <table>
            <thead>
                <tr>
                    <th width="40%">Item</th>
                    <th width="60%">Type</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Training Type</td>
                    <td>
                        @if($softwareHandover->training_type === 'online_webinar_training')
                            Online Webinar Training
                        @elseif($softwareHandover->training_type === 'online_hrdf_training')
                            Online HRDF Training
                        @else
                            {{ $softwareHandover->training_type ?? 'Not specified' }}
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">6. Speaker Category</div>
        <table>
            <thead>
                <tr>
                    <th width="40%">Item</th>
                    <th width="60%">Type</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Language Using</td>
                    <td>
                        @if($softwareHandover->speaker_category === 'english / malay')
                            English / Malay
                        @elseif($softwareHandover->speaker_category === 'mandarin')
                            Mandarin
                        @else
                            {{ $softwareHandover->speaker_category ?? 'Not specified' }}
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">7. PROFORMA INVOICE</div>
        <table>
            <thead>
                <tr>
                    <th width="30%">Type</th>
                    <th width="70%">Files</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Proforma Invoice (Product)</td>
                    <td>
                        @if(isset($softwareHandover->proforma_invoice_product) && !empty($softwareHandover->proforma_invoice_product))
                            @php
                                $productInvoiceFiles = is_string($softwareHandover->proforma_invoice_product)
                                    ? json_decode($softwareHandover->proforma_invoice_product, true)
                                    : $softwareHandover->proforma_invoice_product;

                                if (!is_array($productInvoiceFiles)) {
                                    $productInvoiceFiles = [];
                                }
                            @endphp

                            @foreach($productInvoiceFiles as $index => $file)
                                @php
                                    $fileName = basename($file);
                                    $publicUrl = url('proforma-invoice/' . $file);
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
                    <td>Proforma Invoice (HRDF)</td>
                    <td>
                        @if(isset($softwareHandover->proforma_invoice_hrdf) && !empty($softwareHandover->proforma_invoice_hrdf))
                            @php
                                $hrdfInvoiceFiles = is_string($softwareHandover->proforma_invoice_hrdf)
                                    ? json_decode($softwareHandover->proforma_invoice_hrdf, true)
                                    : $softwareHandover->proforma_invoice_hrdf;

                                if (!is_array($hrdfInvoiceFiles)) {
                                    $hrdfInvoiceFiles = [];
                                }
                            @endphp

                            @foreach($hrdfInvoiceFiles as $index => $file)
                                @php
                                    $fileName = basename($file);
                                    $publicUrl = url('proforma-invoice/' . $file);
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
        <div class="section-title">8. ATTACHMENT</div>
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
                    <td>Confirmation Order</td>
                    <td>
                        @if(isset($softwareHandover->confirmation_order_file) && !empty($softwareHandover->confirmation_order_file))
                            @php
                                $confirmationFiles = is_string($softwareHandover->confirmation_order_file)
                                    ? json_decode($softwareHandover->confirmation_order_file, true)
                                    : $softwareHandover->confirmation_order_file;

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
                    <td>Payment Slip</td>
                    <td>
                        @if(isset($softwareHandover->payment_slip_file) && !empty($softwareHandover->payment_slip_file))
                            @php
                                $paymentFiles = is_string($softwareHandover->payment_slip_file)
                                    ? json_decode($softwareHandover->payment_slip_file, true)
                                    : $softwareHandover->payment_slip_file;

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
                    <td>HRDF Grant</td>
                    <td>
                        @if(isset($softwareHandover->hrdf_grant_file) && !empty($softwareHandover->hrdf_grant_file))
                            @php
                                $hrdfFiles = is_string($softwareHandover->hrdf_grant_file)
                                    ? json_decode($softwareHandover->hrdf_grant_file, true)
                                    : $softwareHandover->hrdf_grant_file;

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
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">9. IMPLEMENTER DEPARTMENT - JOB DESCRIPTION</div>
        <table>
            <tr>
                <td style="font-size: 10px; line-height: 1.5;">
                    <ol style="margin: 0; padding-left: 15px;">
                        <li>Implementer will need to raise ticket for any customization details under Software Handover Form from the date received.</li>
                        <li>Implementer will need to raise ticket for any enhancement details under Software Handover Form from the date received.</li>
                        <li>Implementer will need to take note any special remark under Software Handover Form from the date received.</li>
                    </ol>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
