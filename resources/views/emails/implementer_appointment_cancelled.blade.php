<?php
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
        }
        h3 {
            color: #0056b3;
        }
        .section {
            margin-bottom: 20px;
        }
        .details {
            margin-left: 20px;
        }
        .signature {
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
        .teams-info {
            background-color: #f8f9fa;
            border-left: 4px solid #5558af;
            padding: 15px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <p>Dear Customer,</p>
        <p>Good day to you.</p>

        <p>As per our discussion, our implementation review session has been scheduled.</p>
        <p>Kindly find below the details:</p>

        <div class="section">
            <h3>Implementer Details:</h3>
            <div class="details">
                <p>Implementer: {{ $content['implementerName'] }}</p>
                <p>Email: {{ $content['implementerEmail'] }}</p>
            </div>
        </div>

        <div class="section">
            <h3>Implementation Review Session Details:</h3>
            <div class="details">
                <p>Company Name: {{ $content['companyName'] }}</p>
                <p>Implementation Review Session: Count {{ $content['implementationCount'] }}</p>
                <p>Appointment Type: {{ $content['appointmentType'] }}</p>
                <p>Date: {{ $content['date'] }}</p>
                <p>{{ $content['sessionName'] }}: {{ $content['startTime'] }} – {{ $content['endTime'] }}</p>

                @if($content['appointmentType'] === 'ONLINE' && $content['meetingLink'])
                <div class="teams-info">
                    <h3>Microsoft Teams Meeting Information:</h3>
                    <p><a href="{{ $content['meetingLink'] }}">Join Microsoft Teams Meeting</a></p>

                    @if($content['meetingId'])
                    <p>Meeting ID: {{ $content['meetingId'] }}</p>
                    @endif

                    @if($content['meetingPassword'])
                    <p>Password: {{ $content['meetingPassword'] }}</p>
                    @endif
                </div>
                @endif

                @if($content['remarks'])
                    <p>Remarks: {{ $content['remarks'] }}</p>
                @endif
            </div>
        </div>

        <div class="signature">
            <p>Best Regards,<br>
            {{ $content['implementerName'] }}<br>
            Dedicated Implementer<br>
            TimeTec Cloud Sdn Bhd<br>
            Office Number: {{ $content['officeNumber'] }}</p>
        </div>
    </div>
</body>
</html>
