@component('mail::message')
# Dear {{ $data['email'] }},

Berikut adalah link reset password.

@component('mail::button', ['url' => $data['link']])
Reset Password
@endcomponent

Terimakasih,<br>
{{ config('app.name') }}
@endcomponent
