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
        <div class="section-title">1. COMPANY DETAILS</div>
        <table class="info-grid">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tr>
                <td class="label" width="30%">Company Name</td>
                <td width="70%">{{ $hardwareHandover->company_name }}</td>
            </tr>
            <tr>
                <td class="label">Industry</td>
                <td>{{ $hardwareHandover->industry }}</td>
            </tr>
            <tr>
                <td class="label">Number of Employees</td>
                <td>{{ $hardwareHandover->headcount }}</td>
            </tr>
            <tr>
                <td class="label">Country</td>
                <td>{{ $hardwareHandover->country }}</td>
            </tr>
            <tr>
                <td class="label">State</td>
                <td>{{ $hardwareHandover->state }}</td>
            </tr>
            <tr>
                <td class="label">Salesperson</td>
                <td>{{ $hardwareHandover->salesperson }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">2. SUPERADMIN DETAILS</div>
        <table class="info-grid">
            <thead>
                <tr>
                    <th>Field</th>
                    <th>Value</th>
                </tr>
            </thead>
            <tr>
                <td class="label" width="30%">PIC Name</td>
                <td width="70%">{{ $hardwareHandover->pic_name }}</td>
            </tr>
            <tr>
                <td class="label">PIC HP No.</td>
                <td>{{ $hardwareHandover->pic_phone }}</td>
            </tr>
            <tr>
                <td class="label">Email Address</td>
                <td>{{ $hardwareHandover->email }}</td>
            </tr>
            @if(isset($hardwareHandover->password) && !empty($hardwareHandover->password))
            <tr>
                <td class="label">Password</td>
                <td>{{ $hardwareHandover->password }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
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
                <td width="70%">{{ $hardwareHandover->company_name }}</td>
            </tr>
            <tr>
                <td class="label">Company Address</td>
                <td>
                    @php
                        $address = $hardwareHandover->company_address ?? $lead->companyDetail->address ?? 'Not specified';

                        if ($hardwareHandover->state || $hardwareHandover->country) {
                            $address .= ', ';

                            if ($hardwareHandover->state) {
                                $address .= strtoupper($hardwareHandover->state);
                            }

                            if ($hardwareHandover->state && $hardwareHandover->country) {
                                $address .= ', ';
                            }

                            if ($hardwareHandover->country) {
                                $address .= strtoupper($hardwareHandover->country);
                            }
                        }
                    @endphp
                    {{ $address }}
                </td>
            </tr>
            <tr>
                <td class="label">Salesperson</td>
                <td>{{ $hardwareHandover->salesperson }}</td>
            </tr>
            <tr>
                <td class="label">PIC Name</td>
                <td>{{ $hardwareHandover->pic_name }}</td>
            </tr>
            <tr>
                <td class="label">PIC Email</td>
                <td>{{ $hardwareHandover->email }}</td>
            </tr>
            <tr>
                <td class="label">PIC HP No.</td>
                <td>{{ $hardwareHandover->pic_phone }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">5. IMPLEMENTATION PICS</div>
        <table>
            <thead>
                <tr>
                    <th width="10%">User Role</th>
                    <th width="20%">Client PIC Name</th>
                    <th width="20%">Position</th>
                    <th width="20%">HP Number</th>
                    <th width="30%">Email Address</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>PIC 1</td>
                    <td>{{ $hardwareHandover->pic_name }}</td>
                    <td>{{ $hardwareHandover->pic_position ?? 'DIRECTOR' }}</td>
                    <td>{{ $hardwareHandover->pic_phone }}</td>
                    <td>{{ $hardwareHandover->email }}</td>
                </tr>

                @if(isset($hardwareHandover->implementation_pics) && !empty($hardwareHandover->implementation_pics))
                    @php
                        $implementationPics = is_string($hardwareHandover->implementation_pics)
                            ? json_decode($hardwareHandover->implementation_pics, true)
                            : $hardwareHandover->implementation_pics;

                        if (!is_array($implementationPics)) {
                            $implementationPics = [];
                        }
                    @endphp

                    @foreach($implementationPics as $index => $pic)
                        <tr>
                            <td>PIC {{ $index + 2 }}</td>
                            <td>{{ $pic['pic_name_impl'] ?? '' }}</td>
                            <td>{{ $pic['position'] ?? '' }}</td>
                            <td>{{ $pic['pic_phone_impl'] ?? '' }}</td>
                            <td>{{ $pic['pic_email_impl'] ?? '' }}</td>
                        </tr>
                    @endforeach
                @endif

                @if(!isset($hardwareHandover->implementation_pics) || empty($hardwareHandover->implementation_pics))
                    <tr>
                        <td colspan="5" style="text-align: center; font-style: italic;">No additional implementation PICs</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <div class="section">
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
                    if (isset($hardwareHandover->modules) && !empty($hardwareHandover->modules)) {
                        $modules = is_string($hardwareHandover->modules)
                            ? json_decode($hardwareHandover->modules, true)
                            : $hardwareHandover->modules;

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
                            'legacy_headcount' => $hardwareHandover->attendance_module_headcount ?? null,
                            'legacy_months' => $hardwareHandover->attendance_subscription_months ?? null,
                            'legacy_type' => $hardwareHandover->attendance_purchase_type ?? null
                        ],
                        'Leave' => [
                            'legacy_headcount' => $hardwareHandover->leave_module_headcount ?? null,
                            'legacy_months' => $hardwareHandover->leave_subscription_months ?? null,
                            'legacy_type' => $hardwareHandover->leave_purchase_type ?? null
                        ],
                        'Claim' => [
                            'legacy_headcount' => $hardwareHandover->claim_module_headcount ?? null,
                            'legacy_months' => $hardwareHandover->claim_subscription_months ?? null,
                            'legacy_type' => $hardwareHandover->claim_purchase_type ?? null
                        ],
                        'Payroll' => [
                            'legacy_headcount' => $hardwareHandover->payroll_module_headcount ?? null,
                            'legacy_months' => $hardwareHandover->payroll_subscription_months ?? null,
                            'legacy_type' => $hardwareHandover->payroll_purchase_type ?? null
                        ],
                        'Appraisal' => [
                            'legacy_headcount' => $hardwareHandover->appraisal_module_headcount ?? null,
                            'legacy_months' => $hardwareHandover->appraisal_subscription_months ?? null,
                            'legacy_type' => $hardwareHandover->appraisal_purchase_type ?? null
                        ],
                        'Recruitment' => [
                            'legacy_headcount' => $hardwareHandover->recruitment_module_headcount ?? null,
                            'legacy_months' => $hardwareHandover->recruitment_subscription_months ?? null,
                            'legacy_type' => $hardwareHandover->recruitment_purchase_type ?? null
                        ],
                        'Power BI' => [
                            'legacy_headcount' => $hardwareHandover->power_bi_headcount ?? null,
                            'legacy_months' => $hardwareHandover->power_bi_subscription_months ?? null,
                            'legacy_type' => $hardwareHandover->power_bi_purchase_type ?? null
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
    </div>

    <div class="section">
        <div class="section-title">6. OTHER DETAILS</div>
        <table class="info-grid">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Remark</th>
                </tr>
            </thead>
            <tr>
                <td class="label" width="30%">Customization Details</td>
                <td width="70%">{{ $hardwareHandover->customization_details ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Enhancement Details</td>
                <td>{{ $hardwareHandover->enhancement_details ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Special Remark</td>
                <td>{{ $hardwareHandover->special_remark ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Device Integration</td>
                <td>{{ $hardwareHandover->device_integration ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Existing HR System</td>
                <td>{{ $hardwareHandover->existing_hr_system ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">Experience Implementing Any HR System</td>
                <td>{{ $hardwareHandover->hr_system_experience ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">VIP Package</td>
                <td>{{ $hardwareHandover->vip_package ?: '-' }}</td>
            </tr>
            <tr>
                <td class="label">FingerTec Device</td>
                <td>{{ $hardwareHandover->fingertec_device ?: '-' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">7. CLIENT PURCHASE ANY ONSITE PACKAGE</div>
        <table>
            <thead>
                <tr>
                    <th width="40%">Item</th>
                    <th width="20%">Yes / No</th>
                    <th width="40%">Remark</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Onsite Kick Off Meeting</td>
                    <td>{{ $hardwareHandover->onsite_kickoff ? 'Yes' : 'No' }}</td>
                    <td>{{ $hardwareHandover->onsite_kickoff_remark ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Onsite Webinar Training</td>
                    <td>{{ $hardwareHandover->onsite_webinar ? 'Yes' : 'No' }}</td>
                    <td>{{ $hardwareHandover->onsite_webinar_remark ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Onsite Briefing Session</td>
                    <td>{{ $hardwareHandover->onsite_briefing ? 'Yes' : 'No' }}</td>
                    <td>{{ $hardwareHandover->onsite_briefing_remark ?: '-' }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">8. PAYMENT TERM</div>
        <table>
            <thead>
                <tr>
                    <th width="70%">Criteria</th>
                    <th width="30%">Choose One (Yes / No)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Payment – Full Paid</td>
                    <td>{{ $hardwareHandover->payment_term == 'full_payment' ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <td>Payment – Via HRDF</td>
                    <td>{{ $hardwareHandover->payment_term == 'payment_via_hrdf' ? 'Yes' : 'No' }}</td>
                </tr>
                <tr>
                    <td>Payment – Via Term</td>
                    <td>{{ $hardwareHandover->payment_term == 'payment_via_term' ? 'Yes' : 'No' }}</td>
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
                        <li>Implementer will need to raise ticket for any customization details under hardware Handover Form from the date received.</li>
                        <li>Implementer will need to raise ticket for any enhancement details under hardware Handover Form from the date received.</li>
                        <li>Implementer will need to take note any special remark under hardware Handover Form from the date received.</li>
                    </ol>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
