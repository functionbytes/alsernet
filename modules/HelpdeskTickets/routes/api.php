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
Route::get('tickets', [TicketsController::class, 'index']);
Route::post('tickets', [TicketsController::class, 'store']);
Route::get('tickets/{ticketNumber}', [TicketsController::class, 'show']);
Route::put('tickets/{ticketNumber}', [TicketsController::class, 'update']);

// Ticket messages
Route::get('tickets/{ticketNumber}/messages', [MessagesController::class, 'index']);
Route::post('tickets/{ticketNumber}/messages', [MessagesController::class, 'store']);

// Ticket metadata
Route::get('categories', [CategoriesController::class, 'index']);
Route::get('priorities', [PrioritiesController::class, 'index']);
Route::get('statuses', [StatusesController::class, 'index']);

// Metrics
Route::get('metrics/summary', [MetricsController::class, 'summary']);
Route::get('metrics/by-agent', [MetricsController::class, 'byAgent']);

// Templates
Route::get('templates', [TemplatesController::class, 'index']);

// Recurring tickets
Route::get('recurring-tickets', [RecurringTicketsController::class, 'index']);

// Ratings
Route::get('ratings/summary', [RatingsController::class, 'summary']);

// Full-text search
Route::get('search', [SearchController::class, 'index']);
