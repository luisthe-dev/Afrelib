@extends('mails.main_mail_layout')

@section('mail_content')
    <p class="main-message">
        Dear <b>{{ $User->first_name }}</b>,
        <br />
        <br />
        We are delighted to have you onboard with us for the upcoming AI Challenge. This presents a
        remarkable opportunity for you to participate in and develop cutting-edge AI technology.
        <br />
        <br />
        To ensure a smooth onboarding process, we have provided you with the necessary details
        below:
        <br />
        <br />
        Please visit the Afrelib AI Challenge Hub at <a
            href='http://app.afrelibai.academy/'>http://app.afrelibai.academy/</a> to access the
        resources and tools you'll need throughout the challenge.
        <br />
        <br />
        <b> Login Credentials: </b>
        <br />
        <br />
        Email: {{ $User->email }}
        <br />
        Password: {{ $User->last_name }}
        <br />
        <br />
        We recommend you follow these steps:
    </p>
    <ol>
        <li> Watch the web app video <a
                href='https://drive.google.com/file/d/1khQ5WKtq6p2rXD-esNraSI-pRiPXuyEl/view?usp=drivesdk'>here</a> </li>
        <li> Log in to the platform and change your password to something more memorable for you. </li>
        <li> Take some time to explore the platform and make note of any observations or questions
            you may have. These will be addressed during the upcoming student orientation on <a
                href='http://airmail.calendar/2023-05-24%2012:00:00%20BST'>May 24th, 2023</a> </li>
    </ol>
    <p class="main-message">
        Rest assured, we will be in touch with further details and to address any outstanding questions
        you may have.
        <br />
        <br />
        Once again, we extend our best wishes to you as you embark on this exciting journey.
        <br />
        <br />
        We look forward to witnessing the amazing accomplishments you will achieve.
    </p>
@endsection
