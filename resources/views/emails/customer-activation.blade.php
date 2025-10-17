<!-- filepath: /var/www/html/timeteccrm/resources/views/emails/customer-activation.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Your TimeTec Account</title>
    <style>
        body {
            font-family: 'Arial', 'Helvetica', sans-serif;
            background-color: #F7FAFD;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .container {
            max-width: 600px;
            margin: 32px auto;
            overflow: hidden;
            border-radius: 24px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
        }
        .content-bg {
            background-color: #ffffff;
        }
        .header-bg {
            position: relative;
            height: 270px;
        }
        .header-text {
            position: absolute;
            bottom: 170px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 24px;
            font-weight: 600;
            color: #305edf;
            z-index: 10;
        }
        .header-image {
            position: absolute;
            bottom: 40px;
            left: 50%;
            transform: translateX(-50%);
            width: 70%;
            z-index: 5;
        }
        .body-content {
            padding: 32px;
        }
        .greeting {
            font-size: 16px;
            font-weight: 600;
            color: #305edf;
        }
        .message {
            margin-top: 16px;
            font-size: 16px;
            color: #4b5563;
            line-height: 1.5;
        }
        .button {
            display: inline-block;
            width: 100%;
            margin-top: 32px;
            padding: 12px 16px;
            background-image: linear-gradient(to right, #31c6f6, #107eff);
            color: white !important;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-decoration: none;
            border-radius: 9999px;
            text-align: center;
        }
        .fallback {
            margin-top: 32px;
            font-size: 12px;
            color: #4b5563;
        }
        .link {
            color: #2563eb;
            text-decoration: underline;
            word-wrap: break-word;
        }
        .footer {
            padding: 16px;
            font-size: 12px;
            color: #6b7280;
        }
        .credentials-box {
            background: #2563eb;
            border-radius: 16px;
            padding: 24px;
            margin: 24px 0;
            color: white;
            text-align: center;
        }
        .credentials-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 16px;
            color: white;
        }
        .credential-item {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin: 12px 0;
            border: 1px solid #e5e7eb;
        }
        .credential-label {
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
            color: #6b7280;
            font-weight: 500;
        }
        .credential-value {
            font-size: 16px;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            word-break: break-all;
            background: #f3f4f6;
            padding: 10px;
            border-radius: 6px;
            color: #1f2937;
            text-align: left;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content-bg">
            <!-- Header -->
            <div class="header-bg">
                <div class="header-text">Welcome to</div>
                <img src="https://www.timeteccloud.com/temp/web_portal/images/img-welcometimetec.svg" alt="Welcome Banner" class="header-image">
            </div>

            <!-- Body -->
            <div class="body-content">
                <h2 class="greeting">Hello {{ $name }},</h2>
                <p class="message">
                    Thank you for subscribing to TimeTec solution! To complete your account setup, please login to your customer portal account by clicking the button below.
                </p>

                <!-- Login Credentials -->
                <div class="credentials-box">
                    <div class="credentials-title">🔐 Your Login Credentials</div>

                    <div class="credential-item">
                        <div class="credential-label">Login Email</div>
                        <div class="credential-value">{{ $email }}</div>
                    </div>

                    <div class="credential-item">
                        <div class="credential-label">Password</div>
                        <div class="credential-value">{{ $password }}</div>
                    </div>
                </div>

                <a href="{{ $loginUrl }}" style="display: inline-block; margin-top: 32px; padding: 12px 16px; background-color: #107eff; background-image: linear-gradient(to right, #31c6f6, #107eff); color: #ffffff; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; border-radius: 9999px; text-align: center;">Login My Account</a>

                <!-- Fallback Link -->
                <p class="fallback">
                    If the button doesn't work, you can also copy and paste the following link into your browser:<br>
                    <a href="{{ $loginUrl }}" class="link">{{ $loginUrl }}</a>
                </p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        TimeTec © {{ date('Y') }}, All Rights Reserved.
    </div>
</body>
</html>
