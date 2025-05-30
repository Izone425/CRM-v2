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
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #338cf0;
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
            width: 40%;
        }
        .button-container {
            text-align: center;
            margin-top: 25px;
            margin-bottom: 10px;
        }
        .button {
            background-color: #338cf0;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        .button:hover {
            background-color: #2a70c0;
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
            <h2>New Project Assignment</h2>
        </div>

        <div class="content">
            <p class="greeting">Dear {{ $emailContent['implementer']['name'] }},</p>

            <p>This is to inform you that you have received a new project assignment.</p>

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
                    <th>Database Creation Date</th>
                    <td>{{ $emailContent['createdAt'] }}</td>
                </tr>
                <tr>
                    <th>Invoice Files</th>
                    <td>
                        @if(!empty($emailContent['invoiceFiles']) && count($emailContent['invoiceFiles']) > 0)
                            @foreach($emailContent['invoiceFiles'] as $index => $fileUrl)
                                <a href="{{ $fileUrl }}" target="_blank" class="button" style="background-color: #338cf0; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px; font-size: 12px; display: inline-block; margin-right: 5px; margin-bottom: 5px;">
                                    Invoice {{ $index + 1 }}
                                </a>
                            @endforeach
                        @else
                            No invoice files available
                        @endif
                    </td>
                </tr>
            </table>

            @if($emailContent['handoverFormUrl'])
                <div class="button-container">
                    <a href="{{ $emailContent['handoverFormUrl'] }}" class="button" target="_blank">
                        Click here to view software handover form
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
