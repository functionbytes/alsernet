<?php

namespace App\Mail\Campaigns\Giftvoucher;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Support\Facades\Mail;

class GiftvoucherMail extends Mailable
{
    use Queueable, SerializesModels;

    public $newsletter;
    public $firstname;
    public $lastname;
    public $email;
    public $uid;
    public $subject;
    public $template;
    public $iso;

    public function __construct($newsletter)
    {
        $this->newsletter = $newsletter;
        $this->iso = $newsletter->lang->iso_code;
        $this->uid = $newsletter->uid;
        $this->firstname = $newsletter->firstname;
        $this->lastname = $newsletter->lastname;
        $this->email = $newsletter->email;

        switch ($this->iso) {
            case 'es':
                $this->template = 'mailers.campaigns.giftvoucher.es.giftvoucher';
                $this->subject = 'Aquí tienes tu cheque regalo 10€!!!';
                break;
            case 'pt':
                $this->template = 'mailers.campaigns.giftvoucher.pt.giftvoucher';
                $this->subject = 'Aquí tienes tu cheque regalo 10€!!!';
                break;
            default:
                $this->template = '';
                $this->subject = '';
                break;
        }

    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [$this->email],
            subject: $this->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: $this->template,
            with: [
                'firstname' => $this->firstname,
                'lastname' => $this->lastname,
                'names' => ucwords($this->firstname) . " " . ucwords($this->lastname),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }

}
