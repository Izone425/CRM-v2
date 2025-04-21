<!DOCTYPE html>
<html>
<head>
    <title>Susulan: Penyelesaian HR Berasaskan Awan TimeTec</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Salam Sejahtera <strong>{{ $lead['lastName'] }}</strong>,</p>

    <p>Saya berharap anda berada dalam keadaan sihat dan sejahtera. Saya ingin membuat susulan bagi memastikan anda telah menerima e-mel saya sebelum ini mengenai Penyelesaian HR Berasaskan Awan daripada TimeTec.</p>

    <p><strong>Promosi Peranti Biometrik PERCUMA</strong> masih berlangsung (tertakluk kepada terma dan syarat)! Dan saya berbesar hati untuk membantu anda memudahkan proses HR syarikat anda dengan penyelesaian yang disesuaikan mengikut keperluan anda.</p>

    <p>Bolehkah kita jadualkan sesi demo ringkas? Jika anda tidak berminat, tiada masalah—maklumkan sahaja kepada saya, dan saya tidak akan menghubungi anda lagi selepas ini.</p>

    <p>Yang Benar,</p>
    <p>
        {{ $leadOwnerName }}<br>
        {{ $lead['position'] ?? 'Position Not Specified' }}<br>
        TimeTec Cloud Sdn Bhd<br>
        Pejabat: +603-8070 9933<br>
        WhatsApp: {{ $lead['leadOwnerMobileNumber'] ?? 'N/A' }}
    </p>
</body>
</html>
