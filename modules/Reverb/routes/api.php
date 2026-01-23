<?php

use Illuminate\Support\Facades\Route;
use Modules\Reverb\Http\Controllers\ReverbChannelController;
use Modules\Reverb\Http\Controllers\ReverbEventController;

/*
|--------------------------------------------------------------------------
| Reverb Module API Routes
|--------------------------------------------------------------------------
|
| Prefix: /api/reverb (applied by ServiceProvider)
| Name: api.reverb.* (applied by ServiceProvider)
|
| Real-time WebSocket and broadcasting API endpoints
*/

// Channel management
Route::middleware('can:reverb.channels.view')->group(function () {
    Route::get('/channels', [ReverbChannelController::class, 'list'])->name('channels.list');
    Route::get('/channels/{channel}', [ReverbChannelController::class, 'show'])->name('channels.show');
});

Route::middleware('can:reverb.channels.broadcast')->group(function () {
    Route::post('/channels/{channel}/broadcast', [ReverbChannelController::class, 'broadcast'])->name('channels.broadcast');
});

// Event broadcasting
Route::middleware('can:reverb.events.broadcast')->group(function () {
    Route::post('/broadcast', [ReverbEventController::class, 'broadcast'])->name('events.broadcast');
    Route::get('/events', [ReverbEventController::class, 'list'])->name('events.list');
});

// Presence channels
Route::middleware('can:reverb.presence.view')->group(function () {
    Route::get('/presence/{channel}', [ReverbChannelController::class, 'presence'])->name('presence.show');
});
