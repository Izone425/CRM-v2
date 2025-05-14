<!-- filepath: /var/www/html/timeteccrm/resources/views/emails/customer-reset-password.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Your TimeTec Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #F7FAFD;
            margin: 0;
            padding: 0;
            text-align: center;
        }
        .container {
            max-width: 600px;
            margin: 2rem auto;
            overflow: hidden;
            border-radius: 1.5rem;
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
            font-size: 1.5rem;
            font-weight: 600;
            color: rgb(30, 58, 138);
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
            padding: 2rem;
        }
        .greeting {
            font-size: 1rem;
            font-weight: 600;
            color: rgb(30, 58, 138);
        }
        .message {
            margin-top: 1rem;
            font-size: 1rem;
            color: rgb(75, 85, 99);
        }
        .button {
            display: inline-block;
            width: 100%;
            margin-top: 2rem;
            padding: 0.75rem 1rem;
            background-image: linear-gradient(to right, #31c6f6, #107eff);
            color: white;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-decoration: none;
            border-radius: 9999px;
            transition: opacity 300ms;
            text-align: center;
        }
        .button:hover {
            opacity: 0.9;
        }
        .fallback {
            margin-top: 2rem;
            font-size: 0.75rem;
            color: rgb(75, 85, 99);
        }
        .link {
            color: rgb(37, 99, 235);
            text-decoration: underline;
            word-wrap: break-word;
        }
        .footer {
            padding: 1rem;
            font-size: 0.75rem;
            color: rgb(107, 114, 128);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="content-bg">
            <!-- Header -->
            <div class="header-bg">
                <div class="header-text">Password Reset</div>
                <img src="https://www.timeteccloud.com/temp/web_portal/images/img-welcometimetec.svg" alt="Welcome Banner" class="header-image">
            </div>

            <!-- Body -->
            <div class="body-content">
                <h2 class="greeting">Hello {{ $name }},</h2>
                <p class="message">
                    You are receiving this email because we received a password reset request for your TimeTec customer account.
                </p>
                <p class="message">
                    This password reset link is valid for the next 24 hours.
                </p>

                <a href="{{ $resetLink }}" style="display: inline-block; margin-top: 32px; padding: 12px 16px; background-color: #107eff; background-image: linear-gradient(to right, #31c6f6, #107eff); color: #ffffff; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; text-decoration: none; border-radius: 9999px; text-align: center;">Reset Password</a>

                <!-- Fallback Link -->
                <p class="fallback">
                    If the button doesn't work, you can also copy and paste the following link into your browser:<br>
                    <a href="{{ $resetLink }}" class="link">{{ $resetLink }}</a>
                </p>

                <p class="message" style="margin-top: 2rem;">
                    If you did not request a password reset, no further action is required.
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
