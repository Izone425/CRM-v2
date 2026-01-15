<!DOCTYPE html>
<html>
<head>
    <title>Sample Reseller Invoice</title>
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
                                Bandar Puteri, 47100 Puchong,<br />
                                Selangor, Malaysia<br />
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
                            <span>SAMPLE RESELLER INVOICE</span>
                        </div>
                    </div>
                    <div class="container" style="clear:both;">&nbsp;
                        <div class="row">
                            <div class="col-4 pull-left">
                                <span style="font-weight:bold;font-size:13px;line-height:2.5">TIMETEC CLOUD SDN BHD <small class="fw-normal" style="font-size:9px;">(832542-W)</small></span>
                                <p>
                                Level 18, Tower 5 @ PFCC, Jalan Puteri 1/2,<br />
                                Bandar Puteri, 47100 Puchong,<br />
                                Selangor, Malaysia<br />
                                </p>

                                <br>

                                <span style="font-weight:bold;">Attention: </span>MS FATIMAH<br />
                                <span style="font-weight:bold;">Tel: </span>03-80709933<br />
                                <span style="font-weight:bold;">Email: </span>fatimah.tarmizi@timeteccloud.com<br />
                            </div>
                            <div class="col-4 pull-right">
                                <span><span class="fw-bold">Ref No: </span>{{ $financeInvoice->fc_number }}</span><br />
                                <span><span class="fw-bold">Date: </span>{{ $financeInvoice->created_at->format('j M Y')}}</span><br />
                                <span><span class="fw-bold">Prepared By: </span>{{ $financeInvoice->creator->name ?? 'N/A' }}</span><br />
                                <span><span class="fw-bold">Email: </span>fatimah.tarmizi@timeteccloud.com</span><br />
                                <span><span class="fw-bold">H/P No: </span>03-80709933</span><br /><br />
                                <span><span class="fw-bold">P.Invoice No: </span>{{ $financeInvoice->fc_number }}</span><br />
                                <span><span class="fw-bold">Status </span><strong style="color:red;">UNPAID</strong></span>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr style="border-top:1px solid #989898; background: #005baa; color: #fff;">
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color:#fff;">Item</th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff; width:40%;">Description</th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">Qty</th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">Unit Price<br /><small>(MYR)</small></th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">Sub Total<br /><small>(MYR)</small></th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">SST 8%<br /><small>(MYR)</small></th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">Total Price<br /><small>(MYR)</small></th>
            </tr>
        </thead>
        <tbody>
            <tr style="border:1px solid #989898; border-bottom:1px solid #989898;">
                <td class="text-center" style="border:1px solid #989898;width:20px;">1</td>
                <td style="border:1px solid #989898; line-height:1.2;">
                    <p style="font-weight: bold; font-size: 13px; margin-bottom: 10px;">RESELLER COMMISSION</p>
                    <p><span style="font-weight: bold;">RESELLER NAME:</span> {{ strtoupper($financeInvoice->reseller_name) }}</p>
                    <p><span style="font-weight: bold;">SUBSCRIBER NAME:</span> {{ strtoupper($financeInvoice->subscriber_name) }}</p>
                    <p><span style="font-weight: bold;">INVOICE NUMBER:</span> {{ $financeInvoice->autocount_invoice_number }}</p>
                </td>
                <td class="text-center" style="border:1px solid #989898;">1</td>
                <td class="text-right" style="border:1px solid #989898;">{{ number_format($financeInvoice->reseller_commission_amount, 2) }}</td>
                <td class="text-right" style="border:1px solid #989898;">{{ number_format($financeInvoice->reseller_commission_amount, 2) }}</td>
                <td class="text-right" style="border:1px solid #989898;">-</td>
                <td class="text-right" style="border:1px solid #989898; border-bottom: 1px solid #989898;">{{ number_format($financeInvoice->reseller_commission_amount, 2) }}</td>
            </tr>
            <tr style="background-color:#ffffff; border-color:#fff;">
                <td style="border-right:1px solid #989898; border-left: 1px solid #fff; border-bottom:1px solid #fff;" colspan="4"></td>
                <td style="border:1px solid #989898;" colspan="2" class="text-right">Sub Total (MYR)</td>
                <td style="border: 1px solid #989898" class="text-right">{{ number_format($financeInvoice->reseller_commission_amount, 2) }}</td>
            </tr>
            <tr style="background-color:#ffffff; border-color:#fff;">
                <td style="border-right:1px solid #989898; border-left: 1px solid #fff; border-bottom:1px solid #fff;" colspan="4"></td>
                <td colspan="2" class="text-right" style="border:1px solid #989898;">SST 8%</td>
                <td class="text-right" style="border: 1px solid #989898">0.00</td>
            </tr>
            <tr style="background-color:#ffffff;border-color:#fff;">
                <td style="border-right:1px solid #989898; border-left: 1px solid #fff; border-bottom:1px solid #fff;" colspan="4"></td>
                <td colspan="2" class="text-right" style="border:1px solid #989898;font-weight:bold;">Total (MYR)</td>
                <td style="border: 1px solid #989898;font-weight:bold;" class="text-right">{{ number_format($financeInvoice->reseller_commission_amount, 2) }}</td>
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
        $word_space = 0.0;
        $char_space = 0.0;
        $angle = 0.0;
        $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
    }
</script>
</body>
</html>
