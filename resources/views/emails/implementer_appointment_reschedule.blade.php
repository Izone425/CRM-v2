<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>RESCHEDULED: TIMETEC IMPLEMENTATION APPOINTMENT | {{ $content['lead']['company'] }}</title>
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
            background-color: #2b374f;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px 5px 0 0;
            border-bottom: 5px solid #f39c12;
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
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
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
            border-left: 3px solid #f39c12;
            background-color: #f9f9f9;
        }
        .highlight {
            font-weight: bold;
            color: #f39c12;
        }
        .change-box {
            margin-bottom: 15px;
            padding: 10px;
            border-left: 3px solid #f39c12;
            background-color: #fef5e7;
        }
        .old-schedule {
            text-decoration: line-through;
            color: #e74c3c;
        }
        .new-schedule {
            font-weight: bold;
            color: #27ae60;
        }
        .notice {
            background-color: #f8d7da;
            border-left: 3px solid #dc3545;
            padding: 10px;
            margin: 15px 0;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>APPOINTMENT RESCHEDULED</h2>
        </div>

        <div class="content">
            <p class="greeting">Dear {{ $content['leadOwnerName'] }},</p>

            <div class="notice">
                <strong>NOTICE:</strong> The implementation appointment for {{ $content['lead']['company'] }} has been rescheduled.
            </div>

            <div class="change-box">
                <h3>Schedule Change:</h3>
                <p><strong>Previous Schedule:</strong> <span class="old-schedule">{{ $content['lead']['oldDate'] }}, {{ $content['lead']['oldStartTime'] }} - {{ $content['lead']['oldEndTime'] }}</span></p>
                <p><strong>New Schedule:</strong> <span class="new-schedule">{{ $content['lead']['date'] }}, {{ $content['lead']['startTime'] }} - {{ $content['lead']['endTime'] }}</span></p>
            </div>

            <table>
                <tr>
                    <th>Company Name</th>
                    <td>{{ $content['lead']['company'] }}</td>
                </tr>
                <tr>
                    <th>Contact Person</th>
                    <td>{{ $content['lead']['pic'] }}</td>
                </tr>
                <tr>
                    <th>Contact Number</th>
                    <td>{{ $content['lead']['phone'] }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $content['lead']['email'] }}</td>
                </tr>
                <tr>
                    <th>Implementer</th>
                    <td><span class="highlight">{{ $content['lead']['implementerName'] }}</span></td>
                </tr>
            </table>

            <p>Please update your calendar accordingly. If you have any questions or concerns about this change, please contact your manager as soon as possible.</p>

            <p>Thank you,<br>TimeTec CRM</p>

            <div class="footer">
                <p>This is an automated message from TimeTec CRM system. Please do not reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
