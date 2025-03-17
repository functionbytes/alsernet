<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\ValidationController;
use App\Http\Controllers\Pages\ChatbotController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Pages\PagesController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;


use App\Models\Subscriber\Subscriber;
use App\Jobs\Erp\SynchronizationSubscription;


Route::get('/testprestashop', function () {
    $activeCustomers = DB::connection('prestashop')
        ->table('aalv_customer')
        ->where('active', 1)
        ->get();

    return $activeCustomers;
    //$customers = Customer::all();
});
Route::get('/testsend', function () {
    try {
        Mail::raw('Este es un correo de prueba enviado desde Laravel sin usar Mailable.', function ($message) {
            $message->to('revoxservices@gmail.com')
                    ->subject('Correo de Prueba');
        });

        return '✅ Correo de prueba enviado correctamente.';
    } catch (\Exception $e) {
        return '❌ Error al enviar el correo: ' . $e->getMessage();
    }
});

Route::group(['middleware' => ['web']], function () {



    Route::get('/chatbot', [ChatbotController::class, 'show'])->name('chatbot.show');
    Route::post('/chatbot/handle', [ChatbotController::class, 'handle'])->name('chatbot.handle');

    Route::get('/', [LoginController::class, 'showLoginForm'])->name('index');
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('auth.login');
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/register', [LoginController::class, 'showRegisterForm'])->name('register');
    Route::get('/home', [PagesController::class, 'home'])->name('home');

    Route::get('/clear', function () {
        Artisan::call('dump-autoload');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Artisan::call('config:cache');
        return '<h1>Cache Borrado</h1>';
    });

    Route::group(['prefix' => 'password'], function () {
        Route::get('/confirm', [ForgotPasswordController::class, 'showLinkRequest'])->name('password.confirm');
        Route::get('/reset', [ForgotPasswordController::class, 'showLinkRequest'])->name('password.reset');
        Route::post('/reset', [ResetPasswordController::class, 'reset']);
        Route::post('/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('/reset/{slack}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset.token');
    });

    Route::get('/files/{uid}/{name?}', [ function ($uid, $name) {
        $path = storage_path('app/users/' . $uid . '/home/files/' . $name);
        $mime_type = \App\Library\File::getFileType($path);
        if (\Illuminate\Support\Facades\File::exists($path)) {
            return response()->file($path, array('Content-Type' => $mime_type));
        } else {
            abort(404);
        }
    }])->where('name', '.+')->name('user_files');

    // assets path for customer thumbs
    Route::get('/thumbs/{uid}/{name?}', [ function ($uid, $name) {
        // Do not use $user->getThumbsPath($name), avoid one SQL query!
        $path = storage_path('app/users/' . $uid . '/home/thumbs/' . $name);
        if (\Illuminate\Support\Facades\File::exists($path)) {
            $mime_type = \App\Library\File::getFileType($path);
            return response()->file($path, array('Content-Type' => $mime_type));
        } else {
            abort(404);
        }
    }])->where('name', '.+')->name('user_thumbs');


    Route::get('/p/assets/{path}', [ function ($token) {
        $decodedPath = \App\Library\StringHelper::base64UrlDecode($token);
        $absPath = storage_path($decodedPath);

        if (\Illuminate\Support\Facades\File::exists($absPath)) {
            $mime_type = \App\Library\File::getFileType($absPath);
            return response()->file($absPath, array(
                'Content-Type' => $mime_type,
                'Content-Length' => filesize($absPath),
            ));
        } else {
            abort(404);
        }
    }])->name('public_assets_deprecated');

    Route::get('assets/{dirname}/{basename}', [ function ($dirname, $basename) {
        $dirname = \App\Library\StringHelper::base64UrlDecode($dirname);
        $absPath = storage_path(join_paths($dirname, $basename));

        if (\Illuminate\Support\Facades\File::exists($absPath)) {
            $mimetype = \App\Library\File::getFileType($absPath);
            return response()->file($absPath, array(
                'Content-Type' => $mimetype,
                'Content-Length' => filesize($absPath),
            ));
        } else {
            abort(404);
        }
    }])->name('public_assets');

    Route::get('setting/{filename}', 'SettingController@file');

    Route::get('/datatable_locale', 'Controller@datatable_locale');
    Route::get('/jquery_validate_locale', 'Controller@jquery_validate_locale');

});


