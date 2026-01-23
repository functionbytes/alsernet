<?php

use Modules\Reverb\Http\Controllers\BroadcastController;

Route::group(['middleware' => ['web']], function () {

    // Broadcasting authentication endpoint for Reverb
    // Uses custom BroadcastController that extracts user from session even without cookies
    Route::post('/broadcasting/auth', [BroadcastController::class, 'authenticate'])
        ->name('broadcasting.auth')
        ->middleware('web');

    Route::get('/clear', function () {
        Artisan::call('dump-autoload');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Artisan::call('config:cache');

        return '<h1>Cache Borrado</h1>';
    });

    Route::get('/files/{uid}/{name?}', [function ($uid, $name) {
        $path = storage_path('app/users/'.$uid.'/home/files/'.$name);
        $mime_type = \app\Library\File::getFileType($path);
        if (\Illuminate\Support\Facades\File::exists($path)) {
            return response()->file($path, ['Content-Type' => $mime_type]);
        } else {
            abort(404);
        }
    }])->where('name', '.+')->name('user_files');

    // assets path for customer thumbs
    Route::get('/thumbs/{uid}/{name?}', [function ($uid, $name) {
        // Do not use $user->getThumbsPath($name), avoid one SQL query!
        $path = storage_path('app/users/'.$uid.'/home/thumbs/'.$name);
        if (\Illuminate\Support\Facades\File::exists($path)) {
            $mime_type = \app\Library\File::getFileType($path);

            return response()->file($path, ['Content-Type' => $mime_type]);
        } else {
            abort(404);
        }
    }])->where('name', '.+')->name('user_thumbs');

    Route::get('/p/assets/{path}', [function ($token) {
        $decodedPath = \app\Library\StringHelper::base64UrlDecode($token);
        $absPath = storage_path($decodedPath);

        if (\Illuminate\Support\Facades\File::exists($absPath)) {
            $mime_type = \app\Library\File::getFileType($absPath);

            return response()->file($absPath, [
                'Content-Type' => $mime_type,
                'Content-Length' => filesize($absPath),
            ]);
        } else {
            abort(404);
        }
    }])->name('public_assets_deprecated');

    Route::get('assets/{dirname}/{basename}', [function ($dirname, $basename) {
        $dirname = \app\Library\StringHelper::base64UrlDecode($dirname);
        $absPath = storage_path(join_paths($dirname, $basename));

        if (\Illuminate\Support\Facades\File::exists($absPath)) {
            $mimetype = \app\Library\File::getFileType($absPath);

            return response()->file($absPath, [
                'Content-Type' => $mimetype,
                'Content-Length' => filesize($absPath),
            ]);
        } else {
            abort(404);
        }
    }])->name('public_assets');

});
