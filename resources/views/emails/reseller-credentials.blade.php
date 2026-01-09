<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reseller Account Created</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; background-color: #f4f4f4; margin: 0; padding: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #667eea; color: #ffffff; padding: 30px; text-align: center;">
                            <h1 style="margin: 0; font-size: 24px; color: #ffffff;">TimeTec CRM Reseller Portal</h1>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">
                            <h2 style="color: #667eea; margin-top: 0;">Welcome to TimeTec CRM!</h2>

                            <p style="color: #333;">Dear <strong>{{ $name }}</strong>,</p>

                            <p style="color: #333;">Your reseller account for <strong>{{ $company_name }}</strong> has been successfully created.</p>

                            <!-- Credentials Box -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0;">
                                <tr>
                                    <td style="background: #e8f0fe; border-left: 4px solid #667eea; padding: 15px;">
                                        <p style="margin: 8px 0; color: #333;">
                                            <strong style="color: #667eea;">Login Email:</strong> {{ $email }}
                                        </p>
                                        <p style="margin: 8px 0; color: #333;">
                                            <strong style="color: #667eea;">Password:</strong> {{ $password }}
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Important Note -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0;">
                                <tr>
                                    <td style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px;">
                                        <p style="margin: 0; color: #856404;">
                                            <strong>Important:</strong> Please change your password after your first login for security purposes.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Button -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="margin: 20px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ $login_url }}" style="display: inline-block; background-color: #667eea; color: #ffffff; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;">Login to Portal</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #333;">If you have any questions or need assistance, please don't hesitate to contact our support team.</p>

                            <p style="color: #333;">
                                Best regards,<br>
                                <strong>TimeTec CRM Team</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background: #f8f9fa; padding: 20px; text-align: center;">
                            <p style="margin: 5px 0; font-size: 12px; color: #6c757d;">
                                &copy; {{ date('Y') }} TimeTec CRM. All rights reserved.
                            </p>
                            <p style="margin: 5px 0; font-size: 12px; color: #6c757d;">
                                This is an automated email. Please do not reply to this message.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
