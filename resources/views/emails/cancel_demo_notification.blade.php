<!DOCTYPE html>
<html>
<head>
    <title>Meeting Cancellation Notice</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Hi {{ $lead['lastName'] }},</p>

    <p>I wanted to inform you that our meeting scheduled for
        {{ Carbon\Carbon::createFromFormat('d/m/Y', $lead['date'])->format('j F Y') }}
        at {{ Carbon\Carbon::parse($lead['startTime'])->format('h:iA') }} has been canceled.</p>

    <p>If you'd like to reschedule, just let us know a convenient time.</p>

    <p>Best regards,</p>
    <p>
        {{ $lead['salespersonName'] }}<br>
    </p>
</body>
</html>
