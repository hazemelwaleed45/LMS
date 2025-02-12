<!DOCTYPE html>
<html>
<head>
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
        h1 {
            color: #444;
        }
        .otp {
            font-size: 24px;
            font-weight: bold;
            color: #007BFF;
            margin: 20px 0;
        }
        .footer {
            margin-top: 20px;
            font-size: 14px;
            color: #777;
        }
    </style>
</head>
<body>
    <h1>Password Reset Request</h1>

    <p>You requested to reset your password. Your OTP is:</p>

    <div class="otp">{{ $otp }}</div>

    <p>This OTP is valid for 10 minutes. If you did not request this, please ignore this email.</p>

    <div class="footer">
        <p>Thank you,<br>{{ config('app.name') }}</p>
    </div>
</body>
</html>