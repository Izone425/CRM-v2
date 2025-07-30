<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hardware Handover Rejected</title>
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
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-bottom: 3px solid #dc3545;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .detail-row {
            margin-bottom: 10px;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            width: 150px;
        }
        .reason {
            margin-top: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #dc3545;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 0.9em;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Hardware Handover Rejected</h2>
        </div>

        <div class="content">
            <p>Dear {{ $salespersonName }},</p>

            <p>Your hardware handover request has been rejected. Please review the details below:</p>

            <div class="detail-row">
                <span class="label">Rejected By:</span> {{ $rejecterName }}
            </div>

            <div class="detail-row">
                <span class="label">Rejected Date:</span> {{ $rejectedDate }}
            </div>

            <div class="detail-row">
                <span class="label">Hardware Handover ID:</span> {{ $handoverId }}
            </div>

            <div class="detail-row">
                <span class="label">Salesperson:</span> {{ $salespersonName }}
            </div>

            <div class="reason">
                <h3>Rejection Reason:</h3>
                {!! nl2br(strtoupper(e($rejectReason))) !!}
            </div>

            <p>Please address the issues mentioned above and resubmit your hardware handover request.</p>

            <p>If you have any questions, please contact admin handover.</p>
        </div>
    </div>
</body>
</html>
