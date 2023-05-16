@extends('mails.main_mail_layout')

@section('mail_content')
    <p class="main-message">
        Dear <b>{{ $User->first_name }}</b>,
        <br />
        <br />
        We are thrilled to have you on board Afrelib as a {{ $User->role_name }}!
        <br />
        To get started, please log in to your account using the credentials provided below:<br />
    </p>
    <ol>
        <li>Email: {{ $User->email }}</li>
        <li>Password: {{ $User->last_name }}</li>
        <li><a href="#"> Visit Main Platform </a></li>
    </ol>
    <br />
    <p class="quick-message">
        If you have any questions or encounter any difficulties, don't hesitate to reach out to our support
        team. We're here to help you every step of the way.
    </p>
@endsection
