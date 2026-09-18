<?php

namespace Modules\HelpdeskAgents\Services;

use Laravel\Mcp\Request as McpRequest;
use Laravel\Mcp\Server\Tool;
use Modules\HelpdeskAgents\Mcp\Tools\GetContact360;
use Modules\HelpdeskAgents\Mcp\Tools\GetCustomerContext;
use Modules\HelpdeskAgents\Mcp\Tools\GetCustomerTimeline;
use Modules\HelpdeskAgents\Mcp\Tools\GetOrderDetail;
use Modules\HelpdeskAgents\Mcp\Tools\GetTicketHistory;
use Modules\HelpdeskAgents\Mcp\Tools\ListReplyTemplates;
use Modules\HelpdeskAgents\Mcp\Tools\SearchKnowledge;
use Modules\HelpdeskAgents\Mcp\Tools\SearchOrders;
use Modules\HelpdeskAgents\Mcp\Tools\SearchProducts;

/**
 * Puente entre las herramientas MCP y el tool-calling de AgentLlmService.
 *
 * Es la pieza que hace real la "definicion unica": una clase Laravel\Mcp\Server\Tool
 * se escribe una vez y la consumen dos caminos distintos —
 *
 *   HelpdeskMcpServer  ->  JSON-RPC  ->  Claude Desktop / Claude Code
 *   McpToolBridge      ->  tools[]   ->  AgentLlmService::chatWithTools()
 *
 * — sin duplicar ni el esquema ni la implementacion. Anadir una tool a
 * DEFAULT_TOOLS y a HelpdeskMcpServer::$tools la deja disponible en ambos.
 *
 * El puente traduce en las dos direcciones: el JSON Schema que ya produce
 * Tool::toArray() se entrega al proveedor tal cual, y la Response MCP se
 * aplana a texto para devolverla como resultado de herramienta.
 */
class McpToolBridge
{
    /**
     * Herramientas ofrecidas por defecto al sugerir una respuesta.
     *
     * @var array<int, class-string<Tool>>
     */
    public const DEFAULT_TOOLS = [
        GetCustomerContext::class,
        SearchOrders::class,
        GetOrderDetail::class,
        GetTicketHistory::class,
        GetContact360::class,
        SearchKnowledge::class,
        GetCustomerTimeline::class,
        SearchProducts::class,
        ListReplyTemplates::class,
    ];

    /** @var array<string, Tool>|null */
    private ?array $resolved = null;

    /** @var array<int, class-string<Tool>>|null */
    private ?array $classes = null;

    /**
     * Restringe el puente a un subconjunto de herramientas.
     *
     * Menos herramientas en el prompt significa menos tokens de entrada en
     * CADA iteracion del bucle, asi que conviene ofrecer solo las que la tarea
     * puede necesitar.
     *
     * @param  array<int, class-string<Tool>>  $classes
     */
    public function only(array $classes): static
    {
        $clone = new static;
        $clone->classes = $classes;

        return $clone;
    }

    /**
     * Definiciones en el formato canonico que espera AgentLlmService.
     *
     * @return array<int, array{name: string, description: string, input_schema: array<string, mixed>}>
     */
    public function definitions(): array
    {
        return array_values(array_map(function (Tool $tool): array {
            $array = $tool->toArray();

            return [
                'name' => $array['name'],
                'description' => (string) ($array['description'] ?? ''),
                'input_schema' => $this->normalizeSchema($array['inputSchema'] ?? []),
            ];
        }, $this->tools()));
    }

    /**
     * Ejecutor para AgentLlmService::chatWithTools().
     *
     * Devuelve texto plano en lugar de estructuras: es lo que acaba en el
     * contexto del modelo, y ya viene recortado por byte desde el propio
     * cliente LLM.
     *
     * @return callable(string, array<string, mixed>): string
     */
    public function executor(): callable
    {
        return function (string $name, array $arguments): string {
            $tools = $this->tools();

            if (! isset($tools[$name])) {
                // Al modelo se le devuelve el catalogo real en vez de un "no
                // existe" a secas: normalmente reintenta con el nombre bueno.
                throw new \RuntimeException(
                    "La herramienta '{$name}' no existe. Disponibles: ".implode(', ', array_keys($tools))
                );
            }

            $response = $tools[$name]->handle(new McpRequest($arguments));

            if (is_object($response) && method_exists($response, 'isError') && $response->isError()) {
                throw new \RuntimeException((string) $response->content());
            }

            return (string) $response->content();
        };
    }

    /**
     * @return array<string, Tool> indexado por nombre de herramienta
     */
    public function tools(): array
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $tools = [];

        foreach ($this->classes ?? self::DEFAULT_TOOLS as $class) {
            /** @var Tool $tool */
            $tool = app($class);
            $tools[$tool->name()] = $tool;
        }

        return $this->resolved = $tools;
    }

    /**
     * Los proveedores rechazan un objeto sin `properties`, y PHP serializa un
     * array vacio como `[]` (lista JSON) en vez de `{}` (objeto). Sin esto,
     * una tool sin argumentos hace fallar la peticion entera con un 400.
     *
     * @param  array<string, mixed>  $schema
     * @return array<string, mixed>
     */
    private function normalizeSchema(array $schema): array
    {
        $schema['type'] ??= 'object';

        if (empty($schema['properties'])) {
            $schema['properties'] = (object) [];
        }

        return $schema;
    }
}
