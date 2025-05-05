<!DOCTYPE html>
<html>
<head>
    <title>Request to change Lead Owner</title>
</head>
<body>
    <p>Dear Faiz,</p>
    <p>This is an automated email.</p>

    <p>There is a lead requested to change lead owner, feel free to take action</p>

    <ul>
        <li><strong>Name:</strong> {{ $lead['lastName'] }}</li>
        <li><strong>Company:</strong> {{ $lead['company'] }}</li>
        <li><strong>Company Size:</strong> {{ $lead['companySize'] }}</li>
        <li><strong>Phone:</strong> {{ $lead['phone'] }}</li>
        <li><strong>Email:</strong> {{ $lead['email'] }}</li>
        <li><strong>Country:</strong> {{ $lead['country'] }}</li>
    </ul>

    {{-- <p><strong>Product:</strong> {{ $formatted_products }}</p> --}}
    {{-- <p>{{ $lead['solutions'] }}</p> --}}
</body>
</html>
