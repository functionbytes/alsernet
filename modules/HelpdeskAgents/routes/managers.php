<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskAgents\Http\Controllers\Managers\AiAgentFlowsController;
use Modules\HelpdeskAgents\Http\Controllers\Managers\AiKnowledgeController;
use Modules\HelpdeskAgents\Http\Controllers\Managers\AiTagsController;
use Modules\HelpdeskAgents\Http\Controllers\Managers\AiToolsController;
use Modules\HelpdeskAgents\Http\Controllers\Managers\Settings\AgentSettingsController;

Route::prefix('ai')->group(function () {
    // Settings
    Route::get('settings', [AgentSettingsController::class, 'index'])->name('settings');
    Route::put('settings', [AgentSettingsController::class, 'update'])->name('settings.update');
    Route::post('settings/test-connection', [AgentSettingsController::class, 'testConnection'])->middleware('throttle:60,1')->name('settings.test');

    // Tags
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('tags', [AiTagsController::class, 'index'])->name('tags.index');
        Route::post('tags', [AiTagsController::class, 'store'])->name('tags.store');
        Route::put('tags/{tag}', [AiTagsController::class, 'update'])->name('tags.update');
        Route::delete('tags/{tag}', [AiTagsController::class, 'destroy'])->name('tags.destroy');
        Route::post('tags/{tag}/toggle', [AiTagsController::class, 'toggle'])->name('tags.toggle');
    });

    // Tools
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('tools', [AiToolsController::class, 'index'])->name('tools.index');
        Route::post('tools', [AiToolsController::class, 'store'])->name('tools.store');
        Route::put('tools/{tool}', [AiToolsController::class, 'update'])->name('tools.update');
        Route::delete('tools/{tool}', [AiToolsController::class, 'destroy'])->name('tools.destroy');
        Route::post('tools/{tool}/toggle', [AiToolsController::class, 'toggle'])->name('tools.toggle');
    });

    // Knowledge Base
    Route::middleware('throttle:60,1')->group(function () {
        Route::get('knowledge', [AiKnowledgeController::class, 'index'])->name('knowledge.index');
        Route::post('knowledge', [AiKnowledgeController::class, 'store'])->name('knowledge.store');
        Route::put('knowledge/{knowledge}', [AiKnowledgeController::class, 'update'])->name('knowledge.update');
        Route::delete('knowledge/{knowledge}', [AiKnowledgeController::class, 'destroy'])->name('knowledge.destroy');
        Route::post('knowledge/{knowledge}/toggle', [AiKnowledgeController::class, 'toggle'])->name('knowledge.toggle');
        Route::post('knowledge/{knowledge}/generate-embedding', [AiKnowledgeController::class, 'generateEmbedding'])->name('knowledge.generate-embedding');
    });

    // Flows
    Route::get('/', [AiAgentFlowsController::class, 'index'])->name('flows.index');
    Route::get('/create', [AiAgentFlowsController::class, 'create'])->name('flows.create');
    Route::post('/', [AiAgentFlowsController::class, 'store'])->name('flows.store');
    Route::get('/{flow}/edit', [AiAgentFlowsController::class, 'edit'])->name('flows.edit');
    Route::put('/{flow}', [AiAgentFlowsController::class, 'update'])->name('flows.update');
    Route::delete('/{flow}', [AiAgentFlowsController::class, 'destroy'])->name('flows.destroy');
    Route::post('flows/{flow}/publish', [AiAgentFlowsController::class, 'publish'])->name('flows.publish');
    Route::post('flows/{flow}/archive', [AiAgentFlowsController::class, 'archive'])->name('flows.archive');
    Route::post('flows/{flow}/duplicate', [AiAgentFlowsController::class, 'duplicate'])->name('flows.duplicate');

    // Flow Structure
    Route::put('flows/{flow}/structure', [AiAgentFlowsController::class, 'updateStructure'])->name('flows.structure');
});
