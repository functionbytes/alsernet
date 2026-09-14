<?php

namespace Modules\HelpdeskAgents\Tests\Unit\Mcp;

use Laravel\Mcp\Request as McpRequest;
use Modules\Helpdesk\Models\Customer;
use Modules\HelpdeskAgents\Mcp\McpToolContext;
use Modules\HelpdeskAgents\Mcp\Tools\GetCustomerContext;
use Modules\HelpdeskAgents\Services\McpToolBridge;
use Modules\HelpdeskTickets\Models\Ticket;
use Tests\TestCase;

/**
 * Definicion unica de herramientas y ambito de cliente.
 *
 * Lo que se protege aqui es la regla de seguridad del sugeridor de respuestas:
 * el contenido de un ticket lo escribe el cliente, asi que el modelo no puede
 * usarlo para pedir datos de OTRO cliente. Con el contexto bloqueado a un
 * ticket, cualquier customer_email que llegue en los argumentos se ignora.
 */
class McpToolBridgeTest extends TestCase
{
    private function ticketFor(string $email): Ticket
    {
        // Modelos sin persistir: aqui se ejercita la resolucion de ambito, no
        // el acceso a datos.
        $customer = new Customer(['name' => 'Cliente', 'email' => $email]);
        $ticket = new Ticket(['subject' => 'Prueba']);
        $ticket->setRelation('customer', $customer);

        return $ticket;
    }

    private function resolveEmail(McpRequest $request): string
    {
        $tool = new GetCustomerContext;
        $method = new \ReflectionMethod($tool, 'resolveCustomerEmail');
        $method->setAccessible(true);

        return $method->invoke($tool, $request);
    }

    public function test_a_locked_context_ignores_the_email_the_model_asks_for(): void
    {
        app(McpToolContext::class)->scopeToTicket($this->ticketFor('real@cliente.test'));

        $email = $this->resolveEmail(new McpRequest(['customer_email' => 'victima@otra-empresa.test']));

        $this->assertSame('real@cliente.test', $email);
    }

    public function test_an_open_context_honours_the_email_argument(): void
    {
        app(McpToolContext::class)->release();

        $email = $this->resolveEmail(new McpRequest(['customer_email' => 'consulta@cliente.test']));

        $this->assertSame('consulta@cliente.test', $email);
    }

    public function test_an_open_context_rejects_a_malformed_email(): void
    {
        app(McpToolContext::class)->release();

        $this->expectException(\RuntimeException::class);

        $this->resolveEmail(new McpRequest(['customer_email' => 'no-es-un-email']));
    }

    public function test_release_clears_the_scope_so_it_cannot_leak_between_jobs(): void
    {
        $context = app(McpToolContext::class);
        $context->scopeToTicket($this->ticketFor('primero@cliente.test'));

        $this->assertTrue($context->isLocked());

        $context->release();

        $this->assertFalse($context->isLocked());
        $this->assertNull($context->customerEmail());
    }

    public function test_every_tool_exposes_a_usable_json_schema(): void
    {
        $definitions = app(McpToolBridge::class)->definitions();

        $this->assertNotEmpty($definitions);

        foreach ($definitions as $definition) {
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]{1,64}$/', $definition['name']);
            $this->assertNotSame('', $definition['description'], "{$definition['name']} sin descripcion");
            $this->assertSame('object', $definition['input_schema']['type']);
            // `properties` debe serializar como objeto JSON, no como lista: un
            // array PHP vacio se codifica [] y el proveedor devuelve un 400.
            $this->assertStringContainsString(
                '"properties":{',
                json_encode($definition['input_schema']),
                "{$definition['name']} serializa properties como lista"
            );
        }
    }

    public function test_an_unknown_tool_name_lists_the_real_catalogue(): void
    {
        $executor = app(McpToolBridge::class)->executor();

        try {
            $executor('herramienta-inventada', []);
            $this->fail('Se esperaba una excepcion.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('herramienta-inventada', $e->getMessage());
            $this->assertStringContainsString('get-customer-context', $e->getMessage());
        }
    }

    public function test_only_narrows_the_offered_tools(): void
    {
        $bridge = app(McpToolBridge::class)->only([GetCustomerContext::class]);

        $this->assertCount(1, $bridge->definitions());
        $this->assertSame('get-customer-context', $bridge->definitions()[0]['name']);
    }
}
