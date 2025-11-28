{{-- filepath: /var/www/html/timeteccrm/resources/views/emails/bypass-duplicate-rejection.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bypass Duplicate Request Rejected</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
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
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .content {
            padding: 30px 20px;
        }
        .alert-box {
            background-color: #fef2f2;
            border-left: 4px solid #dc2626;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-box p {
            margin: 0;
            color: #991b1b;
            font-weight: 500;
        }
        .info-section {
            background-color: #f9fafb;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
        .info-row {
            display: flex;
            padding: 10px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #374151;
            min-width: 140px;
        }
        .info-value {
            color: #6b7280;
            flex: 1;
        }
        .reason-box {
            background-color: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        .reason-box h3 {
            margin: 0 0 10px 0;
            color: #92400e;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .reason-box p {
            margin: 0;
            color: #78350f;
            font-style: italic;
        }
        .footer {
            background-color: #f9fafb;
            padding: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 14px;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header" style="background-color:red;">
            <h1>🚫 Bypass Duplicate Request Rejected</h1>
        </div>

        <div class="content">
            <div class="alert-box">
                <p>Your request to bypass duplicate checking has been rejected.</p>
            </div>

            <p>Dear <strong>{{ $requestorName }}</strong>,</p>

            <p>We regret to inform you that your request to bypass duplicate checking for the following lead has been rejected by <strong>{{ $rejectedByName }}</strong>.</p>

            <div class="info-section">
                <div class="info-row">
                    <div class="info-label">Company Name:</div>
                    <div class="info-value"><strong>{{ $companyName }}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Lead Code:</div>
                    <div class="info-value">{{ $leadCode }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Rejected By:</div>
                    <div class="info-value">{{ $rejectedByName }}</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Rejected At:</div>
                    <div class="info-value">{{ $rejectedAt }}</div>
                </div>
            </div>

            <div class="reason-box">
                <h3>Your Request Reason:</h3>
                <p>"{{ $reason }}"</p>
            </div>
        </div>
    </div>
</body>
</html>
