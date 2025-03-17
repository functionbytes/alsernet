<?php

namespace App\Mail\Managers;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AdminRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function build()
    {
        return $this->markdown('emails.adminRegisteredMail')
            ->subject('New User Registered '.env('APP_NAME'))
            ->with('user',$this->user);
    }

}
