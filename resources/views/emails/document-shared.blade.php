<!DOCTYPE html>
<html>

<head>
    <title>Document Shared</title>
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #4f46e5;">New Document Shared With You</h2>

        <p>Hello,</p>

        <p>A document <strong>{{ $document->name }}</strong> has been shared with you via DocForge.</p>

        @if($customMessage)
            <div style="background-color: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0; font-style: italic;">
                "{{ $customMessage }}"
            </div>
        @endif

        <div style="margin: 30px 0; text-align: center;">
            <a href="{{ $shareLink }}"
                style="background-color: #4f46e5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold;">View
                Document</a>
        </div>

        <p style="font-size: 0.9em; color: #6b7280;">or copy and paste this link: <br> {{ $shareLink }}</p>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

        <p style="font-size: 0.8em; color: #9ca3af; text-align: center;">
            Powered by TechSynchronic DocForge
        </p>
    </div>
</body>

</html>