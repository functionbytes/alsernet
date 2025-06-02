<?php

// ========================================
// 3.1 MÓDULO DE INSPECCIÓN - CONTROLADOR
// ========================================

namespace App\Http\Controllers\Callcenters\Returns;

use App\Http\Controllers\Controller;
use App\Models\Return\ReturnRequest;
use App\Models\Return\ReturnRequestProduct;
use App\Models\Return\ReturnInspection;
use App\Services\Return\InspectionService;
use App\Services\Return\BarcodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InspectionController extends Controller
{
    protected $inspectionService;
    protected $barcodeService;

    public function __construct(
        InspectionService $inspectionService,
        BarcodeService $barcodeService
    ) {
        $this->inspectionService = $inspectionService;
        $this->barcodeService = $barcodeService;
    }

    /**
     * Dashboard de inspección
     */
    public function index()
    {
        // Obtener estadísticas
        $stats = [
            'pending_inspection' => ReturnRequestProduct::where('is_received', true)
                ->whereNull('inspection_status')
                ->count(),
            'in_review' => ReturnInspection::pendingReview()->count(),
            'completed_today' => ReturnInspection::whereDate('inspection_date', today())
                ->count(),
            'exceptions' => \App\Models\Return\ReturnException::pending()->count()
        ];

        // Devoluciones pendientes de inspección
        $pendingReturns = ReturnRequest::whereHas('products', function ($q) {
            $q->where('is_received', true)
                ->whereNull('inspection_status');
        })
            ->with(['customer', 'order', 'products'])
            ->latest()
            ->paginate(20);

        // Inspecciones recientes
        $recentInspections = ReturnInspection::with([
            'returnItem.returnRequest',
            'inspector'
        ])
            ->latest()
            ->take(10)
            ->get();

        return view('warehouse.inspections.index', compact(
            'stats',
            'pendingReturns',
            'recentInspections'
        ));
    }

    /**
     * Escanear código de barras para iniciar inspección
     */
    public function scan(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string'
        ]);

        try {
            // Escanear código
            $result = $this->barcodeService->scanBarcode(
                $request->barcode,
                auth()->id()
            );

            if (!$result['success']) {
                return back()->with('error', $result['message']);
            }

            $returnProduct = $result['product'];

            // Verificar si ya fue inspeccionado
            if ($returnProduct->inspection_status) {
                $inspection = ReturnInspection::where('return_item_id', $returnProduct->id)
                    ->first();

                return redirect()->route('warehouse.inspections.show', $inspection);
            }

            // Redirigir a formulario de inspección
            return redirect()->route('warehouse.inspections.create', [
                'item' => $returnProduct->id
            ]);

        } catch (\Exception $e) {
            Log::error('Error scanning barcode for inspection', [
                'barcode' => $request->barcode,
                'error' => $e->getMessage()
            ]);

            return back()->with('error', 'Error al escanear código de barras');
        }
    }

    /**
     * Formulario de inspección
     */
    public function create(Request $request)
    {
        $returnItem = ReturnRequestProduct::with([
            'returnRequest.customer',
            'returnRequest.order',
            'orderProduct'
        ])->findOrFail($request->item);

        // Verificar que esté recibido
        if (!$returnItem->is_received) {
            return redirect()->route('warehouse.inspections.index')
                ->with('error', 'El producto debe ser recibido antes de inspeccionar');
        }

        // Obtener checklist según categoría
        $productCategory = $this->determineProductCategory($returnItem);
        $checklist = $this->inspectionService->getChecklistForProduct($productCategory);

        return view('warehouse.inspections.create', compact(
            'returnItem',
            'checklist',
            'productCategory'
        ));
    }

    /**
     * Guardar inspección
     */
    public function store(Request $request)
    {
        $request->validate([
            'return_item_id' => 'required|exists:return_request_products,id',
            'condition_grade' => 'required|in:A,B,C,D',
            'checklist' => 'required|array',
            'checklist.*.passed' => 'required|boolean',
            'checklist.*.notes' => 'nullable|string',
            'final_decision' => 'nullable|in:restock,outlet,repair,destroy,return_to_supplier',
            'notes' => 'nullable|string',
            'photos' => 'nullable|array',
            'photos.*' => 'image|max:5120' // 5MB max
        ]);

        DB::beginTransaction();

        try {
            $returnItem = ReturnRequestProduct::findOrFail($request->return_item_id);

            // Preparar datos de checklist
            $checklistResults = collect($request->checklist)->map(function ($item, $index) use ($request) {
                return array_merge($item, [
                    'item' => $request->checklist_items[$index] ?? '',
                    'category' => $request->checklist_categories[$index] ?? '',
                    'required' => $request->checklist_required[$index] ?? false
                ]);
            })->toArray();

            // Realizar inspección
            $inspection = $this->inspectionService->inspectProduct($returnItem, [
                'condition_grade' => $request->condition_grade,
                'checklist_results' => $checklistResults,
                'final_decision' => $request->final_decision,
                'notes' => $request->notes,
                'photos' => $request->file('photos') ?? []
            ]);

            DB::commit();

            // Notificar si requiere revisión
            if ($inspection->requires_review) {
                $this->notifyReviewRequired($inspection);
            }

            return redirect()
                ->route('warehouse.inspections.show', $inspection)
                ->with('success', 'Inspección completada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error saving inspection', [
                'item_id' => $request->return_item_id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al guardar la inspección: ' . $e->getMessage());
        }
    }

    /**
     * Ver detalle de inspección
     */
    public function show(ReturnInspection $inspection)
    {
        $inspection->load([
            'returnItem.returnRequest.customer',
            'returnItem.orderProduct',
            'inspector',
            'reviewer',
            'exceptions'
        ]);

        // Calcular impacto financiero
        $financialImpact = $this->calculateFinancialImpact($inspection);

        return view('warehouse.inspections.show', compact(
            'inspection',
            'financialImpact'
        ));
    }

    /**
     * Formulario de revisión
     */
    public function review(ReturnInspection $inspection)
    {
        // Verificar que requiere revisión
        if (!$inspection->requires_review || $inspection->reviewed_at) {
            return redirect()
                ->route('warehouse.inspections.show', $inspection)
                ->with('info', 'Esta inspección no requiere revisión o ya fue revisada');
        }

        $inspection->load([
            'returnItem.returnRequest',
            'returnItem.orderProduct',
            'exceptions'
        ]);

        return view('warehouse.inspections.review', compact('inspection'));
    }

    /**
     * Procesar revisión
     */
    public function processReview(Request $request, ReturnInspection $inspection)
    {
        $request->validate([
            'final_decision' => 'required|in:restock,outlet,repair,destroy,return_to_supplier',
            'review_notes' => 'nullable|string',
            'approve_exceptions' => 'nullable|array',
            'exception_resolutions' => 'nullable|array'
        ]);

        DB::beginTransaction();

        try {
            // Actualizar decisión final si cambió
            if ($request->final_decision !== $inspection->final_decision) {
                $inspection->update(['final_decision' => $request->final_decision]);
            }

            // Aprobar revisión
            $inspection->approveReview(auth()->id(), $request->review_notes);

            // Procesar excepciones si las hay
            if ($request->has('exception_resolutions')) {
                foreach ($request->exception_resolutions as $exceptionId => $resolution) {
                    $exception = $inspection->exceptions()->find($exceptionId);
                    if ($exception) {
                        $exception->resolve(
                            $resolution['type'],
                            auth()->id(),
                            $resolution['notes'] ?? null,
                            $resolution['compensation'] ?? null
                        );
                    }
                }
            }

            // Actualizar estado del producto
            $inspection->returnItem->update([
                'inspection_status' => 'passed',
                'is_approved' => true
            ]);

            // Procesar según decisión
            $this->processInspectionDecision($inspection);

            DB::commit();

            return redirect()
                ->route('warehouse.inspections.show', $inspection)
                ->with('success', 'Revisión completada exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error processing inspection review', [
                'inspection_id' => $inspection->id,
                'error' => $e->getMessage()
            ]);

            return back()
                ->withInput()
                ->with('error', 'Error al procesar la revisión');
        }
    }

    /**
     * Inspección masiva
     */
    public function bulkInspect(Request $request)
    {
        $request->validate([
            'return_id' => 'required|exists:return_requests,id',
            'default_grade' => 'required|in:A,B,C,D',
            'items' => 'nullable|array'
        ]);

        try {
            $returnRequest = ReturnRequest::findOrFail($request->return_id);

            // Obtener items a inspeccionar
            $items = $request->items ?? $returnRequest->products()
                ->where('is_received', true)
                ->whereNull('inspection_status')
                ->pluck('id')
                ->toArray();

            if (empty($items)) {
                return back()->with('info', 'No hay productos pendientes de inspección');
            }

            // Realizar inspección masiva
            $results = $this->inspectionService->bulkInspect(
                $items,
                $request->default_grade
            );

            return back()->with('success', sprintf(
                'Inspección masiva completada: %d exitosas, %d fallidas',
                $results['success'],
                $results['failed']
            ));

        } catch (\Exception $e) {
            return back()->with('error', 'Error en inspección masiva: ' . $e->getMessage());
        }
    }

    /**
     * Reporte de inspección
     */
    public function report(ReturnRequest $returnRequest)
    {
        $report = $this->inspectionService->generateInspectionReport($returnRequest);

        return view('warehouse.inspections.report', compact(
            'returnRequest',
            'report'
        ));
    }

    /**
     * Determinar categoría del producto
     */
    protected function determineProductCategory(ReturnRequestProduct $item): string
    {
        // Aquí se implementaría la lógica para determinar la categoría
        // Por ahora, retornamos 'general'
        return 'general';
    }

    /**
     * Calcular impacto financiero
     */
    protected function calculateFinancialImpact(ReturnInspection $inspection): array
    {
        $originalValue = $inspection->returnItem->total_price;
        $refundAmount = $inspection->returnItem->refund_amount ?? $originalValue;
        $deductions = \App\Models\Return\ReturnCost::where('return_item_id', $inspection->return_item_id)
            ->where('is_deduction', true)
            ->sum('amount');

        $recoveryValue = 0;
        switch ($inspection->final_decision) {
            case ReturnInspection::DECISION_RESTOCK:
                $recoveryValue = $originalValue;
                break;
            case ReturnInspection::DECISION_OUTLET:
                $recoveryValue = $originalValue * 0.5; // 50% del valor
                break;
            case ReturnInspection::DECISION_REPAIR:
                $recoveryValue = $originalValue * 0.7; // 70% del valor
                break;
        }

        return [
            'original_value' => $originalValue,
            'refund_amount' => $refundAmount,
            'deductions' => $deductions,
            'recovery_value' => $recoveryValue,
            'net_loss' => $refundAmount - $recoveryValue
        ];
    }

    /**
     * Procesar decisión de inspección
     */
    protected function processInspectionDecision(ReturnInspection $inspection)
    {
        switch ($inspection->final_decision) {
            case ReturnInspection::DECISION_RESTOCK:
                // Reincorporar al inventario
                $this->restockProduct($inspection);
                break;

            case ReturnInspection::DECISION_OUTLET:
                // Mover a inventario de outlet
                $this->moveToOutlet($inspection);
                break;

            case ReturnInspection::DECISION_REPAIR:
                // Crear orden de reparación
                $this->createRepairOrder($inspection);
                break;

            case ReturnInspection::DECISION_DESTROY:
                // Programar destrucción
                $this->scheduleDestruction($inspection);
                break;

            case ReturnInspection::DECISION_RETURN_TO_SUPPLIER:
                // Crear RMA con proveedor
                $this->createSupplierRMA($inspection);
                break;
        }
    }

    /**
     * Notificar que se requiere revisión
     */
    protected function notifyReviewRequired(ReturnInspection $inspection)
    {
        // Implementar notificación a supervisores
        Log::info('Inspection requires review', [
            'inspection_id' => $inspection->id,
            'return_item_id' => $inspection->return_item_id
        ]);
    }

    // Métodos stub para acciones de inventario
    protected function restockProduct(ReturnInspection $inspection)
    {
        Log::info('Product restocked', ['inspection_id' => $inspection->id]);
    }

    protected function moveToOutlet(ReturnInspection $inspection)
    {
        Log::info('Product moved to outlet', ['inspection_id' => $inspection->id]);
    }

    protected function createRepairOrder(ReturnInspection $inspection)
    {
        Log::info('Repair order created', ['inspection_id' => $inspection->id]);
    }

    protected function scheduleDestruction(ReturnInspection $inspection)
    {
        Log::info('Destruction scheduled', ['inspection_id' => $inspection->id]);
    }

    protected function createSupplierRMA(ReturnInspection $inspection)
    {
        Log::info('Supplier RMA created', ['inspection_id' => $inspection->id]);
    }
}
