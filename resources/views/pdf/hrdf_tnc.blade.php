<div>
    <style>
        /* ul {
            list-style-type: none;
        } */
        ol {
            padding-left:14px;
        }
    </style>
    <div class="page-break-before"></div>
    <!-- -->
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
                <span style="font-weight: bold;">{{ Str::upper($quotation->lead->companyDetail->company_name) }}</span><br />
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
                <span><span class="fw-bold">H/P No: </span>{{ $quotation->sales_person->mobile_number }}</span>
            </div>
        </div>
    </div>

    <!-- -->
    <div style="border-top:1px solid #000;padding-top:10px;">
        <span style="font-size:12px; font-weight:bold;">Terms & Conditions</span>
    <div>
    <ol>
        <li style="font-weight:bold;">Introduction</li>
        <ol style="padding-left:1px;">
            <li style="list-style-type: none;">
                These Terms & Conditions ("Terms") outline the process and requirements for client (“Employer”) to claim financial assistance from HRD Corp for participating in training sessions offered by TimeTec ("Provider"). By enrolling in TimeTec's HRD Corp claimable training, Employers agree to these Terms.
            </li>
        </ol>
        <li style="font-weight:bold;" start="2">Eligibility</li>
        <ol type="a">
            <li>To be eligible for claiming financial assistance, Employers must be registered and in good standing with HRD Corp.</li>
            <li>TimeTec will clearly identify training sessions eligible for HRD Corp claims.</li>
        </ol>
        <li style="font-weight:bold;">Application and Claim Process</li>
        <ol type='a'>
            <li>Employers are responsible for submitting their HRD Corp claim application according to HRD Corp's guidelines and timelines.</li>
            <li>TimeTec will provide necessary documentation for the training session, including attendance records, training materials, and invoices.</li>
        </ol>
        <li style="font-weight:bold;">Payment and Financial Terms</li>
        <ol type='a'>
            <li>Employers are responsible for upfront payment of the full training fee to TimeTec Cloud Sdn. Bhd. Payment details are as follows:<br />
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
            <li>Employers must claim HRD Corp financial assistance directly. TimeTec will assist with preparing the claim but is not responsible for approval or rejection.</li>
        </ol>
        <li style="font-weight:bold;">Employer Responsibilities</li>
        <ol type='a'>
            <li>Employers are responsible for ensuring their employees attend training sessions as scheduled. </li>
            <li>Employers agree to comply with all HRD Corp requirements for claim eligibility, including participant engagement and post-training assessments.</li>
        </ol>
        <li style="font-weight:bold;">Data Protection</li>
        <ol type="a">
            <li>TimeTec is committed to maintaining the confidentiality of Employer and participant data. This data will be used solely for delivering and improving training sessions and complying with HRD Corp requirements. </li>
        </ol>
        <li style="font-weight:bold;">Privacy and Confidentiality</li>
        <ol type="a">
            <li>Details quoted in proposals and purchase orders are confidential and cannot be disclosed without prior written consent from TimeTec.</li>
        </ol>
        <li style="font-weight:bold;">Taxes</li>
        <ol type="a">
            <li>All quoted prices exclude applicable taxes. The Employer is responsible for any taxes required by law.</li>
        </ol>
        <li style="font-weight:bold;">Cancellation and Changes</li>
        <ol type="a">
            <li>Cancellations after signing the agreement will incur an 80% penalty on the contract cost.</li>
            <li>TimeTec will inform Employers promptly of any changes to HRD Corp's policy impacting the claim process. TimeTec is not liable for any loss or inability to claim financial assistance due to these changes.</li>
        </ol>
        <li style="font-weight:bold;">Limitation of Liability</li>
        <ol type="a">
            <li>TimeTec's liability is limited to providing training services and support in preparing claims. TimeTec is not liable for the outcome of any financial assistance claims.</li>
        </ol>
        <li style="font-weight:bold;">Dispute Resolution</li>
        <ol type="a">
            <li>Any disputes arising from these Terms will be resolved through negotiation between the Employer and TimeTec. If unresolved, disputes will be settled according to Malaysian law.</li>
        </ol>
        <li style="font-weight:bold;">Amendment and Termination</li>
        <ol type="a">
            <li>TimeTec may amend these Terms with prior notice to Employers. Continued participation in TimeTec's HRD Corp claimable training after amendments signifies acceptance of the new terms.</li>
        </ol>
    </ol>
    {{-- <div class="page-break-before"></div> --}}
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
                <span><span class="fw-bold">H/P No: </span>{{ $quotation->sales_person->mobile_number }}</span>
            </div>
        </div>
    </div>

    <!-- -->
    <div style="border-top:1px solid #000;padding-top:10px;"></div>
    <ol>
        <li style="font-weight:bold;">Governing Law</li>
        <ol type="a">
            <li>These Terms are governed by the laws of Malaysia, and any disputes will be subject to the jurisdiction of Malaysian courts.</li>
        </ol>
        <li style="font-weight:bold;">Contact Information</li>
        <ol type="a">
            <li>For inquiries or further information regarding these Terms & Conditions, please contact TimeTec at info@timeteccloud.com</li>
        </ol>
    </ol>
    <table style="width: 100%;">
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
            <td style="height: 300px">
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
