<?php

use App\Http\Controllers\Managers\Newsletters\NewslettersConditionsController;
use App\Http\Controllers\Managers\Newsletters\NewslettersListsUserController;
use App\Http\Controllers\Managers\Newsletters\NewslettersReportController;
use App\Http\Controllers\Managers\Newsletters\NewslettersListsController;
use App\Http\Controllers\Managers\Newsletters\NewslettersController;
use App\Http\Controllers\Managers\Settings\SettingsController;
use App\Http\Controllers\Managers\DashboardController;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'shop', 'middleware' => ['auth', 'roles:shops']], function () {

    Route::get('/', [DashboardController::class, 'dashboard'])->name('manager.dashboard');

    Route::group(['prefix' => 'settings'], function () {
        Route::get('/', [SettingsController::class, 'index'])->name('manager.settings');
        Route::post('/update', [SettingsController::class, 'update'])->name('manager.settings.update');
    });

    Route::group(['prefix' => 'newsletters'], function () {

        Route::get('/', [NewslettersController::class, 'index'])->name('manager.newsletters');
        Route::get('/create', [NewslettersController::class, 'create'])->name('manager.newsletters.create');
        Route::get('/lists', [NewslettersListsController::class, 'index'])->name('manager.newsletters.lists');
        Route::post('/update', [NewslettersController::class, 'update'])->name('manager.newsletters.update');

        Route::get('/lists/report', [NewslettersListsController::class, 'report'])->name('manager.newsletters.lists.reports');
        Route::get('/lists/create', [NewslettersListsController::class, 'create'])->name('manager.newsletters.lists.create');
        Route::post('/lists/update', [NewslettersListsController::class, 'update'])->name('manager.newsletters.lists.update');
        Route::post('/lists/store', [NewslettersListsController::class, 'store'])->name('manager.newsletters.lists.store');
        Route::get('/edit/{slack}', [NewslettersController::class, 'edit'])->name('manager.newsletters.edit');
        Route::get('/view/{slack}', [NewslettersController::class, 'view'])->name('manager.newsletters.view');
        Route::get('/destroy/{slack}', [NewslettersController::class, 'destroy'])->name('manager.newsletters.destroy');
        Route::get('/list/{slack}', [NewslettersController::class, 'list'])->name('manager.newsletters.list');


        Route::get('/lists/reports', [NewslettersReportController::class, 'report'])->name('manager.newsletters.lists.reports');
        Route::get('/lists/details/{slack}', [NewslettersListsController::class, 'details'])->name('manager.newsletters.lists.details');
        Route::get('/lists/edit/{slack}', [NewslettersListsController::class, 'edit'])->name('manager.newsletters.lists.edit');
        Route::get('/lists/view/{slack}', [NewslettersListsController::class, 'view'])->name('manager.newsletters.lists.view');
        Route::get('/lists/destroy/{slack}', [NewslettersListsController::class, 'destroy'])->name('manager.newsletters.lists.destroy');
        Route::get('/lists/includes/{slack}', [NewslettersListsController::class, 'includes'])->name('manager.newsletters.lists.includes');
        Route::post('/lists/includes/update', [NewslettersListsController::class, 'updateIncludes'])->name('manager.newsletters.lists.includes.update');

        Route::get('/lists/report/generate', [NewslettersReportController::class, 'generate'])->name('manager.newsletters.lists.reports.generate');

        Route::get('/conditions', [NewslettersConditionsController::class, 'index'])->name('manager.newsletters.conditions');
        Route::get('/conditions/create', [NewslettersConditionsController::class, 'create'])->name('manager.newsletters.conditions.create');
        Route::post('/conditions/store', [NewslettersConditionsController::class, 'store'])->name('manager.newsletters.conditions.store');
        Route::post('/conditions/update', [NewslettersConditionsController::class, 'update'])->name('manager.newsletters.conditions.update');
        Route::get('/conditions/edit/{slack}', [NewslettersConditionsController::class, 'edit'])->name('manager.newsletters.conditions.edit');
        Route::get('/conditions/view/{slack}', [NewslettersConditionsController::class, 'view'])->name('manager.newsletters.conditions.view');
        Route::get('/conditions/destroy/{slack}', [NewslettersConditionsController::class, 'destroy'])->name('manager.newsletters.conditions.destroy');


        Route::get('/lists/destroy/newsletter/{slack}', [NewslettersListsUserController::class, 'destroy'])->name('manager.newsletters.lists.user.destroy');

    });




});
