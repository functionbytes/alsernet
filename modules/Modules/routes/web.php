<?php

use Illuminate\Support\Facades\Route;
use Modules\Modules\Http\Controllers\ModulesController;

/*
|--------------------------------------------------------------------------
| Modules Settings Web Routes
|--------------------------------------------------------------------------
|
| Rutas para la gestión de vistas y operaciones de módulos
| URL: /settings/modules (aplicado por RouteServiceProvider)
| Middleware: web, auth, role:super-admin (aplicado por RouteServiceProvider)
| GET para renderizar vistas, POST/PUT para operaciones de módulos
|
*/

// Modules management routes (Views)
Route::get('/', [ModulesController::class, 'index'])->name('index');
Route::get('/{moduleAlias}', [ModulesController::class, 'show'])->name('show');
Route::get('/{moduleAlias}/edit', [ModulesController::class, 'edit'])->name('edit');
Route::get('/upload/form', [ModulesController::class, 'uploadForm'])->name('uploadForm');

// Modules management operations (POST, PUT, DELETE)
Route::put('/{moduleAlias}', [ModulesController::class, 'update'])->name('update');
Route::post('/install', [ModulesController::class, 'install'])->name('install');
Route::post('/{moduleAlias}/enable', [ModulesController::class, 'enable'])->name('enable');
Route::post('/{moduleAlias}/disable', [ModulesController::class, 'disable'])->name('disable');
Route::post('/{moduleAlias}/uninstall', [ModulesController::class, 'uninstall'])->name('uninstall');
