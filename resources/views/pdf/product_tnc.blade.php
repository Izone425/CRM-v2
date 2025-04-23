    <style>
        ul {
            list-style-type: none;
            padding-left:1px;
        }

        ol {
            padding-left:16px;
        }

        @counter-style paren-decimal {
            system: extends decimal;
            /* prefix: "("; */
            suffix: ") ";
        }

        @counter-style paren-lower-alpha {
            system: extends lower-alpha;
            /* prefix: "("; */
            suffix: ") ";
        }

        [type="1"] {
            list-style: paren-decimal;
        }

        [type="a"] {
            list-style: paren-lower-alpha;
        }
    </style>
    <div class="page-break-before"></div>
    <br />
    <!-- -->
    <div class="row" style="margin-top:10px;">
        <div class="col-lg-12">
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
                <span style="font-weight: bold;">{{ Str::upper($quotation->lead->companyDetail->company_name) }}</span><br />
                <span>
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
                {{-- <span><span class="fw-bold">Prepared By: </span>{{ $quotation->sales_person->name }}</span><br />
                <span><span class="fw-bold">Email: </span>{{ $quotation->sales_person->email }}</span><br />
                <span><span class="fw-bold">H/P No: </span>{{ $quotation->sales_person->mobile_no }}</span> --}}
            </div>
        </div>
    </div>

    <!-- -->
    <div style="border-top:1px solid #000;padding-top:10px;">
    <span style="font-size:12px; font-weight:bold;">Terms & Conditions</span>
    <div>
    <ol style="list-style-type: none;" start="1">
        <li style="font-weight:bold;">Payment</li>
        <ol type="1">
            <li>Initial Payment: 100% payment in advance.</li>
            <li>All payments or purchases made shall be non-refundable, non-transferable, and non-cancellable. </li>
            <li>All payments shall be transferred to the bank account of TimeTec Cloud Sdn Bhd, using the following bank details: <br />
                <table>
                    <tr>
                        <td>Account Name:</td>
                        <td>TimeTec Cloud Sdn Bhd</td>
                    </tr>
                    <tr>
                        <td>Account No:</td>
                        <td>3176526836</td>
                    </tr>
                    <tr>
                        <td>Bank:</td>
                        <td>Public Bank Berhad</td>
                    </tr>
                    <tr>
                        <td>Bank Swift Code:</td>
                        <td>PBBEMYKL</td>
                    </tr>
                </table>
            </li>
        </ol>
        <li style="font-weight:bold;">Software</li>
        <ol type="1" start="4">
            <li>Support hours shall be limited to business hours (Monday to Friday at 9am-6pm GMT+8), excluding Public Holidays.</li>
            <li>The subscription includes 12 months of access to phone, email, online technical support, software updates, and hosting services.</li>
            <li>Subscription will commence from the date of database creation, regardless of whether the Licensed Software is actively used by the customer.</li>
            <li>The subscription fee shall be billed and payable on an annual basis.</li>
            <li>Full subscription fee is required upon order confirmation.</li>
            <li>Subsequent subscription renewals shall be billed 2 (two) months in advance.</li>
            <li>Delivery lead time: The estimated lead time for account login activations is between 2-3 working days upon receiving the receipt of payment.</li>
            <li>Above system must be implemented within 3 months from date of confirmation order.</li>
        </ol>
        <li style="font-weight:bold;">Hardware</li>
        <ol type="1" start="12">
            <li>For the purchased hardware of TimeTec Device, a 24-month limited hardware warranty period is provided to the Customer.</li>
            <li>For the purchased hardware of TimeTec Accessories, a 12-month limited hardware warranty period is provided to the Customer.</li>
            <li>TimeTec Warranty Policy - https://www.fingertec.com/warrantypolicy/FT-warranty-policy.html.</li>
            <li>Delivery lead time: The estimated lead time for hardware delivery is between 7-14 working days upon receiving the receipt of payment.</li>
            <li>Any onsite installation, reallocation, troubleshooting, or repair services are chargeable and the fees will be determined based on the distance and location from TimeTec Cloud Sdn Bhd.</li>
            <li>TimeTec Hardware Policy - https://www.timeteccloud.com/hr-hardware-policy.</li>
        </ol>
        <li style="font-weight:bold;">Customization</li>
        <ol type="1" start="18">
            <li>Customization costs shall be charged at the rate of RM1,500/= per man-day.</li>
            <li>Minor customization refers to the basic enhancement of existing features including aesthetic changes, additional options, and minor behavioral changes, with minimal impact on the overall system stability.</li>
            <li>Major customization refers to the development of non-existing features, which require extensive planning, UI/UX design, with substantial impact on the overall system stability and affects other users' experience.</li>
        </ol>
        <li style="font-weight:bold;">Cloud Security </li>
        <ol type="1" start="21">
            <li>TimeTec Privacy Policy - https://www.timeteccloud.com/privacy-policy.</li>
            <li>TimeTec Cloud Security Policy - https://www.timeteccloud.com/security.</li>
            <li>TimeTec Data Security - https://www.timeteccloud.com/data_security.</li>
            <li>TimeTec GDPR Compliance - https://www.timeteccloud.com/gdpr.</li>
        </ol>
        <li style="font-weight:bold;list-style-type:none;">Tax</li>
        <ol type="1" start="25">
            <li>All the quoted prices are exclusive of tax. The Customer shall be responsible for any applicable tax that is required under the applicable law.</li>
        </ol>
    </ol>
    <div class="page-break-before" style="margin-bottom: 50px;"></div>
    <!-- -->
    <div class="row">
        <div class="col-lg-12" style="margin-top: 15px;">
            <div class="pull-left">
                <span style="font-weight:bold;font-size:13px;line-height:2.5">TIMETEC CLOUD SDN BHD <small class="fw-normal" style="font-size:9px;">(832542-W)</small></span>
                <p>
                Level 18, Tower 5 @ PFCC, Jalan Puteri 1/2, Bandar Puteri,<br />
                47100 Puchong, Selangor Darul Ehsan, Malaysia<br />
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
                <span style="font-weight: bold;">{{ Str::upper($quotation->lead->companyDetail->company_name) }}</span><br />
                <span>
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
                {{-- <span><span class="fw-bold">Prepared By: </span>{{ $quotation->sales_person->name }}</span><br />
                <span><span class="fw-bold">Email: </span>{{ $quotation->sales_person->email }}</span><br />
                <span><span class="fw-bold">H/P No: </span>{{ $quotation->sales_person->mobile_no }}</span> --}}
            </div>
        </div>
    </div>
    <div style="border-top:1px solid #000;padding-top:10px;">
    <!-- -->
    <ol>
        <li style="font-weight:bold; list-style-type:none;">Others</li>
        <ol type="1" start="26">
            <li>Validity: The offer set out in the written proposal including the price and terms, shall be valid for 30 days from the date the written proposal is issued. Unless the offer is accepted in writing by the Customer, the terms shall be valid for the duration of the Agreement.</li>
            <li>Privacy & Confidentiality: Details quoted proposal and purchase order are confidential and shall not be disclosed without prior written consent from TimeTec Cloud Sdn Bhd.</li>
            <li>Amendments: TimeTec Cloud Sdn Bhd's terms and conditions are subjected to amendments. Customers will be notified of any changes and their options to accept or reject the modified terms.</li>
            <li>Goods: All goods shall remain the property of TimeTec Cloud Sdn Bhd unless and until full payment is made. TimeTec Cloud Sdn Bhd reserve the rights to repossess the goods if customer failed to make balance payment after 14 days of installation.</li>
        </ol>
    </ol>
    </div>
    <table style="width: 100%;">
        <tr>
            <td colspan="2">
                By signing below, I have confirmed that I have read above terms and conditions from item number 1 to item number 29. It would automatically be deemed as an incorporated terms and conditions in this quotation proposal.
            </td>
        </tr>
        <tr>
            <td style="height: 150px;">
                <div class="pull-left" style="padding-top:30px;">
                    <div>Prepared By:</div>
                    <div style="position:absolute; padding-top:35px;"><img src="{{ $signature }}" style="width:70%;"></div>
                    <div style="border-top: 1px solid #000;margin-top:100px; width:200px;">{{ $quotation->sales_person->name }}</div>
                    <div>Business Development Executive</div>
                    <div><span style="font-weight:bold;">Email: </span>{{ $quotation->sales_person->email }}</div>
                    <div><span style="font-weight:bold;">H/P No: </span>{{ $quotation->sales_person->mobile_number }}</div>
                </div>
            </td>
            <td style="height: 150px">
                <div class="pull-right" style="padding-top: 30px;">
                    <div>
                        To accept this quotation, please sign here and return:
                    </div>
                    <div style="border-top: 1px solid #000;margin-top:100px; width:200px;">Name:</div>
                    <div>Position:</div>
                    <div>Company Stamp:</div>
                    <div>Date:</div>
                </div>
            </td>
        </tr>
    </table>
    </div>
    </div>

