<!DOCTYPE html>
<html>

<head>
    <title>Team Invitation</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2>You've been invited!</h2>
        <p>Hello,</p>
        <p><strong>{{ $senderName ?? 'A user' }}</strong> has invited you to join the
            <strong>{{ $businessName }}</strong> team on {{ config('app.name') }} as a
            <strong>{{ ucfirst($role) }}</strong>.</p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="{{ $inviteUrl }}"
                style="background-color: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">Accept
                Invitation</a>
        </p>

        <p>If you did not expect this invitation, you can safe ignore this email.</p>

        <p>Best regards,<br>{{ config('app.name') }} Team</p>

        <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
        <p style="font-size: 12px; color: #999;">If the button doesn't work, copy and paste this link into your
            browser:<br>{{ $inviteUrl }}</p>
    </div>
</body>

</html>