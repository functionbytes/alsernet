<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskTickets\Http\Controllers\Api\CategoriesController;
use Modules\HelpdeskTickets\Http\Controllers\Api\MessagesController;
use Modules\HelpdeskTickets\Http\Controllers\Api\MetricsController;
use Modules\HelpdeskTickets\Http\Controllers\Api\PrioritiesController;
use Modules\HelpdeskTickets\Http\Controllers\Api\RatingsController;
use Modules\HelpdeskTickets\Http\Controllers\Api\RecurringTicketsController;
use Modules\HelpdeskTickets\Http\Controllers\Api\SearchController;
use Modules\HelpdeskTickets\Http\Controllers\Api\StatusesController;
use Modules\HelpdeskTickets\Http\Controllers\Api\TemplatesController;
use Modules\HelpdeskTickets\Http\Controllers\Api\TicketsController;

// Tickets
Route::get('tickets', [TicketsController::class, 'index'])->name('tickets.index');
Route::post('tickets', [TicketsController::class, 'store'])->name('tickets.store');
Route::get('tickets/{ticketNumber}', [TicketsController::class, 'show'])->name('tickets.show');
Route::put('tickets/{ticketNumber}', [TicketsController::class, 'update'])->name('tickets.update');

// Ticket messages
Route::get('tickets/{ticketNumber}/messages', [MessagesController::class, 'index'])->name('tickets.messages.index');
Route::post('tickets/{ticketNumber}/messages', [MessagesController::class, 'store'])->name('tickets.messages.store');

// Ticket metadata
Route::get('categories', [CategoriesController::class, 'index'])->name('categories.index');
Route::get('priorities', [PrioritiesController::class, 'index'])->name('priorities.index');
Route::get('statuses', [StatusesController::class, 'index'])->name('statuses.index');

// Metrics
Route::get('metrics/summary', [MetricsController::class, 'summary'])->name('metrics.summary');
Route::get('metrics/by-agent', [MetricsController::class, 'byAgent'])->name('metrics.by-agent');

// Templates
Route::get('templates', [TemplatesController::class, 'index'])->name('templates.index');

// Recurring tickets
Route::get('recurring-tickets', [RecurringTicketsController::class, 'index'])->name('recurring-tickets.index');

// Ratings
Route::get('ratings/summary', [RatingsController::class, 'summary'])->name('ratings.summary');

// Full-text search
Route::get('search', [SearchController::class, 'index'])->name('search.index');
