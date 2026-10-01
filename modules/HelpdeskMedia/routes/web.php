<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskMedia\Http\Controllers\BlockedPlaceholderController;

// Pública a propósito: la ven el agente, el widget del cliente y los canales.
Route::get('helpdesk-media/blocked', BlockedPlaceholderController::class)->name('helpdesk-media.blocked');
