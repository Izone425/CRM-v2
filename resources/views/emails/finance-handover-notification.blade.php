{{-- filepath: /var/www/html/timeteccrm/resources/views/emails/finance-handover-notification.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Finance Handover Notification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <p>Hi Soon Hock,</p>
        <p>You have received a New Finance Handover.</p>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #2c3e50;">Handover Details</h3>
            @if(!empty($related_handovers))
                <p><strong>Related Hardware Handover ID:</strong> {{ implode(', ', $related_handovers) }}</p>
            @endif
            <p><strong>FN ID:</strong> {{ $fn_id }}</p>
            <p><strong>Submitted Date:</strong> {{ $submitted_date }}</p>
            <p><strong>SalesPerson:</strong> {{ $salesperson }}</p>
            <p><strong>Customer:</strong> {{ $customer }}</p>
        </div>

        <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #1976d2;">Reseller Details</h3>
            <p><strong>Company Name:</strong> {{ $reseller_company }}</p>
            <p><strong>PIC Name:</strong> {{ $pic_name }}</p>
            <p><strong>PIC HP No:</strong> {{ $pic_phone }}</p>
            <p><strong>PIC Email Address:</strong> {{ $pic_email }}</p>
        </div>

        <div style="background: #fff3e0; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #f57c00;">Attachment Details</h3>

            @if(!empty($attachment_details['invoice_by_customer']))
                <h4 style="color: #2c3e50;">Invoice by Customer</h4>
                <ul>
                    @foreach($attachment_details['invoice_by_customer'] as $file)
                        <li><a href="{{ $file['url'] }}" target="_blank">{{ $file['name'] }} (View/Download)</a></li>
                    @endforeach
                </ul>
            @endif

            @if(!empty($attachment_details['payment_by_customer']))
                <h4 style="color: #2c3e50;">Payment by Customer</h4>
                <ul>
                    @foreach($attachment_details['payment_by_customer'] as $file)
                        <li><a href="{{ $file['url'] }}" target="_blank">{{ $file['name'] }} (View/Download)</a></li>
                    @endforeach
                </ul>
            @endif

            @if(!empty($attachment_details['invoice_by_reseller']))
                <h4 style="color: #2c3e50;">Invoice by Reseller</h4>
                <ul>
                    @foreach($attachment_details['invoice_by_reseller'] as $file)
                        <li><a href="{{ $file['url'] }}" target="_blank">{{ $file['name'] }} (View/Download)</a></li>
                    @endforeach
                </ul>
            @endif
        </div>

        <p style="margin-top: 30px;">Thank you and have a nice day.</p>

        <div style="border-top: 1px solid #eee; margin-top: 30px; padding-top: 20px; font-size: 12px; color: #666;">
            <p>This is an automated email from TimeTec CRM System.</p>
        </div>
    </div>
</body>
</html>
