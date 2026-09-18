<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Portal\CustomerPortalController;

Route::get('/', [CustomerPortalController::class, 'index'])->name('index');
Route::get('login', [CustomerPortalController::class, 'showLogin'])->name('login');
Route::post('login', [CustomerPortalController::class, 'login'])->name('login.submit');
Route::get('auth/{token}', [CustomerPortalController::class, 'authenticate'])->name('authenticate');
Route::post('logout', [CustomerPortalController::class, 'logout'])->name('logout');
Route::get('tickets', [CustomerPortalController::class, 'tickets'])->name('tickets');
Route::get('tickets/create', [CustomerPortalController::class, 'createTicket'])->name('tickets.create');
Route::post('tickets', [CustomerPortalController::class, 'storeTicket'])->name('tickets.store');
// Deflexión: artículos que podrían resolver la duda antes de abrir el ticket.
// Throttle propio: se dispara mientras el cliente escribe, no al enviar.
Route::post('tickets/suggest-articles', [CustomerPortalController::class, 'suggestArticles'])
    ->middleware('throttle:30,1')
    ->name('tickets.suggest-articles');
Route::get('tickets/{ticketNumber}', [CustomerPortalController::class, 'showTicket'])->name('tickets.show');
// Descarga de un adjunto del propio ticket. El controlador vuelve a comprobar
// que el ticket es del cliente en sesión y que el adjunto cuelga de ese ticket.
Route::get('tickets/{ticketNumber}/attachments/{attachment}', [CustomerPortalController::class, 'downloadAttachment'])
    ->name('tickets.attachments.download')
    ->whereNumber('attachment');
Route::post('tickets/{ticketNumber}/reply', [CustomerPortalController::class, 'replyToTicket'])->name('tickets.reply');
Route::post('tickets/{ticketNumber}/rate', [CustomerPortalController::class, 'rateTicket'])->name('tickets.rate');
Route::get('tickets/{ticketNumber}/rate/{rating}', [CustomerPortalController::class, 'rateTicketFromEmail'])->name('tickets.rate.email')->middleware('signed');
Route::get('account', [CustomerPortalController::class, 'account'])->name('account');
Route::put('account', [CustomerPortalController::class, 'updateAccount'])->name('account.update');
