<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Hardware Handover Installation Notification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: aliceblue;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-bottom: 3px solid #007bff;
        }
        .content {
            padding: 20px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #007bff;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        .device-item {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
            padding: 10px;
            margin-bottom: 15px;
        }
        .device-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Hardware Installation Notification</h1>
            <p>Handover ID: {{ $emailData['handover_id'] }}</p>
            <p>Company: {{ $emailData['company_name'] }}</p>
        </div>

        <div class="content">
            <div class="section">
                <div class="section-title">CATEGORY: {{ $emailData['installation_type'] }}</div>
                <table>
                    <tr>
                        <td width="40%"><strong>TECHNICIAN NAME:</strong></td>
                        <td>{{ $emailData['technician_name'] }}</td>
                    </tr>
                    <tr>
                        <td><strong>PIC NAME:</strong></td>
                        <td>{{ $emailData['pic_details']['name'] }}</td>
                    </tr>
                    <tr>
                        <td><strong>PIC HP NUMBER:</strong></td>
                        <td>{{ $emailData['pic_details']['phone'] }}</td>
                    </tr>
                    <tr>
                        <td><strong>PIC EMAIL:</strong></td>
                        <td>{{ $emailData['pic_details']['email'] }}</td>
                    </tr>
                    <tr>
                        <td><strong>APPOINTMENT DATE:</strong></td>
                        <td>{{ $emailData['appointment_details']['date'] }}</td>
                    </tr>
                    <tr>
                        <td><strong>APPOINTMENT TIME:</strong></td>
                        <td>{{ $emailData['appointment_details']['start_time'] }} - {{ $emailData['appointment_details']['end_time'] }}</td>
                    </tr>
                </table>
            </div>

            <div class="section">
                <div class="section-title">DEVICES FOR INSTALLATION</div>

                @foreach($emailData['devices'] as $index => $device)
                <div class="device-item">
                    <div class="device-title">DEVICE {{ $index + 1 }} / {{ $device['device_type'] }} / {{ $device['serial_number'] }}</div>
                    <p><strong>INSTALLATION ADDRESS:</strong> {{ $device['installation_address'] }}</p>

                    @if(!empty($device['attachments']))
                        <p><strong>ATTACHMENTS:</strong></p>
                        <ul>
                            @foreach($device['attachments'] as $attachment)
                                <li><a href="{{ url('storage/' . $attachment) }}">{{ basename($attachment) }}</a></li>
                            @endforeach
                        </ul>
                    @else
                        <p><strong>ATTACHMENTS:</strong> No attachments uploaded</p>
                    @endif
                </div>
                @endforeach
            </div>
        </div>

        <div class="footer">
            <p>This is an automated notification from TimeTec CRM System.</p>
        </div>
    </div>
</body>
</html>
