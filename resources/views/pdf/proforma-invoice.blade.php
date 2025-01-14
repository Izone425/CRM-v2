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
<header>
    <div class="row">
        <div class="col-lg-12" style="margin-top: 15px;">
            <div class="pull-left">
                <span style="font-weight:bold;font-size:13px;line-height:2.5">TIMETEC CLOUD SDN BHD <small class="fw-normal" style="font-size:9px;">(832542-W)</small></span>
                <p>
                NO 6, 8 & 10, Jalan BK3/2, Bandar Kinrara,<br />
                47180 Puchong, Selangor Darul Ehsan, Malaysia<br />
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
    <div class="container" style="clear:both; margin-bottom:5px;">&nbsp;
        <div class="row">
            <div class="col-4 pull-left">
                <span style="font-weight: bold;">{{ Str::upper($quotation->lead->companyDetail->name) }}</span><br />
                <span>
                    @php
                        $address = "";
                        if (strlen(trim($quotation->lead->companyDetail->company_address1)) > 0) {
                            $address .= Str::upper(trim($quotation->lead->companyDetail->company_address1)).'<br />';
                        }
                        if (strlen(trim($quotation->lead->companyDetail->company_address2)) > 0) {
                            $address .= Str::upper(trim($quotation->lead->companyDetail->company_address2)).'<br />';
                        }
                        if (strlen(trim($quotation->lead->companyDetail->postcode)) > 0) {
                            $address .= trim($quotation->lead->companyDetail->postcode);
                        }
                        $address .= " ".Str::upper(trim($quotation->lead->companyDetail->state)) . '<br />';
                        if ($quotation->lead->country <> 'Malaysia') {
                            $address .= trim($quotation->lead->country);
                        }

                    @endphp
                    {!! $address !!}
                </span>
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
                <span><span class="fw-bold">H/P No: </span>{{ $quotation->sales_person->mobile_number }}</span><br /><br />
                <span><span class="fw-bold">P.Invoice No: </span>{{ $quotation->pi_reference_no }}</span><br />
                <span><span class="fw-bold">Status </span>{!! $quotation->payment_status ? '<strong style="color: green">PAID</strong>' : '<strong style="color:red;">UNPAID</strong>' !!}</span>
            </div>
        </div>
    </div>
