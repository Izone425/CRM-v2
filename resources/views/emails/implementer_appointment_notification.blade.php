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
    <div class="container">
        <div class="header">
            <h2>TIMETEC HR | {{ $content['lead']['appointment_type'] }} | {{ $content['lead']['demo_type'] }}</h2>
        </div>

        <div class="content">
            <p class="greeting">Dear Customer,</p>

            <p>Good day to you.</p>

            <p>It's a great pleasure to have you onboard! We are thrilled that you have chosen to embark on this voyage with TimeTec HR.</p>

            <p>We have carefully mapped out the route for your onboarding process to ensure a smooth journey ahead. Set your sails and all hands-on deck!</p>

            <p>To kick start your journey, please find the details below:</p>

            <div class="remark-box">
                <strong>Kick-Off Online Meeting Date and Time:</strong>
                {{ $content['lead']['date'] }}, {{ \Carbon\Carbon::parse($content['lead']['date'])->format('l') }},
                {{ $content['lead']['session'] }}: {{ $content['lead']['startTime'] }} - {{ $content['lead']['endTime'] }}
            </div>

            <div class="section-header">Implementation File</div>
            <ul class="file-list">
                <li class="file-item">Data Migration Template: <a href="https://timeteccloud0-my.sharepoint.com/:x:/g/personal/faiz_timeteccloud_com/EcpHUT5h3q5Pg45tl48DhgUByt5KkVMPJuJEesUyL4WVLA?e=IRhgct" target="_blank">Import User Sample.xlsx</a></li>

                <li class="file-item">Data Migration Guide (PDF): <a href="https://timeteccloud0-my.sharepoint.com/personal/faiz_timeteccloud_com/_layouts/15/onedrive.aspx?id=%2Fpersonal%2Ffaiz%5Ftimeteccloud%5Fcom%2FDocuments%2F01%20%2D%20TIMETEC%20HR%20%5BSHARED%5D%2F98%20%2D%20%23%20GENERAL%20TASK%20%2D%20TIMETEC%20HR%20STAFF%2F01%20%2D%20MOHD%20HANIF%20BIN%20RAZALI%2F02%20%2D%20KICK%2DOFF%20MEETING%20FILES%2FKICK%20OFF%20MEETING%20FILES%2F02%20%2D%20Import%20User%20File%20Guideline%20%2D%20Data%20Migration%20Explanation%2Epdf&parent=%2Fpersonal%2Ffaiz%5Ftimeteccloud%5Fcom%2FDocuments%2F01%20%2D%20TIMETEC%20HR%20%5BSHARED%5D%2F98%20%2D%20%23%20GENERAL%20TASK%20%2D%20TIMETEC%20HR%20STAFF%2F01%20%2D%20MOHD%20HANIF%20BIN%20RAZALI%2F02%20%2D%20KICK%2DOFF%20MEETING%20FILES%2FKICK%20OFF%20MEETING%20FILES&ga=1" target="_blank">Import User File Guideline.pdf</a></li>

                <li class="file-item">Import Leave Balance Template: <a href="https://timeteccloud0-my.sharepoint.com/:x:/g/personal/faiz_timeteccloud_com/EQO8XFDvlC9JjCU3DnHWcJ4BbgfjxFb8gXfzjOj_wGsP7w?e=2VLsrP" target="_blank">User Leave Balance.xlsx</a></li>

                <li class="file-item">Import Leave Balance Guide (PDF): <a href="https://timeteccloud0-my.sharepoint.com/personal/faiz_timeteccloud_com/_layouts/15/onedrive.aspx?id=%2Fpersonal%2Ffaiz%5Ftimeteccloud%5Fcom%2FDocuments%2F01%20%2D%20TIMETEC%20HR%20%5BSHARED%5D%2F98%20%2D%20%23%20GENERAL%20TASK%20%2D%20TIMETEC%20HR%20STAFF%2F01%20%2D%20MOHD%20HANIF%20BIN%20RAZALI%2F02%20%2D%20KICK%2DOFF%20MEETING%20FILES%2FKICK%20OFF%20MEETING%20FILES%2F04%20%2D%20Import%20User%20Leave%20Balance%20Guideline%2Epdf&parent=%2Fpersonal%2Ffaiz%5Ftimeteccloud%5Fcom%2FDocuments%2F01%20%2D%20TIMETEC%20HR%20%5BSHARED%5D%2F98%20%2D%20%23%20GENERAL%20TASK%20%2D%20TIMETEC%20HR%20STAFF%2F01%20%2D%20MOHD%20HANIF%20BIN%20RAZALI%2F02%20%2D%20KICK%2DOFF%20MEETING%20FILES%2FKICK%20OFF%20MEETING%20FILES&ga=1" target="_blank">Import leave balance guideline.pdf</a></li>
            </ul>

            <div class="section-header">Microsoft Teams Link: <a href="{{ $content['lead']['meetingLink'] }}" target="_blank">Join Meeting</a></div>

            <p>Looking forward to have you in our Kick-Off Meeting session.</p>
        </div>
    </div>
</body>
</html>
