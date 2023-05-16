@extends('mails.main_mail_layout')

@section('mail_content')
    <p class="main-message">
        Dear <b>{{ $User->first_name }}</b>,
        <br />
        <br />
        We are excited to inform you that you have been assigned to the <b>{{ $Team->team_name }}</b> team for the AI
        Challenge. You will be working with a team of talented individuals toward a common goal of
        developing cutting-edge AI technology.
        <br />
        To get started, please log in to your account using the credentials you provided during
        registration. You will have access to your dashboard where you can collaborate with your team
        members, access project resources, and track progress.
    </p>
    <br />
    <p class="quick-message">
        We wish you all the best and look forward to seeing the amazing things you and your team will
        accomplish together.
    </p>
@endsection
