{{-- filepath: /var/www/html/timeteccrm/resources/views/emails/bypass-duplicate-approval.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bypass Duplicate Request Approved</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #28a745; color: white; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #f8f9fa; padding: 20px; border-radius: 0 0 5px 5px; }
        .success-box { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .info-table th, .info-table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .info-table th { background-color: #e9ecef; font-weight: bold; }
        .btn { display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>✅ Bypass Duplicate Request Approved</h2>
        </div>

        <div class="content">
            <div class="success-box">
                <strong>Good news!</strong> Your bypass duplicate request has been approved and the lead has been assigned to you.
            </div>

            <p>Hello <strong>{{ $requestorName }}</strong>,</p>

            <p>Your request to bypass duplicate checking has been <strong>approved</strong> by {{ $approvedByName }} on {{ $approvedAt }}.</p>

            <table class="info-table">
                <tr>
                    <th>Company Name:</th>
                    <td>{{ $companyName }}</td>
                </tr>
                <tr>
                    <th>Lead Code:</th>
                    <td>{{ $leadCode }}</td>
                </tr>
                <tr>
                    <th>Lead ID:</th>
                    <td>{{ $leadId }}</td>
                </tr>
                <tr>
                    <th>Your Original Reason:</th>
                    <td>{{ $reason }}</td>
                </tr>
                <tr>
                    <th>Approved By:</th>
                    <td>{{ $approvedByName }}</td>
                </tr>
                <tr>
                    <th>Approved Date:</th>
                    <td>{{ $approvedAt }}</td>
                </tr>
            </table>

            <div class="success-box">
                <h3>Lead Status Updated:</h3>
                <ul>
                    <li><strong>Category:</strong> Active</li>
                    <li><strong>Stage:</strong> Transfer</li>
                    <li><strong>Status:</strong> New</li>
                    <li><strong>Assigned to:</strong> {{ $requestorName }}</li>
                </ul>
            </div>
    </div>
</body>
</html>
