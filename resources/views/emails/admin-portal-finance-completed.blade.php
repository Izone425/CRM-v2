<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal Finance Invoice Completed</title>
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
        .content {
            padding: 30px;
        }
        .info-section {
            margin: 20px 0;
            line-height: 1.8;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            
            <p>Dear Auni,</p>
            
            <p>Admin Portal handover has been completed with the following details.</p>
            
            <div class="info-section">
                <p><strong>Handover ID:</strong> {{ $financeInvoice->formatted_id }}</p>
                <p><strong>Reseller Name:</strong> {{ $resellerName }}</p>
                <p><strong>Subscriber Name:</strong> {{ $subscriberName }}</p>
                <p><strong>AutoCount Invoice Number:</strong> {{ $autocountInvoice }}</p>
                <p><strong>Completed At:</strong> {{ now()->format('d M Y, H:i') }}</p>
            </div>
            
            <div class="footer">
                <p>Regards<br>TimeTec CRM System</p>
            </div>
        </div>
    </div>
</body>
</html>
