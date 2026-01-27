<!DOCTYPE html>
<html>
<head>
    <title>Quotation</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>

        body {
            font-size: 11px;
            font-family: 'Helvetica';
        }
        header {
            position: fixed;
            top: 0cm;
            left: 0cm;
            right: 0cm;
            height: 3cm;
        }
        .page-break {
            page-break-after: always;
        }
        .page-break-before {
            page-break-after: never;
        }
        tbody {
            page-break-before: always;
        }
        p {
            margin-top:0;
            margin-left:0;
            margin-right:0;
            margin-bottom: 5px;
        }

        .bordered {
            border: 1px solid #000;
        }
    </style>
</head>
<body>
<main>
    <table class="table" cellpadding='0' cellspacing='0' style="border-collapse:collapse;border: none;" style="width:100%;">
        <thead>
            <tr>
                <td colspan="7">
                    <div class="row">
                        <div class="col-lg-12" style="margin-top: 15px;">
                            <div class="pull-left">
                                <span style="font-weight:bold;font-size:13px;line-height:2.5">TIMETEC CLOUD SDN BHD <small class="fw-normal" style="font-size:9px;">(832542-W)</small></span>
                                <p>
                                Level 18, Tower 5 @ PFCC, Jalan Puteri 1/2,<br />
                                Bandar Puteri, 47100 Puchong, Selangor, Malaysia<br />
                                Tel: +6(03)8070 9933    Fax: +6(03)8070 9988<br />
                                Email: info@timeteccloud.com  Website: www.timeteccloud.com
                                </p>
                            </div>
                            <div class="pull-right">
                                <img src="{{ $path_img }}" width="200">
                            </div>
                        </div>
                    </div>
                    <div class="container" style="margin-top:5px;">
                        <div class="row" style="text-align:center; font-weight:bold; font-size:25px;">
                            <span>PROFORMA INVOICE</span>
                        </div>
                    </div>
                    <div class="container" style="clear:both;">&nbsp;
                        <div class="row">
                            <div class="col-4 pull-left">
                                @php
                                    $billTo = $piData['bill_to'] ?? [];
                                @endphp

                                <span style="font-weight: bold;">
                                    {{ strtoupper($billTo['company_name'] ?? $companyName) }}
                                </span><br />

                                @if(!empty($billTo['address']))
                                    {{ $billTo['address'] }}<br />
                                @elseif($companyDetail)
                                    @php
                                        $address = "";
                                        if (strlen(trim($companyDetail->company_address1 ?? '')) > 0) {
                                            $address .= strtoupper(trim($companyDetail->company_address1)).'<br />';
                                        }
                                        if (strlen(trim($companyDetail->company_address2 ?? '')) > 0) {
                                            $address .= strtoupper(trim($companyDetail->company_address2)).'<br />';
                                        }
                                        if (strlen(trim($companyDetail->postcode ?? '')) > 0) {
                                            $address .= trim($companyDetail->postcode);
                                        }
                                        $address .= " " . strtoupper(trim($companyDetail->state ?? '')) . '<br />';
                                        if (($companyDetail->country ?? '') !== 'Malaysia') {
                                            $address .= trim($companyDetail->country);
                                        }
                                    @endphp
                                    {!! $address !!}
                                @endif
                                MYS<br />
                                <br>

                                <span>
                                    <span style="font-weight:bold;">Attention: </span>
                                    {{ $billTo['contact_name'] ?? $companyDetail?->name ?? $softwareHandover->pic_name ?? '-' }}
                                </span><br />

                                <span>
                                    <span style="font-weight:bold;">Tel: </span>
                                    {{ $billTo['contact_phone'] ?? $companyDetail?->contact_no ?? $softwareHandover->pic_phone ?? '-' }}
                                </span><br />

                                <span>
                                    <span style="font-weight:bold;">Email: </span>
                                    {{ $billTo['email'] ?? $companyDetail?->email ?? '-' }}
                                </span><br />
                            </div>
                            <div class="col-4 pull-right">
                                <span><span class="fw-bold">Ref No: </span>{{ $invoiceNo }}</span><br />
                                <span><span class="fw-bold">Date: </span>{{ $piData['date'] ?? date('j M Y') }}</span><br />
                                <span><span class="fw-bold">Prepared By: </span>-</span><br />
                                <span><span class="fw-bold">Email: </span>-</span><br />
                                <span><span class="fw-bold">H/P No: </span>-</span><br /><br />
                                <span><span class="fw-bold">P.Invoice No: </span>{{ $invoiceNo }}</span><br />
                                <span><span class="fw-bold">Status </span>{!! ($piData['status'] ?? 'PAID') === 'PAID' ? '<strong style="color: green">PAID</strong>' : '<strong style="color:red;">UNPAID</strong>' !!}</span>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr style="border-top:1px solid #989898; background: #005baa; color: #fff;">
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color:#fff;">Item</th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff; width:40%;">Description</th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">Qty</th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">Unit Price<br /><small>({{ $piData['currency'] ?? 'MYR' }})</small></th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">Sub Total<br /><small>({{ $piData['currency'] ?? 'MYR' }})</small></th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">SST 8%<br /><small>({{ $piData['currency'] ?? 'MYR' }})</small></th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">Total Price<br /><small>({{ $piData['currency'] ?? 'MYR' }})</small></th>
            </tr>
        </thead>
        <tbody>
            @php
                $items = $piData['items'] ?? [];
                $currency = $piData['currency'] ?? 'MYR';
                $totalBeforeTax = 0;
                $totalAfterTax = 0;
                $totalTax = 0;
            @endphp
            @forelse($items as $index => $item)
            @php
                $qty = $item['qty'] ?? $item['quantity'] ?? 0;
                $unitPrice = $item['price'] ?? $item['unit_price'] ?? 0;
                $billingCycle = $item['billing_cycle'] ?? $item['month'] ?? 12;
                $subTotal = $qty * $unitPrice * $billingCycle;
                $itemSst = $subTotal * 0.08;
                $itemTotal = $subTotal + $itemSst;

                $totalBeforeTax += $subTotal;
                $totalTax += $itemSst;
                $totalAfterTax += $itemTotal;

                // Build description with calculation formula like proforma-invoice-v2
                $description = '(<u><strong>' . $currency . ' ' . number_format($unitPrice, 2) . ' * ' . $qty . ' H/C * ' . $billingCycle . ' MONTHS</strong></u>)<br /><br />';

                // Get license type name
                $licenseType = $item['description'] ?? $item['license_type'] ?? 'TIMETEC LICENSE';
                // Clean up the description - remove "(1 User License)" if present
                $licenseType = str_replace(' (1 User License)', '', $licenseType);
                $licenseType = strtoupper($licenseType);

                $description .= '<strong>' . $licenseType . '</strong>';

                // Add feature bullet points based on license type
                $features = [];
                if (stripos($licenseType, 'ATTENDANCE') !== false || stripos($licenseType, 'TA') !== false) {
                    $features = [
                        'USER FRIENDLY DASHBOARD',
                        'PERFORMANCE INDICATOR',
                        'VARIOUS CLOCKING METHODS',
                        'WORK SCHEDULES & ROSTER',
                        '40+ ADVANCED REPORTS'
                    ];
                } elseif (stripos($licenseType, 'LEAVE') !== false) {
                    $features = [
                        'MOBILE APPLICATION & APPROVAL',
                        'CUSTOMIZABLE LEAVE TYPE & POLICY',
                        'COMPANY LEAVE CALENDAR',
                        'LEAVE REMINDER NOTIFICATION',
                        'LEAVE REPORT'
                    ];
                } elseif (stripos($licenseType, 'CLAIM') !== false) {
                    $features = [
                        'MOBILE APPLICATION & APPROVAL',
                        'CUSTOMIZABLE CLAIM TYPE & POLICY',
                        'RECEIPT ATTACHMENT',
                        'CLAIM ANALYTICS',
                        'CLAIM REPORT'
                    ];
                } elseif (stripos($licenseType, 'PAYROLL') !== false) {
                    $features = [
                        'MOBILE APPLICATION PAYSLIP & EA FORM',
                        'PAYROLL ITEM',
                        'PAYROLL POLICY',
                        'PAYROLL REPORT'
                    ];
                } elseif (stripos($licenseType, 'PROFILE') !== false) {
                    $features = [
                        'EMPLOYEE SELF-SERVICE PORTAL',
                        'DOCUMENT MANAGEMENT',
                        'ORGANIZATION CHART',
                        'EMPLOYEE DIRECTORY'
                    ];
                } elseif (stripos($licenseType, 'HIRE') !== false) {
                    $features = [
                        'JOB POSTING & MANAGEMENT',
                        'CANDIDATE TRACKING',
                        'INTERVIEW SCHEDULING',
                        'ONBOARDING WORKFLOW'
                    ];
                }

                if (!empty($features)) {
                    $description .= '<ul style="list-style-type: disc; padding-left: 10px; margin-left: 0; text-align: left;">';
                    foreach ($features as $feature) {
                        $description .= '<li style="display: list-item; margin-bottom: 3px; text-align: left;">' . $feature . '</li>';
                    }
                    $description .= '</ul>';
                }
            @endphp
            <tr style="border:1px solid #989898; border-bottom:1px solid #989898;">
                <td class="text-center" style="border:1px solid #989898;width:20px;">{{ $index + 1 }}</td>
                <td style="border:1px solid #989898; line-height:1.2;">{!! $description !!}</td>
                <td class="text-center" style="border:1px solid #989898;">{{ $qty }}</td>
                <td class="text-right" style="border:1px solid #989898;">{{ number_format($unitPrice, 2) }}</td>
                <td class="text-right" style="border:1px solid #989898;">{{ number_format($subTotal, 2) }}</td>
                <td class="text-right" style="border:1px solid #989898;">{{ number_format($itemSst, 2) }}</td>
                <td class="text-right" style="border:1px solid #989898; border-bottom: 1px solid #989898;">{{ number_format($itemTotal, 2) }}</td>
            </tr>
            @empty
            <tr style="border:1px solid #989898; border-bottom:1px solid #989898;">
                <td colspan="7" class="text-center" style="border:1px solid #989898; padding: 20px;">No items found</td>
            </tr>
            @endforelse
            <tr style="background-color:#ffffff; border-color:#fff;">
                <td style="border-right:1px solid #989898; border-left: 1px solid #fff; border-bottom:1px solid #fff;" colspan="4"></td>
                <td style="border:1px solid #989898;" colspan="2" class="text-right">Sub Total({{ $currency }})</td>
                <td style="border: 1px solid #989898" class="text-right">{{ number_format($totalBeforeTax, 2) }}</td>
            </tr>
            <tr style="background-color:#ffffff; border-color:#fff;">
                <td style="border-right:1px solid #989898; border-left: 1px solid #fff; border-bottom:1px solid #fff;" colspan="4"></td>
                <td colspan="2" class="text-right" style="border:1px solid #989898;">SST 8%</td>
                <td class="text-right" style="border: 1px solid #989898">{{ number_format($totalTax, 2) }}</td>
            </tr>
            <tr style="background-color:#ffffff;border-color:#fff;">
                <td style="border-right:1px solid #989898; border-left: 1px solid #fff; border-bottom:1px solid #fff;" colspan="4"></td>
                <td colspan="2" class="text-right" style="border:1px solid #989898;font-weight:bold;">Total({{ $currency }})</td>
                <td style="border: 1px solid #989898;font-weight:bold;" class="text-right">{{ number_format($totalAfterTax, 2) }}</td>
            </tr>
            <tr>
                <td colspan="7">
                    <div style="padding-top:30px;">
                        Terms & Conditions:<br />
                        1.	Please keep this invoice for your future reference and correspondence with TimeTec Cloud Sdn Bhd (832542-W)<br />
                        2.	All purchases with TimeTec Cloud Sdn Bhd are bound by the Terms & Conditions.<br />
                        3.	Questions about your invoice, email us at info@timeteccloud.com.<br />
                        4.	Bank Account Details (for TT payment)<br />
                        Banker: <strong>United Overseas Bank (M) Bhd</strong><br />
                        Beneficiary's Name: <strong>TimeTec Cloud Sdn Bhd (832542-W)</strong><br />
                        Account No.: <strong>2253081440</strong><br />
                        Swift Code: <strong>UOVBMYKL</strong><br />
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</main>
 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
 <script type="text/php">
    if (isset($pdf)) {
        $font = null;
        $size = 9;
        $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
        $textWidth = $fontMetrics->getTextWidth($text, $font, $size);
        $x = $pdf->get_width() - 80;
        $y = $pdf->get_height() - 35;
        $color = array(0,0,0);
        $word_space = 0.0;  //  default
        $char_space = 0.0;  //  default
        $angle = 0.0;   //  default
        $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
    }
</script>
</body>
</html>
