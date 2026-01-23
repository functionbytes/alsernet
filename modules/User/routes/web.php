<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\UsersController;

/*
|--------------------------------------------------------------------------
| User Settings Web Routes
|--------------------------------------------------------------------------
|
| Rutas para la gestión de vistas de usuarios
| SOLO GET para renderizar vistas. POST, PUT, DELETE están en routes/api.php
| Prefix: /settings/users (aplicado por UserServiceProvider)
| Name: settings.users.* (aplicado por UserServiceProvider)
| Middleware: web, auth, role:super-admin
|
*/

Route::get('/', [UsersController::class, 'index'])->name('index');
Route::get('/create', [UsersController::class, 'create'])->name('create');
Route::get('{uid}', [UsersController::class, 'view'])->name('show');
Route::get('{uid}/edit', [UsersController::class, 'edit'])->name('edit');
