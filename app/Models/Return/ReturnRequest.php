<?php

namespace App\Models\Return;

use App\Library\Traits\HasUid;
use App\Models\Return\Order\ReturnOrder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ReturnRequest extends Model
{
    use HasUid;

    protected $table = 'return_requests';
    protected $primaryKey = 'id';

    protected $fillable = [
        'number', // Cambiado: ahora referencia a orders.id
        'reference', // Cambiado: ahora referencia a orders.id
        'order_id', // Cambiado: ahora referencia a orders.id
        'customer_id',
        'shop_id',
        'customer_name',
        'status_id',
        'type_id',
        'reason_id',
        'email',
        'phone',
        'return_address',
        'pickup_selection',
        'description',
        'received_date',
        'pickup_date',
        'is_refunded',
        'is_wallet_used',
        'iban',
        'pdf_path',
        'logistics_mode',
        'created_by',
        'total_amount',
        'approved_amount',
        'refunded_amount',
        'request_at'
    ];

    protected $with = ['status', 'returnType', 'returnReason'];

    protected $casts = [
        'received_date' => 'datetime',
        'pickup_date' => 'datetime',
        'is_refunded' => 'boolean',
        'total_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'refunded_amount' => 'decimal:2',
        'request_at' => 'datetime',
    ];

    public function communications()
    {
        return $this->hasMany(ReturnCommunication::class);
    }

// Agregar este observer o usar eventos
    protected static function booted()
    {
        static::updated(function ($return) {
            if ($return->isDirty('status')) {
                // Disparar notificación cuando cambia el estado
                //app(ReturnNotificationService::class)->notifyStatusChange(
               //     $return,
                //    $return->getOriginal('status')
                //);
            }
        });
    }


    // Relaciones actualizadas
    public function order(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\Order\ReturnOrder', 'order_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Customer', 'customer_id', 'id');
    }

    public function products(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnRequestProduct', 'return_request_id', 'id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnStatus', 'status_id');
    }

    public function creator()
    {
        return $this->belongsTo('App\Models\User', 'created_by');
    }


    public function returnType(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnType', 'type_id', 'id');
    }

    public function returnItems()
    {
        return $this->hasMany('App\Models\Return\ReturnRequestProduct', 'return_id', 'id');
    }

    public function returnReason(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\ReturnReason', 'reason_id', 'id');
    }

    public function discussions(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnDiscussion', 'id', 'id');
    }

    public function history(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnHistory', 'id', 'id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnPayment', 'id', 'id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnAttachment', 'id', 'id');
    }

    // Scopes actualizados
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where('email', $email);
    }

    public function scopeByOrder($query, $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeByStatus($query, $statusId)
    {
        return $query->where('status_id', $statusId);
    }

    public function scopePending($query)
    {
        return $query->whereHas('status.state', function($q) {
            $q->where('name', 'New');
        });
    }

    public function scopeCompleted($query)
    {
        return $query->whereHas('status.state', function($q) {
            $q->where('name', 'Close');
        });
    }

    public function scopeApproved($query)
    {
        return $query->whereHas('status.state', function($q) {
            $q->where('name', 'Verification');
        });
    }

    public function scopeRefunded($query)
    {
        return $query->where('is_refunded', true);
    }

    public function scopeNumber($query, $number)
    {
        return $query->where('number', $number);
    }

    public function scopeReference($query, $reference)
    {
        return $query->where('reference', $reference);
    }

    // Métodos auxiliares actualizados
    public function getStatusName($langId = 1, $shopId = 1)
    {
        $translation = $this->status->getTranslation($langId, $shopId);
        return $translation ? $translation->name : $this->status->state->name;
    }

    public function getReturnTypeName($langId = 1, $shopId = 1)
    {
        $translation = $this->returnType->getTranslation($langId, $shopId);
        return $translation ? $translation->name : 'Desconocido';
    }

    public function getReturnReasonName($langId = 1, $shopId = 1)
    {
        $translation = $this->returnReason->getTranslation($langId, $shopId);
        return $translation ? $translation->name : 'Desconocido';
    }

    public function canBeModified()
    {
        return in_array($this->status->state->name, ['New', 'Verification']);
    }

    public function scopeWithFullDetails($query)
    {
        return $query->with([
            'order.products',
            'customer',
            'products.orderProduct',
            'products.returnReason',
            'status.state',
            'status.translations',
            'returnType.translations',
            'returnReason.translations',
            'payments',
            'attachments',
            'history'
        ]);
    }


    public function getLogisticsModeLabel()
    {
        $modes = [
            'customer_transport' => 'Agencia de transporte (cuenta del cliente)',
            'home_pickup' => 'Recogida a domicilio',
            'store_delivery' => 'Entrega en tienda',
            'inpost' => 'InPost'
        ];
        return $modes[$this->logistics_mode] ?? 'No especificado';
    }

    // Nuevos métodos para manejar productos
    public function getTotalProductsQuantity(): float
    {
        return $this->products()->sum('quantity');
    }

    public function getApprovedProductsQuantity(): float
    {
        return $this->products()->sum('approved_quantity');
    }

    public function getTotalRefundAmount(): float
    {
        return $this->products()->sum('refund_amount');
    }

    public function updateTotals(): void
    {
        $this->update([
            'total_amount' => $this->products()->sum('total_price'),
            'approved_amount' => $this->products()->sum('refund_amount')
        ]);
    }

    public function hasApprovedProducts(): bool
    {
        return $this->products()->where('is_approved', true)->exists();
    }

    public function hasRejectedProducts(): bool
    {
        return $this->products()->where('is_approved', false)->exists();
    }

    public function isPending(): bool
    {
        return $this->status->state->name === 'New';
    }

    public function isCompleted(): bool
    {
        return $this->status->state->name === 'Close';
    }

    /**
     * Crear devolución desde orden ERP
     */
    public static function createFromOrder(ReturnOrder $order, array $additionalData = []): self
    {
        $lastNumber = self::max('number') ?? 0;
        $nextNumber = $lastNumber + 1;

        $reference = config('returns.return_reference', 'DEV') . '-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

        $defaultData = [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'customer_name' => $order->customer_name,
            'email' => $order->customer_email,
            'phone' => $order->customer_phone ?? $order->shipping_phone,
            'return_address' => $order->getFormattedShippingAddress(),
            'status_id' => config('returns.default_status_id', 1),
            'shop_id' => config('shop.id', 1),
            'logistics_mode' => 'store_delivery',
            'created_by' => auth()->id() ?? 'system',
            'received_date' => now()
        ];

        $data = array_merge($defaultData, $additionalData);

        return self::create($data);
    }

    /**
     * Obtener información completa para mostrar
     */
    public function getCompleteInfo(): array
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'reference' => $this->reference,
            'order_id' => $this->order_id,
            'order_number' => $this->order?->order_number,
            'customer_name' => $this->customer_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'status' => $this->getStatusName(),
            'status_color' => $this->status->color,
            'return_type' => $this->getReturnTypeName(),
            'return_reason' => $this->getReturnReasonName(),
            'logistics_mode' => $this->getLogisticsModeLabel(),
            'description' => $this->description,
            'total_products' => $this->getTotalProductsQuantity(),
            'total_amount' => number_format($this->total_amount ?? 0, 2) . ' €',
            'approved_amount' => number_format($this->approved_amount ?? 0, 2) . ' €',
            'refunded_amount' => number_format($this->refunded_amount ?? 0, 2) . ' €',
            'is_refunded' => $this->is_refunded,
            'created_at' => $this->created_at,
            'can_be_modified' => $this->canBeModified(),
            'has_approved_products' => $this->hasApprovedProducts(),
            'has_rejected_products' => $this->hasRejectedProducts(),
            'products' => $this->products->map(function($product) {
                return $product->getDisplayInfo();
            })
        ];
    }

    /**
     * Validar que se puede crear devolución para esta orden
     */
    public function validateOrderEligibility(): array
    {
        $errors = [];
        $warnings = [];

        // 1. Verificar que existe la orden
        if (!$this->order) {
            $errors[] = 'La orden no existe en el sistema';
            return ['errors' => $errors, 'warnings' => $warnings]; // No continuar si no hay orden
        }

        // 2. Verificar estado de la orden ERP
        $allowedStatuses = explode(',', config('returns.allowed_erp_statuses', '4,5,6'));
        if (!in_array($this->order->erp_status_id, $allowedStatuses)) {
            $errors[] = "La orden está en estado '{$this->order->erp_status_description}' que no permite devoluciones";
        }

        // 3. Verificar período de devolución
        $returnDaysLimit = config('returns.return_days_limit', 30);
        $daysSinceOrder = $this->order->order_date->diffInDays(now());

        if ($daysSinceOrder > $returnDaysLimit) {
            $errors[] = "El período de devolución de {$returnDaysLimit} días ha expirado (pedido de hace {$daysSinceOrder} días)";
        } elseif ($daysSinceOrder > ($returnDaysLimit - 7)) {
            $warnings[] = "El período de devolución expira pronto (quedan " . ($returnDaysLimit - $daysSinceOrder) . " días)";
        }

        // 4. Verificar devoluciones activas existentes
        $activeReturns = $this->order->returns()
            ->where('id', '!=', $this->id)
            ->whereHas('status', function ($query) {
                $query->where('active', true)
                    ->where('state_id', '!=', 4); // Excluir estado "cerrado"
            })
            ->get();


        if ($activeReturns->count() > 0) {
            $returnNumbers = $activeReturns->pluck('id')->map(function($id) {
                return 'DEV-' . str_pad($id, 6, '0', STR_PAD_LEFT);
            })->implode(', ');

            $errors[] = "Ya existen devoluciones activas para esta orden: {$returnNumbers}";
        }

        // 5. Verificar que tenga productos elegiblesddd
        $returnableProducts = $this->order->products()->returnable()->get();

        if ($returnableProducts->count() === 0) {
            $errors[] = 'Esta orden no tiene productos elegibles para devolución';
        } else {
            // Verificar productos con cantidades disponibles
            $availableProducts = $returnableProducts->filter(function($product) {
                return $product->available_for_return > 0;
            });

            if ($availableProducts->count() === 0) {
                $errors[] = 'Todos los productos de esta orden ya han sido devueltos';
            } elseif ($availableProducts->count() < $returnableProducts->count()) {
                $partiallyReturned = $returnableProducts->count() - $availableProducts->count();
                $warnings[] = "{$partiallyReturned} producto(s) ya han sido devueltos parcial o totalmente";
            }
        }

        // 6. Verificar monto mínimo para devolución
        $minReturnAmount = config('returns.min_return_amount', 0);
        if ($minReturnAmount > 0 && $this->order->total_amount < $minReturnAmount) {
            $errors[] = "El monto de la orden (" . number_format($this->order->total_amount, 2) . "€) es menor al mínimo requerido para devoluciones (" . number_format($minReturnAmount, 2) . "€)";
        }

        // 7. Verificar información del cliente
        if (empty($this->order->customer_email)) {
            $warnings[] = 'La orden no tiene email de cliente registrado';
        }

        if (empty($this->order->shipping_address)) {
            $warnings[] = 'La orden no tiene dirección de envío completa';
        }

        // 8. Verificar método de pago original
        if (empty($this->order->payment_method_id)) {
            $warnings[] = 'No se encontró información del método de pago original';
        }

        // 9. Verificar productos con precio 0 (promociones)
        $freeProducts = $this->order->products()->where('total_price', 0)->count();
        if ($freeProducts > 0) {
            $warnings[] = "La orden contiene {$freeProducts} producto(s) gratuito(s) que no se pueden devolver";
        }

        // 10. Verificar productos de catálogos específicos
        $restrictedCatalogs = config('returns.restricted_catalogs', []);
        if (!empty($restrictedCatalogs)) {
            $restrictedProducts = $this->order->products()
                ->whereIn('catalog_id', $restrictedCatalogs)
                ->count();
            if ($restrictedProducts > 0) {
                $warnings[] = "{$restrictedProducts} producto(s) pertenecen a catálogos con restricciones especiales";
            }
        }

        // 11. Verificar orden de cliente VIP o especial
        if ($this->order->customer && method_exists($this->order->customer, 'isVip') && $this->order->customer->isVip()) {
            $warnings[] = 'Cliente VIP - Aplicar políticas especiales de devolución';
        }

        // 12. Verificar si es fin de semana/festivo (para recogidas)
        if (now()->isWeekend()) {
            $warnings[] = 'Las recogidas a domicilio no están disponibles en fin de semana';
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'can_proceed' => empty($errors),
            'order_info' => [
                'erp_order_id' => $this->order->erp_order_id,
                'order_date' => $this->order->order_date->format('d/m/Y'),
                'days_since_order' => $daysSinceOrder,
                'total_amount' => $this->order->total_amount,
                'returnable_products' => $returnableProducts->count(),
                'available_products' => $availableProducts->count() ?? 0,
                'customer_name' => $this->order->customer_name,
                'status' => $this->order->erp_status_description
            ]
        ];
    }

    public function validateOrderEligibilitys()
    {
        $errors = [];
        $warnings = [];
        $canProceed = true;

        // Validar que la orden exista
        if (!$this->order) {
            $errors[] = 'La orden no existe';
            $canProceed = false;
            return compact('canProceed', 'errors', 'warnings');
        }

        // Validar estado de la orden
        if (!in_array($this->order->status, ['delivered', 'completed'])) {
            $errors[] = 'La orden debe estar entregada o completada para poder devolverla';
            $canProceed = false;
        }

        // Validar tiempo límite (30 días por defecto)
        $daysSinceDelivery = $this->order->delivered_at ?
            now()->diffInDays($this->order->delivered_at) : null;

        if ($daysSinceDelivery && $daysSinceDelivery > 30) {
            $warnings[] = "Han pasado {$daysSinceDelivery} días desde la entrega. El período normal es de 30 días.";
        }

        // Validar si ya hay devoluciones previas
        $previousReturns = self::where('order_id', $this->order_id)
            ->where('id_return_request', '!=', $this->id_return_request)
            ->whereIn('status', ['approved', 'pending', 'processing'])
            ->count();

        if ($previousReturns > 0) {
            $warnings[] = "Esta orden ya tiene {$previousReturns} solicitud(es) de devolución en proceso";
        }

        return compact('canProceed', 'errors', 'warnings');
    }

    // Calcular total de la devolución
    public function calculateTotal()
    {
        $total = $this->returnItems->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $this->update(['total_amount' => $total]);
        return $total;
    }

    // Validar productos devueltos
    public function validateReturnedProducts()
    {
        $errors = [];

        foreach ($this->returnItems as $item) {
            $orderProduct = $this->order->orderProducts()
                ->where('product_id', $item->product_id)
                ->first();

            if (!$orderProduct) {
                $errors[] = "El producto {$item->product->name} no pertenece a esta orden";
                continue;
            }

            // Calcular cantidad ya devuelta
            $alreadyReturned = ReturnItem::join('return_requests', 'return_items.return_request_id', '=', 'return_requests.id_return_request')
                ->where('return_requests.order_id', $this->order_id)
                ->where('return_items.product_id', $item->product_id)
                ->whereIn('return_requests.status', ['approved', 'processing'])
                ->where('return_requests.id_return_request', '!=', $this->id_return_request)
                ->sum('return_items.quantity');

            $availableToReturn = $orderProduct->quantity - $alreadyReturned;

            if ($item->quantity > $availableToReturn) {
                $errors[] = "La cantidad a devolver de {$item->product->name} excede la cantidad disponible. Disponible: {$availableToReturn}";
            }
        }

        return $errors;
    }

    /**
     * Validar productos específicos para devolución
     */
    public function validateProductsForReturn(array $selectedProducts): array
    {
        $errors = [];
        $warnings = [];

        foreach ($selectedProducts as $index => $productData) {
            $orderProduct = OrderProduct::find($productData['order_product_id']);

            if (!$orderProduct) {
                $errors[] = "Producto #{$index}: No existe en el sistema";
                continue;
            }

            // Verificar que pertenece a esta orden
            if ($orderProduct->order_id !== $this->order_id) {
                $errors[] = "Producto '{$orderProduct->product_name}': No pertenece a esta orden";
                continue;
            }

            // Verificar que es retornable
            if (!$orderProduct->is_returnable) {
                $errors[] = "Producto '{$orderProduct->product_name}': No es elegible para devolución";
                continue;
            }

            // Verificar cantidad disponible
            $requestedQuantity = floatval($productData['quantity']);
            $availableQuantity = $orderProduct->available_for_return;

            if ($requestedQuantity <= 0) {
                $errors[] = "Producto '{$orderProduct->product_name}': La cantidad debe ser mayor a 0";
            } elseif ($requestedQuantity > $availableQuantity) {
                $errors[] = "Producto '{$orderProduct->product_name}': Cantidad solicitada ({$requestedQuantity}) excede la disponible ({$availableQuantity})";
            }

            // Verificar período de devolución específico del producto
            if (!$orderProduct->isWithinReturnPeriod()) {
                $deadline = $orderProduct->getReturnDeadline();
                $errors[] = "Producto '{$orderProduct->product_name}': Período de devolución expirado (límite: {$deadline->format('d/m/Y')})";
            }

            // Validar motivo de devolución
            if (!empty($productData['return_reason_id'])) {
                $reason = \App\Models\Return\ReturnReason::find($productData['return_reason_id']);
                if (!$reason || !$reason->active) {
                    $errors[] = "Producto '{$orderProduct->product_name}': Motivo de devolución no válido";
                } elseif (!$reason->isValidForReturnType($this->getReturnTypeForReason())) {
                    $warnings[] = "Producto '{$orderProduct->product_name}': El motivo seleccionado no es típico para este tipo de devolución";
                }
            }

            // Validar condición del producto
            if (!empty($productData['condition']) && !in_array($productData['condition'], ['new', 'good', 'fair', 'poor', 'damaged'])) {
                $errors[] = "Producto '{$orderProduct->product_name}': Condición del producto no válida";
            }

            // Advertir sobre productos de alto valor
            if ($orderProduct->total_price > config('returns.high_value_product_threshold', 500)) {
                $warnings[] = "Producto '{$orderProduct->product_name}': Producto de alto valor (requiere aprobación especial)";
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'can_proceed' => empty($errors)
        ];
    }

    /**
     * Obtener tipo de devolución basado en el motivo
     */
    private function getReturnTypeForReason(): string
    {
        if (!$this->type_id) {
            return 'all';
        }

        $typeMapping = [
            1 => 'refund',
            2 => 'replacement',
            3 => 'repair'
        ];

        return $typeMapping[$this->type_id] ?? 'all';
    }

    /**
     * Validación rápida (solo errores críticos)
     */
    public function quickValidation(): bool
    {
        if (!$this->order) return false;

        $allowedStatuses = explode(',', config('returns.allowed_erp_statuses', '4,5,6'));
        if (!in_array($this->order->erp_status_id, $allowedStatuses)) return false;

        $returnDaysLimit = config('returns.return_days_limit', 30);
        if ($this->order->order_date->diffInDays(now()) > $returnDaysLimit) return false;

        if ($this->order->hasActiveReturns()) return false;

        return true;
    }

    /**
     * Obtener productos disponibles para agregar a la devolución
     */
    public function getAvailableProducts()
    {
        if (!$this->order) {
            return collect();
        }

        return $this->order->products()
            ->returnable()
            ->whereRaw('quantity > (
            SELECT COALESCE(SUM(rp.quantity), 0)
            FROM return_request_products rp
            INNER JOIN return_requests rr ON rp.return_request_id = rr.id
            INNER JOIN return_status rs ON rr.status_id = rs.id
            WHERE rp.order_product_id = order_products.id
              AND rr.id != ?
              AND rs.active = 1
              AND rs.state_id != 5
        )', [$this->id])
            ->get();
    }

    /**
     * Calcular el resumen financiero de la devolución
     */
    public function getFinancialSummary(): array
    {
        $products = $this->products;

        return [
            'total_requested' => $products->sum('total_price'),
            'total_approved' => $products->where('is_approved', true)->sum('refund_amount'),
            'total_rejected' => $products->where('is_approved', false)->sum('total_price'),
            'total_pending' => $products->whereNull('is_approved')->sum('total_price'),
            'products_count' => $products->count(),
            'approved_count' => $products->where('is_approved', true)->count(),
            'rejected_count' => $products->where('is_approved', false)->count(),
            'pending_count' => $products->whereNull('is_approved')->count(),
            'refunded_amount' => $this->refunded_amount ?? 0,
            'pending_refund' => max(0, ($products->where('is_approved', true)->sum('refund_amount')) - ($this->refunded_amount ?? 0))
        ];
    }

    /**
     * Verificar si todos los productos están procesados
     */
    public function areAllProductsProcessed(): bool
    {
        return $this->products()->whereNull('is_approved')->count() === 0;
    }

    /**
     * Procesar aprobación masiva de productos
     */
    public function approveAllProducts(): bool
    {
        try {
            DB::transaction(function () {
                $this->products()->whereNull('is_approved')->each(function ($product) {
                    $product->approve();
                });

                $this->updateTotals();
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Error approving all products', [
                'return_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Procesar rechazo masivo de productos
     */
    public function rejectAllProducts(string $reason = null): bool
    {
        try {
            DB::transaction(function () use ($reason) {
                $this->products()->whereNull('is_approved')->each(function ($product) use ($reason) {
                    $product->reject($reason);
                });

                $this->updateTotals();
            });

            return true;
        } catch (\Exception $e) {
            Log::error('Error rejecting all products', [
                'return_id' => $this->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Obtener el próximo estado sugerido
     */
    public function getNextSuggestedStatus(): ?int
    {
        if (!$this->areAllProductsProcessed()) {
            return null; // No sugerir cambio hasta que todos estén procesados
        }

        $hasApproved = $this->hasApprovedProducts();
        $hasRejected = $this->hasRejectedProducts();

        if ($hasApproved && !$hasRejected) {
            // Todos aprobados → Verificación/Aprobado
            return config('returns.approved_status_id', 2);
        } elseif (!$hasApproved && $hasRejected) {
            // Todos rechazados → Rechazado
            return 6; // ID del estado rechazado
        } elseif ($hasApproved && $hasRejected) {
            // Mixto → Negociación
            return 5; // ID del estado negociación
        }

        return null;
    }

    /**
     * Generar número de devolución legible
     */
    public function getReturnNumber(): string
    {
        return 'DEV-' . str_pad($this->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Verificar si necesita aprobación manual
     */
    public function needsManualApproval(): bool
    {
        // Reglas de negocio para aprobación manual
        $totalAmount = $this->total_amount ?? 0;
        $highValueThreshold = config('returns.manual_approval_threshold', 500);

        return $totalAmount > $highValueThreshold ||
            $this->products()->where('return_condition', 'damaged')->exists() ||
            $this->logistics_mode === 'home_pickup';
    }

    /**
     * Obtener tiempo transcurrido desde la creación
     */
    public function getProcessingTime(): array
    {
        $created = $this->created_at;
        $now = now();

        return [
            'days' => $created->diffInDays($now),
            'hours' => $created->diffInHours($now),
            'human' => $created->diffForHumans(),
            'is_overdue' => $created->diffInDays($now) > config('returns.sla_days', 7)
        ];
    }

    /**
     * Scope para devoluciones que requieren atención
     */
    public function scopeRequiresAttention($query)
    {
        return $query->where(function($q) {
            $q->where('created_at', '<', now()->subDays(config('returns.sla_days', 7)))
                ->orWhereHas('products', function($pq) {
                    $pq->whereNull('is_approved');
                });
        });
    }

    /**
     * Scope para devoluciones de alto valor
     */
    public function scopeHighValue($query, $threshold = null)
    {
        $threshold = $threshold ?? config('returns.high_value_threshold', 1000);
        return $query->where('total_amount', '>', $threshold);
    }

    /**
     * Obtener métricas de la devolución
     */
    public function getMetrics(): array
    {
        $processingTime = $this->getProcessingTime();
        $financialSummary = $this->getFinancialSummary();

        return [
            'return_number' => $this->getReturnNumber(),
            'processing_time' => $processingTime,
            'financial_summary' => $financialSummary,
            'needs_manual_approval' => $this->needsManualApproval(),
            'is_high_value' => ($this->total_amount ?? 0) > config('returns.high_value_threshold', 1000),
            'completion_percentage' => $this->products->count() > 0 ?
                (($this->products->whereNotNull('is_approved')->count() / $this->products->count()) * 100) : 0,
            'next_suggested_status' => $this->getNextSuggestedStatus()
        ];
    }


    public static function getTotalReturnedQuantity($orderId, $productId)
    {
        return DB::table('return_request_products AS rrp')
            ->join('return_requests AS rr', 'rrp.request_id', '=', 'rr.id') // Cambiar return_id por request_id
            ->join('return_status AS rs', 'rr.status_id', '=', 'rs.id')
            ->where('rr.order_id', $orderId)
            ->where('rrp.product_id', $productId)
            ->where('rs.active', true)
            ->whereNotIn('rs.state_id', [4, 5]) // Excluir estados cerrados
            ->sum('rrp.quantity');
    }

// PROBLEMA 2: getReturnableByOrder() usa consulta SQL cruda incorrecta
    public static function getReturnableByOrder(int $orderId): Collection
    {
        return self::where('order_id', $orderId)
            ->where('is_returnable', true)
            ->whereRaw('quantity > (
            SELECT COALESCE(SUM(rrp.quantity), 0)
            FROM return_request_products rrp
            INNER JOIN return_requests rr ON rrp.request_id = rr.id
            INNER JOIN return_status rs ON rr.status_id = rs.id
            WHERE rrp.product_id = return_order_products.id
              AND rs.active = 1
              AND rs.state_id NOT IN (4, 5)
        )')
            ->get();
    }

}
