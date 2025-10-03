{{-- filepath: /var/www/html/timeteccrm/resources/views/emails/hrdf-handover-completed.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRDF Handover Completed</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
            text-align: center;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 8px 8px;
            border: 1px solid #e5e7eb;
        }
        .info-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border-left: 4px solid #10b981;
        }
        .status-completed {
            background: #d1fae5;
            color: #065f46;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="content">
        <p>Hi {{ $salesperson->name }},</p>

        <p>Your HRDF handover has been completed successfully.</p>

        <div class="info-box">
            <p><strong>Company Name:</strong> {{ $companyName }}</p>
            <p><strong>HRDF Handover ID:</strong> {{ $handoverId }}</p>
            <p><strong>Status:</strong> <span class="status-completed">COMPLETED</span></p>
        </div>
    </div>
</body>
</html>
