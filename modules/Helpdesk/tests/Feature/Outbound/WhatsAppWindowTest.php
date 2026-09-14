<?php

namespace Modules\Helpdesk\Tests\Feature\Outbound;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Tests\TestCase;

class WhatsAppWindowTest extends TestCase
{
    use DatabaseTransactions;

    protected $connectionsToTransact = [null, 'helpdesk'];

    public function test_ventana_abierta_para_whatsapp_reciente(): void
    {
        $c = Conversation::factory()->create([
            'channel' => 'whatsapp',
            'last_customer_message_at' => now()->subHours(2),
        ]);

        $this->assertTrue($c->isWhatsAppWindowOpen());
    }

    public function test_ventana_cerrada_para_whatsapp_antiguo(): void
    {
        $c = Conversation::factory()->create([
            'channel' => 'whatsapp',
            'last_customer_message_at' => now()->subHours(25),
        ]);

        $this->assertFalse($c->isWhatsAppWindowOpen());
    }

    public function test_ventana_cerrada_para_whatsapp_sin_mensaje_del_cliente(): void
    {
        $c = Conversation::factory()->create([
            'channel' => 'whatsapp',
            'last_customer_message_at' => null,
        ]);

        $this->assertFalse($c->isWhatsAppWindowOpen());
    }

    public function test_ventana_siempre_abierta_para_canales_no_whatsapp(): void
    {
        $c = Conversation::factory()->create([
            'channel' => 'web',
            'last_customer_message_at' => null,
        ]);

        $this->assertTrue($c->isWhatsAppWindowOpen());
    }
}
