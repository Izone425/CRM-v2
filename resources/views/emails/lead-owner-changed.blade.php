<!DOCTYPE html>
<html>
<head>
    <title>Changes on Lead Owner</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Hello,</p>

    {{-- Conditional logic for rejection or approval --}}
    <p>
        @if(isset($rejected) && $rejected)
            The lead owner change request for <strong>{{ $lead['company'] }}</strong> (Code: {{ $lead['lead_code'] }}) has been
            <span style="color: red;"><strong>rejected</strong></span>.
        @else
            The lead <strong>{{ $lead['company'] }}</strong> (Code: {{ $lead['lead_code'] }}) has been reassigned:
        @endif
    </p>

    <ul>
        <li><strong>Previous Owner:</strong> {{ $previousOwnerName }}</li>
        <li><strong>New Owner:</strong> {{ $newOwnerName }}</li>
        <li><strong>Phone:</strong> {{ $lead['phone'] }}</li>
        <li><strong>Email:</strong> {{ $lead['email'] }}</li>
    </ul>

    {{-- Only show reason if it's a rejection --}}
    @if(isset($rejected) && $rejected)
        <p><strong>Reason:</strong> {{ $reason }}</p>
    @endif

    <p>Please take note of this change in the CRM system.</p>

    <p>Regards,<br />
    TimeTec HR Team</p>
</body>
</html>
