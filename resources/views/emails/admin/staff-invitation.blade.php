<x-mail::message>
# You've been invited to Quintessential Mart

Hi {{ $admin->name }},

You've been invited to join the **Quintessential Mart** admin team as a **{{ $admin->roles->first()?->name ?? 'team member' }}**.

Click below to set your password and get started. This link expires in 7 days.

<x-mail::button :url="$activationUrl">
Set up my account
</x-mail::button>

If you weren't expecting this invitation, you can safely ignore this email.

Thanks,<br>
The Quintessential Mart team
</x-mail::message>
