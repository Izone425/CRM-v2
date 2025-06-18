<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        table {
            border-collapse: collapse;
            width: 60%; /* Reduced from 100% to 80% */
            margin: 20px 0;
            max-width: 600px; /* Added max-width for better control */
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left; /* Explicitly set text alignment to left */
        }
        th {
            background-color: #f2f2f2;
            font-weight: normal;
            text-align: left; /* Ensure header text aligns left */
            width: 40%; /* Make the header column fixed width */
        }
        .contact-info {
            margin-top: 30px;
        }
        a {
            color: #0056b3;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <p>Dear Customer,</p>

    <p>We're excited to welcome you to TimeTec HR! This email confirms that your license has been successfully activated.</p>

    <p>Your license details are as follows:</p>

    <table>
        <tr>
            <th>KICK-OFF/ACTIVATION DATE</th>
            <td>{{ $emailContent['licenses']['kickOffDate'] }}</td>
        </tr>
        <tr>
            <th>BUFFER LICENSE</th>
            <td>{{ $emailContent['licenses']['bufferLicense']['start'] }} – {{ $emailContent['licenses']['bufferLicense']['end'] }}</td>
        </tr>
        <tr>
            <th>PAID LICENSE</th>
            <td>{{ $emailContent['licenses']['paidLicense']['start'] }} – {{ $emailContent['licenses']['paidLicense']['end'] }}</td>
        </tr>
        <tr>
            <th>Year Purchase</th>
            <td>{{ str_replace(' and 0 months', '', $emailContent['licenses']['paidLicense']['duration']) }}</td>
        </tr>
        <tr>
            <th>NEXT RENEWAL</th>
            <td>{{ $emailContent['licenses']['nextRenewal'] }}</td>
        </tr>
    </table>

    <p>If you have any questions or need assistance getting started, please don't hesitate to contact our support team. They're happy to help! You can reach them by phone at 03-80709933 or by email at <a href="mailto:support@timeteccloud.com">support@timeteccloud.com</a>.</p>

    <p>Thank you for choosing TimeTec HR. We look forward to a successful partnership!</p>
</body>
</html>
