<!DOCTYPE html>
<html>
<head>
    <title>Follow-Up on TimeTec HR Cloud Solutions</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Dear <strong>{{ $lead['lastName'] }}</strong>,</p>

    <p>I hope this email finds you well. I wanted to quickly follow up to ensure that my previous emails about our HR Cloud Solutions at TimeTec reached you.</p>

    <p>The <strong>FREE Biometric Device promotion</strong> is still available, and I’d love to discuss how we can streamline your HR processes with solutions tailored to your needs.</p>

    <p>Would it make sense to schedule a quick demo? If you're not interested, no worries—just let me know, and I won’t follow up further.</p>

    <p>Best regards,</p>
    <p>
        {{ $leadOwnerName }}<br>
        {{ $lead['position'] ?? 'Position Not Specified' }}<br>
        TimeTec Cloud Sdn Bhd<br>
        Office: +603-8070 9933<br>
        WhatsApp: {{ $lead['leadOwnerMobileNumber'] ?? 'N/A' }}
    </p>
</body>
</html>
