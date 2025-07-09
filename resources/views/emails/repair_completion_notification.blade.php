<!-- filepath: /var/www/html/timeteccrm/resources/views/emails/repair_completion_notification.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Repair Completion Notification</title>
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
        }
        .header {
            background-color: #28a745; /* GREEN COLOR */
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px 5px 0 0;
            border-bottom: 5px solid #218838;
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
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        .button:hover {
            background-color: #218838;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>REPAIR HANDOVER ID {{ $emailContent['repair_id'] }} : COMPLETED</h2>
        </div>

        <div class="content">
            <p class="greeting">Dear All,</p>

            <p>This is to inform you that a repair handover has been completed.</p>

            <table>
                <tr>
                    <th>Company Name</th>
                    <td>{{ $emailContent['company']['name'] }}</td>
                </tr>
                <tr>
                    <th>Repair Handover ID</th>
                    <td>{{ $emailContent['repair_id'] }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{{ $emailContent['status'] }}</td>
                </tr>
                <tr>
                    <th>Completed by</th>
                    <td>{{ $emailContent['completed_by'] }}</td>
                </tr>
                <tr>
                    <th>Completed Date</th>
                    <td>{{ $emailContent['completed_date'] }}</td>
                </tr>
            </table>

            @if(isset($emailContent['pdf_url']))
                <div class="button-container">
                    <a href="{{ $emailContent['pdf_url'] }}" class="button" target="_blank">
                        View Repair Handover Form
                    </a>
                </div>
            @endif

            <p>Thank you,<br>
            TimeTec CRM</p>
        </div>
    </div>
</body>
</html>
