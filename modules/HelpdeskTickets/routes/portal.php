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
Route::get('tickets/{ticketNumber}', [CustomerPortalController::class, 'showTicket'])->name('tickets.show');
Route::post('tickets/{ticketNumber}/reply', [CustomerPortalController::class, 'replyToTicket'])->name('tickets.reply');
Route::post('tickets/{ticketNumber}/rate', [CustomerPortalController::class, 'rateTicket'])->name('tickets.rate');
Route::get('tickets/{ticketNumber}/rate/{rating}', [CustomerPortalController::class, 'rateTicketFromEmail'])->name('tickets.rate.email');
Route::get('account', [CustomerPortalController::class, 'account'])->name('account');
Route::put('account', [CustomerPortalController::class, 'updateAccount'])->name('account.update');
