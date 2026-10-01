<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskAiPrompts\Http\Controllers\QualityController;

/*
|--------------------------------------------------------------------------
| HelpdeskAiPrompts: calidad del asistente (regresión + alertas)
|--------------------------------------------------------------------------
| Lo monta QualityServiceProvider con el mismo middleware (`can:helpdesk.ai-prompts.view`),
| prefijo (`panel/helpdesk/ai-prompts`) y nombre (`helpdesk-ai-prompts.`) que web.php.
| Lanzar una regresión gasta IA: exige además `.manage` y se limita por usuario.
*/

Route::prefix('calidad')->name('quality.')->group(function () {
    Route::get('/', [QualityController::class, 'index'])->name('index');
    Route::get('reports/{report}', [QualityController::class, 'show'])->name('reports.show');

    Route::post('cases/{case}/regression', [QualityController::class, 'run'])
        ->middleware(['can:helpdesk.ai-prompts.manage', 'throttle:6,1'])
        ->name('regression.run');
});
