<?php


namespace App\Services\Return;

use App\Models\Return\ReturnRequest;
use App\Models\Return\ReturnRequestProduct;
use App\Models\Return\ReturnInspection;
use App\Models\Return\ReturnException;
use App\Models\Return\ReturnCost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class InspectionService
{
    /**
     * Realizar inspección de un producto
     */
    public function inspectProduct(ReturnRequestProduct $returnItem, array $data): ReturnInspection
    {
        DB::beginTransaction();

        try {
            // Crear registro de inspección
            $inspection = ReturnInspection::create([
                'return_item_id' => $returnItem->id,
                'inspector_id' => auth()->id(),
                'inspection_date' => now(),
                'condition_grade' => $data['condition_grade'],
                'checklist_results' => $data['checklist_results'],
                'notes' => $data['notes'] ?? null
            ]);

            // Determinar decisión automática
            $inspection->final_decision = $data['final_decision'] ??
                ReturnInspection::getAutomaticDecision($data['condition_grade']);

            // Verificar si requiere revisión
            $inspection->requires_review = $inspection->determineIfRequiresReview();

            // Guardar fotos si las hay
            if (!empty($data['photos'])) {
                $photoPaths = $this->saveInspectionPhotos($inspection, $data['photos']);
                $inspection->inspection_photos = $photoPaths;
            }

            $inspection->save();

            // Actualizar estado del producto
            $returnItem->update([
                'inspection_status' => $this->determineInspectionStatus($inspection),
                'inspection_notes' => $data['notes'] ?? null
            ]);

            // Detectar y crear excepciones si es necesario
            $this->detectExceptions($inspection, $returnItem);

            // Calcular costes/deducciones
            $this->calculateCosts($inspection, $returnItem);

            DB::commit();

            Log::info('Product inspection completed', [
                'return_item_id' => $returnItem->id,
                'grade' => $inspection->condition_grade,
                'decision' => $inspection->final_decision
            ]);

            return $inspection;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error during product inspection', [
                'return_item_id' => $returnItem->id,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Determinar estado de inspección
     */
    protected function determineInspectionStatus(ReturnInspection $inspection): string
    {
        if ($inspection->requires_review) {
            return 'requires_review';
        }

        if ($inspection->condition_grade === ReturnInspection::GRADE_A) {
            return 'passed';
        }

        if ($inspection->condition_grade === ReturnInspection::GRADE_D) {
            return 'failed';
        }

        return 'passed';
    }

    /**
     * Detectar excepciones automáticamente
     */
    protected function detectExceptions(ReturnInspection $inspection, ReturnRequestProduct $returnItem)
    {
        $exceptions = [];

        // 1. Verificar condición reportada vs encontrada
        $reportedCondition = $returnItem->return_condition;
        $foundCondition = $inspection->mapGradeToCondition();

        if ($reportedCondition === 'unopened' && $inspection->condition_grade !== ReturnInspection::GRADE_A) {
            $exceptions[] = [
                'type' => ReturnException::TYPE_USED_AS_NEW,
                'severity' => ReturnException::SEVERITY_HIGH,
                'description' => 'Producto reportado como sin abrir pero se encontró abierto/usado'
            ];
        }

        // 2. Verificar partes faltantes del checklist
        $missingParts = collect($inspection->checklist_results)
            ->where('category', 'accessories')
            ->where('passed', false)
            ->pluck('item')
            ->toArray();

        if (!empty($missingParts)) {
            $exceptions[] = [
                'type' => ReturnException::TYPE_MISSING_PARTS,
                'severity' => count($missingParts) > 2 ?
                    ReturnException::SEVERITY_MEDIUM :
                    ReturnException::SEVERITY_LOW,
                'description' => 'Partes faltantes: ' . implode(', ', $missingParts)
            ];
        }

        // 3. Verificar daños no reportados
        if ($inspection->condition_grade === ReturnInspection::GRADE_D &&
            !in_array($reportedCondition, ['damaged', 'used'])) {
            $exceptions[] = [
                'type' => ReturnException::TYPE_DAMAGED_BY_CARRIER,
                'severity' => ReturnException::SEVERITY_HIGH,
                'description' => 'Producto con daños no reportados por el cliente'
            ];
        }

        // Crear excepciones
        foreach ($exceptions as $exceptionData) {
            ReturnException::create([
                'return_request_id' => $returnItem->request_id,
                'return_inspection_id' => $inspection->id,
                'exception_type' => $exceptionData['type'],
                'severity' => $exceptionData['severity'],
                'description' => $exceptionData['description'],
                'resolution' => ReturnException::RESOLUTION_PENDING,
                'requires_escalation' => $exceptionData['severity'] === ReturnException::SEVERITY_HIGH
            ]);
        }
    }

    /**
     * Calcular costes y deducciones
     */
    protected function calculateCosts(ReturnInspection $inspection, ReturnRequestProduct $returnItem)
    {
        $costs = [];
        $totalDeduction = 0;

        // 1. Deducción por condición
        $conditionDeductions = [
            ReturnInspection::GRADE_A => 0,
            ReturnInspection::GRADE_B => 0.15, // 15% deducción
            ReturnInspection::GRADE_C => 0.30, // 30% deducción
            ReturnInspection::GRADE_D => 1.00  // 100% deducción
        ];

        $deductionRate = $conditionDeductions[$inspection->condition_grade] ?? 0;
        $conditionDeduction = $returnItem->total_price * $deductionRate;

        if ($conditionDeduction > 0) {
            $costs[] = [
                'type' => 'condition_deduction',
                'amount' => $conditionDeduction,
                'description' => 'Deducción por condición del producto'
            ];
            $totalDeduction += $conditionDeduction;
        }

        // 2. Deducción por partes faltantes
        $missingParts = collect($inspection->checklist_results)
            ->where('category', 'accessories')
            ->where('passed', false);

        foreach ($missingParts as $part) {
            $partCost = $part['estimated_value'] ?? 10; // Valor por defecto
            $costs[] = [
                'type' => 'missing_part',
                'amount' => $partCost,
                'description' => 'Parte faltante: ' . $part['item']
            ];
            $totalDeduction += $partCost;
        }

        // 3. Costes de procesamiento según decisión
        $processingCosts = [
            ReturnInspection::DECISION_REPAIR => 25,
            ReturnInspection::DECISION_DESTROY => 15,
            ReturnInspection::DECISION_RETURN_TO_SUPPLIER => 20
        ];

        if (isset($processingCosts[$inspection->final_decision])) {
            $processingCost = $processingCosts[$inspection->final_decision];
            $costs[] = [
                'type' => 'processing',
                'amount' => $processingCost,
                'description' => 'Coste de procesamiento'
            ];
        }

        // Guardar costes
        foreach ($costs as $cost) {
            ReturnCost::create([
                'return_request_id' => $returnItem->request_id,
                'return_item_id' => $returnItem->id,
                'cost_type' => $cost['type'],
                'amount' => $cost['amount'],
                'description' => $cost['description'],
                'applied_by' => auth()->id()
            ]);
        }

        // Actualizar monto de reembolso del producto
        $finalRefundAmount = max(0, $returnItem->total_price - $totalDeduction);
        $returnItem->update([
            'refund_amount' => $finalRefundAmount,
            'is_approved' => $inspection->condition_grade !== ReturnInspection::GRADE_D
        ]);

        // Actualizar totales de la devolución
        $returnItem->returnRequest->updateTotals();
    }

    /**
     * Guardar fotos de inspección
     */
    protected function saveInspectionPhotos(ReturnInspection $inspection, array $photos): array
    {
        $paths = [];
        $directory = "returns/inspections/{$inspection->return_item_id}";

        Storage::makeDirectory($directory);

        foreach ($photos as $index => $photo) {
            if ($photo->isValid()) {
                $filename = "inspection_{$inspection->id}_{$index}." . $photo->extension();
                $path = $photo->storeAs($directory, $filename);
                $paths[] = $path;
            }
        }

        return $paths;
    }

    /**
     * Obtener checklist por categoría de producto
     */
    public function getChecklistForProduct($productCategory): array
    {
        // Checklist base
        $baseChecklist = [
            [
                'category' => 'general',
                'item' => 'Producto corresponde a la descripción',
                'required' => true
            ],
            [
                'category' => 'general',
                'item' => 'Número de serie/modelo coincide',
                'required' => true
            ],
            [
                'category' => 'condition',
                'item' => 'Sin daños físicos visibles',
                'required' => true
            ],
            [
                'category' => 'condition',
                'item' => 'Funciona correctamente',
                'required' => true
            ],
            [
                'category' => 'packaging',
                'item' => 'Embalaje original incluido',
                'required' => false
            ],
            [
                'category' => 'packaging',
                'item' => 'Manual de instrucciones incluido',
                'required' => false
            ]
        ];

        // Agregar items específicos por categoría
        switch ($productCategory) {
            case 'electronics':
                $baseChecklist[] = [
                    'category' => 'accessories',
                    'item' => 'Cargador incluido',
                    'required' => true,
                    'estimated_value' => 25
                ];
                $baseChecklist[] = [
                    'category' => 'accessories',
                    'item' => 'Cables incluidos',
                    'required' => true,
                    'estimated_value' => 15
                ];
                break;

            case 'clothing':
                $baseChecklist[] = [
                    'category' => 'condition',
                    'item' => 'Sin manchas o decoloración',
                    'required' => true
                ];
                $baseChecklist[] = [
                    'category' => 'condition',
                    'item' => 'Etiquetas originales',
                    'required' => false
                ];
                break;

            case 'furniture':
                $baseChecklist[] = [
                    'category' => 'accessories',
                    'item' => 'Tornillería completa',
                    'required' => true,
                    'estimated_value' => 10
                ];
                $baseChecklist[] = [
                    'category' => 'condition',
                    'item' => 'Sin rayones profundos',
                    'required' => true
                ];
                break;
        }

        return $baseChecklist;
    }

    /**
     * Procesar inspección masiva
     */
    public function bulkInspect(array $items, $defaultGrade = ReturnInspection::GRADE_B): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => []
        ];

        foreach ($items as $itemId) {
            try {
                $returnItem = ReturnRequestProduct::find($itemId);

                if (!$returnItem || $returnItem->is_received !== true) {
                    $results['errors'][] = "Producto {$itemId} no encontrado o no recibido";
                    $results['failed']++;
                    continue;
                }

                // Inspección rápida con valores por defecto
                $this->inspectProduct($returnItem, [
                    'condition_grade' => $defaultGrade,
                    'checklist_results' => $this->getQuickChecklistResults($defaultGrade),
                    'notes' => 'Inspección masiva'
                ]);

                $results['success']++;

            } catch (\Exception $e) {
                $results['errors'][] = "Error en producto {$itemId}: " . $e->getMessage();
                $results['failed']++;
            }
        }

        return $results;
    }

    /**
     * Obtener resultados rápidos de checklist
     */
    protected function getQuickChecklistResults($grade): array
    {
        $checklist = $this->getChecklistForProduct('general');
        $passAll = in_array($grade, [ReturnInspection::GRADE_A, ReturnInspection::GRADE_B]);

        return collect($checklist)->map(function ($item) use ($passAll) {
            return array_merge($item, [
                'passed' => $passAll || !$item['required'],
                'notes' => null
            ]);
        })->toArray();
    }

    /**
     * Generar reporte de inspección
     */
    public function generateInspectionReport(ReturnRequest $returnRequest): array
    {
        $inspections = ReturnInspection::whereIn('return_item_id',
            $returnRequest->products->pluck('id')
        )->get();

        return [
            'summary' => [
                'total_items' => $returnRequest->products->count(),
                'inspected' => $inspections->count(),
                'pending' => $returnRequest->products->count() - $inspections->count(),
                'grade_distribution' => $inspections->groupBy('condition_grade')
                    ->map->count(),
                'decision_distribution' => $inspections->groupBy('final_decision')
                    ->map->count(),
                'requires_review' => $inspections->where('requires_review', true)->count()
            ],
            'exceptions' => ReturnException::where('return_request_id', $returnRequest->id)
                ->with('inspection')
                ->get(),
            'estimated_recovery' => [
                'restock_value' => $inspections->where('final_decision', ReturnInspection::DECISION_RESTOCK)
                    ->sum(function ($inspection) {
                        return $inspection->returnItem->total_price;
                    }),
                'outlet_value' => $inspections->where('final_decision', ReturnInspection::DECISION_OUTLET)
                    ->sum(function ($inspection) {
                        return $inspection->returnItem->total_price * 0.5; // 50% valor outlet
                    }),
                'total_deductions' => ReturnCost::where('return_request_id', $returnRequest->id)
                    ->sum('amount')
            ]
        ];
    }
}

