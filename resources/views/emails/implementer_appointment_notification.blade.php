<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>SUBJECT: TIMETEC HR | {{ $content['lead']['appointment_type'] }} | {{ $content['lead']['demo_type'] }} | {{ $content['lead']['company'] }}</title>
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
            background-color: #2b374f;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        .button:hover {
            background-color: #1a2535;
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
            border-left: 3px solid #2b374f;
            background-color: #f9f9f9;
        }
        .highlight {
            font-weight: bold;
            color: #2b374f;
        }
        .file-list {
            margin-left: 20px;
            padding-left: 15px;
        }
        .file-item {
            margin-bottom: 8px;
        }
        .section-header {
            margin-top: 20px;
            margin-bottom: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <p class="greeting">Dear Customer,<br>Good day to you.</p>

    <p>It's a great pleasure to have you onboard! We are thrilled that you have chosen to embark on this voyage with TimeTec HR. <br>We have carefully mapped out the route for your onboarding process to ensure a smooth journey ahead. Set your sails and all hands-on deck!</p>

    <p>To kick start your journey, please find the details below:</p>

    <div class="section-header">* Kick Off Meeting details:</div>
    <div class="remark-box">
        <div><strong>Date:</strong> {{ \Carbon\Carbon::parse($content['lead']['date'])->format('d M Y, l') }}</div>
        <div><strong>Time:</strong> {{ $content['lead']['startTime'] }} - {{ $content['lead']['endTime'] }}</div>
        <div><strong>Meeting Type:</strong> Kick-Off Meeting Session</div>
    </div>

    <div class="section-header">* Microsoft Teams meeting details:</div>
    <div class="remark-box">
        <div><strong>Link:</strong> <a href="{{ $content['lead']['meetingLink'] }}" class="meeting-link" target="_blank">{{ $content['lead']['meetingLink'] }}</a></div>
    </div>

    <div class="section-header">* Implementer details:</div>
    <div class="remark-box">
        <div><strong>Name:</strong> {{ $content['lead']['implementerName'] }}</div>
        <div><strong>Email:</strong> <a href="mailto:{{ $content['lead']['implementerEmail'] }}">{{ $content['lead']['implementerEmail'] }}</a></div>
    </div>

    <div class="section-header">Implementation File</div>
    <ul class="file-list">
        <li class="file-item">Software Onboarding Process: <a href="{{ route('implementer.files', 'software-onboarding-process.pdf') }}" target="_blank">Software Onboarding Process.pdf</a></li>

        <li class="file-item">Data Migration Template: <a href="{{ route('implementer.files', 'import-user-example.xlsx') }}" target="_blank">Import User Sample.xlsx</a></li>

        <li class="file-item">Data Migration Guide (PDF): <a href="{{ route('implementer.files', 'data-migration-explaination.pdf') }}" target="_blank">Import User File Guideline.pdf</a></li>

        <li class="file-item">Import Leave Balance Template: <a href="{{ route('implementer.files', 'user-leave-balance-template.xlsx') }}" target="_blank">User Leave Balance.xlsx</a></li>

        <li class="file-item">Import Leave Balance Guide (PDF): <a href="{{ route('implementer.files', 'user-leave-balance-guideline.pdf') }}" target="_blank">Import leave balance guideline.pdf</a></li>
    </ul>
    <p>Looking forward to have you in our Kick-Off Meeting session.</p>
</body>
</html>
