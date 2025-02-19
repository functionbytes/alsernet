<?php

namespace App\Http\Controllers\Pages;

use App\Conversations\DataCollectionConversation;
use App\Http\Controllers\Controller;
use BotMan\BotMan\BotMan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChatbotController extends Controller
{
    public function handle(Request $request)
    {
        dd($request->all());
        $botman = app('botman');

        $botman->hears('Enviar datos', function (BotMan $bot) {
            $bot->startConversation(new DataCollectionConversation());
        });

        $botman->listen();
    }

    public function show()
    {
        return view('pages.partials.botman');
    }

}
