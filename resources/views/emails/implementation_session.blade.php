<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SUBJECT: TIMETEC HR | {{ $content['appointmentType'] }} | {{ $content['type'] }} | {{ $content['companyName'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        .meeting-link {
            color: #0066cc; /* Changed to standard blue link color */
            text-decoration: underline;
        }
        .container {
            max-width: 720px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #2b374f;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px 5px 0 0;
            border-bottom: 5px solid #2b374f;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        .section-header {
            margin-top: 20px;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .remark-box {
            margin-bottom: 10px;
            padding: 10px;
            border-left: 3px solid #2b374f;
            background-color: #f9f9f9;
        }
        .detail-item {
            margin-left: 10px;
            margin-bottom: 5px;
        }
        .detail-label {
            font-weight: bold;
        }
        .meeting-link {
            color: #2b374f;
            text-decoration: underline;
        }
        .signature {
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <p class="greeting">Dear Customer,</p>

    <p>Kindly find the details for our Implementation Review Session below:</p>

    <div class="section-header">* Implementation Review Session details:</div>
    <div class="remark-box">
        <div><strong>Date:</strong> {{ \Carbon\Carbon::parse($content['date'])->format('d M Y, l') }}</div>
        <div><strong>Time:</strong> {{ $content['startTime'] }} – {{ $content['endTime'] }}</div>
        <div><strong>Demo Type:</strong> {{ $content['type'] }}</div>
        <div><strong>Appointment Type:</strong> {{ $content['appointmentType'] }}</div>
    </div>

    <div class="section-header">* Microsoft Teams meeting details:</div>
    <div class="remark-box">
        <div><strong>Link:</strong> <a href="{{ $content['meetingLink'] }}" class="meeting-link" target="_blank">{{ $content['meetingLink'] }}</a></div>
    </div>

    <div class="section-header">* Implementer details:</div>
    <div class="remark-box">
        <div><strong>Name:</strong> {{ $content['implementerName'] }}</div>
        <div><strong>Email:</strong> <a href="mailto:{{ $content['implementerEmail'] }}">{{ $content['implementerEmail'] }}</a></div>
    </div>

    <p class="signature">Looking forward to have you in our Implementation Review Session.</p>
</body>
</html>
