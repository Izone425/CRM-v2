<!DOCTYPE html>
<html>
<head>
    <title>Introduction to TimeTec HR Cloud Solutions</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Dear <strong>{{ $lead['lastName'] }}</strong>,</p>

    <p>I hope this email finds you well. I’m <strong>{{ $leadOwnerName }}</strong> from TimeTec. We received your interest in our advertisement and wanted to introduce you to our HR Cloud Solutions, designed to streamline your HR tasks:</p>

    <ul>
        <li><strong>Attendance:</strong> Ensure accurate time tracking</li>
        <li><strong>Payroll:</strong> Ensure timely payroll management</li>
        <li><strong>Claim:</strong> Speed up claims processing</li>
        <li><strong>Leave:</strong> Simplify leave management</li>
    </ul>

    <p>As part of a special promotion, you can receive a <strong>FREE Biometric Device</strong> with your subscription to our Time Attendance module.</p>

    <p>Let’s schedule a demo at your convenience to explore how our tailored solutions can benefit your organisation and provide you with details on how to claim your FREE Biometric Device.</p>

    <p>To help you get started, please review our brochure
        <a href="https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf" target="_blank">here</a>
    </p>

    <p>Best regards,</p>
    <p>{{ $leadOwnerName }}<br>
        {{ $lead['department']}}<br>
        TimeTec Cloud Sdn Bhd<br>
        Office: +603-8070 9933<br>
        WhatsApp: {{ $lead['leadOwnerMobileNumber'] ?? 'N/A' }}
    </p>
</body>
</html>
