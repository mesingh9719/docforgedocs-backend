<x-mail::message>
    # You've been invited!

    You have been invited to join the **{{ $businessName }}** team on DocForge as a **{{ ucfirst($role) }}**.

    <x-mail::button :url="$inviteUrl">
        Accept Invitation
    </x-mail::button>

    If you did not expect this invitation, you can ignore this email.

    Thanks,<br>
    The {{ config('app.name') }} Team
</x-mail::message>