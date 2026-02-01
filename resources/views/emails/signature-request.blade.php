<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Signature Request</title>
</head>

<body style="margin: 0; padding: 0;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6;
                  padding:40px 0;
                  font-family:arial, 'helvetica neue', helvetica, sans-serif;">
        <tr>
            <td align="center">

                <!-- MAIN CARD -->
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;
                              border-radius:12px;
                              box-shadow:0 10px 25px rgba(0,0,0,0.08);
                              padding:40px;">
                    <tr>
                        <td>

                            <!-- SENDER / BRAND -->
                            <p style="margin:0 0 20px 0;
                                      font-size:16px;
                                      font-weight:600;
                                      color:#111827;">
                                DocForgeDocs
                            </p>

                            <!-- TITLE -->
                            <h2 style="margin:0 0 14px 0;
                                       font-size:22px;
                                       font-weight:600;
                                       color:#111827;">
                                Signature Request
                            </h2>

                            <!-- GREETING -->
                            <p style="margin:0 0 18px 0;
                                      font-size:15px;
                                      line-height:1.6;
                                      color:#374151;">
                                Hello <strong>{{ $signerName }}</strong>,
                            </p>

                            <!-- MAIN MESSAGE -->
                            <p style="margin:0 0 22px 0;
                                      font-size:15px;
                                      line-height:1.6;
                                      color:#4b5563;">
                                <strong>{{ $senderName }}</strong> has requested your signature
                                on a document using <strong>DocForgeDocs</strong>.
                            </p>

                            <!-- DOCUMENT DETAILS -->
                            <table width="100%" cellpadding="0" cellspacing="0" style="background:#f9fafb;
                                          border:1px solid #e5e7eb;
                                          border-radius:8px;
                                          padding:14px 16px;
                                          margin:0 0 26px 0;">
                                <tr>
                                    <td style="font-size:14px; color:#111827;">
                                        <strong>Document name</strong><br>
                                        <span style="color:#4b5563;">{{ $documentName }}</span>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 28px 0;
                                      font-size:15px;
                                      line-height:1.6;
                                      color:#4b5563;">
                                Please review the document and sign where required.
                            </p>

                            <!-- CTA BUTTON -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:10px 0 32px 0;">
                                        <a href="{{ $signLink }}" style="background:#4F46E5;
                                                  color:#ffffff;
                                                  padding:14px 36px;
                                                  font-size:15px;
                                                  font-weight:600;
                                                  text-decoration:none;
                                                  border-radius:8px;
                                                  display:inline-block;">
                                            Review & Sign Document
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- FALLBACK LINK -->
                            <p style="margin:0 0 8px 0;
                                      font-size:13px;
                                      color:#6b7280;">
                                If the button above does not work, copy and paste this link into your browser:
                            </p>

                            <p style="margin:0 0 28px 0;
                                      font-size:13px;
                                      line-height:1.5;
                                      word-break:break-all;
                                      color:#4F46E5;">
                                {{ $signLink }}
                            </p>

                            <hr style="border:none;
                                       border-top:1px solid #e5e7eb;
                                       margin:30px 0;">

                            <!-- PLATFORM DISCLOSURE -->
                            <p style="margin:0 0 18px 0;
                                      font-size:13px;
                                      line-height:1.6;
                                      color:#6b7280;">
                                This email was sent by <strong>DocForgeDocs</strong>,
                                a digital document and electronic signature platform.
                                The document was shared by <strong>{{ $senderName }}</strong>.
                                If you were not expecting this request, no action is required.
                            </p>

                            <!-- SIGN OFF -->
                            <p style="margin:0;
                                      font-size:14px;
                                      color:#111827;">
                                Regards,<br>
                                <strong>DocForgeDocs Team</strong>
                            </p>

                        </td>
                    </tr>
                </table>

                <!-- FOOTER -->
                <p style="margin:18px 0 0 0;
                          font-size:12px;
                          color:#9ca3af;">
                    © {{ date('Y') }} DocForgeDocs. All rights reserved.
                </p>

            </td>
        </tr>
    </table>
</body>

</html>