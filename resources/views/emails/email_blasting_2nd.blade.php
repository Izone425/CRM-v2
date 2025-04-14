<!DOCTYPE html>
<html>
<head>
    <title>Introduction to TimeTec HR Cloud Solutions</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Dear <strong>{{ $lead['lastName'] }}</strong>,</p>

    <p>I hope this email finds you well. I wanted to follow up on my previous email regarding our HR Cloud Solutions at TimeTec. I’m not sure if you had a chance to review it yet, but I wanted to make sure you didn’t miss out on our special promotion.</p>

    <p>Our solutions are designed to simplify and streamline your HR processes, including:</p>

    <ul>
        <li><strong>Attendance:</strong> Ensure accurate time tracking</li>
        <li><strong>Payroll:</strong> Ensure timely payroll management</li>
        <li><strong>Claim:</strong> Speed up claims processing</li>
        <li><strong>Leave:</strong> Simplify leave management</li>
    </ul>

    <p>As mentioned, with a subscription to our Time Attendance module, you can receive a <strong>FREE Biometric Device</strong> as part of our special promotion (terms and conditions apply).</p>

    <p>I’d love to schedule a demo at your convenience to show you how these solutions can benefit your organization and provide more details on the promotion.</p>

    <p>Please let me know if there’s a time that works best for you.</p>

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
