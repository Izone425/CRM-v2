<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New Software Handover</title>
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; }
        .header { background: #338cf0; color: #fff; padding: 16px; border-radius: 6px 6px 0 0; text-align: center; }
        .content { background: #fff; border: 1px solid #eee; border-top: none; border-radius: 0 0 6px 6px; padding: 24px; }
        .row { margin-bottom: 12px; }
        .label { font-weight: bold; display: inline-block; min-width: 160px; }
        .button { background: #338cf0; color: #fff; padding: 10px 18px; border-radius: 4px; text-decoration: none; font-weight: bold; display: inline-block; margin-top: 18px; }
        .footer { margin-top: 32px; font-size: 12px; color: #888; text-align: center; }
        .button-container {
            display: block;
            text-align: center;
            margin-top: 18px;
        }
        .button {
            background: #2563EB;
            color: #fff;
            padding: 10px 18px;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: background 0.2s;
        }
        .button:hover {
            background: #174ea6;
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>NEW SOFTWARE HANDOVER ID {{ $sw_id }}</h2>
        </div>
        <div class="content">
            <div class="row"><span class="label">DATE:</span> {{ $date }}</div>
            <div class="row"><span class="label">SW ID:</span> {{ $sw_id }}</div>
            <div class="row"><span class="label">SALESPERSON:</span> {{ $salesperson }}</div>
            <div class="row"><span class="label">COMPANY NAME:</span> {{ $company_name }}</div>
        </div>
        <div class="footer">
            This is an automated notification from TimeTec CRM.<br>
            &copy; {{ date('Y') }} TimeTec Cloud Sdn Bhd. All rights reserved.
        </div>
    </div>
</body>
</html>
