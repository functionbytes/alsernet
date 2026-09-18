<?php

use Laravel\Mcp\Facades\Mcp;
use Modules\HelpdeskAgents\Mcp\Servers\HelpdeskMcpServer;
use Nwidart\Modules\Facades\Module;

/*
|--------------------------------------------------------------------------
| Servidores MCP
|--------------------------------------------------------------------------
|
| Cargado automaticamente por laravel/mcp cuando este fichero existe.
|
| El servidor del helpdesk expone datos de clientes reales (ERP Oracle de
| produccion, tienda viva) en modo solo lectura. Dos transportes, con perfiles
| de riesgo distintos:
|
|  - local (stdio): lo arranca el propio usuario con `php artisan mcp:start
|    helpdesk` desde su Claude Code / Claude Desktop. No abre ningun puerto y
|    corre con los permisos de quien lo lanza. Es la via recomendada.
|
|  - web (HTTP): util para un cliente MCP que no puede lanzar procesos. Va
|    autenticado y detras del permiso `helpdesk.ai.use`. NO debe publicarse
|    fuera de la red interna. Se apaga con HELPDESKAGENTS_MCP_SERVER=false.
|
| El sugeridor de respuestas del helpdesk NO pasa por aqui: ejecuta las mismas
| herramientas dentro de la aplicacion via McpToolBridge, precisamente para no
| tener que exponer esta ruta a internet.
|
*/

if (! class_exists(HelpdeskMcpServer::class) || Module::find('HelpdeskAgents')?->isEnabled() !== true) {
    return;
}

Mcp::local('helpdesk', HelpdeskMcpServer::class);

if (config('helpdeskagents.mcp.server_enabled', true)) {
    Mcp::web(
        (string) config('helpdeskagents.mcp.server_route', 'mcp/helpdesk'),
        HelpdeskMcpServer::class
    )->middleware([
        // Guard de sesion: es el unico definido en config/auth.php. Para
        // autenticar clientes MCP con tokens Sanctum hay que anadir antes el
        // guard 'sanctum' a config/auth.php y cambiar 'auth' por 'auth:sanctum'.
        'web',
        'auth',
        'can:helpdesk.ai.use',
    ]);
}
