<!-- filepath: /var/www/html/timeteccrm/resources/views/emails/repair_status_changed.blade.php -->
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $repair_id }} | {{ $company_name }}</title>
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
            background-color: #2b374f;
            color: white;
            padding: 15px;
            text-align: center;
            border-radius: 5px 5px 0 0;
            border-bottom: 5px solid #e74c3c;
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
            background-color: #e74c3c;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }
        .button:hover {
            background-color: #c0392b;
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
        .device-section {
            margin-bottom: 15px;
        }
        .device-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
            background-color: #f2f2f2;
            padding: 8px;
            border-left: 3px solid #e74c3c;
        }
        .device-item {
            margin-bottom: 10px;
            padding: 10px;
            border-left: 3px solid #e74c3c;
            background-color: #f9f9f9;
        }
        .remark-box {
            margin-bottom: 10px;
            padding: 10px;
            border-left: 3px solid #e74c3c;
            background-color: #f9f9f9;
        }
        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            color: white;
            background-color: #28a745;
        }
        .status.accepted { background-color: #e74c3c; }
        .spare-parts-list {
            margin-top: 15px;
        }
        .spare-part-item {
            margin-bottom: 5px;
            padding: 5px;
            border-bottom: 1px solid #ddd;
        }
        .attachments {
            margin-top: 8px;
        }
        .attachment-button {
            background-color: #2b374f;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            display: inline-block;
            margin-right: 5px;
            margin-bottom: 5px;
            width: 75px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>REPAIR ASSIGNMENT ACCEPTED</h2>
        </div>

        <div class="content">
            <p class="greeting">Dear All,</p>

            <p>This is to inform you that you have received a new repair assignment.</p>

            <table>
                <tr>
                    <th>Company Name</th>
                    <td>{{ $company_name }}</td>
                </tr>
                <tr>
                    <th>Repair Handover ID</th>
                    <td>{{ $repair->formatted_handover_id }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{{ $status }}</td>
                </tr>
                <tr>
                    <th>Submitted by</th>
                    <td>{{ $submitted_by ?? 'Admin User' }}</td>
                </tr>
                <tr>
                    <th>Submitted Date</th>
                    <td>{{ $submitted_at ?? now()->format('d M Y, h:i A') }}</td>
                </tr>
                <tr>
                    <th>Accepted By</th>
                    <td>{{ $technician }}</td>
                </tr>
                <tr>
                    <th>Accepted Date</th>
                    <td>{{ $accepted_at }}</td>
                </tr>
            </table>

            <div class="button-container">
                <a href="{{ $pdf_url ?? url('/admin/repairs/view/' . $repair->id) }}" class="button" target="_blank">
                    View Repair Ticket Form
                </a>
            </div>

            <p>If you need any additional information, please contact your manager.</p>

            <p>Thank you,<br>TimeTec CRM</p>
        </div>
    </div>
</body>
</html>
