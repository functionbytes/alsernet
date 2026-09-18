<?php

namespace Modules\HelpdeskAgents\Tests\Feature;

use Illuminate\Support\Facades\Http;
use Modules\HelpdeskAgents\Mcp\McpToolContext;
use Modules\HelpdeskAgents\Mcp\Servers\HelpdeskMcpServer;
use Modules\HelpdeskAgents\Mcp\Tools\GetCustomerContext;
use Modules\HelpdeskAgents\Mcp\Tools\ListReplyTemplates;
use Tests\TestCase;

/**
 * Servidor MCP del helpdesk.
 *
 * Comparte definicion de herramientas con el sugeridor de respuestas, pero se
 * sirve en el modo ABIERTO: quien invoca es una persona autenticada, no el
 * texto de un ticket. Lo que se comprueba aqui es que ese modo pide un cliente
 * explicito en vez de heredar ninguno.
 */
class HelpdeskMcpServerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        // Un servidor MCP nunca hereda ambito: quien invoca es una persona.
        app(McpToolContext::class)->release();
    }

    public function test_it_advertises_every_tool(): void
    {
        $tools = (new \ReflectionClass(HelpdeskMcpServer::class))
            ->getDefaultProperties()['tools'];

        $this->assertCount(9, $tools);
        $this->assertContains(GetCustomerContext::class, $tools);
    }

    public function test_a_tool_call_without_a_customer_is_rejected(): void
    {
        $response = HelpdeskMcpServer::tool(GetCustomerContext::class, []);

        // Sin ticket que fije el ambito y sin customer_email, no hay sobre
        // quien consultar: mejor un error claro que una consulta a ciegas.
        $response->assertHasErrors();
    }

    public function test_a_tool_call_with_a_malformed_email_is_rejected(): void
    {
        HelpdeskMcpServer::tool(GetCustomerContext::class, ['customer_email' => 'no-es-un-email'])
            ->assertHasErrors();
    }

    public function test_a_tool_that_needs_no_customer_answers(): void
    {
        // list-reply-templates es catalogo interno, no dato de cliente: no
        // exige ambito y debe responder aunque no haya email.
        HelpdeskMcpServer::tool(ListReplyTemplates::class, ['limit' => 3])
            ->assertHasNoErrors();
    }
}
