<?php

namespace Modules\Helpdesk\Tests\Feature\Inbox;

class WhatsAppWindowEnforcementTest extends InboxTestCase
{
    public function test_rechaza_texto_libre_fuera_de_la_ventana_de_whatsapp(): void
    {
        $conversation = $this->createConversation([
            'channel' => 'whatsapp',
            'last_customer_message_at' => now()->subHours(25),
        ]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.messages.store', $conversation), [
                'body' => 'Respuesta libre fuera de ventana',
            ])
            ->assertStatus(422)
            ->assertJsonPath('wa_window_closed', true);
    }

    public function test_permite_nota_interna_fuera_de_la_ventana(): void
    {
        $conversation = $this->createConversation([
            'channel' => 'whatsapp',
            'last_customer_message_at' => now()->subHours(25),
        ]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.messages.store', $conversation), [
                'body' => 'Nota interna del agente',
                'is_internal' => true,
            ])
            ->assertCreated();
    }

    public function test_permite_texto_libre_dentro_de_la_ventana(): void
    {
        $conversation = $this->createConversation([
            'channel' => 'whatsapp',
            'last_customer_message_at' => now()->subHours(2),
        ]);

        $this->actingAs($this->manager)
            ->postJson(route('manager.helpdesk.conversations.messages.store', $conversation), [
                'body' => 'Respuesta dentro de ventana',
            ])
            ->assertCreated();
    }
}
