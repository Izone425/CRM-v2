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
            max-width: 750px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #8b5cf6;
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
            width: 25%;
        }
        .button-container {
            text-align: center;
            margin-top: 25px;
            margin-bottom: 10px;
        }
        .button {
            background-color: #8b5cf6;
            color: white !important;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        .button:hover {
            background-color: #6630e4;
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
            <h2>Hardware Handover: Pending Migration</h2>
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
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: left; background-color: #f2f2f2; width: 30%;">Product</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: center; background-color: #f2f2f2; width: 5%;">Quantity</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: center; background-color: #f2f2f2; width: 35%;">Status</th>
                                    <th style="border: 1px solid #ddd; padding: 8px; text-align: center; background-color: #f2f2f2; width: 30%;">Serial Numbers</th>
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
                                                    <span style="color: #27ae60;">Pending Migration</span>
                                                </td>
                                                <td style="border: 1px solid #ddd; padding: 8px;">
                                                    @if(isset($emailContent['device_serials']["{$device}_serials"]))
                                                        <ul style="margin: 0; padding-left: 20px;">
                                                            @foreach($emailContent['device_serials']["{$device}_serials"] as $serialData)
                                                                @if(!empty($serialData['serial']))
                                                                    <li>{{ $serialData['serial'] }}</li>
                                                                @endif
                                                            @endforeach
                                                        </ul>
                                                    @else
                                                        <span style="color: #777;">No serial numbers recorded</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endif
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" style="border: 1px solid #ddd; padding: 8px; text-align: center;">No devices pending migration</td>
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
                                <a href="{{ $fileUrl }}" target="_blank" class="button" style="color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; display: inline-block; margin-right: 5px; margin-bottom: 5px; width: 75px; text-align: center">
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
                                <a href="{{ $fileUrl }}" target="_blank" class="button" style="color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; display: inline-block; margin-right: 5px; margin-bottom: 5px;">
                                    Sales Order {{ $index + 1 }}
                                </a>
                            @endforeach
                        @else
                            No invoice files available
                        @endif
                    </td>
                </tr>
            </table>

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
