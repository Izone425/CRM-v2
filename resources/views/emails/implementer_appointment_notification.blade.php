<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>TIMETEC IMPLEMENTATION APPOINTMENT | {{ $content['lead']['company'] }}</title>
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>TIMETEC IMPLEMENTATION APPOINTMENT</h2>
        </div>

        <div class="content">
            <p class="greeting">Dear {{ $content['leadOwnerName'] }},</p>

            <p>A implemnetation appointment has been scheduled with the following details:</p>

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
                    <th>Implementation Type</th>
                    <td><span class="highlight">{{ $content['lead']['demo_type'] }}</span></td>
                </tr>
                <tr>
                    <th>Appointment Type</th>
                    <td><span class="highlight">{{ $content['lead']['appointment_type'] }}</span></td>
                </tr>
                <tr>
                    <th>Appointment Date</th>
                    <td><span class="highlight">{{ $content['lead']['date'] }}</span></td>
                </tr>
                <tr>
                    <th>Appointment Time</th>
                    <td><span class="highlight">{{ $content['lead']['startTime'] }} - {{ $content['lead']['endTime'] }}</span></td>
                </tr>
                <tr>
                    <th>Implementer</th>
                    <td><span class="highlight">{{ $content['lead']['implementerName'] }}</span></td>
                </tr>
                @endif
            </table>

            @if(isset($content['lead']['remarks']) && !empty($content['lead']['remarks']))
            <div class="row">
                <h3>Remarks:</h3>
                <div class="remark-box">
                    {{ $content['lead']['remarks'] }}
                </div>
            </div>
            @endif

            <p>Please make the necessary arrangements to attend this appointment. If you have any questions or need to reschedule, please contact your manager as soon as possible.</p>

            <p>Thank you,<br>TimeTec CRM</p>

            <div class="footer">
                <p>This is an automated message from TimeTec CRM system. Please do not reply to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
