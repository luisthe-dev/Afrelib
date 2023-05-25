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

    protected $User = array(
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'role_name' => '',
    );

    public function __construct($user)
    {
        $this->User = $user;
    }


    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to the AI Challenge Platform',
        );
    }


    public function content(): Content
    {
        if ($this->User['role_name'] == 'Student') {
            return new Content(
                view: 'mails.student_signup_mail',
                with: ['User' => $this->User]
            );
        } else {
            return new Content(
                view: 'mails.signup_mail',
                with: ['User' => $this->User]
            );
        }
    }
}
