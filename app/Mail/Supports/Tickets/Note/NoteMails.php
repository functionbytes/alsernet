<?php

namespace App\Mail\Supports\Tickets\Note;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailable;

class NoteMails extends Mailable
{
    use Dispatchable,  InteractsWithQueue, SerializesModels;

    public $note;

    public function __construct($note)
    {
        $this->note = $note->ticket;
    }
   public function build()
   {
        return $this->subject("INOQUALABPAGO APROBADO")
                    ->to($this->email)
                    ->markdown('mailers.tikects.approved')
                    ->with([
                        'uid' => $this->uid,
                        'email' => $this->email,
                        'firstname' => $this->firstname,
                        'lastname' => $this->lastname,
                        'payment' => $this->payment,
                        'method' => $this->method,
                        'total' => $this->total,
        ]);
    }

}
