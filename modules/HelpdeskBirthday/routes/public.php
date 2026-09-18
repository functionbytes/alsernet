<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskBirthday\Http\Controllers\UnsubscribeController;

/*
| Baja pública del correo de cumpleaños.
|
| Sin 'auth': el destinatario no tiene cuenta. La protección es la firma de
| URL::signedRoute, que impide dar de baja a terceros manipulando el email.
*/
Route::get('cumpleanos/baja', [UnsubscribeController::class, 'unsubscribe'])
    ->middleware('signed')
    ->name('helpdeskbirthday.unsubscribe');
