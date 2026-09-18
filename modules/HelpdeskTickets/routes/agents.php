<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Agents\CannedRepliesController;
use Modules\HelpdeskTickets\Http\Controllers\Agents\DashboardController;
use Modules\HelpdeskTickets\Http\Controllers\Agents\MessagesController;
use Modules\HelpdeskTickets\Http\Controllers\Agents\ReportsController;
use Modules\HelpdeskTickets\Http\Controllers\Agents\TicketsController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

Route::prefix('tickets')->name('tickets.')->group(function () {
    Route::get('/', [TicketsController::class, 'index'])->name('index');
    Route::get('/create', [TicketsController::class, 'create'])->name('create');
    Route::post('/', [TicketsController::class, 'store'])->name('store');
    Route::get('/{ticket}', [TicketsController::class, 'show'])->name('show');
    Route::get('/{ticket}/edit', [TicketsController::class, 'edit'])->name('edit');
    Route::put('/{ticket}', [TicketsController::class, 'update'])->name('update');
    Route::delete('/{ticket}', [TicketsController::class, 'destroy'])->name('destroy');
    Route::post('/{ticket}/assign', [TicketsController::class, 'assign'])->name('assign');
    Route::delete('/{ticket}/assign', [TicketsController::class, 'unassign'])->name('unassign');
    Route::post('/{ticket}/close', [TicketsController::class, 'close'])->name('close');
    Route::post('/{ticket}/reopen', [TicketsController::class, 'reopen'])->name('reopen');
});

Route::prefix('tickets/{ticket}/messages')->name('messages.')->group(function () {
    Route::get('/', [MessagesController::class, 'index'])->name('index');
    Route::post('/', [MessagesController::class, 'store'])->name('store');
    Route::put('/{message}', [MessagesController::class, 'update'])->name('update');
    Route::delete('/{message}', [MessagesController::class, 'destroy'])->name('destroy');
});

Route::prefix('canned-replies')->name('canned-replies.')->group(function () {
    Route::get('/search', [CannedRepliesController::class, 'search'])->name('search');
});

Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/', [ReportsController::class, 'index'])->name('index');
    Route::get('/export', [ReportsController::class, 'export'])->name('export');
});
