<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskSocial\Http\Controllers\Settings\SocialModuleSettingsController;

Route::get('/', [SocialModuleSettingsController::class, 'index'])->name('index');
Route::put('/', [SocialModuleSettingsController::class, 'update'])->name('update');
