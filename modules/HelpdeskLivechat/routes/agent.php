<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskLivechat\Http\Controllers\Agent\AgentCatalogController;

/*
|--------------------------------------------------------------------------
| HelpdeskLivechat — rutas de agente (panel)
|--------------------------------------------------------------------------
| Montadas por HelpdeskLivechatServiceProvider con prefijo `panel/helpdesk/livechat`
| y nombre `helpdesk-livechat.agent.`, bajo web+auth+can:helpdesk.conversations.reply.
| El agente busca productos del catálogo y los comparte en la conversación
| (coviewer) sin salir del panel.
*/

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/conversations/{conversation}/catalog/search', [AgentCatalogController::class, 'search'])
        ->name('catalog.search');

    Route::post('/conversations/{conversation}/catalog/share', [AgentCatalogController::class, 'share'])
        ->name('catalog.share');
});
