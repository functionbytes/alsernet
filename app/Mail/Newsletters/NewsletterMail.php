<?php

namespace App\Mail\Newsletters;

use App\Models\Newsletter\Newsletter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewsletterMail extends Mailable
{
    use Queueable, SerializesModels;

    public $newsletter;

    /**
     * Crea una nueva instancia de la clase de correo.
     *
     * @param Newsletter $newsletter
     */
    public function __construct(Newsletter $newsletter)
    {
        $this->newsletter = $newsletter;
    }

    /**
     * Construir el mensaje del correo.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Newsletter - ' . $this->newsletter->title)
            ->view('emails.newsletters.actions')  // Asegúrate de tener la vista de correo configurada
            ->with([
                'newsletter' => $this->newsletter,
            ]);
    }
}
