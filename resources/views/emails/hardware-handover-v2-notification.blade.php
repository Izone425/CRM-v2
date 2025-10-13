{{-- filepath: /var/www/html/timeteccrm/resources/views/emails/hardware-handover-v2-notification.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hardware Handover Notification</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2c3e50; border-bottom: 2px solid #3498db; padding-bottom: 10px; margin-top: 0;">
            Hardware Handover Notification
        </h2>

        <p>Dear {{ $salespersonName }},</p>

        <div style="background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
            <p style="margin: 5px 0;"><strong>Hardware Handover ID:</strong> {{ $handoverId }}</p>
            <p style="margin: 5px 0;"><strong>Company Name:</strong> {{ $companyName }}</p>
            <p style="margin: 5px 0;"><strong>SalesPerson Name:</strong> {{ $salespersonName }}</p>
            <p style="margin: 5px 0;"><strong>Updated by:</strong> {{ $updatedByName }}</p>
        </div>

        <h3 style="color: #2c3e50; margin-top: 25px; margin-bottom: 15px;">Invoice Details:</h3>

        <table style="width: 100%; border-collapse: collapse; margin: 15px 0; border: 1px solid #ddd;">
            <thead>
                <tr style="background-color: #3498db; color: white;">
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: center;">No</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: left;">Invoice Number</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: center;">Attachment</th>
                    <th style="padding: 12px; border: 1px solid #ddd; text-align: center;">Payment Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoiceData as $index => $invoice)
                    <tr style="border-bottom: 1px solid #ddd;">
                        <td style="padding: 8px; text-align: center; border: 1px solid #ddd;">{{ $index + 1 }}</td>
                        <td style="padding: 8px; border: 1px solid #ddd;">{{ $invoice['invoice_no'] }}</td>
                        <td style="padding: 8px; text-align: center; border: 1px solid #ddd;">
                            @if(isset($invoice['invoice_file']) && $invoice['invoice_file'])
                                @php
                                    $fileUrl = url('storage/' . $invoice['invoice_file']);
                                @endphp
                                <a href="{{ $fileUrl }}"
                                   target="_blank"
                                   style="color: #3498db; text-decoration: underline; font-weight: bold;">
                                    📄 PDF
                                </a>
                            @else
                                <span style="color: #999;">📄 No File</span>
                            @endif
                        </td>
                        <td style="padding: 8px; text-align: center; border: 1px solid #ddd;">
                            <span style="
                                padding: 4px 8px;
                                border-radius: 4px;
                                font-size: 12px;
                                font-weight: bold;
                                background-color: {{ $invoice['payment_status'] === 'Full Payment' ? '#d4edda' : ($invoice['payment_status'] === 'Partial Payment' ? '#fff3cd' : '#f8d7da') }};
                                color: {{ $invoice['payment_status'] === 'Full Payment' ? '#155724' : ($invoice['payment_status'] === 'Partial Payment' ? '#856404' : '#721c24') }};
                            ">
                                {{ $invoice['payment_status'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
