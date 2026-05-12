<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskPrestashop\Http\Controllers\Api\PsEventReceiverController;

Route::post('/event', [PsEventReceiverController::class, 'handle'])->name('event');
