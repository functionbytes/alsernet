<?php

use Illuminate\Support\Facades\Route;
use Modules\HelpdeskAgents\Http\Controllers\Managers\AiAgentFlowsController;
use Modules\HelpdeskAgents\Http\Controllers\Managers\AiAgentSettingsController;

Route::prefix('ai')->group(function () {
    // Settings
    Route::get('settings', [AiAgentSettingsController::class, 'index'])->name('manager.helpdesk.ai.settings');
    Route::put('settings', [AiAgentSettingsController::class, 'update'])->name('manager.helpdesk.ai.settings.update');
    Route::post('settings/test-connection', [AiAgentSettingsController::class, 'testConnection'])->name('manager.helpdesk.ai.settings.test');
    Route::post('settings/get-models', [AiAgentSettingsController::class, 'getModels'])->name('manager.helpdesk.ai.settings.get-models');
    Route::get('settings/statistics', [AiAgentSettingsController::class, 'statistics'])->name('manager.helpdesk.ai.settings.statistics');

    // Tags
    Route::get('tags', [AiAgentSettingsController::class, 'tagsIndex'])->name('manager.helpdesk.ai.tags.index');
    Route::post('tags', [AiAgentSettingsController::class, 'tagsStore'])->name('manager.helpdesk.ai.tags.store');
    Route::put('tags/{tag}', [AiAgentSettingsController::class, 'tagsUpdate'])->name('manager.helpdesk.ai.tags.update');
    Route::delete('tags/{tag}', [AiAgentSettingsController::class, 'tagsDestroy'])->name('manager.helpdesk.ai.tags.destroy');
    Route::post('tags/{tag}/toggle', [AiAgentSettingsController::class, 'tagsToggle'])->name('manager.helpdesk.ai.tags.toggle');

    // Tools
    Route::get('tools', [AiAgentSettingsController::class, 'toolsIndex'])->name('manager.helpdesk.ai.tools.index');
    Route::post('tools', [AiAgentSettingsController::class, 'toolsStore'])->name('manager.helpdesk.ai.tools.store');
    Route::put('tools/{tool}', [AiAgentSettingsController::class, 'toolsUpdate'])->name('manager.helpdesk.ai.tools.update');
    Route::delete('tools/{tool}', [AiAgentSettingsController::class, 'toolsDestroy'])->name('manager.helpdesk.ai.tools.destroy');
    Route::post('tools/{tool}/toggle', [AiAgentSettingsController::class, 'toolsToggle'])->name('manager.helpdesk.ai.tools.toggle');

    // Knowledge Base
    Route::get('knowledge', [AiAgentSettingsController::class, 'knowledgeIndex'])->name('manager.helpdesk.ai.knowledge.index');
    Route::post('knowledge', [AiAgentSettingsController::class, 'knowledgeStore'])->name('manager.helpdesk.ai.knowledge.store');
    Route::put('knowledge/{knowledge}', [AiAgentSettingsController::class, 'knowledgeUpdate'])->name('manager.helpdesk.ai.knowledge.update');
    Route::delete('knowledge/{knowledge}', [AiAgentSettingsController::class, 'knowledgeDestroy'])->name('manager.helpdesk.ai.knowledge.destroy');
    Route::post('knowledge/{knowledge}/toggle', [AiAgentSettingsController::class, 'knowledgeToggle'])->name('manager.helpdesk.ai.knowledge.toggle');
    Route::post('knowledge/{knowledge}/generate-embedding', [AiAgentSettingsController::class, 'knowledgeGenerateEmbedding'])->name('manager.helpdesk.ai.knowledge.generate-embedding');

    // Flows
    Route::get('/', [AiAgentFlowsController::class, 'index'])->name('manager.helpdesk.ai.flows.index');
    Route::get('/create', [AiAgentFlowsController::class, 'create'])->name('manager.helpdesk.ai.flows.create');
    Route::post('/', [AiAgentFlowsController::class, 'store'])->name('manager.helpdesk.ai.flows.store');
    Route::get('/{flow}', [AiAgentFlowsController::class, 'show'])->name('manager.helpdesk.ai.flows.show');
    Route::get('/{flow}/edit', [AiAgentFlowsController::class, 'edit'])->name('manager.helpdesk.ai.flows.edit');
    Route::put('/{flow}', [AiAgentFlowsController::class, 'update'])->name('manager.helpdesk.ai.flows.update');
    Route::delete('/{flow}', [AiAgentFlowsController::class, 'destroy'])->name('manager.helpdesk.ai.flows.destroy');
    Route::post('flows/{flow}/publish', [AiAgentFlowsController::class, 'publish'])->name('manager.helpdesk.ai.flows.publish');
    Route::post('flows/{flow}/archive', [AiAgentFlowsController::class, 'archive'])->name('manager.helpdesk.ai.flows.archive');
    Route::post('flows/{flow}/duplicate', [AiAgentFlowsController::class, 'duplicate'])->name('manager.helpdesk.ai.flows.duplicate');

    // Flow Nodes
    Route::post('flows/{flow}/nodes', [AiAgentFlowsController::class, 'storeNode'])->name('manager.helpdesk.ai.flows.nodes.store');
    Route::put('flows/{flow}/nodes/{node}', [AiAgentFlowsController::class, 'updateNode'])->name('manager.helpdesk.ai.flows.nodes.update');
    Route::delete('flows/{flow}/nodes/{node}', [AiAgentFlowsController::class, 'deleteNode'])->name('manager.helpdesk.ai.flows.nodes.delete');

    // Flow Structure
    Route::put('flows/{flow}/structure', [AiAgentFlowsController::class, 'updateStructure'])->name('manager.helpdesk.ai.flows.structure');
});
