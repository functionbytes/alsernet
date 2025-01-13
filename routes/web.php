<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\VerificationController;
use App\Http\Controllers\Auth\ValidationController;
use App\Http\Controllers\Auth\LoginController;

use App\Http\Controllers\Pages\ContactsController;
use App\Http\Controllers\Pages\FeedingsController;
use App\Http\Controllers\Pages\InterestedsController;
use App\Http\Controllers\Pages\PagesController;
use App\Http\Controllers\Pages\FaqsController;
use App\Http\Controllers\Pages\BlogController;


Route::get('/clear-cache', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    return 'Application cache cleared';
});

Route::get('/auth-checker', function () {
    if (auth::check()) {
        return true;
    } else {
        return false;
    }
})->name('auth-checker');

Route::get('/users/{user_id}', function ($user_id) {
    return view('welcome');
});

Route::get('language/switch/{language}', function (Request $request, $language) {
    $request->session()->put('active_language', $language);
    return redirect()->back();
})->name('language.switch');



Route::group(['middleware' => ['web']], function () {

    Route::get('/', [PagesController::class, 'index'])->name('index');

    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('auth.login');
    Route::get('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/register', [LoginController::class, 'showRegisterForm'])->name('register');

    Route::get('/', [PagesController::class, 'index'])->name('index');
    Route::get('/verified', [VerificationController::class, 'verified'])->name('verified');
    Route::get('/validation', [ValidationController::class, 'validation'])->name('validation');
    Route::get('/home', [PagesController::class, 'home'])->name('home');
    Route::get('/faqs', [PagesController::class, 'faqs'])->name('faqs');
    Route::get('/terms', [PagesController::class, 'terms'])->name('terms');
    Route::get('/politics', [PagesController::class, 'politics'])->name('politics');
    Route::get('/coming', [PagesController::class, 'coming'])->name('coming');

    Route::controller(VerificationController::class)->group(function() {
        Route::get('/email/verify', 'show')->name('verification.notice')->middleware('auth');
        Route::get('/email/verify/{id}/{hash}', 'verify')->name('verification.verify')->middleware(['auth', 'signed']);
        Route::post('/email/resend', 'resend')->middleware(['auth', 'throttle:6,1'])->name('verification.resend');
    });


    Route::get('/clear', function () {
        Artisan::call('dump-autoload');
        Artisan::call('cache:clear');
        Artisan::call('route:clear');
        Artisan::call('view:clear');
        Artisan::call('config:clear');
        Artisan::call('config:cache');
        return '<h1>Cache Borrado</h1>';
    });

    Route::group(['prefix' => 'blogs'], function () {
        Route::get('/', [BlogController::class, 'index'])->name('blogs');
        Route::get('/{slug}', [BlogController::class, 'view'])->name('blogs.view');
        Route::post('/filters', [BlogController::class, 'filters'])->name('blogs.filters');
        Route::get('/categories/{slug}', [BlogController::class, 'categories'])->name('blogs.categories');
        Route::get('/tags/{slug}', [BlogController::class, 'tags'])->name('blogs.tags');
    });


    Route::group(['prefix' => 'faqs'], function () {
        Route::get('/', [FaqsController::class, 'index'])->name('faqs');
        Route::get('/{slug}', [FaqsController::class, 'view'])->name('faqs.view');
    });


    Route::group(['prefix' => 'interesteds'], function () {
        Route::get('/{slack}', [InterestedsController::class, 'index'])->name('interesteds');
        Route::post('/interesteds/store', [InterestedsController::class, 'interested'])->name('interesteds.store');
        Route::post('/interesteds/payments', [InterestedsController::class, 'payments'])->name('interesteds.payments');
        Route::post('/interesteds/generate', [InterestedsController::class, 'generate'])->name('interesteds.generate');
        Route::post('/interesteds/coupon/apply', [InterestedsController::class, 'applyCoupon'])->name('interesteds.coupon.apply');
        Route::post('/interesteds/coupon/clear', [InterestedsController::class, 'clearCoupon'])->name('interesteds.coupon.clear');

    });

    Route::group(['prefix' => 'feedings'], function () {
        Route::get('/{slack}', [FeedingsController::class, 'index'])->name('feedings');
        Route::post('/interesteds/store', [FeedingsController::class, 'store'])->name('feedings.store');

    });

    Route::group(['prefix' => 'payments'], function () {
        Route::get('/processing', [InterestedsController::class, 'processing'])->name('payments.processing');
        Route::get('/response', [InterestedsController::class, 'response'])->name('payments.response');
        Route::get('/sțatus/{token}', [InterestedsController::class, 'status'])->name('payments.status');
    });

    Route::group(['prefix' => 'contacts'], function () {

        Route::get('/', [ContactsController::class, 'index'])->name('contacts');
        Route::get('/success/{slug}', [ContactsController::class, 'success'])->name('contacts.success');
        Route::post('/store', [ContactsController::class, 'storage'])->name('contacts.store');
    });

    Route::group(['prefix' => 'password'], function () {
        Route::get('/confirm', [ForgotPasswordController::class, 'showLinkRequest'])->name('password.confirm');
        Route::get('/reset', [ForgotPasswordController::class, 'showLinkRequest'])->name('password.reset');
        Route::post('/reset', [ResetPasswordController::class, 'reset']);
        Route::post('/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
        Route::get('/reset/{slack}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset.token');
    });

});
