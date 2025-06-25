<?php

namespace App\Models\Return\Order;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'erp_data'
    ];

    protected $casts = [
        'order_date' => 'date',
        'total_amount' => 'decimal:2',
        'payment_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'erp_data' => 'json',
    ];

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
        return $query->whereIn('erp_status_id', ['4', '5', '6']);
    }
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
        return $this->returns()->whereHas('status', function($q) {
                $q->where('active', true)
                    ->whereNotIn('id_return_state', [5]); // No cerradas
            })->exists();
    }
    public function canCreateReturn(): bool
    {
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
    public static function createFromErp(array $data): self
    {

        return self::create([
            'erp_order_id'           => $data['idpedidocli'],
            'order_number'           => $data['npedidocli'],
            'customer_id'            => $data['cliente']['idcliente'] ?? null,
            'customer_name'          => trim($data['cliente']['nombre'] . ' ' . $data['cliente']['apellidos']),
            'customer_email'         => $data['cliente']['email'] ?? null,
            'customer_cif'           => $data['cliente']['cif'] ?? null,
            'order_date'             => $data['fpedido'],
            'total_amount'           => $data['total_con_impuestos'],
            'status'                 => 'active',
            'erp_status_id'          => $data['estado']['idestado'] ?? null,
            'erp_status_description' => $data['estado']['descripcion'] ?? null,
            'payment_method_id'      => $data['forma_pago_pedido_cliente']['resource']['idformapago'] ?? null,
            'payment_amount'         => $data['forma_pago_pedido_cliente']['resource']['importe'] ?? null,
            'warehouse_id'           => $data['almacen']['idalmacen'] ?? null,
            'warehouse_description'  => $data['almacen']['descripcion'] ?? null,
            'shipping_address'       => $data['envio']['calle'] ?? null,
            'shipping_province'      => is_array($data['envio']['provincia']) ? implode(',', $data['envio']['provincia'])  : $data['envio']['provincia'] ?? null,
            'shipping_city'          => $data['envio']['localidad'] ?? null,
            'shipping_postal_code'   => $data['envio']['cp'] ?? null,
            'shipping_country'       => $data['envio']['pais'] ?? null,
            'shipping_phone'         => $data['envio']['telefono'] ?? null,
            'shipping_cost'          => $data['envio']['coste'] ?? 0,
            'series_description'     => $data['serie']['descripcorta'] ?? null,
            'erp_data'               => json_encode($data, JSON_UNESCAPED_UNICODE),
        ]);
    }
    public function updateFromErp(array $data): bool
    {
        return $this->update([
            'customer_name' => trim(($data['cliente']['nombre'] ?? '') . ' ' . ($data['cliente']['apellidos'] ?? '')),
            'customer_email' => $data['cliente']['email'] ?? null,
            'total_amount' => $data['total_con_impuestos'],
            'erp_status_id' => $data['estado']['idestado'],
            'erp_status_description' => $data['estado']['descripcion'],
            'erp_data' => $data,
            'updated_at' => now()
        ]);
    }   §j5hr4eg3tfqr12 =-9
    public function products(): HasMany
    {
        return $this->hasMany('App\Models\Return\Order\ReturnOrderProduct', 'order_id', 'id');
    }
    public function customer(): BelongsTo
    {
        return $this->belongsTo('App\Models\Customer', 'customer_id', 'id');
    }
    public function returns(): HasMany
    {
        return $this->hasMany('App\Models\Return\ReturnRequest', 'order_id', 'id');
    }


}
