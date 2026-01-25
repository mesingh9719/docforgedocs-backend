<meta charset="UTF-8">
<title>Member Invitation</title>

<div style="padding:10px;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:40px 0;
              font-family:arial, 'helvetica neue', helvetica, sans-serif;">
        <tr>
            <td align="center">

                <!-- Card -->
                <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;
                          border-radius:12px;
                          box-shadow:0 10px 25px rgba(0,0,0,0.08);
                          padding:40px;">
                    <tr>
                        <td>

                            <h2 style="margin:0 0 10px 0;
                                   font-size:22px;
                                   font-weight:600;
                                   color:#111827;">
                                Hello {{ $name }},
                            </h2>

                            <p style="margin:0 0 20px 0;
                                  font-size:15px;
                                  line-height:1.6;
                                  color:#4b5563;">
                                You have been invited by <strong>{{ $inviterName }}</strong>
                                to join <strong>{{ $businessName }}</strong> on
                                <strong>DocForge</strong>.
                            </p>

                            <p style="margin:0 0 28px 0;
                                  font-size:15px;
                                  line-height:1.6;
                                  color:#4b5563;">
                                Click the button below to accept the invitation and access
                                the workspace.
                            </p>

                            <!-- Button -->
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding:10px 0 35px 0;">
                                        <a href="{{ $inviteUrl }}" style="background:#4F46E5;
                                              color:#ffffff;
                                              padding:14px 34px;
                                              font-size:15px;
                                              font-weight:600;
                                              text-decoration:none;
                                              border-radius:8px;
                                              display:inline-block;">
                                            Accept Invitation
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <!-- Fallback -->
                            <p style="margin:0 0 8px 0;
                                  font-size:13px;
                                  color:#6b7280;">
                                If the button above does not work, copy and paste this link into your browser:
                            </p>

                            <p style="margin:0 0 30px 0;
                                  font-size:13px;
                                  line-height:1.5;
                                  word-break:break-all;
                                  color:#4F46E5;">
                                {{ $inviteUrl }}
                            </p>

                            <hr style="border:none;
                                   border-top:1px solid #e5e7eb;
                                   margin:30px 0;">

                            <p style="margin:0 0 20px 0;
                                  font-size:13px;
                                  line-height:1.6;
                                  color:#6b7280;">
                                If you were not expecting this invitation, no further action is required.
                            </p>

                            <p style="margin:0;
                                  font-size:14px;
                                  color:#111827;">
                                Regards,<br>
                                <strong>DocForge Team</strong>
                            </p>

                        </td>
                    </tr>
                </table>

                <!-- Footer -->
                <p style="margin:18px 0 0 0;
                      font-size:12px;
                      color:#9ca3af;">
                    © {{ date('Y') }} DocForge. All rights reserved.
                </p>

            </td>
        </tr>
    </table>
</div>