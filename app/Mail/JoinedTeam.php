<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class JoinedTeam extends Mailable
{
    use Queueable, SerializesModels;

    protected $User = array(
        'first_name' => '',
    );

    protected $Team = array(
        'team_name' => ''
    );

    public function __construct($user, $team)
    {
        $this->User = $user;
        $this->Team = $team;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Congratulations on Joining Your AI Challenge Team!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.joined_team_mail',
            with: ['User' => $this->User, 'Team' => $this->Team]
        );
    }
}
