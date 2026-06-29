<?php

use Illuminate\Support\Facades\Route;
use Modules\Reviews\Http\Controllers\PublicReviewController;

Route::middleware(['web', 'throttle:60,1'])->group(function () {
    Route::get('/reviews/widget', [PublicReviewController::class, 'widget'])
        ->name('reviews.widget');

    Route::get('/reviews/embed-code', [PublicReviewController::class, 'embedCode'])
        ->name('reviews.embed-code');

    Route::get('/reviews/cards', [PublicReviewController::class, 'cardsJson'])
        ->name('reviews.cards');

    Route::get('/reviews/data', [PublicReviewController::class, 'dataJson'])
        ->name('reviews.public-data');
});
