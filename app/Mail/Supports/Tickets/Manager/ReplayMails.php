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

    public function __construct($ticket)
    {
        $this->ticket = $order->ticket;
        $this->uid = $order->uid;
        $this->email = $order->user->email;
        $this->firstname = $order->user->firstname;
        $this->lastname = $order->user->lastname;
        $this->payment = humanize_date($order->payment_at);
        $this->method = $order->method->title;
        $this->total = $order->total;
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
