<?php

namespace App\Conversations;

use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Support\Facades\Http;

class DataCollectionConversation extends Conversation
{
    protected $name;
    protected $email;

    public function askName()
    {
        $this->ask('¿Cuál es tu nombre?', function (Answer $answer) {
            $this->name = $answer->getText();
            $this->askEmail();
        });
    }

    public function askEmail()
    {
        $this->ask('¿Cuál es tu correo electrónico?', function (Answer $answer) {
            $this->email = $answer->getText();
            $this->confirmData();
        });
    }

    public function confirmData()
    {
        $this->say("Gracias, {$this->name}. Enviaremos tus datos al servidor.");
        $this->sendDataToEndpoint();
    }

    public function sendDataToEndpoint()
    {
        $endpoint = 'https://example.com/api/receive-data';

        $response = Http::post($endpoint, [
            'name' => $this->name,
            'email' => $this->email,
        ]);

        if ($response->successful()) {
            $this->say('Tus datos han sido enviados correctamente.');
        } else {
            $this->say('Hubo un error al enviar tus datos.');
        }
    }

    public function run()
    {
        $this->askName();
    }
}
