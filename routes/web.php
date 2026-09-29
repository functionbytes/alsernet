<?php

use App\Http\Controllers\FileServeController;

Route::group(['middleware' => ['web']], function () {

    // Seguridad 29-sep-2026: eliminadas GET /clear (público: vaciaba cachés y
    // contadores de throttle; hay POST protegido en System > Caché), /files,
    // /thumbs y /p/assets (lectura de ficheros sin normalizar, sin uso).

    // Referenciada por nombre en PathHelper::generatePublicPath(); confinada a storage/app/public.
    Route::get('assets/{dirname}/{basename}', [FileServeController::class, 'publicAsset'])
        ->name('public_assets');

});