</header>
<main style="margin-top:305px;">
    <table class="table table-bordered" style="border-color: #005baa;">
        <thead>
            <tr style="background: #005baa; color: #fff; border-color:#005baa;">
                <th class="text-center" style="vertical-align: middle;">Item</th>
                <th class="text-center" style="vertical-align: middle; width:40%;">Description</th>
                <th class="text-center" style="vertical-align: middle;">Qty</th>
                <th class="text-center" style="vertical-align: middle;">Unit Price<br /><small>({{ $quotation->currency }})</small></th>
                <th class="text-center" style="vertical-align: middle;">Sub Total<br /><small>({{ $quotation->currency }})</small></th>
                <th class="text-center" style="vertical-align: middle;">SST 8%<br /><small>({{ $quotation->currency }})</small></th>
                <th class="text-center" style="vertical-align: middle;">Total Price<br /><small>({{ $quotation->currency }})</small></th>
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

            @if($quotation->quotation_type == 'hrdf' && $loop->iteration%2 == 0)
                        <tr style="border-color: #000;">
                            <td class="text-center" style="width:20px; border-color:#000;">{{ ++$i }}</td>
                            <td style="border-color:#000; line-height:1.1;">{!! $description !!}</td>
                            <td class="text-center" style="border:1px solid #000">{{ $item->quantity }}</td>
                            <td class="text-right" style="border:1px solid #000">{{ $item->unit_price }}</td>
                            <td class="text-right" style="border:1px solid #000">{{ $item->total_before_tax }}</td>
                            <td class="text-right" style="border:1px solid #000">{{ $item->taxation ?? '-' }}</td>
                            <td class="text-right" style="border:1px solid #000">{{ $item->total_after_tax }}</td>
                        </tr>
                    </tbody>
                </table>
                <div style="page-break-inside: auto;"></div>
                <div style="margin-top:305px;"></div>
                <table class="table table-bordered" style="border-color: #005baa;">
                    <thead style="border:none;">
                        <tr style="background: #005baa; color: #fff; border-color:#005baa;">
                            <th class="text-center" style="vertical-align: middle;">Item</th>
                            <th class="text-center" style="vertical-align: middle;width:40%;">Description</th>
                            <th class="text-center" style="vertical-align: middle;">Qty</th>
                            <th class="text-center" style="vertical-align: middle;">Unit Price<br /><small>({{ $quotation->currency }})</small></th>
                            <th class="text-center" style="vertical-align: middle;">Sub Total<br /><small>({{ $quotation->currency }})</small></th>
                            <th class="text-center" style="vertical-align: middle;">SST 8%<br /><small>({{ $quotation->currency }})</small></th>
                            <th class="text-center" style="vertical-align: middle;">Total Price<br /><small>({{ $quotation->currency }})</small></th>
                        </tr>
                    </thead>
                    <tbody>
            @elseif($quotation->quotation_type == 'product' && $loop->iteration%4 == 0)
                <tr style="border-color: #000;">
                    <td class="text-center" style="width:20px; border-color:#000;">{{ ++$i }}</td>
                    <td style="border-color:#000; line-height:1.1;">{!! $description !!}</td>
                    <td class="text-center" style="border:1px solid #000">{{ $item->quantity }}</td>
                    <td class="text-right" style="border:1px solid #000">{{ $item->unit_price }}</td>
                    <td class="text-right" style="border:1px solid #000">{{ $item->total_before_tax }}</td>
                    <td class="text-right" style="border:1px solid #000">{{ $item->taxation ?? '-' }}</td>
                    <td class="text-right" style="border:1px solid #000">{{ $item->total_after_tax }}</td>
                </tr>
                </tbody>
            </table>
            <div style="page-break-inside: auto;"></div>
            <div style="margin-top:305px;"></div>
            <table class="table table-bordered" style="border-color: #005baa;">
                <thead style="border:none;">
                    <tr style="background: #005baa; color: #fff; border-color:#005baa;">
                        <th class="text-center" style="vertical-align: middle;">Item</th>
                        <th class="text-center" style="vertical-align: middle;width:40%;">Description</th>
                        <th class="text-center" style="vertical-align: middle;">Qty</th>
                        <th class="text-center" style="vertical-align: middle;">Unit Price<br /><small>({{ $quotation->currency }})</small></th>
                        <th class="text-center" style="vertical-align: middle;">Sub Total<br /><small>({{ $quotation->currency }})</small></th>
                        <th class="text-center" style="vertical-align: middle;">SST 8%<br /><small>({{ $quotation->currency }})</small></th>
                        <th class="text-center" style="vertical-align: middle;">Total Price<br /><small>({{ $quotation->currency }})</small></th>
                    </tr>
                </thead>
                <tbody>
            @else
            <tr style="border-color: #000;">
                <td class="text-center" style="width:20px; border-color:#000;">{{ ++$i }}</td>
                <td style="border-color:#000; line-height:1.1;">{!! $description !!}</td>
                <td class="text-center" style="border-color: #000">{{ $item->quantity }}</td>
                <td class="text-right" style="border-color: #000">{{ $item->unit_price }}</td>
                <td class="text-right" style="border-color: #000">{{ $item->total_before_tax }}</td>
                <td class="text-right" style="border-color: #000">{{ $item->taxation ?? '-' }}</td>
                <td class="text-right" style="border-color: #000">{{ $item->total_after_tax }}</td>
            </tr>
            @endif
            @endforeach
            <tr style="background-color:#ffffff; border-color:#fff;">
                <td style="border-right:1px solid #000; border-left: 1px solid #fff; border-bottom:1px solid #fff;" colspan="4"></td>
                <td style="border:1px solid #000;" colspan="2" class="text-right">Sub Total({{ $quotation->currency}})</td>
                <td style="border: 1px solid #000" class="text-right">{{ number_format($totalBeforeTax,2) }}</td>
            </tr>
            <tr style="background-color:#ffffff; border-color:#fff;">
                <td style="border-right:1px solid #000; border-left: 1px solid #fff; border-bottom:1px solid #fff;" colspan="4"></td>
                <td colspan="2" class="text-right" style="border:1px solid #000;">SST 8%</td>
                <td class="text-right" style="border: 1px solid #000">{{ number_format($totalTax,2) ?? '-' }}</td>
            </tr>
            <tr style="background-color:#ffffff;border-color:#fff;">
                <td style="border-right:1px solid #000; border-left: 1px solid #fff; border-bottom:1px solid #fff;" colspan="4"></td>
                <td colspan="2" class="text-right" style="border:1px solid #000;font-weight:bold;">Total({{ $quotation->currency}})</td>
                <td style="border: 1px solid #000;font-weight:bold;" class="text-right">{{ number_format($totalAfterTax,2) }}</td>
            </tr>
        </tbody>
    </table>
    {{-- <div class="page-break"></div> --}}
    <div>
        Terms & Conditions:<br />
        1.	Please keep this invoice for your future reference and correspondence with TimeTec Cloud Sdn Bhd (832542-W)<br />
        2.	All purchases with TimeTec Cloud Sdn Bhd are bound by the Terms & Conditions.<br />
        3.	Questions about your invoice, email us at info@timeteccloud.com.<br />
        4.	Bank Account Details (for TT payment)<br />
        Banker: <strong>PUBLIC BANK BERHAD</strong><br />
        Beneficiary's Name: <strong>TimeTec Cloud Sdn Bhd (832542-W)</strong><br />
        Account No.: <strong>3176 5268 36 (MYR)</strong><br />
        <strong>3593 6726 19 (USD)</strong><br />
        Swift Code: <strong>PBBEMYKL</strong><br />
    </div>


    {{-- @if($quotation->quotation_type == 'product')
        @include('pdf.product_tnc')
    @else
        @include('pdf.hrdf_tnc')
    @endif --}}
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
