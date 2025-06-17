<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Project Assignment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 650px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #10b981;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .row {
            margin-bottom: 15px;
        }
        .label {
            font-weight: bold;
            min-width: 180px;
            display: inline-block;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            width: 30%;
        }
        .button-container {
            text-align: center;
            margin-top: 25px;
            margin-bottom: 10px;
        }
        .button {
            background-color: #10b981;
            color: white !important;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        .button:hover {
            background-color: #219653;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Hardware Handover: Completed</h2>
        </div>

        <div class="content">
            <p class="greeting">Dear {{ $emailContent['salesperson']['name'] }} and {{ $emailContent['implementer']['name'] }},</p>

            <p>This is to inform you that you have received a new hardware information.</p>

            <table>
                <tr>
                    <th>Company Name</th>
                    <td>{{ $emailContent['company']['name'] }}</td>
                </tr>
                <tr>
                    <th>Implementer Name</th>
                    <td>{{ $emailContent['implementer']['name'] }}</td>
                </tr>
                <tr>
                    <th>Salesperson Name</th>
                    <td>{{ $emailContent['salesperson']['name'] }}</td>
                </tr>
                <tr>
                    <th>Device Inventory</th>
                    <td>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                            <thead>
                                <tr>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2;">Product</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: center; background-color: #f2f2f2;">Quantity</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: center; background-color: #f2f2f2;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($emailContent['devices']))
                                    @foreach($emailContent['devices'] as $device => $details)
                                        @if($details['quantity'] !== 0)
                                            <tr>
                                                <td style="border: 1px solid #ddd; padding: 8px;">{{ strtoupper(str_replace('_', ' ', $device)) }}</td>
                                                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">{{ $details['quantity'] }}</td>
                                                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                                                    <span style="color: green;">Completed</span>
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                    @else
                                    <tr>
                                        <td colspan="3" style="border: 1px solid #ddd; padding: 8px; text-align: center;">No devices pending stock</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </td>
                </tr>
                <tr>
                    <th>Invoice Files</th>
                    <td>
                        @if(!empty($emailContent['invoiceFiles']) && count($emailContent['invoiceFiles']) > 0)
                            @foreach($emailContent['invoiceFiles'] as $index => $fileUrl)
                                <a href="{{ $fileUrl }}" target="_blank" class="button" style="background-color: #2ecc71; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; display: inline-block; margin-right: 5px; margin-bottom: 5px; width: 75px; text-align: center">
                                    Invoice {{ $index + 1 }}
                                </a>
                            @endforeach
                        @else
                            No invoice files available
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Sales Order Files</th>
                    <td>
                        @if(!empty($emailContent['salesOrderFiles']) && count($emailContent['salesOrderFiles']) > 0)
                            @foreach($emailContent['salesOrderFiles'] as $index => $fileUrl)
                                <a href="{{ $fileUrl }}" target="_blank" class="button" style="background-color: #2ecc71; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; display: inline-block; margin-right: 5px; margin-bottom: 5px;">
                                    Sales Order {{ $index + 1 }}
                                </a>
                            @endforeach
                        @else
                            No invoice files available
                        @endif
                    </td>
                </tr>
                @if(!empty($emailContent['admin_remarks']))
                <tr>
                    <th>Admin Remarks</th>
                    <td>
                        <table style="width: 100%; border-collapse: collapse; margin: 0;">
                            <tbody>
                                @foreach($emailContent['admin_remarks'] as $index => $remark)
                                    <tr>
                                        <td style="border: 1px solid #e0e0e0; padding: 12px; background-color: {{ $index % 2 == 0 ? '#f9f9f9' : '#ffffff' }};">
                                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                                                <div style="font-weight: bold; color: #10b981;">Remark {{ $index + 1 }}</div>
                                            </div>

                                            <div style="margin-bottom: 10px; padding-left: 10px; border-left: 3px solid #10b981;">
                                                {{ $remark['text'] }}
                                            </div>

                                            @if(!empty($remark['attachments']))
                                                <div style="margin-top: 10px;">
                                                    <div style="font-weight: bold; margin-bottom: 5px;">Attachments:</div>
                                                    <div>
                                                        @foreach($remark['attachments'] as $attachment)
                                                            <a href="{{ $attachment['url'] }}" target="_blank" style="background-color: #10b981; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; display: inline-block; margin-right: 5px; margin-bottom: 5px;">
                                                                {{ pathinfo($attachment['filename'], PATHINFO_FILENAME) }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </td>
                </tr>
                @endif
            </table>

            @if($emailContent['handoverFormUrl'])
                <div class="button-container">
                    <a href="{{ $emailContent['handoverFormUrl'] }}" class="button" target="_blank">
                        Click here to view hardware handover form
                    </a>
                </div>
            @else
                <p style="color: #777; text-align: center; margin-top: 20px;">
                    The software handover form is not available at this moment.
                </p>
            @endif

            <p>If you need any additional information, please contact your manager.</p>

            <p>Thank you,<br>
            TimeTec CRM</p>
        </div>

        <div class="footer">
            <p>This is an automated notification from the TimeTec CRM system. Please do not reply to this email.</p>
            <p>© {{ date('Y') }} TimeTec Cloud Sdn Bhd. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
