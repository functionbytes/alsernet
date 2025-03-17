<?php

namespace App\Mail;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class DirectMail extends Mailable
{
    use Queueable, SerializesModels;

    public $email;
    public $ticket;
    // protected $ticket;
    // protected $replySubject;
    // protected $body;
    // protected $imap_username;

    /**
     * Create a new message instance.
     */
    public function __construct(Email $email, $ticket)
    {
        $this->email = $email;
        $this->ticket = $ticket;
        // $this->ticket = $ticket;
        // $this->replySubject = $replySubject;
        // $this->body = $body;
        // $this->imap_username = $imap_username;
    }

    public function build()
    {
        $this->withSwiftMessage(function ($swiftMessage) {
            $swiftMessage->getHeaders()->addTextHeader('In-Reply-To', $this->email->getHeaders()->get('In-Reply-To')->getBodyAsString());
            $swiftMessage->getHeaders()->addTextHeader('References', $this->email->getHeaders()->get('References')->getBodyAsString());
            $swiftMessage->setBody($this->email->getBody(), 'text/html');
        });
        // return $this->view('admin.email.template')
        //             ->subject($this->replySubject)
        //             ->with([
        //                 'ticket' => $this->ticket,
        //                 'body' => $this->body,
        //             ]);
        // return $this->view('admin.email.template', $data);
    }

    /**
     * Get the message envelope.
     */
    // public function envelope(): Envelope
    // {
    //     return new Envelope(
    //         subject: 'Direct Mail',
    //     );
    // }

    // /**
    //  * Get the message content definition.
    //  */
    // public function content(): Content
    // {
    //     return new Content(
    //         view: 'view.name',
    //     );
    // }

    // /**
    //  * Get the attachments for the message.
    //  *
    //  * @return array<int, \Illuminate\Mail\Mailables\Attachment>
    //  */
    // public function attachments(): array
    // {
    //     return [];
    // }
}
