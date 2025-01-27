<?php

namespace App\Mail\Supports\Tickets\Customer;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailable;

class ReplayMails extends Mailable
{
    use Dispatchable,  InteractsWithQueue, SerializesModels;

    public $ticket;

    public function __construct($ticket)
    {
        $this->ticket = $tikect->ticket;
        $this->slack = $tikect->slack;
        $this->email = $tikect->user->email;
        $this->firstname = $tikect->user->firstname;
        $this->lastname = $tikect->user->lastname;
        $this->payment = humanize_date($tikect->payment_at);
        $this->method = $tikect->method->title;
        $this->total = $tikect->total;
    }

   public function build()
   {
        return $this->subject("INOQUALABPAGO APROBADO")
                    ->to($this->email)
                    ->markdown('mailers.tikects.approved')
                    ->with([
                        'slack' => $this->slack,
                        'email' => $this->email,
                        'firstname' => $this->firstname,
                        'lastname' => $this->lastname,
                        'payment' => $this->payment,
                        'method' => $this->method,
                        'total' => $this->total,
        ]);

    }

}
