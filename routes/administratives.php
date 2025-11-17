<?php



use App\Http\Controllers\Administratives\Orders\DocumentsController;
use App\Http\Controllers\Administratives\DashboardController;

Route::group(['prefix' => 'administrative', 'middleware' => ['auth', 'roles:administratives']], function () {

    Route::get('/', [DashboardController::class, 'dashboard'])->name('administrative.dashboard');


    Route::group(['prefix' => 'orders'], function () {

        Route::get('/', [DocumentsController::class, 'index'])->name('administrative.documents');
        Route::get('/create', [DocumentsController::class, 'create'])->name('administrative.documents.create');
        Route::post('/store', [DocumentsController::class, 'store'])->name('administrative.documents.store');
        Route::post('/update', [DocumentsController::class, 'update'])->name('administrative.documents.update');
        Route::get('/edit/{slack}', [DocumentsController::class, 'edit'])->name('administrative.documents.edit');
        Route::get('/view/{slack}', [DocumentsController::class, 'view'])->name('administrative.documents.view');
        Route::get('/destroy/{slack}', [DocumentsController::class, 'destroy'])->name('administrative.documents.destroy');

        Route::post('/files', [DocumentsController::class, 'storeFiles'])->name('administrative.documents.files');
        Route::get('/delete/files/{id}', [DocumentsController::class, 'deleteFiles'])->name('administrative.documents.files.delete');
        Route::get('/get/files/{id}', [DocumentsController::class, 'getFiles'])->name('administrative.documents.files.get');

        Route::get('/summary/{id}', [DocumentsController::class, 'summary'])->name('administrative.documents.summary');


    });


});
