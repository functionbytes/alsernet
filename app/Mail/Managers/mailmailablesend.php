<?php

namespace App\Mail\Managers;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Template\Template;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;

class mailmailablesend extends Mailable
{
    use Queueable, SerializesModels;

    public $template, $data;

    public function __construct($template, $data)
    {
        $this->template = $template;
        $this->data = $data;
    }

    public function build()
    {
        $template = Template::where('code', $this->template)->first();

        $data = $this->data;

        $body = $template->body;
        $subject = $template->subject;
        foreach($this->data as $key => $value){
            $subject = str_replace('{{'.$key.'}}' , $this->data[$key] , $subject);
            $subject = str_replace('{{ '.$key.' }}' , $this->data[$key] , $subject);

            $body = str_replace('{{'.$key.'}}' , $this->data[$key] , $body);
            $body = str_replace('{{ '.$key.' }}' , $this->data[$key] , $body);
        }

        $data['emailBody']  =   $body;
        $this->subject( $subject );
        return $this->view('admin.email.template', $data);
    }

}
