<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskAiPrompts\Http\Controllers\AiPromptBlockController;
use Modules\HelpdeskAiPrompts\Http\Controllers\AiPromptCaseController;
use Modules\HelpdeskAiPrompts\Http\Controllers\AiPromptIndexController;
use Modules\HelpdeskAiPrompts\Http\Controllers\AiPromptTesterController;

/*
|--------------------------------------------------------------------------
| HelpdeskAiPrompts panel routes
|--------------------------------------------------------------------------
| Mounted with prefix `panel/helpdesk/ai-prompts` and name
| `helpdesk-ai-prompts.`. The whole group already requires
| `helpdesk.ai-prompts.view`; every write action below additionally
| requires `helpdesk.ai-prompts.manage`.
*/

Route::get('/', [AiPromptIndexController::class, 'index'])->name('index');

Route::prefix('tester')->name('tester.')->group(function () {
    Route::post('detect', [AiPromptTesterController::class, 'detect'])->name('detect');
    Route::post('execute', [AiPromptTesterController::class, 'execute'])->name('execute');
});

Route::middleware('can:helpdesk.ai-prompts.manage')->group(function () {
    Route::prefix('cases')->name('cases.')->group(function () {
        Route::get('create', [AiPromptCaseController::class, 'create'])->name('create');
        Route::post('/', [AiPromptCaseController::class, 'store'])->name('store');
        Route::post('test-draft', [AiPromptCaseController::class, 'testDraft'])->name('test-draft');
        Route::post('{case}/test', [AiPromptCaseController::class, 'test'])->name('test');
        Route::get('{case}/edit', [AiPromptCaseController::class, 'edit'])->name('edit');
        Route::put('{case}', [AiPromptCaseController::class, 'update'])->name('update');
        Route::delete('{case}', [AiPromptCaseController::class, 'destroy'])->name('destroy');
        Route::patch('{case}/toggle-active', [AiPromptCaseController::class, 'toggleActive'])->name('toggle-active');
        Route::post('{case}/duplicate', [AiPromptCaseController::class, 'duplicate'])->name('duplicate');
        Route::get('{case}/history', [AiPromptCaseController::class, 'history'])->name('history');
        Route::post('{case}/versions/{version}/restore', [AiPromptCaseController::class, 'restoreVersion'])->name('versions.restore');
    });

    Route::prefix('blocks')->name('blocks.')->group(function () {
        Route::get('create', [AiPromptBlockController::class, 'create'])->name('create');
        Route::post('/', [AiPromptBlockController::class, 'store'])->name('store');
        Route::get('{block}/edit', [AiPromptBlockController::class, 'edit'])->name('edit');
        Route::put('{block}', [AiPromptBlockController::class, 'update'])->name('update');
        Route::delete('{block}', [AiPromptBlockController::class, 'destroy'])->name('destroy');
        Route::patch('{block}/toggle-active', [AiPromptBlockController::class, 'toggleActive'])->name('toggle-active');
        Route::get('{block}/history', [AiPromptBlockController::class, 'history'])->name('history');
        Route::post('{block}/versions/{version}/restore', [AiPromptBlockController::class, 'restoreVersion'])->name('versions.restore');
    });
});
