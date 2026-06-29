<?php

use Illuminate\Support\Facades\Route;
use Modules\Shortcode\Http\Controllers\ShortcodeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rutas API del módulo Shortcode. Todas aplican rate limit y requieren
| autenticación Sanctum.
|
*/

// El RouteServiceProvider del módulo ya aplica prefix "api" y name "api.".
// Aquí sólo añadimos el segmento propio del recurso.
// Rate limit personalizado 'shortcode-api': per-user si auth, per-IP si no.
// Definido en ShortcodeServiceProvider::registerRateLimiters.
Route::middleware(['auth:sanctum', 'throttle:shortcode-api'])
    ->prefix('shortcodes')
    ->name('shortcode.')
    ->group(function () {
        // Compilar shortcodes en contenido arbitrario
        Route::post('/compile', [ShortcodeController::class, 'compile'])->name('compile');

        // Eliminar shortcodes del contenido
        Route::post('/strip', [ShortcodeController::class, 'strip'])->name('strip');

        // Listar nombres de shortcodes registrados
        Route::get('/list', [ShortcodeController::class, 'list'])->name('list');

        // Listar shortcodes con metadata (para pickers/UI)
        Route::get('/registered', [ShortcodeController::class, 'registered'])->name('registered');

        // Verificar si un shortcode existe
        Route::post('/check', [ShortcodeController::class, 'check'])->name('check');

        // Limpiar cache del compilador
        Route::post('/clear-cache', [ShortcodeController::class, 'clearCache'])->name('clear-cache');

        // Stats de uso
        Route::get('/stats', [ShortcodeController::class, 'stats'])->name('stats');
        Route::post('/stats/reset', [ShortcodeController::class, 'resetStats'])->name('stats.reset');
    });
