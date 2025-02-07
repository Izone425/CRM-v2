<!DOCTYPE html>
<html>
<head>
    <title>New Lead Assigned</title>
</head>
<body>
    <p>Dear {{ $salespersonName->name }}, <br> This is an automated email.</p>
    <p>{{ $leadOwnerName }} has assigned the following lead to you. Please follow up on the leads below:</p>

    <ul>
        {{-- <li><strong>Landing Page:</strong> {{ $lead['referrerName'] }}</li> --}}
        <li><strong>Name:</strong> {{ strtoupper($lead['lastName']) }}</li>
        <li><strong>Company:</strong> {{ strtoupper($lead['company']) }}</li>
        <li><strong>Company Size:</strong> {{ $lead['companySize'] }}</li>
        <li><strong>Phone:</strong> {{ $lead['phone'] }}</li>
        <li><strong>Email:</strong> {{ $lead['email'] }}</li>
        <li><strong>Country:</strong> {{ $lead['country'] }}</li>
    </ul>

    {{-- <p><strong>Product:</strong> {{ $formatted_products }}</p> --}}
    {{-- <p>{{ $lead['solutions'] }}</p> --}}
    <p><strong>Remark:</strong><br> {!! nl2br(e(strtoupper($remark))) !!}</p>
</body>
</html>
