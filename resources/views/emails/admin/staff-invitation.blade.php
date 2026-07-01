<x-mail::message>
# You've been invited to gobuy

Hi {{ $admin->name }},

You've been invited to join the **gobuy** admin team as a **{{ $admin->roles->first()?->name ?? 'team member' }}**.

Click below to set your password and get started. This link expires in 7 days.

<x-mail::button :url="$activationUrl">
Set up my account
</x-mail::button>

If you weren't expecting this invitation, you can safely ignore this email.

Thanks,<br>
The gobuy team
</x-mail::message>
