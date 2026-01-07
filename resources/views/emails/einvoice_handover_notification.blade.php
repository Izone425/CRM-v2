<!DOCTYPE html>
<html>
<head>
    <title>New E-Invoice Registration</title>
</head>
<body>
    <p>Hi Auni,<br>
    You have received new E-Invoice Registration from TimeTec HR CRM.</p>

    <p><strong>SalesPerson:</strong> {{ strtoupper($salesperson) }}<br>
    <strong>Company Name:</strong> <a href="{{ $lead_url }}" target="_blank" style="color: #2563eb; text-decoration: none;">{{ $company_name }}</a></p>

    <p>Best regards,<br>
    TimeTec HR CRM System</p>
</body>
</html>
