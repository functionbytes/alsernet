<?php

namespace App\Services\Return;


use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReturnRule;
use App\Models\ReturnValidation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ReturnValidationService
{
    /**
     * Validar si un producto puede ser devuelto
     */
    public function validateProductReturn(
        Order $order,
        Product $product,
        array $returnData = []
    ): ReturnValidation {
        // Buscar regla aplicable
        $rule = ProductReturnRule::getApplicableRule($product);

        // Preparar datos para validación
        $validationData = array_merge($returnData, [
            'purchase_date' => $order->created_at,
            'order_amount' => $order->total,
            'product_price' => $this->getProductPriceFromOrder($order, $product),
        ]);

        // Crear validación
        $validation = ReturnValidation::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'return_rule_id' => $rule?->id,
            'validation_status' => ReturnValidation::STATUS_PENDING,
            'validation_results' => [],
        ]);

        // Ejecutar validaciones
        $results = $this->executeValidations($order, $product, $rule, $validationData);

        // Actualizar validación con resultados
        $validation->update([
            'validation_status' => $this->determineValidationStatus($results),
            'validation_results' => $results,
            'failure_reasons' => $results['valid'] ? null : implode('; ', $results['errors']),
            'validated_at' => now(),
        ]);

        return $validation;
    }

    /**
     * Ejecutar todas las validaciones
     */
    protected function executeValidations(
        Order $order,
        Product $product,
        ?ProductReturnRule $rule,
        array $data
    ): array {
        $results = [
            'valid' => true,
            'errors' => [],
            'warnings' => [],
            'details' => [],
        ];

        // Validaciones básicas
        $basicValidation = $this->validateBasicRequirements($order, $product, $data);
        $results = $this->mergeValidationResults($results, $basicValidation);

        // Validaciones de regla específica
        if ($rule) {
            $ruleValidation = $rule->validateReturn($data);
            $results = $this->mergeValidationResults($results, $ruleValidation);
        } else {
            // Validaciones por defecto si no hay regla
            $defaultValidation = $this->validateDefaultRules($order, $product, $data);
            $results = $this->mergeValidationResults($results, $defaultValidation);
        }

        // Validaciones de negocio específicas
        $businessValidation = $this->validateBusinessRules($order, $product, $data);
        $results = $this->mergeValidationResults($results, $businessValidation);

        return $results;
    }

    /**
     * Validaciones básicas requeridas
     */
    protected function validateBasicRequirements(Order $order, Product $product, array $data): array
    {
        $results = ['valid' => true, 'errors' => [], 'warnings' => []];

        // Verificar que la orden existe y está pagada
        if (!$order || $order->status !== 'completed') {
            $results['valid'] = false;
            $results['errors'][] = 'La orden debe estar completada para procesar devoluciones';
        }

        // Verificar que el producto existe en la orden
        $orderItem = $order->items()->where('product_id', $product->id)->first();
        if (!$orderItem) {
            $results['valid'] = false;
            $results['errors'][] = 'El producto no se encuentra en esta orden';
        }

        // Verificar que no se ha devuelto ya
        $existingReturn = ReturnValidation::where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->where('validation_status', ReturnValidation::STATUS_PASSED)
            ->first();

        if ($existingReturn) {
            $results['valid'] = false;
            $results['errors'][] = 'Este producto ya ha sido devuelto';
        }

        // Verificar cantidad disponible para devolución
        if (isset($data['quantity']) && $orderItem) {
            $availableQuantity = $this->getAvailableReturnQuantity($order, $product);
            if ($data['quantity'] > $availableQuantity) {
                $results['valid'] = false;
                $results['errors'][] = "Solo {$availableQuantity} unidades disponibles para devolución";
            }
        }

        return $results;
    }

    /**
     * Validaciones por defecto cuando no hay regla específica
     */
    protected function validateDefaultRules(Order $order, Product $product, array $data): array
    {
        $results = ['valid' => true, 'errors' => [], 'warnings' => []];

        // Período de devolución por defecto (30 días)
        $daysSincePurchase = $order->created_at->diffInDays(now());
        $defaultReturnPeriod = $product->category?->default_return_days ?? 30;

        if ($daysSincePurchase > $defaultReturnPeriod) {
            $results['valid'] = false;
            $results['errors'][] = "El período de devolución de {$defaultReturnPeriod} días ha expirado";
        }

        // Verificar si la categoría permite devoluciones
        if ($product->category && !$product->category->allow_returns) {
            $results['valid'] = false;
            $results['errors'][] = 'Los productos de esta categoría no permiten devoluciones';
        }

        return $results;
    }

    /**
     * Validaciones de reglas de negocio específicas
     */
    protected function validateBusinessRules(Order $order, Product $product, array $data): array
    {
        $results = ['valid' => true, 'errors' => [], 'warnings' => []];

        // Validar productos digitales
        if ($product->type === 'digital') {
            $results['valid'] = false;
            $results['errors'][] = 'Los productos digitales no pueden ser devueltos';
        }

        // Validar productos personalizados
        if ($product->is_customized ?? false) {
            $results['valid'] = false;
            $results['errors'][] = 'Los productos personalizados no pueden ser devueltos';
        }

        // Validar productos en oferta/liquidación
        if ($this->isProductOnClearance($product, $order)) {
            $results['warnings'][] = 'Los productos en liquidación tienen políticas de devolución especiales';
        }

        // Validar límite de devoluciones por cliente
        $customerReturnCount = $this->getCustomerReturnCount($order->user_id);
        if ($customerReturnCount >= 5) { // Límite configurable
            $results['warnings'][] = 'Cliente con múltiples devoluciones recientes - revisar manualmente';
        }

        // Validar valor de la devolución
        $returnValue = $this->calculateReturnValue($order, $product, $data);
        if ($returnValue > 1000) { // Umbral configurable
            $results['warnings'][] = 'Devolución de alto valor - requiere aprobación adicional';
        }

        return $results;
    }

    /**
     * Determinar el estado de validación basado en los resultados
     */
    protected function determineValidationStatus(array $results): string
    {
        if (!$results['valid']) {
            return ReturnValidation::STATUS_FAILED;
        }

        if (!empty($results['warnings'])) {
            // Si hay advertencias, requiere revisión manual
            return ReturnValidation::STATUS_MANUAL_REVIEW;
        }

        return ReturnValidation::STATUS_PASSED;
    }

    /**
     * Combinar resultados de validación
     */
    protected function mergeValidationResults(array $existing, array $new): array
    {
        return [
            'valid' => $existing['valid'] && $new['valid'],
            'errors' => array_merge($existing['errors'] ?? [], $new['errors'] ?? []),
            'warnings' => array_merge($existing['warnings'] ?? [], $new['warnings'] ?? []),
            'details' => array_merge($existing['details'] ?? [], $new['details'] ?? []),
        ];
    }

    /**
     * Obtener precio del producto desde la orden
     */
    protected function getProductPriceFromOrder(Order $order, Product $product): float
    {
        $orderItem = $order->items()->where('product_id', $product->id)->first();
        return $orderItem ? $orderItem->price : $product->price;
    }

    /**
     * Obtener cantidad disponible para devolución
     */
    protected function getAvailableReturnQuantity(Order $order, Product $product): int
    {
        $orderItem = $order->items()->where('product_id', $product->id)->first();
        if (!$orderItem) {
            return 0;
        }

        $returnedQuantity = ReturnValidation::where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->where('validation_status', ReturnValidation::STATUS_PASSED)
            ->sum('validation_results->quantity') ?? 0;

        return $orderItem->quantity - $returnedQuantity;
    }

    /**
     * Verificar si el producto está en liquidación
     */
    protected function isProductOnClearance(Product $product, Order $order): bool
    {
        // Implementar lógica específica
        return $product->tags?->contains('clearance') ?? false;
    }

    /**
     * Obtener contador de devoluciones del cliente
     */
    protected function getCustomerReturnCount(int $userId): int
    {
        return ReturnValidation::whereHas('order', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
            ->where('validation_status', ReturnValidation::STATUS_PASSED)
            ->where('created_at', '>=', now()->subDays(90))
            ->count();
    }

    /**
     * Calcular valor de la devolución
     */
    protected function calculateReturnValue(Order $order, Product $product, array $data): float
    {
        $productPrice = $this->getProductPriceFromOrder($order, $product);
        $quantity = $data['quantity'] ?? 1;

        return $productPrice * $quantity;
    }

    /**
     * Validar múltiples productos de una orden
     */
    public function validateMultipleProductReturns(Order $order, array $products): array
    {
        $results = [];

        foreach ($products as $productData) {
            $product = Product::find($productData['product_id']);
            if ($product) {
                $validation = $this->validateProductReturn($order, $product, $productData);
                $results[] = [
                    'product_id' => $product->id,
                    'validation_id' => $validation->id,
                    'status' => $validation->validation_status,
                    'errors' => $validation->getValidationErrors(),
                    'warnings' => $validation->getValidationWarnings(),
                ];
            }
        }

        return $results;
    }

    /**
     * Reevaluar validación existente
     */
    public function revalidateReturn(ReturnValidation $validation, array $newData = []): ReturnValidation
    {
        $order = $validation->order;
        $product = $validation->product;
        $rule = $validation->returnRule;

        $validationData = array_merge($newData, [
            'purchase_date' => $order->created_at,
            'order_amount' => $order->total,
            'product_price' => $this->getProductPriceFromOrder($order, $product),
        ]);

        $results = $this->executeValidations($order, $product, $rule, $validationData);

        $validation->update([
            'validation_status' => $this->determineValidationStatus($results),
            'validation_results' => $results,
            'failure_reasons' => $results['valid'] ? null : implode('; ', $results['errors']),
            'validated_at' => now(),
        ]);

        return $validation;
    }
}
