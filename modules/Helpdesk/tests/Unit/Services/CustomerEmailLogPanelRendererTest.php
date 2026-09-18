<?php

namespace Modules\Helpdesk\Tests\Unit\Services;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Modules\Helpdesk\Models\Conversation;
use Modules\Helpdesk\Models\Customer;
use Modules\Helpdesk\Services\CustomerEmailLogPanelRenderer;
use Modules\HelpdeskEmailActivity\Models\EmailLog;
use Tests\TestCase;

/**
 * CustomerEmailLogPanelRenderer es el lado Helpdesk del punto de extensión
 * EntityPanelRegistry de HelpdeskEmailActivity — mismo patrón de test que
 * Modules\HelpdeskTickets\Tests\Unit\Services\TicketEmailLogPanelRendererTest.
 */
class CustomerEmailLogPanelRendererTest extends TestCase
{
    use DatabaseTransactions;

    // Customer vive en 'helpdesk'; EmailLog vive en la conexión default
    // ('mysql' en este entorno) — mismo gotcha ya documentado en
    // TicketEmailLogPanelRendererTest::$connectionsToTransact.
    protected array $connectionsToTransact = ['mariadb', 'helpdesk', 'mysql'];

    private CustomerEmailLogPanelRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->renderer = new CustomerEmailLogPanelRenderer;
    }

    public function test_supports_returns_true_for_customer_entity_type(): void
    {
        $this->assertTrue($this->renderer->supports(Customer::class));
    }

    public function test_supports_returns_false_for_a_different_entity_type(): void
    {
        $this->assertFalse($this->renderer->supports(Conversation::class));
        $this->assertFalse($this->renderer->supports('Modules\\HelpdeskTickets\\Models\\Ticket'));
    }

    public function test_render_returns_null_when_the_customer_no_longer_exists(): void
    {
        $emailLog = EmailLog::factory()->create([
            'entity_type' => Customer::class,
            'entity_id' => 999999,
        ]);

        $this->assertNull($this->renderer->render($emailLog));
    }

    public function test_render_includes_the_customer_summary(): void
    {
        $customer = Customer::factory()->create([
            'name' => 'Cliente de prueba',
            'email' => 'cliente-panel@example.test',
            'total_conversations' => 3,
        ]);

        $emailLog = EmailLog::factory()->create([
            'entity_type' => Customer::class,
            'entity_id' => $customer->id,
        ]);

        $html = $this->renderer->render($emailLog);

        $this->assertNotNull($html);
        $this->assertStringContainsString('Cliente de prueba', $html);
        $this->assertStringContainsString('cliente-panel@example.test', $html);
    }
}
