<?php

namespace App\Mail\Supports\Tickets\Manager;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailable;

class ReplayMails extends Mailable
{
    use Dispatchable,  InteractsWithQueue, SerializesModels;

    public $ticket;

    public function __construct($tickets)
    {
        $this->ticket = $tickets->ticket;
        $this->uid = $tickets->uid;
        $this->email = $tickets->user->email;
        $this->firstname = $tickets->user->firstname;
        $this->lastname = $tickets->user->lastname;
        $this->payment = humanize_date($tickets->payment_at);
        $this->method = $tickets->method->title;
        $this->total = $tickets->total;
    }

   public function build()
   {
        return $this->subject("INOQUALABPAGO APROBADO")
                    ->to($this->email)
                    ->markdown('mailers.orders.approved')
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
