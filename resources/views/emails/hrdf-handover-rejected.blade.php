{{-- filepath: /var/www/html/timeteccrm/resources/views/emails/hrdf-handover-rejected.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRDF Handover Rejected</title>
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
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
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
            border-left: 4px solid #ef4444;
        }
        .status-rejected {
            background: #fee2e2;
            color: #991b1b;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: bold;
            display: inline-block;
        }
        .reason-box {
            background: #fef2f2;
            border: 1px solid #f87171;
            border-radius: 8px;
            padding: 16px;
            margin: 16px 0;
        }
        .reason-label {
            font-weight: bold;
            color: #991b1b;
            margin-bottom: 8px;
        }
        .reason-text {
            color: #991b1b;
            white-space: pre-wrap;
        }
    </style>
</head>
<body>
    <div class="content">
        <p>Hi {{ $salesperson->name }},</p>

        <p>Your HRDF handover has been rejected. Please review the reason below and make necessary corrections.</p>

        <div class="info-box">
            <p><strong>Company Name:</strong> {{ $companyName }}</p>
            <p><strong>HRDF Handover ID:</strong> {{ $handoverId }}</p>
            <p><strong>Status:</strong> <span class="status-rejected">REJECTED</span></p>
        </div>

        @if($rejectReason)
        <div class="reason-box">
            <div class="reason-label">Reason for Rejection:</div>
            <div class="reason-text">{{ $rejectReason }}</div>
        </div>
        @endif

        <p>Please review and resubmit your HRDF handover with the necessary corrections.</p>
    </div>
</body>
</html>
