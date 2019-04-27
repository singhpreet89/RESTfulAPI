@component('mail::message')
# Hello {{ $user->name }}

You changed your email. So we need to verify your email again using the button:

@component('mail::button', ['url' => route('verify', $user->verification_token)])
Verify Account
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent