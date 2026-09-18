<?php

namespace Modules\HelpdeskAgents\Mcp\Tools;

use Illuminate\Support\Facades\Log;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Modules\HelpdeskAgents\Mcp\McpToolContext;
use Nwidart\Modules\Facades\Module;

/**
 * Base de las herramientas MCP del helpdesk.
 *
 * Cada tool es una envoltura fina sobre un servicio que YA existe
 * (ErpContextService, PrestashopContextService, ContactAggregatorService...).
 * Aqui no vive logica de negocio: la cache, el circuit breaker y el control de
 * propiedad estan en esos servicios y no se replican.
 *
 * Tres reglas que esta clase hace cumplir, no solo documenta:
 *
 *  1. SOLO LECTURA. Ninguna subclase escribe en el ERP ni en PrestaShop.
 *     Cambiar el estado de un pedido o abrir una devolucion sigue siendo una
 *     accion humana, con su propia pantalla y su propio permiso.
 *  2. El email del cliente lo resuelve resolveCustomerEmail(), que en modo
 *     bloqueado ignora lo que pida el modelo. Ver McpToolContext.
 *  3. Los resultados se recortan antes de salir: un historial de pedidos largo
 *     no puede inflar el contexto del LLM sin techo.
 */
abstract class HelpdeskTool extends Tool
{
    /** Filas maximas devueltas por una lista. */
    protected int $maxRows = 20;

    /**
     * Punto de entrada unico: convierte cualquier fallo de la tool en un error
     * MCP en vez de dejarlo escapar.
     *
     * Sin esto, una excepcion (ambito sin resolver, ERP caido, argumento
     * invalido) sube hasta la capa de transporte y rompe la respuesta JSON-RPC
     * entera, en lugar de contestar "esta herramienta ha fallado" y dejar que
     * el cliente siga. En el camino interno la semantica se conserva:
     * McpToolBridge vuelve a lanzar al ver un Response de error, y
     * AgentLlmService se lo reporta al modelo como herramienta fallida.
     */
    final public function handle(Request $request): Response
    {
        try {
            return $this->run($request);
        } catch (\Throwable $e) {
            Log::warning('HelpdeskTool: fallo al ejecutar', [
                'tool' => static::class,
                'error' => $e->getMessage(),
            ]);

            return Response::error($e->getMessage());
        }
    }

    abstract protected function run(Request $request): Response;

    protected function context(): McpToolContext
    {
        return app(McpToolContext::class);
    }

    /**
     * Email del cliente sobre el que opera la tool.
     *
     * @throws \RuntimeException cuando no hay ninguno resoluble
     */
    protected function resolveCustomerEmail(Request $request): string
    {
        $context = $this->context();

        if ($context->isLocked()) {
            $email = $context->customerEmail();

            if ($email === null) {
                throw new \RuntimeException('El ticket no tiene un cliente con email asociado.');
            }

            return $email;
        }

        $email = trim((string) ($request->get('customer_email') ?? ''));

        if ($email === '') {
            throw new \RuntimeException('Falta customer_email.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('customer_email no es una direccion valida.');
        }

        return $email;
    }

    /**
     * Dependencia opcional en runtime: un modulo satelite puede estar
     * desinstalado o desactivado. Se comprueba igual que en
     * CustomerSummaryService, para degradar en vez de reventar.
     */
    protected function moduleAvailable(string $module, string $serviceClass): bool
    {
        return Module::find($module)?->isEnabled() === true && class_exists($serviceClass);
    }

    /**
     * @param  array<int|string, mixed>  $rows
     * @return array<int, mixed>
     */
    protected function limitRows(array $rows, ?int $max = null): array
    {
        return array_slice(array_values($rows), 0, $max ?? $this->maxRows);
    }

    /**
     * Deja solo las claves utiles de cada fila. Un pedido del ERP trae decenas
     * de columnas que al modelo no le dicen nada y se pagan en tokens.
     *
     * @param  array<int, mixed>  $rows
     * @param  array<int, string>  $keys
     * @return array<int, array<string, mixed>>
     */
    protected function pluckKeys(array $rows, array $keys): array
    {
        return array_values(array_map(function ($row) use ($keys): array {
            $row = is_array($row) ? $row : (array) $row;

            $out = [];
            foreach ($keys as $key) {
                if (array_key_exists($key, $row)) {
                    $out[$key] = $row[$key];
                }
            }

            return $out !== [] ? $out : $row;
        }, $rows));
    }

    /**
     * Texto plano acotado — para descripciones y cuerpos de mensaje.
     */
    protected function clip(?string $text, int $chars = 400): string
    {
        return mb_substr(trim(strip_tags((string) $text)), 0, $chars);
    }
}
