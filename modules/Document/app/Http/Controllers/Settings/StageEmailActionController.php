<?php

namespace Modules\Document\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Document\Entities\DocumentStageEmailAction;

class StageEmailActionController extends Controller
{
    /**
     * Display a listing of all stage email action configurations
     */
    public function index(): View
    {
        $configurations = DocumentStageEmailAction::ordered()->get()->groupBy('validation_stage');
        $pageTitle = 'Acciones de Email por Etapa';
        $breadcrumb = 'Configuración / Email / Acciones Automáticas';

        return view('documents::settings.stage-email-actions.index', [
            'configurations' => $configurations,
            'availableStages' => DocumentStageEmailAction::AVAILABLE_STAGES,
            'availableActions' => DocumentStageEmailAction::AVAILABLE_ACTIONS,
            'pageTitle' => $pageTitle,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    /**
     * Show the form for editing configurations for a specific stage
     */
    public function edit(string $stage): View
    {
        if (! array_key_exists($stage, DocumentStageEmailAction::AVAILABLE_STAGES)) {
            abort(404, 'Validation stage not found');
        }

        $configurations = DocumentStageEmailAction::forStage($stage)->ordered()->get();

        // Ensure all actions have a configuration record
        $configuredActions = $configurations->pluck('email_action')->toArray();
        foreach (array_keys(DocumentStageEmailAction::AVAILABLE_ACTIONS) as $action) {
            if (! in_array($action, $configuredActions)) {
                $configurations->push(
                    new DocumentStageEmailAction([
                        'validation_stage' => $stage,
                        'email_action' => $action,
                        'is_enabled' => false,
                        'sort_order' => count($configuredActions),
                    ])
                );
            }
        }

        $pageTitle = 'Editar Acciones - '.DocumentStageEmailAction::AVAILABLE_STAGES[$stage];
        $breadcrumb = 'Configuración / Email / Acciones Automáticas / Editar';

        return view('documents::settings.stage-email-actions.edit', [
            'stage' => $stage,
            'stageName' => DocumentStageEmailAction::AVAILABLE_STAGES[$stage],
            'configurations' => $configurations,
            'availableActions' => DocumentStageEmailAction::AVAILABLE_ACTIONS,
            'pageTitle' => $pageTitle,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    /**
     * Update configurations for a specific stage
     */
    public function update(Request $request, string $stage): RedirectResponse
    {
        if (! array_key_exists($stage, DocumentStageEmailAction::AVAILABLE_STAGES)) {
            abort(404, 'Validation stage not found');
        }

        $validated = $request->validate([
            'email_actions' => 'required|array',
            'email_actions.*' => 'boolean',
            'sort_order' => 'array',
            'sort_order.*' => 'integer',
        ]);

        // Update or create configurations
        foreach (DocumentStageEmailAction::AVAILABLE_ACTIONS as $action => $label) {
            $isEnabled = $validated['email_actions'][$action] ?? false;
            $sortOrder = $validated['sort_order'][$action] ?? 0;

            DocumentStageEmailAction::updateOrCreate(
                [
                    'validation_stage' => $stage,
                    'email_action' => $action,
                ],
                [
                    'is_enabled' => (bool) $isEnabled,
                    'sort_order' => (int) $sortOrder,
                ]
            );
        }

        return redirect()->route('settings.documents.stage-email-actions.index')
            ->with('success', "Configuración de acciones de email para {$stage} actualizada correctamente");
    }
}
