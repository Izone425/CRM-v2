<!-- filepath: /var/www/html/timeteccrm/resources/views/emails/implementer_appointment_cancelled.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Implementation Appointment Cancelled</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #f44336;
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: bold;
            font-size: 24px;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
        }
        .footer {
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            IMPLEMENTATION APPOINTMENT CANCELLED
        </div>
        <div class="content">
            <p>Dear Sir/Madam,</p>

            <p>Please be informed that the following implementation session has been <strong>cancelled</strong>:</p>

            <table>
                <tr>
                    <th>Implementation Type</th>
                    <td>{{ $content['appointmentType'] }}</td>
                </tr>
                <tr>
                    <th>Company</th>
                    <td>{{ $content['companyName'] }}</td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td>{{ $content['date'] }}</td>
                </tr>
                <tr>
                    <th>Time</th>
                    <td>{{ $content['time'] }}</td>
                </tr>
                <tr>
                    <th>Implementer</th>
                    <td>{{ $content['implementer'] }}</td>
                </tr>
                <tr>
                    <th>Cancelled By</th>
                    <td>{{ $content['cancelledBy'] }}</td>
                </tr>
                <tr>
                    <th>Cancelled Date</th>
                    <td>{{ $content['cancelledDate'] }}</td>
                </tr>
                <tr>
                    <th>Remarks</th>
                    <td>{{ $content['remarks'] }}</td>
                </tr>
            </table>

            <p>If you have any questions, please contact us.</p>

            <p>Thank you.</p>

            <p>Best regards,<br>
            TimeTec Implementation Team</p>
        </div>
        <div class="footer">
            This is an automated email. Please do not reply to this email.
        </div>
    </div>
</body>
</html>
