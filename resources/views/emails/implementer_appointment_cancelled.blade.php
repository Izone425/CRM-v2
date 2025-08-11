<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CANCELLED: TIMETEC HR | {{ $content['appointmentType'] }} | {{ $content['companyName'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 720px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #d9534f;
            color: white;
            padding: 5px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .cancelled-banner {
            background-color: #d9534f;
            color: white;
            padding: 10px 15px;
            text-align: center;
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .remark-box {
            margin-bottom: 20px;
            padding: 15px;
            border-left: 3px solid #d9534f;
            background-color: #f9f9f9;
        }
        .detail-row {
            margin-bottom: 10px;
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
            border-top: 1px solid #eee;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>This appointment has been cancelled</h2>
        </div>

        <div class="content">
            <p class="greeting">Dear Customer,</p>

            <p>Please be informed that the following appointment has been <strong>cancelled</strong>:</p>

            <div class="remark-box">
                <div class="detail-row">
                    <strong>Company:</strong> {{ $content['companyName'] }}
                </div>
                <div class="detail-row">
                    <strong>Appointment Type:</strong> {{ $content['appointmentType'] }}
                </div>
                <div class="detail-row">
                    <strong>Date:</strong> {{ $content['date'] }}
                </div>
                <div class="detail-row">
                    <strong>Time:</strong> {{ $content['time'] }}
                </div>
                <div class="detail-row">
                    <strong>Implementer:</strong> {{ $content['implementer'] }}
                </div>
            </div>

            <p>If you have any questions or would like to reschedule this appointment, please contact your TimeTec HR representative or reply to this email.</p>

            <p>We apologize for any inconvenience this may have caused.</p>

            <p>Thank you for your understanding.</p>

            <p>Best regards,<br>
            TimeTec HR Team</p>
        </div>

        <div class="footer">
            <p>This is an automated message from TimeTec HR. Please do not reply directly to this email.</p>
        </div>
    </div>
</body>
</html>
