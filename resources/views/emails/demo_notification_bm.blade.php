<!DOCTYPE html>
<html>
<head>
    <title>Maklumat Sesi Demo</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Hai dan Salam Sejahtera <strong>{{ $lead['lastName'] }}</strong>,</p>

    <p>Seperti yang telah dibincangkan melalui panggilan telefon, sesi demo webinar anda telah kami jadualkan.</p>

    <p>Berikut adalah maklumat wakil jualan kami yang akan membantu anda pada sesi demo kelak:</p>
    <ul>
        <li><strong>Wakil Jualan:</strong> {{ $lead['salespersonName'] }}</li>
        <li><strong>No Telefon:</strong> {{ $lead['salespersonPhone'] }}</li>
        <li><strong>E-mel:</strong> {{ $lead['salespersonEmail'] }}</li>
    </ul>

    <p><strong>Maklumat Syarikat:</strong></p>
    <ul>
        <li><strong>Syarikat:</strong> {{ $lead['company'] }}</li>
        <li><strong>No Telefon:</strong> {{ $lead['phone'] }}</li>
        <li><strong>PIC:</strong> {{ $lead['pic'] }}</li>
        <li><strong>E-mel:</strong> {{ $lead['email'] }}</li>
    </ul>

    <p><strong>Maklumat Sesi Demo:</strong></p>
    <ul>
        <li><strong>Jenis Demo:</strong> Demo Webinar Dalam Talian</li>
        <li>
            <strong>Tarikh / Masa Demo:</strong>
            {{ \Carbon\Carbon::createFromFormat('d/m/Y', $lead['date'])->format('j F Y') }}
            {{ \Carbon\Carbon::parse($lead['startTime'])->format('h:iA') }} -
            {{ \Carbon\Carbon::parse($lead['endTime'])->format('h:iA') }}
        </li>
        <li>
            <strong>Pautan Mesyuarat:</strong>
            <a href="{{ $lead['demo_type'] === 'WEBINAR DEMO' ? $lead['salespersonMeetingLink'] : $lead['meetingLink'] }}" target="_blank">
                {{ $lead['demo_type'] === 'WEBINAR DEMO' ? $lead['salespersonMeetingLink'] : $lead['meetingLink'] }}
            </a>
        </li>
    </ul>

    <p>Best regards,</p>
    <p>
        {{ $leadOwnerName }}<br>
        {{ $lead['position'] ?? 'Jabatan Tidak Dinyatakan' }}<br>
        TimeTec Cloud Sdn Bhd<br>
        Pejabat: +603-8070 9933<br>
        WhatsApp: {{ $lead['leadOwnerMobileNumber'] ?? 'N/A' }}
    </p>

    <p>
        <img src="{{ asset('img/refer-earn.png') }}" alt="Refer & Earn"
             style="width: 100%; max-width: 600px; display: block; margin-top: 20px;">
    </p>
</body>
</html>
