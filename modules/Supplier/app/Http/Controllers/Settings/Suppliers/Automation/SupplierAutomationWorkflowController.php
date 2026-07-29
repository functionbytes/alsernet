<?php

namespace Modules\Supplier\Http\Controllers\Settings\Suppliers\Automation;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Modules\Supplier\Http\Controllers\Settings\Suppliers\SupplierAutomationController;
use Modules\Supplier\Http\Requests\Automation\StoreWorkflowRequest;
use Modules\Supplier\Http\Requests\Automation\UpdateWorkflowRequest;
use Modules\Supplier\Models\Automation\AutomationWorkflow;
use Modules\Supplier\Services\AutomationOrchestrationService;
use Modules\Supplier\Traits\HasFlashMessages;

class SupplierAutomationWorkflowController extends Controller
{
    use HasFlashMessages;

    public function __construct(protected AutomationOrchestrationService $orchestrationService) {}

    /**
     * Show create workflow page
     */
    public function create(): View
    {
        $pageTitle = 'Crear Workflow de Automatización';
        $breadcrumb = 'Configuración / Proveedores / Automatización / Crear';

        return view('supplier::settings.views.automation.create', compact('pageTitle', 'breadcrumb'));
    }

    /**
     * Store new workflow
     */
    public function store(StoreWorkflowRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = auth()->id();
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['priority'] = $validated['priority'] ?? 5;
        $validated['timeout_seconds'] = $validated['timeout_seconds'] ?? 300;
        $validated['max_retries'] = $validated['max_retries'] ?? 3;

        AutomationWorkflow::create($validated);
        SupplierAutomationController::forgetDashboardStats();

        return $this->flashSuccess('Workflow creado exitosamente', 'settings.suppliers.automation.index');
    }

    /**
     * Show edit workflow page
     */
    public function edit(string $uid): View
    {
        $workflow = AutomationWorkflow::where('uid', $uid)->firstOrFail();
        $pageTitle = 'Editar Workflow de Automatización';
        $breadcrumb = 'Configuración / Proveedores / Automatización / Editar';

        return view('supplier::settings.views.automation.edit', compact('pageTitle', 'breadcrumb', 'workflow'));
    }

    /**
     * Update workflow
     */
    public function update(UpdateWorkflowRequest $request, string $uid): RedirectResponse
    {
        $workflow = AutomationWorkflow::where('uid', $uid)->firstOrFail();
        $validated = $request->validated();
        $validated['is_active'] = $request->boolean('is_active', $workflow->is_active);
        $workflow->update($validated);

        return $this->flashSuccess('Workflow actualizado exitosamente', 'settings.suppliers.automation.index');
    }

    /**
     * Delete workflow
     */
    public function destroy(Request $request, string $uid): RedirectResponse|JsonResponse
    {
        try {
            AutomationWorkflow::where('uid', $uid)->firstOrFail()->delete();
            SupplierAutomationController::forgetDashboardStats();

            if ($request->ajax()) {
                return response()->json(['success' => true, 'message' => 'Workflow eliminado exitosamente']);
            }

            return $this->flashSuccess('Workflow eliminado exitosamente', 'settings.suppliers.automation.index');

        } catch (\Exception $e) {
            Log::error('Error deleting workflow: '.$e->getMessage());

            if ($request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Error al eliminar el workflow'], 500);
            }

            return $this->flashError('Error al eliminar el workflow: '.$e->getMessage(), 'settings.suppliers.automation.index');
        }
    }

    /**
     * Run a specific workflow
     */
    public function run(string $uid): JsonResponse
    {
        try {
            $workflow = AutomationWorkflow::where('uid', $uid)->firstOrFail();
            $result = $this->orchestrationService->executeWorkflow($workflow);

            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'execution' => $result['execution'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Error running workflow: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar el workflow',
            ], 500);
        }
    }

    /**
     * Run all active workflows
     */
    public function runAll(): JsonResponse
    {
        try {
            $activeWorkflows = AutomationWorkflow::where('is_active', true)->get();

            if ($activeWorkflows->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay workflows activos para ejecutar',
                ], 400);
            }

            $executed = 0;
            foreach ($activeWorkflows as $workflow) {
                $result = $this->orchestrationService->executeWorkflow($workflow);
                if ($result['success']) {
                    $executed++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Se ejecutaron {$executed} de {$activeWorkflows->count()} workflows",
            ]);

        } catch (\Exception $e) {
            Log::error('Error running all workflows: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al ejecutar todos los workflows',
            ], 500);
        }
    }

    /**
     * Toggle workflow active status
     */
    public function toggle(string $uid): JsonResponse
    {
        try {
            $workflow = AutomationWorkflow::where('uid', $uid)->firstOrFail();
            $workflow->is_active = ! $workflow->is_active;
            $workflow->save();

            SupplierAutomationController::forgetDashboardStats();

            return response()->json([
                'success' => true,
                'is_active' => $workflow->is_active,
                'message' => $workflow->is_active
                    ? 'Flujo de trabajo activado exitosamente'
                    : 'Flujo de trabajo desactivado exitosamente',
            ]);

        } catch (\Exception $e) {
            Log::error('Error toggling workflow: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado del flujo de trabajo',
            ], 500);
        }
    }
}
