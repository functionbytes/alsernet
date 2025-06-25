<?php

namespace App\Services\Return;

use App\Models\Return\ReturnCost;
use App\Models\Return\ReturnRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ReturnCostService
{
    // Porcentajes de deducciones automáticas
    const RESTOCKING_FEE_PERCENTAGE = 15; // 15% de reposición
    const INSPECTION_FEE_FIXED = 5.00; // 5€ fijo de inspección

    /**
     * Agregar un costo manual a una devolución
     */
    public function addManualCost(ReturnRequest $return, array $data): ReturnCost
    {
        return DB::transaction(function () use ($return, $data) {
            $cost = $return->costs()->create([
            'cost_type' => $data['cost_type'],
            'amount' => $data['amount'],
            'description' => $data['description'] ?? null,
            'is_automatic' => false,
            'applied_by' => auth()->user()->name ?? 'Sistema'
            ]);

            $this->recalculateRefund($return);

            return $cost;
        });
    }

    /**
     * Aplicar deducciones automáticas según las reglas de negocio
     */
    public function applyAutomaticDeductions(ReturnRequest $return): Collection
    {
        $appliedCosts = collect();

        DB::transaction(function () use ($return, &$appliedCosts) {
    // Eliminar costos automáticos previos
            $return->costs()->where('is_automatic', true)->delete();

    // 1. Costo de reposición (si aplica)
            if ($this->shouldApplyRestockingFee($return)) {
                $restockingCost = $this->applyRestockingFee($return);
                $appliedCosts->push($restockingCost);
            }

    // 2. Costo de inspección (siempre aplica)
            $inspectionCost = $this->applyInspectionFee($return);
            $appliedCosts->push($inspectionCost);

    // 3. Costo de envío de retorno (si el cliente es responsable)
            if ($this->shouldChargeReturnShipping($return)) {
                $shippingCost = $this->applyReturnShippingCost($return);
                $appliedCosts->push($shippingCost);
            }

            $this->recalculateRefund($return);
        });

        return $appliedCosts;
    }

    /**
     * Calcular el reembolso final
     */
    public function calculateFinalRefund(ReturnRequest $return): array
    {
        $originalAmount = $return->original_amount ?? 0;
        $totalCosts = $return->costs->sum('amount');
        $finalRefund = max(0, $originalAmount - $totalCosts);

        return [
            'original_amount' => $originalAmount,
            'total_deductions' => $totalCosts,
            'final_refund' => $finalRefund,
            'costs_breakdown' => $this->getCostsBreakdown($return)
        ];
    }

    /**
     * Obtener desglose de costos
     */
    public function getCostsBreakdown(ReturnRequest $return): array
    {
        return $return->costs->groupBy('cost_type')->map(function ($costs, $type) {
            return [
                'type' => $type,
                'label' => ReturnCost::find($costs->first()->id)->cost_type_label,
                'count' => $costs->count(),
                'total' => $costs->sum('amount'),
                'items' => $costs->map(function ($cost) {
                    return [
                        'id' => $cost->id,
                        'description' => $cost->description,
                        'amount' => $cost->amount,
                        'is_automatic' => $cost->is_automatic,
                        'created_at' => $cost->created_at->format('d/m/Y H:i')
                    ];
                })
            ];
        })->values()->toArray();
    }

    /**
     * Eliminar un costo manual
     */
    public function removeCost(ReturnCost $cost): bool
    {
        if ($cost->is_automatic) {
            throw new \Exception('No se pueden eliminar costos automáticos directamente');
        }

        $return = $cost->return;
        $deleted = $cost->delete();

        if ($deleted) {
            $this->recalculateRefund($return);
        }

        return $deleted;
    }

    // Métodos privados de apoyo

    private function shouldApplyRestockingFee(ReturnRequest $return): bool
    {
    // Aplicar si el producto fue abierto o usado
        return in_array($return->reason, ['changed_mind', 'no_longer_needed', 'found_better_price']);
    }

    private function applyRestockingFee(ReturnRequest $return): ReturnCost
    {
        $amount = $return->original_amount * (self::RESTOCKING_FEE_PERCENTAGE / 100);

        return $return->costs()->create([
            'cost_type' => ReturnCost::TYPE_RESTOCKING,
            'amount' => round($amount, 2),
            'description' => 'Cargo por reposición de stock (' . self::RESTOCKING_FEE_PERCENTAGE . '%)',
            'is_automatic' => true,
            'applied_by' => 'Sistema'
        ]);
    }

    private function applyInspectionFee(ReturnRequest $return): ReturnCost
    {
        return $return->costs()->create([
            'cost_type' => ReturnCost::TYPE_INSPECTION,
            'amount' => self::INSPECTION_FEE_FIXED,
            'description' => 'Cargo fijo por inspección del producto',
            'is_automatic' => true,
            'applied_by' => 'Sistema'
        ]);
    }

    private function shouldChargeReturnShipping(ReturnRequest $return): bool
    {
        return !in_array($return->reason, ['defective', 'wrong_item', 'damaged']);
    }

    private function applyReturnShippingCost(ReturnRequest $return): ReturnCost
    {

        $estimatedShippingCost = 8.50; // Ejemplo: costo fijo o calculado

        return $return->costs()->create([
            'cost_type' => ReturnCost::TYPE_SHIPPING,
            'amount' => $estimatedShippingCost,
            'description' => 'Costo de envío de devolución',
            'is_automatic' => true,
            'applied_by' => 'Sistema'
        ]);
    }

    private function recalculateRefund(ReturnRequest $return): void
    {
        $return->touch(); // Actualiza el timestamp
    }

}
