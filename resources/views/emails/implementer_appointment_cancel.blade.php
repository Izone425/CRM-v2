<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>CANCELLED: TIMETEC IMPLEMENTATION APPOINTMENT | {{ $content['lead']['company'] }}</title>
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
            border-bottom: 5px solid #e74c3c;
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
        .button-container {
            text-align: center;
            margin-top: 25px;
            margin-bottom: 10px;
        }
        .button {
            background-color: #e74c3c;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        .button:hover {
            background-color: #c0392b;
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
            border-left: 3px solid #e74c3c;
            background-color: #f9f9f9;
        }
        .highlight {
            font-weight: bold;
            color: #e74c3c;
        }
        .cancelled {
            color: #e74c3c;
            font-weight: bold;
            font-size: 16px;
            text-align: center;
            padding: 10px;
            background-color: #fcede9;
            border: 1px dashed #e74c3c;
            margin-bottom: 20px;
        }
        .cancelled-text {
            text-decoration: line-through;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>CANCELLED IMPLEMENTATION APPOINTMENT</h2>
        </div>

        <div class="content">
            <p class="greeting">Dear {{ $content['leadOwnerName'] }},</p>

            <div class="cancelled">
                IMPORTANT: The following im appointment has been cancelled
            </div>

            <p>The implementation appointment with the following details has been <strong>cancelled</strong>:</p>

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
                    <th>Cancelled Appointment</th>
                    <td>
                        <span class="cancelled-text">
                            {{ $content['lead']['demo_type'] }} |
                            {{ $content['lead']['date'] }},
                            {{ $content['lead']['startTime'] }} - {{ $content['lead']['endTime'] }}
                        </span>
                    </td>
                </tr>
            </table>

            <p>Please update your calendar accordingly. If you had made any arrangements for this appointment, please cancel them as well.</p>

            <p>If you wish to reschedule this appointment, please contact your manager or create a new appointment in the TimeTec CRM system.</p>

            <p>Thank you,<br>TimeTec CRM</p>

            <div class="footer">
                <p>This is an automated message from TimeTec CRM system. Please do not reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
