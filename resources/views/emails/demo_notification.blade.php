<!DOCTYPE html>
<html>
<head>
    <title>Demo Session Details</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Hi {{ $lead['lastName'] }},</p>

    <p>Good day to you. As per our phone call discussion, our {{ strtolower($lead['demo_type']) }} session has been scheduled. Kindly find below the details of our salesperson who will be attending to your inquiries:</p>

    <p><strong>Salesperson Details:</strong></p>
    <ul>
        <li><strong>Salesperson:</strong> {{ $lead['salespersonName'] }}</li>
        <li><strong>Phone No:</strong> {{ $lead['salespersonPhone'] }}</li>
        <li><strong>Email:</strong> {{ $lead['salespersonEmail'] }}</li>
    </ul>

    <p><strong>Leads Details:</strong></p>
    <ul>
        <li><strong>Company:</strong> {{ $lead['company'] }}</li>
        <li><strong>Phone No:</strong> {{ $lead['phone'] }}</li>
        <li><strong>PIC:</strong> {{ $lead['pic'] }}</li>
        <li><strong>Email:</strong> {{ $lead['email'] }}</li>
    </ul>

    <p><strong>Demo Session Details:</strong></p>
    <ul>
        <li><strong>Demo Type:</strong> {{ $lead['demo_type'] }}</li>
        <li><strong>Appointment Type:</strong> {{ $lead['appointment_type'] }}</li>
        <li>
            <strong>Demo Date / Time:</strong>
            {{ \Carbon\Carbon::createFromFormat('d/m/Y', $lead['date'])->format('j F Y') }}
            {{ \Carbon\Carbon::parse($lead['startTime'])->format('h:iA') }} -
            {{ \Carbon\Carbon::parse($lead['endTime'])->format('h:iA') }}
        </li>
        @if($lead['appointment_type'] === 'ONLINE' && !empty($lead['meetingLink']))
            <li>
                <strong>Meeting Link:</strong>
                <a href="{{ $lead['demo_type'] === 'WEBINAR DEMO' ? $lead['salespersonMeetingLink'] : $lead['meetingLink'] }}" target="_blank">
                    {{ $lead['demo_type'] === 'WEBINAR DEMO' ? $lead['salespersonMeetingLink'] : $lead['meetingLink'] }}
                </a>
            </li>
        @elseif($lead['appointment_type'] === 'ONSITE')
            <li><strong>Demo Location:</strong> ONSITE DEMO AT PROSPECT OFFICE</li>
        @endif
    </ul>

    <p>Best regards,</p>
    <p>
        {{ $leadOwnerName }}<br>
        {{ $lead['position'] }}<br>
        TimeTec Cloud Sdn Bhd<br>
        Office Number: +603-8070 9933<br>
        HP Number: {{ $lead['leadOwnerMobileNumber'] }}
    </p>
    <p>
        <img src="{{ asset('img/refer-earn.png') }}" alt="Refer & Earn"
             style="width: 100%; max-width: 600px; display: block; margin-top: 20px;">
    </p>
</body>
</html>
