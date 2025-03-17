<?php

namespace App\Mail\Subscribers;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use App\Models\Subscriber\Subscriber;
use App\Models\Layout\Layout;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class UnsubscribersPartiesMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subscriber;

    public function __construct(Subscriber $subscriber)
    {
        $this->subscriber = $subscriber;
    }
    public function build()
    {

        $this->subscriber = $this->subscriber;
        $template = Layout::where('alias', 'unsubscribed_parties_email')::where('lang_id',  $this->subscriber->id)->first();

        if (is_null($template)) {
            throw new \Exception("Layout/template 'sender_verification_email' is missing!");
        }

        $htmlContent = $template->content;

        $htmlContent = str_replace('{USER_NAME}', $this->name, $htmlContent);
        $htmlContent = str_replace('{USER_EMAIL}', $this->email, $htmlContent);
        $htmlContent = str_replace('{VERIFICATION_LINK}', $this->generateVerificationUrl(), $htmlContent);

        return $this->subject('Newsletter Welcome - ' )
            ->view('emails.newsletters.actions')
            ->with([
                'htmlContent' => $htmlContent,
        ]);
    }

    public function generateVerificationUrl()
    {
        return route('SenderController@verify', [ 'token' => $this->generateVerificationToken() ]);
    }

    public function generateVerificationToken()
    {
        $token = urlencode(encrypt($this->uid));
        return $token;
    }

    public function generateRecord()
    {
        $token = Crypt::encryptString($this->uid);
        return $token;
    }


}
