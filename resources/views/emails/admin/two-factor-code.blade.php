<x-mail::message>
# Your verification code

Hi {{ $admin->name }}, use this code to finish signing in to the Quintessential Mart admin:

<x-mail::panel>
# {{ $code }}
</x-mail::panel>

This code expires in 10 minutes. If you didn't try to sign in, change your password right away.

Thanks,<br>
The Quintessential Mart team
</x-mail::message>
