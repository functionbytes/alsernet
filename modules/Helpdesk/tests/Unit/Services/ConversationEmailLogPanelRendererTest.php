<?php

namespace Modules\Helpdesk\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\ConversationEmailLogPanelRenderer;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Tests\TestCase;

/**
 * ConversationEmailLogPanelRenderer es el lado Helpdesk del punto de
 * extensión EntityPanelRegistry de HelpdeskEmailActivity — mismo patrón de test
 * que TicketEmailLogPanelRendererTest.
 */
class ConversationEmailLogPanelRendererTest extends TestCase
{
    use DatabaseTransactions;

    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private ConversationEmailLogPanelRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new ConversationEmailLogPanelRenderer;
    }

    public function test_supports_returns_true_for_conversation_entity_type(): void
    {
        $this->assertTrue($this->renderer->supports(Conversation::class));
    }

    public function test_supports_returns_false_for_a_different_entity_type(): void
    {
        $this->assertFalse($this->renderer->supports(Customer::class));
    }

    public function test_render_returns_null_when_the_conversation_no_longer_exists(): void
    {
        $emailLog = EmailLog::factory()->create([
            'entity_type' => Conversation::class,
            'entity_id' => 999999,
        ]);

        $this->assertNull($this->renderer->render($emailLog));
    }

    public function test_render_includes_the_channel_and_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Cliente del hilo']);
        $conversation = Conversation::factory()->create([
            'customer_id' => $customer->id,
            'channel' => 'whatsapp',
        ]);

        $emailLog = EmailLog::factory()->create([
            'entity_type' => Conversation::class,
            'entity_id' => $conversation->id,
        ]);

        $html = $this->renderer->render($emailLog);

        $this->assertNotNull($html);
        $this->assertStringContainsString('WhatsApp', $html);
        $this->assertStringContainsString('Cliente del hilo', $html);
    }
}
