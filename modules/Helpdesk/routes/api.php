<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Api\CannedRepliesController;
use Modules\Helpdesk\Http\Controllers\Api\CategoriesController;
use Modules\Helpdesk\Http\Controllers\Api\CustomersController;
use Modules\Helpdesk\Http\Controllers\Api\MessagesController;
use Modules\Helpdesk\Http\Controllers\Api\MetricsController;
use Modules\Helpdesk\Http\Controllers\Api\PrioritiesController;
use Modules\Helpdesk\Http\Controllers\Api\RatingsController;
use Modules\Helpdesk\Http\Controllers\Api\RecurringTicketsController;
use Modules\Helpdesk\Http\Controllers\Api\SearchController;
use Modules\Helpdesk\Http\Controllers\Api\StatusesController;
use Modules\Helpdesk\Http\Controllers\Api\TemplatesController;
use Modules\Helpdesk\Http\Controllers\Api\TicketsController;

Route::get('tickets', [TicketsController::class, 'index']);
Route::post('tickets', [TicketsController::class, 'store']);
Route::get('tickets/{ticketNumber}', [TicketsController::class, 'show']);
Route::put('tickets/{ticketNumber}', [TicketsController::class, 'update']);

Route::get('tickets/{ticketNumber}/messages', [MessagesController::class, 'index']);
Route::post('tickets/{ticketNumber}/messages', [MessagesController::class, 'store']);

Route::get('categories', [CategoriesController::class, 'index']);
Route::get('priorities', [PrioritiesController::class, 'index']);
Route::get('statuses', [StatusesController::class, 'index']);

// Customers
Route::get('customers', [CustomersController::class, 'index']);
Route::post('customers', [CustomersController::class, 'store']);
Route::get('customers/{id}', [CustomersController::class, 'show']);

// Metrics
Route::get('metrics/summary', [MetricsController::class, 'summary']);
Route::get('metrics/by-agent', [MetricsController::class, 'byAgent']);

// Canned Replies
Route::get('canned-replies', [CannedRepliesController::class, 'index']);

// Ticket Templates
Route::get('templates', [TemplatesController::class, 'index']);

// Full-text search
Route::get('search', [SearchController::class, 'index']);

// Recurring tickets
Route::get('recurring-tickets', [RecurringTicketsController::class, 'index']);

// Ratings
Route::get('ratings/summary', [RatingsController::class, 'summary']);
