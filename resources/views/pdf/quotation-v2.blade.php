<!DOCTYPE html>
<html>
<head>
    <title>Quotation</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <style>

        body {
            font-size: 11px;
            font-family: 'Helvetica';
            /* margin-top: 3cm;
            margin-left: 2cm;
            margin-right: 2cm;
            margin-bottom: 2cm; */
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

        /* table, th, td {
            border: 1px solid black;
            border-collapse: collapse;
        } */
    </style>
</head>
<body>
<main>
    <table class="table" cellpadding='0' cellspacing='0' style="border-collapse:collapse;border: none;" style="width:100%;">
        <thead>
            <tr style="border:none;">
                <td colspan="7" style="border:none; border-collapse:collapse;">
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
                        <div class="row" style="border: 1px solid #005baa; background:#005baa; color:#fff; text-align:center; font-weight:bold; font-size:12px;">

                        </div>
                    </div>
                    <div class="container" style="clear:both;">&nbsp;
                        <div class="row">
                            <div class="col-4 pull-left">
                            @if ($quotation->lead && $quotation->lead->companyDetail)
                                <span style="font-weight: bold;">
                                    {{ Str::upper($quotation->lead->companyDetail->company_name) }}
                                </span><br />

                                @php
                                    $company = $quotation->lead->companyDetail;
                                    $address = "";

                                    if (strlen(trim($company->company_address1 ?? '')) > 0) {
                                        $address .= Str::upper(trim($company->company_address1)).'<br />';
                                    }

                                    if (strlen(trim($company->company_address2 ?? '')) > 0) {
                                        $address .= Str::upper(trim($company->company_address2)).'<br />';
                                    }

                                    if (strlen(trim($company->postcode ?? '')) > 0) {
                                        $address .= trim($company->postcode);
                                    }

                                    $address .= " " . Str::upper(trim($company->state ?? '')) . '<br />';

                                    if (($company->country ?? '') !== 'Malaysia') {
                                        $address .= trim($company->country);
                                    }
                                @endphp

                                {!! $address !!}<br />
                            @endif
                                <br>

                                <span><span style="font-weight:bold;" >Attention: </span>{{ $quotation->lead->companyDetail->name }}</span><br />
                                <span><span style="font-weight:bold;">Tel: </span>{{ $quotation->lead->companyDetail->contact_no }}</span><br />
                                <span><span style="font-weight:bold;" >Email: </span>{{ $quotation->lead->companyDetail->email }}</span><br />
                            </div>
                            <div class="col-4 pull-right">
                                <span><span class="fw-bold">Ref No: </span>{{ $quotation->quotation_reference_no }}</span><br />
                                <span><span class="fw-bold">Date: </span>{{ $quotation->quotation_date->format('j M Y')}}</span><br />
                                <span><span class="fw-bold">Prepared By: </span>{{ $quotation->sales_person->name }}</span><br />
                                <span><span class="fw-bold">Email: </span>{{ $quotation->sales_person->email }}</span><br />
                                <span><span class="fw-bold">H/P No: </span>{{ $quotation->sales_person->mobile_number }}</span>
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
            <tr style="border-top:1px solid #989898; background: #005baa; color: #fff;">
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color:#fff;">Item</th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff; width:40%;">Description</th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">Qty</th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">Unit Price<br /><small>({{ $quotation->currency }})</small></th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">Sub Total<br /><small>({{ $quotation->currency }})</small></th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">SST 8%<br /><small>({{ $quotation->currency }})</small></th>
                <th class="text-center" style="border:1px solid #eeeeee;vertical-align: middle; color: #fff;">Total Price<br /><small>({{ $quotation->currency }})</small></th>
            </tr>
        </thead>
        <tbody>
            @php
                $i = 0;
                $totalBeforeTax = 0;
                $totalAfterTax = 0;
                $totalTax = 0;
                $sortedItems = $quotation->items->sortBy('sort_order');
            @endphp
            @foreach($sortedItems as $item)
            @php
                $totalTax += $item->taxation;
                $totalBeforeTax += $item->total_before_tax;
                $totalAfterTax += $item->total_after_tax;

                $description = '';
                if ($item->product->solution == 'software') {
                    $description .= '(<u><strong>'. $item->quotation->currency . ' ' .$item->unit_price . ' * ' . $item->quantity . ' H/C * ' . $item->subscription_period . ' MONTHS</strong></u>)<br /><br />';
                }
                $description .= $item->description;
            @endphp

            <tr style="border:1px solid #989898; border-bottom:1px solid #989898;">
                <td class="text-center" style="border:1px solid #989898;width:20px;">{{ ++$i }}</td>
                <td style="border:1px solid #989898; line-height:1.2;">{!! $description !!}</td>
                <td class="text-center" style="border:1px solid #989898">{{ $item->quantity }}</td>
                <td class="text-right" style="border:1px solid #989898">{{ $item->unit_price }}</td>
                <td class="text-right" style="border:1px solid #989898">{{ $item->total_before_tax }}</td>
                <td class="text-right" style="border:1px solid #989898">{{ $item->taxation ?? '-' }}</td>
                <td class="text-right" style="border:1px solid #989898; border-bottom: 1px solid #989898;">{{ $item->total_after_tax }}</td>
            </tr>
            @endforeach
            <tr style="background-color:#ffffff; border-color:#fff;">
                <td style="border-right:1px solid #989898; border-left: 1px solid #fff; border-bottom:1px solid #fff;" colspan="4"></td>
                <td style="border:1px solid #989898;" colspan="2" class="text-right">Sub Total({{ $quotation->currency}})</td>
                <td style="border: 1px solid #989898" class="text-right">{{ number_format($totalBeforeTax,2) }}</td>
            </tr>
            <tr style="background-color:#ffffff; border-color:#fff;">
                <td style="border-right:1px solid #989898; border-left: 1px solid #fff; border-bottom:1px solid #fff;" colspan="4"></td>
                <td colspan="2" class="text-right" style="border:1px solid #989898;">SST 8%</td>
                <td class="text-right" style="border: 1px solid #989898">{{ number_format($totalTax,2) ?? '-' }}</td>
            </tr>
            <tr style="background-color:#ffffff;border-color:#fff;">
                <td style="border-right:1px solid #989898; border-left: 1px solid #fff; border-bottom:1px solid #fff;" colspan="4"></td>
                <td colspan="2" class="text-right" style="border:1px solid #989898;font-weight:bold;">Total({{ $quotation->currency}})</td>
                <td style="border: 1px solid #989898;font-weight:bold;" class="text-right">{{ number_format($totalAfterTax,2) }}</td>
            </tr>
        </tbody>
    </table>
    <div class="page-break"></div>
    @if($quotation->quotation_type == 'product')
        @include('pdf.product_tnc')
    @else
        @include('pdf.hrdf_tnc')
    @endif
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
