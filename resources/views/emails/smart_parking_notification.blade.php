<!DOCTYPE html>
<html>
<head>
    <title>Product Lead Notification</title>
</head>
<body>
    <h1>Dear Parking Sales Team </h1>

    <p>This is an automated email.</p>

    <p>Please follow up on the leads below: </p>
    <p><strong>Landing Page:</strong> {{ $lead->lead_code }}</p>

    <p><strong>Name:</strong> {{ $lead->name }}</p>
    <p><strong>Company:</strong> {{ $lead->company_name }}</p>
    <p><strong>Company Size:</strong> {{ $lead->company_size }}</p>
    <p><strong>Phone:</strong> {{ $lead->phone }}</p>
    <p><strong>Email:</strong> {{ $lead->email }}</p>
    <p><strong>Country:</strong> {{ $lead->country }}</p>
    <p><strong>Products Interested In:</strong></p>
    <ul>
        @foreach($products as $product)
            <li>{{ $product }}</li>
        @endforeach
    </ul>

    <p>Please reach out to the lead to discuss further details.</p>
</body>
</html>
