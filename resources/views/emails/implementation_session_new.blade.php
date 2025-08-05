<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .email-wrapper {
            background-color: #f9f9f9;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            padding: 30px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 20px;
        }
        .logo {
            max-width: 150px;
            margin-bottom: 15px;
        }
        .title {
            color: #2563eb;
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }
        h3 {
            color: #2563eb;
            font-size: 16px;
            font-weight: 600;
            margin-top: 25px;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 1px solid #eaeaea;
        }
        .section {
            margin-bottom: 25px;
        }
        .details {
            background-color: #f8fafc;
            border-left: 4px solid #2563eb;
            padding: 15px;
            margin-left: 0;
            border-radius: 0 4px 4px 0;
        }
        .details p {
            margin: 8px 0;
        }
        .details strong {
            font-weight: 600;
            color: #444;
        }
        .meeting-info {
            background-color: #eef2ff;
            border-left: 4px solid #4f46e5;
            padding: 15px;
            margin-top: 15px;
            border-radius: 0 4px 4px 0;
        }
        .meeting-link {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
        }
        .meeting-link:hover {
            text-decoration: underline;
        }
        .remarks {
            background-color: #f8f9fa;
            padding: 15px;
            margin-top: 15px;
            border-radius: 4px;
            font-style: italic;
        }
        .signature {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
            color: #555;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            color: #888;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="container">
            <div class="header">
                <!-- You can add your logo here -->
                <!-- <img src="https://your-company.com/logo.png" alt="TimeTec Logo" class="logo"> -->
                <h1 class="title">Implementation Session Scheduled</h1>
            </div>

            <p>Dear Customer,</p>
            <p>Good day to you.</p>

            <p>As per our discussion, our implementation review session has been scheduled.</p>
            <p>Kindly find below the details:</p>

            <div class="section">
                <h3>Implementer Details</h3>
                <div class="details">
                    <p><strong>Implementer:</strong> {{ $content['implementerName'] }}</p>
                    <p><strong>Email:</strong> <a href="mailto:{{ $content['implementerEmail'] }}">{{ $content['implementerEmail'] }}</a></p>
                </div>
            </div>

            <div class="section">
                <h3>Implementation Review Session Details</h3>
                <div class="details">
                    <p><strong>Company Name:</strong> {{ $content['companyName'] }}</p>
                    <p><strong>Implementation Review Session:</strong> Count {{ $content['implementationCount'] }}</p>
                    <p><strong>Appointment Type:</strong> {{ $content['appointmentType'] }}</p>
                    <p><strong>Date:</strong> {{ $content['date'] }}</p>
                    <p><strong>{{ $content['sessionName'] }}:</strong> {{ $content['startTime'] }} – {{ $content['endTime'] }}</p>

                    @if($content['meetingLink'] || $content['meetingId'] || $content['meetingPassword'])
                    <div class="meeting-info">
                        <h3 style="border: none; margin-top: 0; padding-bottom: 0;">Meeting Information</h3>
                        @if($content['meetingLink'])
                            <p><strong>Meeting Link:</strong> <a href="{{ $content['meetingLink'] }}" class="meeting-link" target="_blank">Join Microsoft Teams Meeting</a></p>
                        @endif

                        @if($content['meetingId'])
                            <p><strong>Meeting ID:</strong> {{ $content['meetingId'] }}</p>
                        @endif

                        @if($content['meetingPassword'])
                            <p><strong>Meeting Password:</strong> {{ $content['meetingPassword'] }}</p>
                        @endif
                    </div>
                    @endif

                    @if($content['remarks'])
                    <div class="remarks">
                        <strong>Remarks:</strong><br>
                        {{ $content['remarks'] }}
                    </div>
                    @endif
                </div>
            </div>

            <div class="signature">
                <p>Best Regards,</p>
                <p>
                    <strong>{{ $content['implementerName'] }}</strong><br>
                    Dedicated Implementer<br>
                    TimeTec Cloud Sdn Bhd<br>
                    Office Number: {{ $content['officeNumber'] }}
                </p>
            </div>

            <div class="footer">
                <p>This is an automated email notification. Please do not reply directly to this email.</p>
            </div>
        </div>
    </div>
</body>
</html>
