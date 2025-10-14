{{-- filepath: /var/www/html/timeteccrm/resources/views/emails/installation-appointment.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Installation Appointment</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e74c3c;
        }
        .logo {
            color: #e74c3c;
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .content {
            margin-bottom: 20px;
        }
        .section {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #e74c3c;
        }
        .section-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 10px;
            color: #e74c3c;
            text-transform: uppercase;
        }
        .info-row {
            margin-bottom: 8px;
        }
        .label {
            font-weight: bold;
            display: inline-block;
            min-width: 150px;
        }
        .device-list {
            background-color: #fff;
            padding: 15px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            margin-top: 10px;
        }
        .device-item {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .device-item:last-child {
            border-bottom: none;
        }
        .greeting {
            margin-bottom: 20px;
            font-size: 16px;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content">
            <div class="greeting">
                <strong>Dear Customer</strong><br>
                Good day to you
            </div>

            <p>Installation details as per below:</p>

            <div class="section">
                <div class="section-title">Technician Details</div>
                <div class="info-row">
                    <span class="label">Technician Name:</span> {{ $technician_name }}
                </div>
                <div class="info-row">
                    <span class="label">Technician Hp Number:</span> {{ $technician_phone ?: '017-380 4549' }}
                </div>
            </div>

            <div class="section">
                <div class="section-title">Customer Details</div>
                <div class="info-row">
                    <span class="label">Company Name:</span> {{ $company_name }}
                </div>
                <div class="info-row">
                    <span class="label">PIC Name:</span> {{ $pic_name }}
                </div>
                <div class="info-row">
                    <span class="label">PIC Hp Number:</span> {{ $pic_phone }}
                </div>
                <div class="info-row">
                    <span class="label">PIC Email:</span> {{ $pic_email }}
                </div>
            </div>

            <div class="section">
                <div class="section-title">Installation Details</div>
                <div class="info-row">
                    <span class="label">Installation Date:</span> {{ $appointment_date }}
                </div>
                <div class="info-row">
                    <span class="label">Installation Time:</span> {{ $installation_time }}
                </div>
                <div class="info-row">
                    <span class="label">Installation Address:</span> {{ $installation_address }}
                </div>

                <div style="margin-top: 15px;">
                    <span class="label">Installation Device:</span>
                    <div class="device-list">
                        @if($devices['tc10'] > 0)
                            <div class="device-item">
                                <strong>TC10 = {{ $devices['tc10'] }} UNIT{{ $devices['tc10'] > 1 ? 'S' : '' }}</strong>
                            </div>
                        @endif

                        @if($devices['face_id5'] > 0)
                            <div class="device-item">
                                <strong>FACE ID 5 = {{ $devices['face_id5'] }} UNIT{{ $devices['face_id5'] > 1 ? 'S' : '' }}</strong>
                            </div>
                        @endif

                        <div class="device-item">
                            <strong>TC20 = {{ $devices['tc20'] > 0 ? $devices['tc20'] . ' UNIT' . ($devices['tc20'] > 1 ? 'S' : '') : 'N/A' }}</strong>
                        </div>

                        <div class="device-item">
                            <strong>FACE ID 6 = {{ $devices['face_id6'] > 0 ? $devices['face_id6'] . ' UNIT' . ($devices['face_id6'] > 1 ? 'S' : '') : 'N/A' }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>This is an automated email from TimeTec CRM System.</p>
            <p>Reference ID: {{ $hardware_id }}</p>
        </div>
    </div>
</body>
</html>
