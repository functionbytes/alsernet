<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\UsersController;

/*
|--------------------------------------------------------------------------
| User Settings API Routes
|--------------------------------------------------------------------------
|
| Rutas API para la gestión de usuarios (POST, PUT, DELETE)
| Las vistas GET están en web.php
| Prefix: /settings/users (aplicado por UserServiceProvider)
| Name: settings.users.* (aplicado por UserServiceProvider)
| Middleware: web, auth, role:super-admin
|
*/

Route::post('/', [UsersController::class, 'store'])->name('store');
Route::post('/update', [UsersController::class, 'update'])->name('update');
Route::delete('{uid}', [UsersController::class, 'destroy'])->name('destroy');
