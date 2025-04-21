<!DOCTYPE html>
<html>
<head>
    <title>Notis Pembatalan Mesyuarat</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Hai {{ $lead['lastName'] }},</p>

    <p>Saya ingin memaklumkan bahawa mesyuarat kita yang dijadualkan pada
        {{ Carbon\Carbon::createFromFormat('d/m/Y', $lead['date'])->format('j F Y') }}
        pada pukul {{ Carbon\Carbon::parse($lead['startTime'])->format('h:iA') }} telah dibatalkan.</p>

    <p>Jika anda ingin menjadualkan semula mesyuarat ini, sila maklumkan masa yang sesuai untuk anda.</p>

    <p>Yang benar,</p>
    <p>
        {{ $lead['salespersonName'] }}<br>
    </p>
</body>
</html>
