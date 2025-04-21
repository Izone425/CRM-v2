<!DOCTYPE html>
<html>
<head>
    <title>Susulan Terakhir: Penyelesaian HR Berasaskan Awan TimeTec</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Salam Sejahtera <strong>{{ $lead['lastName'] }}</strong>,</p>

    <p>Saya berharap segala urusan anda berjalan dengan lancar. Ini merupakan e-mel susulan terakhir daripada saya. Bagi memastikan sebarang maklumat yang dikongsikan sebelum ini tidak terlepas pandang, dan dalam masa yang sama tidak mengganggu ruang peti masuk anda.</p>

    <p>Sekiranya waktu ini kurang sesuai untuk berbincang mengenai Penyelesaian HR Berasaskan Awan daripada kami, mohon maklumkan masa yang lebih bersesuaian, atau individu lain yang lebih berkaitan untuk saya hubungi.</p>

    <p>Sekiranya anda atau syarikat anda ingin meneliti semula perkara ini pada masa akan datang, saya sedia untuk berhubung semula dengan anda.</p>

    <p>Terima kasih atas masa yang diluangkan, dan saya ucapkan selamat maju jaya.</p>

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
