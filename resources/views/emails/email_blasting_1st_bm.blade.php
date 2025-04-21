<!DOCTYPE html>
<html>
<head>
    <title>Introduction to TimeTec HR Cloud Solutions</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <p>Salam Sejahtera <strong>{{ $lead['lastName'] }}</strong>,</p>

    <p>Saya <strong>{{ $leadOwnerName }}</strong> dari TimeTec. Kami telah menerima informasi anda daripada iklan kami dan ingin memperkenalkan anda kepada Penyelesaian HR Berasaskan Awan kami yang direka khas untuk memudahkan pengurusan tugasan HR anda.</p>

    <ul>
        <li><strong>Kehadiran:</strong> Pastikan rekod masa yang tepat</li>
        <li><strong>Penggajian:</strong> Urus pembayaran gaji tepat pada masanya</li>
        <li><strong>Tuntutan:</strong> Percepatkan proses tuntutan</li>
        <li><strong>Cuti:</strong> Mudahkan pengurusan cuti</li>
    </ul>

    <p>Sebagai sebahagian daripada promosi istimewa kami, anda berpeluang menerima <strong>Peranti Biometrik PERCUMA</strong> dengan langganan modul Kehadiran Masa kami (tertakluk kepada terma dan syarat).</p>

    <p>Jom sertai sesi demo pada waktu yang paling sesuai dengan anda dan lihat bagaimana penyelesaian kami boleh memberi manfaat kepada organisasi anda, termasuk cara untuk menebus Peranti Biometrik PERCUMA.</p>

    <p>Bagi maklumat lanjut, anda boleh rujuk risalah kami 
        <a href="https://www.timeteccloud.com/download/brochure/TimeTecHR-E.pdf" target="_blank">DISINI</a>
    </p>

    <p>Yang Benar,</p>
    <p>{{ $leadOwnerName }}<br>
        {{ $lead['position'] }}<br>
        TimeTec Cloud Sdn Bhd<br>
        Pejabat: +603-8070 9933<br>
        WhatsApp: {{ $lead['leadOwnerMobileNumber'] ?? 'N/A' }}
    </p>
</body>
</html>
