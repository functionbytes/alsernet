<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

use App\Models\EmailTemplate;

class ContactMail extends Mailable
{
    use Queueable, SerializesModels;

    public $template, $contactData;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($template, $contactData)
    {
        $this->template = $template;
        $this->contactData = $contactData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $template = EmailTemplate::where('code', $this->template)->first();
        $contactData = $this->contactData;

        $body = $template->body;
        $subject = $template->subject;
        foreach($this->contactData as $key => $value){
            $subject = str_replace('{{'.$key.'}}' , $this->contactData[$key] , $subject);
            $subject = str_replace('{{ '.$key.' }}' , $this->contactData[$key] , $subject);

            $body = str_replace('{{'.$key.'}}' , $this->contactData[$key] , $body);
            $body = str_replace('{{ '.$key.' }}' , $this->contactData[$key] , $body);
        }

        $data['emailBody']  =   $body;
        $this->subject( $subject );
        return $this->from($contactData['Contact_email'])
                    ->to(setting('contact_form_mail'))
                    ->view('admin.email.template',$data);
    }
}
