<!DOCTYPE html>
<html>
<head>
    <title>Webinar Demo Session Details</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Hi {{ $lead['lastName'] }},</p>

    <p>Good day to you. As per our phone call discussion, our webinar demo session has been scheduled. Kindly find below the details of our salesperson who will be attending to your inquiries:</p>

    <p><strong>Salesperson Details:</strong></p>
    <ul>
        <li><strong>Salesperson:</strong> {{ $lead['salespersonName'] }}</li>
        <li><strong>Phone No:</strong> {{ $lead['salespersonPhone'] }}</li>
        <li><strong>Email:</strong> {{ $lead['salespersonEmail'] }}</li>
    </ul>

    <p><strong>Company & Contact Details:</strong></p>
    <ul>
        <li><strong>Company:</strong> {{ $lead['company'] }}</li>
        <li><strong>Phone No:</strong> {{ $lead['phone'] }}</li>
        <li><strong>PIC:</strong> {{ $lead['pic'] }}</li>
        <li><strong>Email:</strong> {{ $lead['email'] }}</li>
    </ul>

    <p><strong>Demo Session Details:</strong></p>
    <ul>
        <li><strong>Demo Type:</strong> {{ $lead['demo_type'] }}</li>
        <li><strong>Demo Date / Time:</strong> {{ $lead['date'] }} {{ $lead['startTime']->format('h:iA') }} - {{ $lead['endTime']->format('h:iA') }}</li>
        <li><strong>Meeting Link:</strong> <a href="{{ $lead['meetingLink'] }}" target="_blank">{{ $lead['meetingLink'] }}</a></li>
    </ul>

    <p>Best regards,</p>
    <p>
        {{ $leadOwnerName }}<br>
        {{ $lead['department'] }}<br>
        TimeTec Cloud Sdn Bhd<br>
        Office: +603-8070 9933<br>
        WhatsApp: {{ $lead['leadOwnerMobileNumber'] }}
    </p>
</body>
</html>
