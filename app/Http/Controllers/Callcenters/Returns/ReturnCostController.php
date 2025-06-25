<?php

namespace App\Http\Controllers\Callcenters\Returns;

use App\Http\Requests\StoreReturnCostRequest;
use App\Services\Return\ReturnCostService;
use App\Http\Controllers\Controller;
use App\Models\Return\ReturnRequest;
use Illuminate\Http\JsonResponse;
use App\Models\Return\ReturnCost;
use Illuminate\Http\Request;

class ReturnCostController extends Controller
{
    private ReturnCostService $costService;

    public function __construct(ReturnCostService $costService)
    {
        $this->costService = $costService;
    }

    /**
     * Mostrar lista de costos de una devolución
     */
    public function index(ReturnRequest $return)
    {
            $this->authorize('view', $return);

            $costsBreakdown = $this->costService->getCostsBreakdown($return);
            $refundCalculation = $this->costService->calculateFinalRefund($return);

            if (request()->wantsJson()) {
                return response()->json([
                    'costs' => $costsBreakdown,
                    'refund' => $refundCalculation
                ]);
            }

        return view('returns.costs.index', compact('return', 'costsBreakdown', 'refundCalculation'));
    }

    /**
     * Agregar un costo manual
     */
    public function store(StoreReturnCostRequest $request, ReturnRequest $return)
    {
        $this->authorize('manageCosts', $return);

        try {
            $cost = $this->costService->addManualCost($return, $request->validated());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Costo agregado exitosamente',
                    'cost' => $cost->load('return'),
                    'refund' => $this->costService->calculateFinalRefund($return)
                ], 201);
            }

            return redirect()
                ->route('returns.costs.index', $return)
                ->with('success', 'Costo agregado exitosamente');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    /**
     * Aplicar deducciones automáticas
     */
    public function applyAutomatic(ReturnRequest $return)
    {
        $this->authorize('manageCosts', $return);

        try {
            $appliedCosts = $this->costService->applyAutomaticDeductions($return);

            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'Deducciones automáticas aplicadas',
                    'applied_costs' => $appliedCosts,
                    'refund' => $this->costService->calculateFinalRefund($return)
                ]);
            }

            return redirect()
                ->route('returns.costs.index', $return)
                ->with('success', 'Deducciones automáticas aplicadas: ' . $appliedCosts->count() . ' costos agregados');

        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Eliminar un costo manual
     */
    public function destroy(ReturnRequest $return, ReturnCost $cost)
    {
        $this->authorize('manageCosts', $return);

        // Verificar que el costo pertenece a esta devolución
        if ($cost->return_id !== $return->id) {
            abort(404);
        }

        try {
            $this->costService->removeCost($cost);

            if (request()->wantsJson()) {
                return response()->json([
                    'message' => 'Costo eliminado exitosamente',
                    'refund' => $this->costService->calculateFinalRefund($return)
                ]);
            }

            return redirect()
                ->route('returns.costs.index', $return)
                ->with('success', 'Costo eliminado exitosamente');

        } catch (\Exception $e) {
            if (request()->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Obtener resumen de reembolso
     */
    public function summary(ReturnRequest $return): JsonResponse
    {
        $this->authorize('view', $return);

        $summary = $this->costService->calculateFinalRefund($return);

        return response()->json($summary);
    }

}
