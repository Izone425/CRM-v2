<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseller Handover Completed</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background-color: #667eea;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .info-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 40%;
            color: #555;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            background: #10b981;
            color: white;
            border-radius: 4px;
            font-size: 14px;
            font-weight: bold;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>✅ Reseller Handover Completed</h1>
        </div>

        <div class="content">
            <p>Dear Team,</p>

            <p>A reseller handover has been completed with the following details:</p>

            <table class="info-table">
                <tr>
                    <td>FB ID:</td>
                    <td><strong>{{ $record->fb_id }}</strong></td>
                </tr>
                <tr>
                    <td>Reseller Name:</td>
                    <td>{{ $record->reseller_name }}</td>
                </tr>
                <tr>
                    <td>Subscriber Name:</td>
                    <td>{{ $record->subscriber_name }}</td>
                </tr>
                <tr>
                    <td>Official Receipt Number:</td>
                    <td><strong>{{ $officialReceiptNumber }}</strong></td>
                </tr>
                <tr>
                    <td>Status:</td>
                    <td><span class="badge">COMPLETED</span></td>
                </tr>
                <tr>
                    <td>Completed At:</td>
                    <td>{{ now()->format('d M Y, H:i') }}</td>
                </tr>
            </table>

            <p>Please proceed with the necessary follow-up actions.</p>

            <p>Best regards,<br>TimeTec CRM System</p>
        </div>

        <div class="footer">
            <p>This is an automated email from TimeTec CRM. Please do not reply to this email.</p>
            <p>&copy; {{ date('Y') }} TimeTec Cloud. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
