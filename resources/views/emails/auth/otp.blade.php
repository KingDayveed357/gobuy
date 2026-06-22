@component('mail::message')
# Verify your email

Hi {{ $user->name }}, use the code below to verify your gobuy account.

@component('mail::panel')
# {{ $code }}
@endcomponent

This code expires in {{ $ttlMinutes }} minutes. If you didn't request it, you can safely ignore this email.

Thanks,
The gobuy team
@endcomponent
