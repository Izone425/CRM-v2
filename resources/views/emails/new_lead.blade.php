<!DOCTYPE html>
<html>
<head>
    <title>New Lead Coming</title>
</head>
<body>
    <p>Dear {{ $leadOwnerName }},</p>
    <p>This is an automated email.</p>

    <p>We have a new lead created by {{ $lead['creator'] }}</p>
    <p>Please follow up on the leads below:</p>

    <ul>
        <li><strong>Landing Page:</strong> CRM</li>
        <li><strong>Name:</strong> {{ $lead['lastName'] }}</li>
        <li><strong>Company:</strong> {{ $lead['company'] }}</li>
        <li><strong>Company Size:</strong> {{ $lead['companySize'] }}</li>
        <li><strong>Phone:</strong> {{ $lead['phone'] }}</li>
        <li><strong>Email:</strong> {{ $lead['email'] }}</li>
        <li><strong>Country:</strong> {{ $lead['country'] }}</li>
        <li><strong>Lead Source:</strong> {{ $lead['lead_code'] }}</li>
    </ul>

    <p><strong>Product:</strong> {{ $formatted_products }}</p>
    {{-- <p>{{ $lead['solutions'] }}</p> --}}
</body>
</html>
