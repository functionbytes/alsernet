<?php

use App\Http\Controllers\FileServeController;

Route::group(['middleware' => ['web']], function () {

    Route::get('/clear', [FileServeController::class, 'clearCache']);

    Route::get('/files/{uid}/{name}', [FileServeController::class, 'userFile'])
        ->where('name', '.+')
        ->name('user_files');

    Route::get('/thumbs/{uid}/{name}', [FileServeController::class, 'userThumb'])
        ->where('name', '.+')
        ->name('user_thumbs');

    Route::get('/p/assets/{path}', [FileServeController::class, 'publicAssetDeprecated'])
        ->name('public_assets_deprecated');

    Route::get('assets/{dirname}/{basename}', [FileServeController::class, 'publicAsset'])
        ->name('public_assets');

});
