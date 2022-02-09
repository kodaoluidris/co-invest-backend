@component('mail::message')
# Introduction

Reset or change your password.
click on the link below to reset your password

@component('mail::button', ['url' => $token, 'color' => 'success'])
Change Password
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
