<?php

use App\Http\Controllers\Callcenters\DashboardController;
use App\Http\Controllers\Callcenters\Faqs\FaqsController;
use App\Http\Controllers\Callcenters\Faqs\CategoriesController as FaqsCategoriesController;
use App\Http\Controllers\Callcenters\Returns\ReturnController;
use App\Http\Controllers\Callcenters\Settings\SettingsController;
use App\Http\Controllers\Callcenters\Tickets\CommentsController;
use App\Http\Controllers\Callcenters\Tickets\TicketsController;
use Illuminate\Support\Facades\Route;

Route::prefix('callcenter')
    ->middleware(['auth',  'check.roles.permissions:callcenter'])
    ->name('callcenter.')
    ->group(function () {

        Route::get('/', [DashboardController::class, 'dashboard'])->name('dashboard');

        Route::prefix('notifications')->group(function () {
            Route::get('/', [NotificationsController::class, 'index'])->name('notifications');
            Route::get('/mark-as-read', [NotificationsController::class, 'markasread'])->name('notifications.markasread');
            Route::get('/all', [NotificationsController::class, 'show'])->name('notifications.markallnotify');
        });

        Route::prefix('returns')->name('returns.')->group(function () {
            Route::get('/', [ReturnController::class, 'index'])->name('index');
            Route::get('/validate', [ReturnController::class, 'validate'])->name('validate');
            Route::get('/generate/{uid}', [ReturnController::class, 'generate'])->name('generate');
            Route::get('/create', [ReturnController::class, 'create'])->name('create');
            Route::post('/store', [ReturnController::class, 'store'])->name('store');
            Route::post('/update/{id}', [ReturnController::class, 'update'])->name('update');
            Route::get('/edit/{uid}', [ReturnController::class, 'edit'])->name('edit');
            // Ver detalle de devolución
            Route::get('/show/{id}', [ReturnController::class, 'show'])->name('show');
            Route::get('/destroy/{uid}', [ReturnController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/status', [ReturnController::class, 'updateStatus'])->name('status.update');
            Route::post('/{id}/approve', [ReturnController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [ReturnController::class, 'reject'])->name('reject');
            Route::post('/{id}/assign', [ReturnController::class, 'assign'])->name('assign');
            Route::post('/{id}/discussion', [ReturnController::class, 'addDiscussion'])->name('discussion.add');
            Route::post('/{id}/attachment', [ReturnController::class, 'uploadAttachment'])->name('attachment.upload');
            Route::get('/{id}/payments', [ReturnController::class, 'getPayments'])->name('payments');
            Route::post('/{id}/payment', [ReturnController::class, 'addPayment'])->name('payment.add');
            Route::get('/export', [ReturnController::class, 'export'])->name('export');
            Route::get('/{id}/pdf', [ReturnController::class, 'downloadPDF'])->name('pdf');
            Route::post('/bulk-update', [ReturnController::class, 'bulkUpdate'])->name('bulk.update');

            Route::post('/validate-products', [ReturnController::class, 'validateProducts'])->name('validate-products');

            // Cancelar devolución
            Route::post('/{id}/cancel', [ReturnController::class, 'cancel'])->name('cancel');

            // Obtener productos de una orden (AJAX)
            Route::get('/order/{orderId}/products', [ReturnController::class, 'getOrderProducts'])->name('order-products');

                // AJAX endpoints
                Route::post('/carrier-time-slots', [ReturnController::class, 'getCarrierTimeSlots']);
                Route::post('/inpost-lockers', [ReturnController::class, 'getNearbyInPostLockers']);
                Route::post('/inpost-locker-details', [ReturnController::class, 'getInPostLockerDetails']);
                Route::post('/available-stores', [ReturnController::class, 'getAvailableStores']);
                Route::get('/{id}/tracking', [ReturnController::class, 'getTrackingStatus']);
                Route::post('/{id}/cancel-pickup', [ReturnController::class, 'cancelPickup']);
                Route::get('/document/{id}/download', [ReturnController::class, 'downloadDocument'])
                    ->name('returns.documents.download');
                Route::post('/scan-barcode', [ReturnController::class, 'scanBarcode']);


        });

        Route::prefix('settings')->group(function () {
            Route::get('/profile', [SettingsController::class, 'profile'])->name('settings.profile');
            Route::get('/notifications', [SettingsController::class, 'notifications'])->name('settings.notifications');
            Route::post('/profile/update', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
            Route::post('/notifications/update', [SettingsController::class, 'updateNotifications'])->name('settings.notifications.update');
        });

        Route::prefix('faqs')->group(function () {
            Route::get('/', [FaqsController::class, 'index'])->name('faqs');
            Route::get('/create', [FaqsController::class, 'create'])->name('faqs.create');
            Route::post('/store', [FaqsController::class, 'store'])->name('faqs.store');
            Route::post('/update', [FaqsController::class, 'update'])->name('faqs.update');
            Route::get('/edit/{uid}', [FaqsController::class, 'edit'])->name('faqs.edit');
            Route::get('/destroy/{uid}', [FaqsController::class, 'destroy'])->name('faqs.destroy');

            Route::get('/categories', [FaqsCategoriesController::class, 'index'])->name('faqs.categories');
            Route::get('/categories/create', [FaqsCategoriesController::class, 'create'])->name('faqs.categories.create');
            Route::post('/categories/store', [FaqsCategoriesController::class, 'store'])->name('faqs.categories.store');
            Route::post('/categories/update', [FaqsCategoriesController::class, 'update'])->name('faqs.categories.update');
            Route::get('/categories/edit/{uid}', [FaqsCategoriesController::class, 'edit'])->name('faqs.categories.edit');
            Route::get('/categories/destroy/{uid}', [FaqsCategoriesController::class, 'destroy'])->name('faqs.categories.destroy');
        });

        Route::prefix('tickets')->group(function () {
            Route::get('/', [TicketsController::class, 'index'])->name('tickets');
            Route::get('/create', [TicketsController::class, 'create'])->name('tickets.create');
            Route::post('/store', [TicketsController::class, 'store'])->name('tickets.store');
            Route::post('/update', [TicketsController::class, 'update'])->name('tickets.update');
            Route::get('/edit/{uid}', [TicketsController::class, 'edit'])->name('tickets.edit');
            Route::get('/view/{uid}', [TicketsController::class, 'view'])->name('tickets.view');
            Route::delete('/destroy/{uid}', [TicketsController::class, 'destroy'])->name('tickets.destroy');

            Route::post('/note/create', [TicketsController::class, 'note'])->name('tickets.note.create');
            Route::delete('/note/{uid}', [TicketsController::class, 'notedestroy'])->name('tickets.note.destroy');

            Route::get('/comment/{uid}', [CommentsController::class, 'view'])->name('tickets.comments');
            Route::post('/comment/post/{uid}', [CommentsController::class, 'postComment'])->name('tickets.comments.post');
            Route::post('/comment/edit/{uid}', [CommentsController::class, 'updateedit'])->name('tickets.comments.edit');
            Route::get('/comment/delete/{uid}', [CommentsController::class, 'deletecomment'])->name('tickets.comments.delete');
        });
    });
