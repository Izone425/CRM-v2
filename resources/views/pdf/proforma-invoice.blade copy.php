<!DOCTYPE html>
<html>
    <head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
        <style>
            /* @import url('https://fonts.googleapis.com/css2?family=Barlow+Semi+Condensed:wght@200;300;400;500;600;700;800;900&family=Barlow:wght@100;400&display=swap'); */
            body {
                font-size: 10px;
                font-family: 'Helvetica'
            }
            .page-break {
                page-break-after: always;
            }
            p {
                margin-top:0;
                margin-left:0;
                margin-right:0;
                margin-bottom: 5px;
            }
            tr:nth-child(odd) {background-color: #f2f2f2;}
        </style>
    </head>
    <body>
        <!-- content starts here -->
        <table class="table table-striped" style="width:100%;">
            <thead>
                <tr style="background-color: #ffffff; margin-bottom:10px;">
                    <td colspan="4">
                        <span class="fw-bold" style="font-size:13px;line-height:2.5">Timetec Cloud Sdn Bhd <small class="fw-normal" style="font-size:9px;">(9228701)</small></span>
                        <p>
                        NO 6, 8 & 10, Jalan BK3/2, Bandar Kinrara,<br />
                        47180 Puchong, Selangor Darul Ehsan, Malaysia<br />
                        Tel: +6(03)8070 9933    Fax: +6(03)8070 9988<br />
                        Email: info@fingertec.com  Website: www.fingertec.com
                        </p>
                    </td>
                    <td colspan="3" class="text-end">
                        <img src="{{ asset('/img/logo-ttc.png') }}" width="200">
                    </td>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="7" style="background-color:#ffffff;">&nbsp;</td>
                </tr>
                <tr style="background-color: #ffffff;">
                    <th colspan="7" class="text-center" style="font-size:16px; padding-top:10px;padding-bottom:10px; background-color:#e0e0e0;">Proforma Invoice</th>
                </tr>
                <tr>
                    <td colspan="7" style="background-color:#ffffff;">&nbsp;</td>
                </tr>
                <tr style="background-color:#ffffff;">
                    <td colspan="4" valign="top">
                        <div class="text-start" style="line-height:1.5;">
                            <span><span class="fw-bold">Proforma Invoice For: </span></span><br />
                            <span><span class="fw-bold">{{ Str::upper($quotation->company->name) }}</span></span><br />
                            <span><span>
                                @php
                                    $address = "";
                                    if (strlen(trim($quotation->company->address1)) > 0) {
                                        $address .= trim($quotation->company->address1).'<br />';
                                    }
                                    if (strlen(trim($quotation->company->address2)) > 0) {
                                        $address .= trim($quotation->company->address2).'<br />';
                                    }
                                    if (strlen(trim($quotation->company->postcode)) > 0) {
                                        $address .= trim($quotation->company->postcode);
                                    }
                                    $address .= " ".trim($quotation->company->state) . '<br />';
                                    $address .= trim($quotation->company->country);

                                @endphp
                                {!! $address !!}<br />
                            </span></span></br>
                            <span class="me-3"><span class="fw-bold">Tel: </span>{{ $quotation->company->telephone }}</span>
                            <span><span class="fw-bold">Fax: </span>{{ $quotation->company->fax }}</span><br />
                            <span><span class="fw-bold">Attn: </span>{{ $quotation->company->pic_name }}</span>
                        </div>
                    </td>
                    <td colspan="3" valign="top">
                        <div class="text-start" style="line-height:1.5;">
                            <span><span class="fw-bold">Ref No: </span>{{ $quotation->quotation_reference_no }}</span><br />
                            <span><span class="fw-bold">Date: </span>{{ $quotation->quotation_date->format('j M Y')}}</span><br />
                            <span><span class="fw-bold">Prepared By: </span>{{ auth()->user()->name }}</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="7" style="background-color:#ffffff;">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="7" style="background-color:#ffffff;">
                        <span>We thank you for your interest in our products and we are pleased to quote you as follows:</span>
                    </td>
                </tr>
                <tr style="background-color:#e0e0e0;" class="pb-2">
                    <th class="pt-1 pb-1 ps-2">Item</th>
                    <th class="pt-1 pb-1 ps-2" style="width:350px;">Description</th>
                    <th class="pt-1 pb-1 ps-2 text-end">Qty</th>
                    <th class="pt-1 pb-1 ps-2 text-end">Unit Price<br /><small>({{ $quotation->currency }})</small></th>
                    <th class="pt-1 pb-2 ps-2 text-end">Sub Total<br /><small>({{ $quotation->currency }})</small><</th>
                    <th class="pt-1 pb-1 ps-2 text-end">8% SST<br /><small>({{ $quotation->currency }})</small></th>
                    <th class="pt-1 pb-1 ps-2 pe-1 text-end">Total Price<br /><small>({{ $quotation->currency }})</small></th>
                </tr>
                <tr>
                    <td colspan="7" style="background-color:#ffffff;">&nbsp;</td>
                </tr>
                @php
                    $i = 0;
                    $totalBeforeTax = 0;
                    $totalAfterTax = 0;
                    $totalTax = 0;
                @endphp
                @foreach($quotation->items as $item)
                @php
                    $totalTax += $item->taxation;
                    $totalBeforeTax += $item->total_before_tax;
                    $totalAfterTax += $item->total_after_tax;

                    $description = '';
                    if ($item->product->solution == 'software') {
                        $description .= '(<u><strong>'. $item->quotation->currency . ' ' .$item->unit_price . ' * ' . $item->quotation->headcount . ' H/C * ' . $item->subscription_period . ' MONTHS</strong></u>)<br /><br />';
                    }
                    $description .= $item->description;
                @endphp
                <tr style="background-color:#ffffff;border-bottom:1px solid #e0e0e0;"valign="middle">
                    <td class="text-center ps-1 pe-1" style="width:20px;">{{ ++$i }}</td>
                    <td>{!! $description !!}</td>
                    <td class="text-end pe-1">{{ $item->quantity }}</td>
                    <td class="text-end pe-1">{{ $item->unit_price }}</td>
                    <td class="text-end pe-1">{{ $item->total_before_tax }}</td>
                    <td class="text-end pe-1">{{ $item->taxation ?? '-' }}</td>
                    <td class="text-end pe-1">{{ $item->total_after_tax }}</td>
                </tr>
                @endforeach
                <tr style="background-color:#ffffff;">
                    <td colspan="4"></td>
                    <td colspan="2" class="text-end">Sub Total(Excluding Tax)</td>
                    <td class="text-end">{{ number_format($totalBeforeTax,2) }}</td>
                </tr>
                <tr style="background-color:#ffffff;">
                    <td colspan="4"></td>
                    <td colspan="2" class="text-end" style="border-bottom: 1px solid #e0e0e0;">8% SST</td>
                    <td class="text-end" style="border-bottom: 1px solid #e0e0e0;">{{ number_format($totalTax,2) ?? '-' }}</td>
                </tr>
                <tr style="background-color:#ffffff; border-bottom: 1px solid #e0e0e0;">
                    <td colspan="4"></td>
                    <td colspan="2" class="text-end" style="border-bottom: 1px solid #e0e0e0;">Total(Including Tax)</td>
                    <td class="text-end fw-bold">{{ number_format($totalAfterTax,2) }}</td>
                </tr>
                <tr>
                    <td colspan="7" style="background-color:#ffffff;">&nbsp;</td>
                </tr>
                <tr style="line-height:1.5;background-color:#e0e0e0;">
                    <td colspan="7" class="pt-1 pb-1 fw-bold ps-2">
                    TERMS AND CONDITIONS
                    </td>
                </tr>
                <tr style="background-color:#ffffff;">
                    <td colspan="7" style="font-size:8px;">
                    <div style="border: 0.5px solid #e0e0e0; padding-top:5px;">
                        <ol>
                            <li>
                            Bank Account Details: - <br/>
                            Bank Name : <strong>Public Bank Berhad</strong> <br/>
                            Account Number : <strong>3188 7855 17</strong><br/>
                            Name : <strong>TimeTec Computing Sdn Bhd</strong>
                            </li>
                        </ol>
                    </div>
                    </td>
                </tr>
                <tr style="line-height:1.5;background-color:#ffffff;">
                    <td colspan="7">
                        <span>
                            Note: Prices are subjected to change without prior notice. We hope that our quotation is favourable to you and looking forward to receive your valued orders in due course. Thank you and regards,
                        </span>
                    </td>
                </tr>
                <tr>
                    <td colspan="7" style="background-color:#ffffff;">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="7" style="background-color:#ffffff;">&nbsp;</td>
                </tr>
                <tr>
                    <td colspan="7" style="background-color:#ffffff;">&nbsp;</td>
                </tr>
            </tbody>
        </table>
        <!-- content ends here -->

        <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.3/dist/jquery.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.min.js"></script>
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
        </script
    </body>
</html>
