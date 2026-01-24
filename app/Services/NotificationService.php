<?php

namespace App\Services;

use App\Services\Msg91Service;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\VerifyEmail;
use App\Mail\TeamInvitation;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $msg91Service;
    protected $environment;

    public function __construct(Msg91Service $msg91Service)
    {
        $this->msg91Service = $msg91Service;
        $this->environment = config('services.app.email_environment', 'local');
    }

    /**
     * Send Verification Email
     */
    public function sendVerificationEmail(User $user, string $link)
    {
        if ($this->environment === 'production') {
            Log::info("Sending Verification Email via MSG91 to {$user->email}. Link: {$link}");
            return $this->msg91Service->sendEmail(
                ['email' => $user->email, 'name' => $user->name],
                config('services.msg91.verification_template_id', 'email_verification_docforge_docs'),
                [
                    'name' => $user->name,
                    'verification_link' => $link,
                    'year' => date('Y'),
                    'VAR1' => config('app.name', 'DocForgeDocs'),
                ]
            );
        } else {
            Log::info("Sending Verification Email via SMTP to {$user->email}");
            // Send via Laravel Mail
            return Mail::to($user->email)->send(new VerifyEmail($user, $link));
        }
    }

    /**
     * Send Team Invitation Email
     */
    public function sendTeamInvitation(string $email, string $link, string $businessName, string $role, string $senderName)
    {
        if ($this->environment === 'production') {
            Log::info("Sending Team Invitation via MSG91 to {$email}");
            return $this->msg91Service->sendEmail(
                $email,
                config('services.msg91.invitation_template_id', 'team_invitation_docforge_docs'),
                [
                    'link' => $link,
                    'business_name' => $businessName,
                    'role' => $role,
                    'sender_name' => $senderName,
                ]
            );
        } else {
            Log::info("Sending Team Invitation via SMTP to {$email}");
            // Send via Laravel Mail
            return Mail::to($email)->send(new TeamInvitation($link, $businessName, $role));
        }
    }
}
