<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskAiPrompts\Http\Controllers\AgentActionsController;

/*
|--------------------------------------------------------------------------
| HelpdeskAiPrompts: acciones del catálogo lanzadas por el agente (bandeja)
|--------------------------------------------------------------------------
| Montado desde el ServiceProvider con web + auth, prefijo
| `panel/helpdesk/agent-actions` y nombre `helpdesk-ai-prompts.agent-actions.`.
| NO exige `.view` (biblioteca): basta `.agent-actions` más la política de
| la conversación. Ejecutar: 20/min por usuario.
*/

Route::middleware('can:helpdesk.ai-prompts.agent-actions')->group(function () {
    Route::get('conversations/{conversation}', [AgentActionsController::class, 'index'])->name('index');
    Route::post('conversations/{conversation}/run', [AgentActionsController::class, 'run'])
        ->middleware('throttle:20,1')
        ->name('run');
});
