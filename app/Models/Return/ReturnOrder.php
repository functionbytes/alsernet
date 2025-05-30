<?php

namespace App\Models\Return;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReturnOrder extends Model
{
    protected $table = 'return_orders';
    protected $primaryKey = 'id';

    protected $fillable = [
        'erp_order_id',
        'order_number',
        'customer_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'customer_cif',
        'order_date',
        'total_amount',
        'status',
        'erp_status_id',
        'erp_status_description',
        'payment_method_id',
        'payment_amount',
        'warehouse_id',
        'warehouse_description',
        'shipping_address',
        'shipping_province',
        'shipping_city',
        'shipping_postal_code',
        'shipping_country',
        'shipping_phone',
        'shipping_cost',
        'series_description',
        'created_by_erp',
        'erp_data'
    ];

    protected $casts = [
        'order_date' => 'date',
        'total_amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'erp_data' => 'json',
        'created_by_erp' => 'boolean'
    ];

    // Relaciones
    public function products(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnOrderProduct', 'order_id', 'id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Customer', 'customer_id', 'id');
    }

    public function returns(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnRequest', 'order_id', 'id');
    }



    // Scopes
    public function scopeByErpId($query, $erpId)
    {
        return $query->where('erp_order_id', $erpId);
    }

    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByEmail($query, $email)
    {
        return $query->where('customer_email', $email);
    }

    public function scopeReturnable($query)
    {
        return $query->whereIn('erp_status_id', ['4', '5', '6']); // Estados que permiten devolución
    }

    // Métodos auxiliares
    public function getTotalProductsAttribute(): int
    {
        return $this->products()->sum('quantity');
    }

    public function getReturnableProductsAttribute()
    {
        return $this->products()->where('is_returnable', true);
    }

    public function hasActiveReturns(): bool
    {
        return $this->returns()
            ->whereHas('status', function($q) {
                $q->where('active', true)
                    ->whereNotIn('id_return_state', [5]); // No cerradas
            })
            ->exists();
    }

    public function canCreateReturn(): bool
    {
        // Verificar si la orden permite devoluciones
        return in_array($this->erp_status_id, ['4', '5', '6']) &&
            !$this->hasActiveReturns() &&
            $this->order_date->diffInDays(now()) <= config('returns.return_days_limit', 30);
    }

    public function canCreateReturns(): bool
    {
        // Verificar si la orden permite devoluciones
        return in_array($this->erp_status_id, ['7']) &&
            $this->order_date->diffInDays(now()) <= config('returns.return_days_limit', 30);
    }

    public function getFormattedShippingAddress(): string
    {
        $parts = array_filter([
            $this->shipping_address,
            $this->shipping_city,
            $this->shipping_province,
            $this->shipping_postal_code,
            $this->shipping_country
        ]);

        return implode(', ', $parts);
    }

    /**
     * Crear orden desde datos ERP
     */
    public static function createFromErpData(array $erpData): self
    {
        $orderData = $erpData['resource'];

        return self::create([
            'erp_order_id' => $orderData['idpedidocli'],
            'order_number' => $orderData['npedidocli'],
            'customer_id' => $orderData['cliente']['idcliente'] ?? null,
            'customer_name' => trim(($orderData['cliente']['nombre'] ?? '') . ' ' . ($orderData['cliente']['apellidos'] ?? '')),
            'customer_email' => $orderData['cliente']['email'] ?? null,
            'customer_cif' => $orderData['cliente']['cif'] ?? null,
            'order_date' => $orderData['fpedido'],
            'total_amount' => $orderData['total_con_impuestos'],
            'status' => 'active',
            'erp_status_id' => $orderData['estado']['idestado'],
            'erp_status_description' => $orderData['estado']['descripcion'],
            'payment_method_id' => $orderData['forma_pago_pedido_cliente']['resource']['idformapago'] ?? null,
            'payment_amount' => $orderData['forma_pago_pedido_cliente']['resource']['importe'] ?? null,
            'warehouse_id' => $orderData['almacen']['idalmacen'] ?? null,
            'warehouse_description' => $orderData['almacen']['descripcion'] ?? null,
            'shipping_address' => $orderData['envio']['calle'] ?? null,
            'shipping_province' => $orderData['envio']['provincia'] ?? null,
            'shipping_city' => $orderData['envio']['localidad'] ?? null,
            'shipping_postal_code' => $orderData['envio']['cp'] ?? null,
            'shipping_country' => $orderData['envio']['pais'] ?? null,
            'shipping_phone' => $orderData['envio']['telefono'] ?? null,
            'shipping_cost' => $orderData['envio']['coste'] ?? 0,
            'series_description' => $orderData['serie']['descripcorta'] ?? null,
            'created_by_erp' => true,
            'erp_data' => $erpData
        ]);
    }

    /**
     * Actualizar desde ERP
     */
    public function updateFromErpData(array $erpData): bool
    {
        $orderData = $erpData['resource'];

        return $this->update([
            'customer_name' => trim(($orderData['cliente']['nombre'] ?? '') . ' ' . ($orderData['cliente']['apellidos'] ?? '')),
            'customer_email' => $orderData['cliente']['email'] ?? null,
            'total_amount' => $orderData['total_con_impuestos'],
            'erp_status_id' => $orderData['estado']['idestado'],
            'erp_status_description' => $orderData['estado']['descripcion'],
            'erp_data' => $erpData,
            'updated_at' => now()
        ]);
    }
}
