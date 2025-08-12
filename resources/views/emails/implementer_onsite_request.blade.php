<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Onsite Request</title>
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
            border: 1px solid #ddd;
        }
        .header {
            background-color: #3490dc;
            color: white;
            padding: 10px 20px;
            text-align: center;
        }
        .section {
            margin-bottom: 20px;
            padding: 10px;
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #6c757d;
        }
        .label {
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        table td:first-child {
            width: 40%;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>TIMETEC CRM - ONSITE REQUEST</h2>
        </div>

        <div class="section">
            <h3>Onsite Request Details</h3>
            <table>
                <tr>
                    <td>Implementer ID</td>
                    <td>{{ $content['implementerId'] }}</td>
                </tr>
                <tr>
                    <td>Implementer Name</td>
                    <td>{{ $content['implementerName'] }}</td>
                </tr>
                <tr>
                    <td>Request Date & Time</td>
                    <td>{{ $content['requestDateTime'] }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h3>Onsite Information</h3>
            <table>
                <tr>
                    <td>Company</td>
                    <td>{{ $content['companyName'] }}</td>
                </tr>
                <tr>
                    <td>Onsite Category</td>
                    <td><strong>{{ $content['onsiteCategory'] }}</strong></td>
                </tr>
                <tr>
                    <td>Date</td>
                    <td>{{ $content['dateAndDay'] }}</td>
                </tr>
                <tr>
                    <td>Day Type</td>
                    <td>{{ $content['dayType'] }}</td>
                </tr>
                <tr>
                    <td>Sessions</td>
                    <td>{!! $content['sessions'] !!}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <h3>Additional Details</h3>
            <table>
                <tr>
                    <td>Attendees</td>
                    <td>{{ $content['attendees'] }}</td>
                </tr>
                <tr>
                    <td>Remarks</td>
                    <td>{{ $content['remarks'] }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>This is an automated message from TimeTec CRM. Please do not reply to this email.</p>
            <p>© {{ date('Y') }} TimeTec CRM. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
