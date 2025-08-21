<!-- filepath: /var/www/html/timeteccrm/resources/views/emails/overtime-reminder.blade.php -->

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
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
            border-bottom: 3px solid #0275d8;
        }
        .content {
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>TIMETEC HR | WEEKEND SUPPORT REMINDER</h2>
        </div>

        <div class="content">
            <p><strong>TYPE:</strong> REMINDER {{ $data['reminderType'] }}</p>
            <p><strong>SUPPORT STANDBY:</strong> {{ $data['staffName'] }}</p>
            <p><strong>OVERTIME SCHEDULE:</strong> DAY 1 / {{ $data['saturdayDate'] }}</p>
            <p><strong>OVERTIME SCHEDULE:</strong> DAY 2 / {{ $data['sundayDate'] }}</p>

            <p>This is an automated reminder that you are scheduled for weekend support duty this weekend.</p>
            <p>Please ensure you are available during working hours on both days.</p>
        </div>

        <div class="footer">
            <p>This is an automated message from the TimeTec HR Support System.</p>
        </div>
    </div>
</body>
</html>
