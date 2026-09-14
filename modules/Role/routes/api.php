<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Modules\Role\Http\Controllers\PermissionController;
use Modules\Role\Http\Controllers\RoleController;
use Modules\Role\Http\Controllers\UserPermissionController;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Model binding for route parameters
Route::model('role', Role::class);
Route::model('user', User::class);
Route::model('permission', Permission::class);

// Role API routes (POST, PUT, DELETE)
Route::prefix('roles')->name('roles.')->group(function () {
    Route::post('/bulk-action', [RoleController::class, 'bulkAction'])->name('bulk-action');
    Route::post('/', [RoleController::class, 'store'])->name('store');
    Route::put('{role}', [RoleController::class, 'update'])->name('update');
    Route::delete('{role}', [RoleController::class, 'destroy'])->name('destroy');
    Route::post('{role}/permissions', [RoleController::class, 'updatePermissions'])->name('update.permissions');
    Route::post('{role}/modules', [RoleController::class, 'updateModules'])->name('update.modules');
    Route::post('{role}/users', [RoleController::class, 'assignUsers'])->name('assign.users');
    Route::post('{role}/users/bulk-remove', [RoleController::class, 'bulkRemoveUsers'])->name('users.bulk-remove');
    Route::delete('{role}/users/{user}', [RoleController::class, 'removeUser'])->name('remove.user');
    Route::post('{role}/duplicate', [RoleController::class, 'duplicate'])->name('duplicate');
    Route::post('{role}/copy-from', [RoleController::class, 'copyPermissionsFrom'])->name('copy-from');
});

// Permission API routes (POST, PUT, DELETE)
Route::prefix('permissions')->name('permissions.')->group(function () {
    Route::post('/bulk-action', [PermissionController::class, 'bulkAction'])->name('bulk-action');
    Route::post('/', [PermissionController::class, 'store'])->name('store');
    Route::put('{permission}', [PermissionController::class, 'update'])->name('update');
    Route::delete('{permission}', [PermissionController::class, 'destroy'])->name('destroy');
});

// User direct permissions API routes (POST mutations)
Route::prefix('user-permissions')->name('user-permissions.')->group(function () {
    Route::post('/{user}', [UserPermissionController::class, 'update'])->name('update');
    Route::post('/{user}/sync', [UserPermissionController::class, 'sync'])->name('sync');
});
