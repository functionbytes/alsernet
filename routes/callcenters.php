<?php


use App\Http\Controllers\Callcenters\DashboardController;
use App\Http\Controllers\Callcenters\Faqs\FaqsController;
use App\Http\Controllers\Callcenters\Faqs\CategoriesController as FaqsCategoriesController;
use App\Http\Controllers\Callcenters\Settings\SettingsController;
use App\Http\Controllers\Callcenters\Tickets\CommentsController;
use App\Http\Controllers\Callcenters\Tickets\TicketsController;
use Illuminate\Support\Facades\Route;


Route::group(['prefix' => 'callcenter', 'middleware' => ['auth', 'roles:callcenters']], function () {

    Route::get('/', [DashboardController::class, 'dashboard'])->name('callcenter.dashboard');

    Route::group(['prefix' => 'notifications'], function () {

        Route::get('/', [NotificationsController::class, 'index'])->name('callcenter.notifications');
        Route::get('/mark-as-read', [NotificationsController::class, 'markasread'])->name('callcenter.notifications.markasread');
        Route::get('/all', [NotificationsController::class, 'show'])->name('callcenter.notifications.markallnotify');

    });

    Route::group(['prefix' => 'assigneds'], function () {
        Route::get('/assigned', [AssignedsController::class, 'markNotification'])->name('callcenter.tickets.allactiveinprogresstickets');
        Route::get('/reopen', [AssignedsController::class, 'allactivereopentickets'])->name('callcenter.tickets.allactivereopentickets');
        Route::get('/onhold', [AssignedsController::class, 'allactiveonholdtickets'])->name('callcenter.tickets.allactiveonholdtickets');
        Route::get('/assigned', [AssignedsController::class, 'allactiveassignedtickets'])->name('callcenter.tickets.allactiveassignedtickets');
    });

    Route::group(['prefix' => 'settings'], function () {
        Route::get('/profile', [SettingsController::class, 'profile'])->name('callcenter.settings.profile');
        Route::get('/notifications', [SettingsController::class, 'notifications'])->name('callcenter.settings.notifications');
        Route::post('/profile/update', [SettingsController::class, 'updateProfile'])->name('callcenter.settings.profile.update');
        Route::post('/notifications/update', [SettingsController::class, 'updateNotifications'])->name('callcenter.settings.notifications.update');
    });


    Route::group(['prefix' => 'faqs'], function () {

        Route::get('/', [FaqsController::class, 'index'])->name('callcenter.faqs');
        Route::get('/create', [FaqsController::class, 'create'])->name('callcenter.faqs.create');
        Route::post('/store', [FaqsController::class, 'store'])->name('callcenter.faqs.store');
        Route::post('/update', [FaqsController::class, 'update'])->name('callcenter.faqs.update');
        Route::get('/edit/{slack}', [FaqsController::class, 'edit'])->name('callcenter.faqs.edit');
        Route::get('/destroy/{slack}', [FaqsController::class, 'destroy'])->name('callcenter.faqs.destroy');

        Route::get('/categories', [FaqsCategoriesController::class, 'index'])->name('callcenter.faqs.categories');
        Route::get('/categories/create', [FaqsCategoriesController::class, 'create'])->name('callcenter.faqs.categories.create');
        Route::post('/categories/store', [FaqsCategoriesController::class, 'store'])->name('callcenter.faqs.categories.store');
        Route::post('/categories/update', [FaqsCategoriesController::class, 'update'])->name('callcenter.faqs.categories.update');
        Route::get('/categories/edit/{slack}', [FaqsCategoriesController::class, 'edit'])->name('callcenter.faqs.categories.edit');
        Route::get('/categories/destroy/{slack}', [FaqsCategoriesController::class, 'destroy'])->name('callcenter.faqs.categories.destroy');

    });

    Route::group(['prefix' => 'tickets'], function () {

        Route::get('/', [TicketsController::class, 'index'])->name('callcenter.tickets');
        Route::get('/create', [TicketsController::class, 'create'])->name('callcenter.tickets.create');
        Route::post('/store', [TicketsController::class, 'store'])->name('callcenter.tickets.store');
        Route::post('/update', [TicketsController::class, 'update'])->name('callcenter.tickets.update');
        Route::get('/edit/{slack}', [TicketsController::class, 'edit'])->name('callcenter.tickets.edit');
        Route::get('/view/{slack}', [TicketsController::class, 'view'])->name('callcenter.tickets.view');

        Route::delete('/destroy/{slack}', [TicketsController::class, 'destroy'])->name('callcenter.tickets.destroy');
        Route::post('/reopen/{slack}', [CommentsController::class, 'reopenticket'])->name('callcenter.tickets.reopen');
        Route::get('/previous/{slack}', [TicketsController::class, 'previous'])->name('callcenter.tickets.previous');

        Route::post('/image/upload/{slack}', [TicketsController::class, 'storeMedia'])->name('callcenter.tickets.image.store');
        Route::post('/image/upload', [TicketsController::class, 'guestmedia'])->name('callcenter.tickets.image.upload');

        Route::post('/priority/change', [TicketsController::class, 'changepriority'])->name('callcenter.tickets.change.priority');

        Route::post('/note/create', [TicketsController::class, 'note'])->name('callcenter.tickets.note.create');
        Route::delete('/note/{slack}', [TicketsController::class, 'notedestroy'])->name('callcenter.tickets.note.destroy');

        Route::get('/comment/{slack}', [CommentsController::class, 'view'])->name('callcenter.tickets.comments');
        Route::post('/comment/post/{slack}', [CommentsController::class, 'postComment'])->name('callcenter.tickets.comments.post');
        Route::post('/comment/edit/{slack}', [CommentsController::class, 'updateedit'])->name('callcenter.tickets.comments.edit');
        Route::get('/comment/delete/{slack}', [CommentsController::class, 'deletecomment'])->name('callcenter.tickets.comments.delete');
        Route::delete('/comment/image/upload/{slack}', [CommentsController::class, 'imagedestroy'])->name('callcenter.tickets.image.destroy');

        Route::post('/assigned', [TicketsController::class, 'create'])->name('callcenter.tickets.assigned');
        Route::get('/assigned/{slack}', [TicketsController::class, 'edit'])->name('callcenter.tickets.assigned.view');
        Route::get('/assigned/edit/{slack}', [TicketsController::class, 'view'])->name('callcenter.tickets.assigned.edit');

        Route::post('/notes/store', [TicketsController::class, 'notestore'])->name('callcenter.tickets.notes.store');
        Route::get('/notes/show/{slack}', [TicketsController::class, 'noteshow'])->name('callcenter.tickets.notes.show');
        Route::get('/notes/delete/{slack}', [TicketsController::class, 'notedelete'])->name('callcenter.tickets.notes.delete');

        Route::post('/employeesreplyingremove', 'AdminTicketController@employeesreplyingremove')->name('employeesreplyingremove');
        Route::post('/employeesreplyingstore', 'AdminTicketController@employeesreplyingstore')->name('employeesreplyingstore');
        Route::get('/getemployeesreplying/{id}', 'AdminTicketController@getemployeesreplying')->name('getemployeesreplying');
        Route::get('/ticket/{ticket_id}', 'AdminTicketController@destroy');
        Route::post('/priority/change/', 'AdminTicketController@changepriority');
        Route::get('/ticket/delete/tickets', 'AdminTicketController@ticketmassdestroy')->name('admin.ticket.sremove');
        Route::get('/ticket-view/{ticket_id}', 'AdminTicketController@show')->name('admin.ticketshow');
        Route::post('/ticket-comment/{ticket_id}', 'AdminTicketController@commentshow')->name('admin.ticketcomment');

        Route::post('/closed/{ticket_id}', 'AdminTicketController@close');
        Route::get('/delete-ticket/{id}', 'AdminTicketController@destroy');

        Route::get('/createticket', 'AdminTicketController@createticket');
        Route::post('/createticket', 'AdminTicketController@gueststore');
        Route::post('/imageupload', 'AdminTicketController@guestmedia')->name('imageuploadadmin');
        Route::get('/alltickets', 'AdminTicketController@alltickets')->name('admin.alltickets');

        Route::get('/inprogress', [NotificationsController::class, 'allactiveinprogresstickets'])->name('callcenter.notifications.markallnotify');

        Route::get('/selfassigneds', [TrashedsController::class, 'selfassignticketview'])->name('callcenter.tickets.selfassigned');
        Route::get('/assigneds', [TrashedsController::class, 'myassignedTickets'])->name('callcenter.tickets.assigneds');
        Route::get('/closeds', [TrashedsController::class, 'myclosedtickets'])->name('callcenter.tickets.closeds');
        Route::get('/suspends', [TrashedsController::class, 'mysuspendtickets'])->name('callcenter.tickets.history.suspendss');

        Route::get('/trasheds', [TrashedsController::class, 'tickettrashed'])->name('callcenter.tickets.trasheds');
        Route::get('/trasheds/view/{slack}', [TrashedsController::class, 'tickettrashedview'])->name('callcenter.tickets.trasheds.view');
        Route::post('/trasheds/restore/{slack}', [TrashedsController::class, 'tickettrashedrestore'])->name('callcenter.tickets.trasheds.restore');
        Route::delete('/trasheds/destroy/{slack}', [TrashedsController::class, 'tickettrasheddestroy'])->name('callcenter.tickets.trasheds.destroy');
        Route::post('/trasheds/restore/all', [TrashedsController::class, 'alltrashedticketrestore'])->name('callcenter.tickets.trasheds.restore.all');
        Route::post('/trasheds/destroy/all', [TrashedsController::class, 'alltrashedticketdelete'])->name('callcenter.tickets.trasheds.destroy.all');

        Route::get('/history/{slack}', [TrashedsController::class, 'tickethistory'])->name('callcenter.tickets.history');
        Route::get('/history/users/{slack}', [TrashedsController::class, 'customerprevioustickets'])->name('callcenter.tickets.history.users');

    });

});
