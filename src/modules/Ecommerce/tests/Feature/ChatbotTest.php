<?php

namespace Modules\Ecommerce\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatbot_responds_to_greeting(): void
    {
        $response = $this->post(route('shop.chatbot.ask'), [
            'message' => 'Hola',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['response' => ['text']]);
    }

    public function test_chatbot_responds_to_shipping_question(): void
    {
        $response = $this->post(route('shop.chatbot.ask'), [
            'message' => '¿Cuánto cuesta el envio?',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['response' => ['text']]);
    }

    public function test_chatbot_validates_required_message(): void
    {
        $response = $this->post(route('shop.chatbot.ask'), []);

        $response->assertSessionHasErrors(['message']);
    }
}
