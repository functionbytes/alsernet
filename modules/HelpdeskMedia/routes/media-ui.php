<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskMedia\Http\Controllers\MediaUiController;

// Cargado con web+auth y el prefijo panel/helpdesk/media desde MediaUiServiceProvider.
Route::get('conversations/{conversation}', [MediaUiController::class, 'conversation'])
    ->whereNumber('conversation')
    ->name('conversation');

Route::get('tickets/{ticket}', [MediaUiController::class, 'ticket'])
    ->whereNumber('ticket')
    ->name('ticket');
