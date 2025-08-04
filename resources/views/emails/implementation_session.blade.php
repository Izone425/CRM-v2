<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #f5f5f5;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 20px;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <p>Dear Customer,</p>
            <p>Good day to you.</p>

            <p>As per our discussion, our implementation review session has been scheduled.</p>
            <p>Kindly find below the details:</p>

            <h3>Implementer Details:</h3>
            <p>
                Implementer: {{ $content['implementerName'] }}<br>
                Email: {{ $content['implementerEmail'] }}
            </p>

            <h3>Implementation Review Session Details:</h3>
            <p>
                Company Name: {{ $content['companyName'] }}<br>
                Implementation Review Session: Count {{ $content['implementationCount'] }}<br>
                Appointment Type: {{ $content['appointmentType'] }}<br>
                Date: {{ $content['date'] }}<br>
                {{ $content['sessionTime'] }}<br>
                @if(!empty($content['meetingLink']))
                Meeting Link: {{ $content['meetingLink'] }}<br>
                @endif
                @if(!empty($content['meetingId']))
                Meeting ID: {{ $content['meetingId'] }}<br>
                @endif
                @if(!empty($content['meetingPassword']))
                Meeting Password: {{ $content['meetingPassword'] }}<br>
                @endif
            </p>

            @if(!empty($content['remarks']))
            <h3>Remarks:</h3>
            <p>{{ $content['remarks'] }}</p>
            @endif

            <div class="footer">
                <p>
                    Best Regards,<br>
                    {{ $content['implementerName'] }}<br>
                    Dedicated Implementer<br>
                    TimeTec Cloud Sdn Bhd<br>
                    Office Number: +603-8070 9933
                </p>
            </div>
        </div>
    </div>
</body>
</html>
