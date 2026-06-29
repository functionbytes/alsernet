<?php

use Illuminate\Support\Facades\Route;
use Modules\Newsletter\Http\Controllers\Settings\NewsletterSettingsController;

Route::get('/', [NewsletterSettingsController::class, 'index'])->name('index');
Route::patch('/', [NewsletterSettingsController::class, 'update'])->name('update');
