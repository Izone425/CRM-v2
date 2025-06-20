<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Repair Ticket Notification</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: #f7f7f7;
        }
        .container {
            width: 100%;
            max-width: 650px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .header {
            background-color: #2b374f;
            padding: 25px;
            text-align: center;
            border-bottom: 5px solid #e74c3c;
        }
        .header h2 {
            margin: 0;
            color: #ffffff;
            font-size: 22px;
            font-weight: 500;
        }
        .content {
            padding: 30px;
            background-color: #1a1a1a1c;
        }
        .ticket-id {
            font-size: 20px;
            font-weight: 600;
            color: #e74c3c;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eaeaea;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #2b374f;
            font-size: 16px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .details {
            background-color: #f9f9f9;
            border-radius: 6px;
            padding: 15px;
        }
        .detail-row {
            margin-bottom: 8px;
            display: flex;
            flex-wrap: wrap;
        }
        .label {
            font-weight: 500;
            width: 140px;
            color: #555;
        }
        .value {
            flex: 1;
            color: #333;
        }
        .remark {
            background-color: #f2f6fc;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #e74c3c;
            border-radius: 4px;
        }
        .remark-text {
            margin-bottom: 15px;
        }
        .attachments {
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px dashed #d9d9d9;
        }
        .attachment-item {
            margin: 6px 0;
        }
        .attachment-item a {
            color: #2c7be5;
            text-decoration: none;
            font-weight: 500;
        }
        .attachment-item a:hover {
            text-decoration: underline;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
            font-size: 12px;
            color: #777;
            text-align: center;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 15px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 14px;
            transition: background-color 0.3s;
        }
        .btn:hover {
            background-color: #c0392b;
        }
        .logo {
            margin-bottom: 15px;
        }
        .logo img {
            height: 40px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <!-- You can add your company logo here -->
                <!-- <img src="{{ asset('images/timetec-logo.png') }}" alt="TimeTec Logo"> -->
            </div>
            <h2>NEW REPAIR TICKET NOTIFICATION</h2>
        </div>

        <div class="content">
            <div class="ticket-id">
                Repair Ticket: {{ $emailContent['repair_id'] }}
            </div>

            <div class="section">
                <div class="section-title">Contact Information</div>
                <div class="details">
                    <div class="detail-row">
                        <span class="label">Company Name:</span>
                        <span class="value">{{ $emailContent['company']['name'] }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Contact Name:</span>
                        <span class="value">{{ $emailContent['pic']['name'] }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Contact Phone:</span>
                        <span class="value">{{ $emailContent['pic']['phone'] }}</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Contact Email:</span>
                        <span class="value">{{ $emailContent['pic']['email'] }}</span>
                    </div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Device Details</div>
                <div class="details">
                    @if(isset($emailContent['devices']) && count($emailContent['devices']) > 0)
                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; grid-auto-flow: row;">
                            @foreach($emailContent['devices'] as $index => $device)
                            <div style="background-color: #f2f6fc; padding: 15px; border-radius: 6px; border-left: 3px solid #e74c3c; margin-bottom: 15px;">
                                <div class="detail-row" style="margin-bottom: 10px;">
                                    <span class="label" style="width: 100%; margin-bottom: 5px; color: #2b374f; font-weight: 600;">Device {{ $index + 1 }}:</span>
                                    <span class="value" style="width: 100%;">{{ $device['device_model'] }}</span>
                                    <span class="label" style="width: 100%; margin-bottom: 5px; color: #2b374f; font-weight: 600;">Serial Number:</span>
                                    <span class="value" style="width: 100%;">{{ $device['device_serial'] }}</span>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <!-- Fallback for backward compatibility -->
                        <div style="background-color: #f2f6fc; padding: 15px; border-radius: 6px; border-left: 3px solid #e74c3c;">
                            <div class="detail-row" style="margin-bottom: 10px;">
                                <span class="label">Device Model:</span>
                                <span class="value">{{ $emailContent['device']['model'] ?? 'N/A' }}</span>
                                <span class="label">Serial Number:</span>
                                <span class="value">{{ $emailContent['device']['serial'] ?? 'N/A' }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            @if(count($emailContent['remarks']) > 0)
            <div class="section">
                <div class="section-title">Repair Remarks</div>
                <div class="details" style="padding: 5px 15px;">
                    @foreach($emailContent['remarks'] as $index => $remark)
                        <div class="remark">
                            <div class="remark-text">
                                <strong>Remarks {{ $index + 1 }}:</strong> <span style="text-transform: uppercase;">{{ $remark['text'] }}</span>
                            </div>

                            @if(count($remark['attachments']) > 0)
                                <div class="attachments">
                                    <strong>Supporting Documents:</strong>
                                    @foreach($remark['attachments'] as $attachment)
                                        <div class="attachment-item">
                                            <a href="{{ $attachment['url'] }}" target="_blank">
                                                📎 {{ $attachment['filename'] }}
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
