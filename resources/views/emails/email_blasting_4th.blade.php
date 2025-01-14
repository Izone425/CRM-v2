<!DOCTYPE html>
<html>
<head>
    <title>Final Follow-Up on TimeTec HR Cloud Solutions</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Dear <strong>{{ $lead['lastName'] }}</strong>,</p>

    <p>I hope all is well. I’m reaching out with this final email to ensure I'm not cluttering your inbox.</p>

    <p>If now isn’t the right time for a conversation about our HR Cloud Solutions, feel free to let me know when might be a better time, or if there's someone else I should be in touch with.</p>

    <p>Should you or your company revisit this down the road, I’d be happy to reconnect whenever it becomes a priority.</p>

    <p>Thank you for your time, and I wish you continued success.</p>

    <p>Best regards,</p>
    <p>
        {{ $leadOwnerName }}<br>
        {{ $lead['department'] ?? 'Department Not Specified' }}<br>
        TimeTec Cloud Sdn Bhd<br>
        Office: +603-8070 9933<br>
        WhatsApp: {{ $lead['leadOwnerMobileNumber'] ?? 'N/A' }}
    </p>
</body>
</html>
