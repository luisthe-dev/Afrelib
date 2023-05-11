@extends('mails.main_mail_layout')

@section('mail_content')
    <h1> You’re resetting your password </h1>
    <p class="main-message">
        Hello <b>Luis</b>,
        <br />
        <br />
        You are getting this mail because you requested a password reset.
        <br />
        Alternatively, you can copy and paste the link below in your browser <a
            href=''>www.(URL).com/reset-password</a>
        <br />
        If you need any assistance with this kindly contact support via <a href=''>support@domain.com</a>
    </p>
    <a href='' class="message-button"> Recover My Account </a>
@endsection
