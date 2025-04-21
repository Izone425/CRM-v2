<!DOCTYPE html>
<html>
<head>
    <title>Susulan: Penyelesaian HR Berasaskan Awan TimeTec</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Salam Sejahtera <strong>{{ $lead['lastName'] }}</strong>,</p>

    <p>Saya harap anda berada dalam keadaan baik!</p>

    <p>Saya ingin membuat sedikit susulan berkenaan e-mel saya sebelum ini mengenai Penyelesaian HR Berasaskan Awan daripada TimeTec. Saya tidak pasti sama ada anda berkesempatan membacanya, namun saya ingin memastikan anda tidak terlepas tawaran promosi istimewa yang kami sedang tawarkan.</p>

    <p>Penyelesaian kami direka untuk memudahkan dan memperkemas proses HR anda, termasuk:</p>

    <ul>
        <li><strong>Kehadiran:</strong> Pastikan rekod masa yang tepat</li>
        <li><strong>Penggajian:</strong> Urus pembayaran gaji tepat pada masanya</li>
        <li><strong>Tuntutan:</strong> Percepatkan proses tuntutan</li>
        <li><strong>Cuti:</strong> Mudahkan pengurusan cuti</li>
    </ul>

    <p>Seperti yang dimaklumkan, anda berpeluang menerima <strong>Peranti Biometrik PERCUMA</strong> dengan setiap langganan modul Sistem Kehadiran kami, sebahagian daripada promosi istimewa ini (tertakluk kepada terma dan syarat).</p>

    <p>Saya juga ingin menjadualkan sesi demo pada waktu yang sesuai dengan anda bagi mempamerkan bagaimana penyelesaian ini dapat memberi manfaat kepada organisasi anda serta berkongsi maklumat lanjut mengenai promosi tersebut.</p>

    <p>Anda boleh maklumkan masa yang sesuai, dan saya akan aturkan mengikut keselesaan anda!</p>

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
