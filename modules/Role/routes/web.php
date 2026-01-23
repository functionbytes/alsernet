<?php

use Illuminate\Support\Facades\Route;
use Modules\Role\Http\Controllers\PermissionController;
use Modules\Role\Http\Controllers\RoleController;
use Spatie\Permission\Models\Role;

// Model binding for {role} parameter
Route::model('role', Role::class);

// Roles management routes
Route::prefix('roles')->name('roles.')->group(function () {
    Route::get('/', [RoleController::class, 'index'])->name('index');
    Route::get('/create', [RoleController::class, 'create'])->name('create');
    Route::get('/matrix', [RoleController::class, 'showPermissionMatrix'])->name('matrix');
    Route::get('/show/{role}', [RoleController::class, 'show'])->name('show');
    Route::get('/edit/{role}', [RoleController::class, 'edit'])->name('edit');
    Route::get('/modules/{role}', [RoleController::class, 'showModules'])->name('show.modules');
    Route::get('/permissions/{role}', [RoleController::class, 'showPermissions'])->name('show.permissions');
    Route::get('/users/{role}', [RoleController::class, 'showUsers'])->name('show.users');
});

// Permissions management routes (view only - API routes in routes/api.php)
Route::prefix('permissions')->name('permissions.')->group(function () {
    Route::get('/', [PermissionController::class, 'index'])->name('index');
    Route::get('/create', [PermissionController::class, 'create'])->name('create');
    Route::get('/edit/{id}', [PermissionController::class, 'edit'])->name('edit');
});
