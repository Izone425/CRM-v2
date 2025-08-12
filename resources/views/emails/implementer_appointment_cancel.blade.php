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
        }
        .container {
            max-width: 720px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #d9534f;
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
        .footer {
            margin-top: 20px;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
        .remark-box {
            margin-bottom: 10px;
            padding: 10px;
            border-left: 3px solid #d9534f;
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>APPOINTMENT CANCELLED</h2>
        </div>
        <div class="content">
            <p>Dear Customer,</p>

            <p>Please be informed that the following appointment has been <strong>cancelled</strong>:</p>

            <div class="remark-box">
                <div class="row">
                    <span class="label">Company:</span>
                    <span>{{ $content['companyName'] }}</span>
                </div>
                <div class="row">
                    <span class="label">Appointment Type:</span>
                    <span>{{ $content['appointmentType'] }}</span>
                </div>
                <div class="row">
                    <span class="label">Date:</span>
                    <span>{{ $content['date'] }}</span>
                </div>
                <div class="row">
                    <span class="label">Time:</span>
                    <span>{{ $content['time'] }}</span>
                </div>
                <div class="row">
                    <span class="label">Implementer:</span>
                    <span>{{ $content['implementer'] }}</span>
                </div>
            </div>

            <p>If you have any questions or would like to reschedule, please contact our team.</p>

            <p>Thank you for your understanding.</p>

            <p>Best regards,<br>TimeTec HR Team</p>
        </div>

        <div class="footer">
            <p>This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>
