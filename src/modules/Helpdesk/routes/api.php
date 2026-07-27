<?php

use Illuminate\Support\Facades\Route;
use Modules\Helpdesk\Http\Controllers\Api\BannersApiController;
use Modules\Helpdesk\Http\Controllers\Api\V1\AgentsApiController;
use Modules\Helpdesk\Http\Controllers\Api\V1\ConversationsApiController;
use Modules\Helpdesk\Http\Controllers\Api\V1\CustomersApiController;
use Modules\Helpdesk\Http\Controllers\Api\V1\TagsApiController;

Route::get('agents/me', [AgentsApiController::class, 'me'])->name('agents.me');

Route::apiResource('customers', CustomersApiController::class)->except(['destroy']);

Route::apiResource('conversations', ConversationsApiController::class)->except(['destroy']);
Route::post('conversations/{conversation}/close', [ConversationsApiController::class, 'close'])->name('conversations.close');
Route::post('conversations/{conversation}/messages', [ConversationsApiController::class, 'storeMessage'])->name('conversations.messages.store');

Route::apiResource('tags', TagsApiController::class)->only(['index', 'store']);

Route::get('banners', [BannersApiController::class, 'index'])->name('banners.index');
