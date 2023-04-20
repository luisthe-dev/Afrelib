<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SignUpMail extends Mailable
{
    use Queueable, SerializesModels;

    protected $User;

    public function __construct()
    {
        //
    }


    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome To Afrelib',
        );
    }


    public function content(): Content
    {
        return new Content(
            view: 'mails.signup_mail',
            with: []
        );
    }
}
