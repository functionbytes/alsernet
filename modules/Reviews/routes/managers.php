<?php

use Illuminate\Support\Facades\Route;
use Modules\Reviews\Http\Controllers\Managers\ReviewSettingsController;
use Modules\Reviews\Http\Controllers\Managers\ReviewsManagerController;
use Modules\Reviews\Http\Controllers\Managers\ReviewSourcesController;

/*
| Bandeja de moderación de opiniones.
|
| El grupo ya exige 'can:reviews.view' (lectura); las acciones que cambian algo
| piden además 'reviews.moderate' desde el propio controlador.
*/
Route::get('/', [ReviewsManagerController::class, 'index'])->name('reviews.index');

/* Ajustes del módulo. Las URIs van en inglés como el resto del panel
   (settings, manage, campaigns...); los textos de pantalla, en español. */
Route::get('/settings', [ReviewSettingsController::class, 'index'])->name('reviews.settings');
Route::post('/settings', [ReviewSettingsController::class, 'update'])->name('reviews.settings.update');

/* Fichas de las que se leen opiniones (tiendas físicas en Google). */
Route::get('/sources', [ReviewSourcesController::class, 'index'])->name('reviews.sources.index');
Route::get('/sources/{source}', [ReviewSourcesController::class, 'show'])->name('reviews.sources.show')->whereNumber('source');
Route::post('/sources', [ReviewSourcesController::class, 'store'])->name('reviews.sources.store');
Route::post('/sources/{source}', [ReviewSourcesController::class, 'update'])->name('reviews.sources.update');
Route::post('/sources/{source}/test', [ReviewSourcesController::class, 'test'])->name('reviews.sources.test');
Route::post('/sources/{source}/fetch', [ReviewSourcesController::class, 'fetch'])->name('reviews.sources.fetch');
Route::delete('/sources/{source}', [ReviewSourcesController::class, 'destroy'])->name('reviews.sources.destroy');
Route::get('/{review}', [ReviewsManagerController::class, 'show'])->name('reviews.show')->whereNumber('review');
Route::post('/bulk', [ReviewsManagerController::class, 'bulk'])->name('reviews.bulk');
Route::post('/{review}/approve', [ReviewsManagerController::class, 'approve'])->name('reviews.approve');
Route::post('/{review}/reject', [ReviewsManagerController::class, 'reject'])->name('reviews.reject');
Route::post('/{review}/answer', [ReviewsManagerController::class, 'answer'])->name('reviews.answer');
Route::post('/{review}/translate', [ReviewsManagerController::class, 'translate'])->name('reviews.translate');
Route::post('/{review}/pull-translations', [ReviewsManagerController::class, 'pullTranslations'])->name('reviews.translations.pull');
Route::post('/{review}/screen', [ReviewsManagerController::class, 'screen'])->name('reviews.screen');
Route::post('/{review}/translations/{translation}/approve', [ReviewsManagerController::class, 'approveTranslation'])->name('reviews.translations.approve');
Route::post('/{review}/translations/{translation}', [ReviewsManagerController::class, 'updateTranslation'])->name('reviews.translations.update');
Route::post('/{review}/publish-translations', [ReviewsManagerController::class, 'publishTranslations'])->name('reviews.translations.publish');
