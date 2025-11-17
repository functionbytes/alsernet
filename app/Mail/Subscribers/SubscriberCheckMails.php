<?php

namespace App\Mail\Subscribers;

use Illuminate\Queue\SerializesModels;
use App\Models\Subscriber\Subscriber;
use App\Models\Layout\Layout;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class SubscriberCheckMails extends Mailable
{
    use Queueable, SerializesModels;

    public $subscriber;
    public $htmlContent;
    public function __construct(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;

        $template = Layout::where('alias', 'suscription_check_email')->where('lang_id', $this->subscriber->lang_id)->first();

        $this->htmlContent = $template->content ?? '<h2>Hello {CUSTOMER_NAME}, welcome aboard</h2><p>Please confirm your registration using this link: {ACTIVATION_URL}</p>';

        $this->htmlContent = str_replace(
            '{CUSTOMER_NAME}',
            trim(($this->subscriber->firstname ?? '') . ' ' . ($this->subscriber->lastname ?? '')),
            $this->htmlContent
        );

        $this->htmlContent = str_replace(
            '{ACTIVATION_URL}',
            $this->generateVerificationUrl(),
            $this->htmlContent
        );
    }

    public function build()
    {
        return $this->subject('Subscribers check')
            ->html($this->htmlContent)
            ->withHeaders([
                'Content-Type' => 'text/html; charset=UTF-8',
            ]);
    }

    public function generateVerificationUrl()
    {
        return 'https://cristian.preproduccion.a-alvarez.com/' . $this->generateVerificationToken();
    }

    public function generateVerificationToken()
    {
        return urlencode(Crypt::encryptString($this->subscriber->email));
    }
}
