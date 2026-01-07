<!DOCTYPE html>
<html>
<head>
    <title>E-Invoice Registration Completed</title>
</head>
<body>
    <p><strong>Dear</strong> {{ strtoupper($salesperson_name) }},<br>

    <p><a href="{{ $lead_url }}" target="_blank" style="color: #2563eb; text-decoration: none;"><strong>{{ $company_name }}</strong></a> has been successfully registered with E-Invoice.</p>

    <p><strong>Project Code:</strong> {{ $project_code }}</p>

    <p>Thank you for your attention to this matter.</p>

    <p>Best regards,<br>
    TimeTec HR CRM System</p>
</body>
</html>
