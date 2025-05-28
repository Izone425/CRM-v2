<!DOCTYPE html>
<html>
<head>
    <title>BD Referral Lead Status Update</title>
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
            background-color: #338cf0;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            padding: 20px;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
        }
        .status-closed {
            background-color: #4CAF50;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
            display: inline-block;
        }
        .status-lost {
            background-color: #f44336;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-weight: bold;
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
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>BD Referral Lead Status Update</h2>
        </div>
        <div class="content">
            <p>Dear {{ $emailContent['recipient_name'] }},</p>

            <p>This is to inform you that a BD Referral Program lead has been marked as
                <span class="{{ $emailContent['lead']['status'] === 'Closed' ? 'status-closed' : 'status-lost' }}">
                    {{ $emailContent['lead']['status'] }}
                </span>
            </p>

            <h3>Lead Details:</h3>
            <table>
                <tr>
                    <th>Lead ID</th>
                    <td>{{ $emailContent['lead']['id'] }}</td>
                </tr>
                <tr>
                    <th>Company Name</th>
                    <td>{{ $emailContent['lead']['company_name'] }}</td>
                </tr>
                <tr>
                    <th>Contact Person</th>
                    <td>{{ $emailContent['lead']['contact_person'] }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $emailContent['lead']['email'] }}</td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td>{{ $emailContent['lead']['phone'] }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <span class="{{ $emailContent['lead']['status'] === 'Closed' ? 'status-closed' : 'status-lost' }}">
                            {{ $emailContent['lead']['status'] }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Date Closed</th>
                    <td>{{ $emailContent['lead']['closed_date'] }}</td>
                </tr>
                <tr>
                    <th>Remarks</th>
                    <td>{{ $emailContent['remarks'] }}</td>
                </tr>
                <tr>
                    <th>Closed By</th>
                    <td>{{ $emailContent['closed_by'] }}</td>
                </tr>
            </table>

            <p>If you need any additional information, please contact the HR team.</p>

            <p>Thank you,<br>
            TimeTec CRM</p>
        </div>
    </div>
</body>
</html>
