<?php

namespace App\Mail\Managers;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class OfflineOrderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct($content)
    {
        $this->content = $content;
    }

    public function build()
    {
        return $this->markdown('emails.offlineOrderMail')->subject('Regarding your order on '.env('APP_NAME'))->with('content',$this->content);
    }
}
