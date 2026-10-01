<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskAiPrompts\Http\Controllers\AiActionCatalogController;
use Modules\HelpdeskAiPrompts\Http\Controllers\AiActionController;

/*
|--------------------------------------------------------------------------
| HelpdeskAiPrompts: catálogo de acciones (pestaña "Acciones")
|--------------------------------------------------------------------------
| Se monta desde el ServiceProvider con el mismo middleware, prefijo
| (`panel/helpdesk/ai-prompts`) y nombre (`helpdesk-ai-prompts.`) que web.php.
| El grupo ya exige `helpdesk.ai-prompts.view`; todo lo de aquí es de
| escritura (o ejecuta acciones reales) y exige además `.manage`.
| La lista de acciones es la pestaña `?tab=acciones` del índice.
*/

Route::middleware('can:helpdesk.ai-prompts.manage')
    ->prefix('actions')
    ->name('actions.')
    ->group(function () {
        Route::get('create', [AiActionController::class, 'create'])->name('create');
        Route::post('/', [AiActionController::class, 'store'])->name('store');
        Route::get('{aiAction}/edit', [AiActionController::class, 'edit'])->name('edit');
        Route::put('{aiAction}', [AiActionController::class, 'update'])->name('update');
        Route::delete('{aiAction}', [AiActionController::class, 'destroy'])->name('destroy');
        Route::patch('{aiAction}/toggle-active', [AiActionController::class, 'toggleActive'])->name('toggle-active');
        // Ejecuta la acción de verdad (bridge/http): 15/min por usuario.
        Route::post('{aiAction}/test', [AiActionController::class, 'test'])
            ->middleware('throttle:15,1')
            ->name('test');
        Route::get('{aiAction}/history', [AiActionController::class, 'history'])->name('history');
        Route::post('{aiAction}/versions/{version}/restore', [AiActionController::class, 'restoreVersion'])->name('versions.restore');
    });

// Catálogo para el editor de ChatFlow: solo lectura, basta `.view` (el del grupo).
Route::get('actions/catalog', [AiActionCatalogController::class, 'index'])->name('actions.catalog-json');
