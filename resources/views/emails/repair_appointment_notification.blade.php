<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repair Appointment Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            border-bottom: 3px solid #007bff;
            margin-bottom: 20px;
        }
        h1 {
            color: #007bff;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #f8f9fa;
            text-align: left;
            padding: 12px;
            font-weight: bold;
            width: 30%;
        }
        td {
            padding: 12px;
        }
        .section {
            margin-bottom: 30px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        .btn {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }
        .signature {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
        .highlight {
            background-color: #fff3cd;
            padding: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TimeTec Repair Appointment Notification</h1>
    </div>

    <div class="section">
        <p>Dear {{ $content['leadOwnerName'] }},</p>
        <p>A repair appointment has been scheduled with the following details:</p>
    </div>

    <div class="section">
        <h2>Appointment Details</h2>
        <table>
            <tr>
                <th>Company</th>
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
                <th>Repair Type</th>
                <td><strong>{{ $content['lead']['repair_type'] }}</strong></td>
            </tr>
            <tr>
                <th>Appointment Type</th>
                <td><strong>{{ $content['lead']['appointment_type'] }}</strong></td>
            </tr>
            <tr>
                <th>Appointment Date</th>
                <td><strong>{{ $content['lead']['date'] }}</strong></td>
            </tr>
            <tr>
                <th>Appointment Time</th>
                <td><strong>{{ $content['lead']['startTime'] }} - {{ $content['lead']['endTime'] }}</strong></td>
            </tr>
            <tr>
                <th>Technician</th>
                <td><strong>{{ $content['lead']['technicianName'] }}</strong></td>
            </tr>
        </table>
    </div>

    @if(isset($content['lead']['remarks']) && !empty($content['lead']['remarks']))
    <div class="section">
        <h2>Remarks</h2>
        <div class="highlight">
            {{ $content['lead']['remarks'] }}
        </div>
    </div>
    @endif
</body>
</html>
