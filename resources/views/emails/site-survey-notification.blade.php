<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333333;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #6366F1;
        }
        .content {
            padding: 20px;
        }
        .footer {
            margin-top: 20px;
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
            border-top: 1px solid #e9ecef;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #dee2e6;
        }
        th, td {
            padding: 12px 15px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>SITE SURVEY HANDOVER ID {{ $surveyId }}</h2>
        </div>

        <div class="content">
            <p>Dear Khairul Izzuddin,</p>
            <p>Good day to you.</p>
            <p>Refer to the details below for the site survey information:</p>

            <table>
                <tr>
                    <th style="width: 40%;">Site Survey Handover ID</th>
                    <td style="width: 60%;">{{ $surveyId }}</td>
                </tr>
                <tr>
                    <th>Date Submitted</th>
                    <td>{{ $dateSubmitted }}</td>
                </tr>
                <tr>
                    <th>Submitted by</th>
                    <td>{{ $salesperson }}</td>
                </tr>
                <tr>
                    <th>Company Name</th>
                    <td>{{ $companyName }}</td>
                </tr>
                <tr>
                    <th>Device Model</th>
                    <td>{{ $deviceModel }}</td>
                </tr>
                <tr>
                    <th>Date</th>
                    <td>{{ $date }}</td>
                </tr>
                <tr>
                    <th>Start Time - End Time</th>
                    <td>{{ $timeRange }}</td>
                </tr>
                <tr>
                    <th>SalesPerson Remark</th>
                    <td>{{ $remark }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>This is an automated email. Please do not reply directly to this message.</p>
        </div>
    </div>
</body>
</html>
