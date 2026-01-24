<!DOCTYPE html>
<html>

<head>
    <title>Verify Your Email</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>Welcome to {{ config('app.name') }}, {{ $user->name }}!</h2>
        <p>Please click the button below to verify your email address.</p>
        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ $verificationUrl }}"
                style="background-color: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Verify
                Email Address</a>
        </p>
        <p>If you didn't create an account, you can safely ignore this email.</p>
        <p>Best regards,<br>{{ config('app.name') }} Team</p>
        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="font-size: 12px; color: #999;">If the button doesn't work, copy and paste this link into your
            browser:<br>{{ $verificationUrl }}</p>
    </div>
</body>

</html>