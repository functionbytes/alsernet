<?php

namespace App\Models\Return\Order;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Return\ReturnRequestProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ReturnOrderProduct extends Model
{
    protected $table = 'return_order_products';
    protected $primaryKey = 'id';

    protected $fillable = [
        'order_id',
        'erp_product_id',
        'product_code',
        'product_name',
        'product_description',
        'quantity',
        'unit_price',
        'total_price',
        'catalog_id',
        'is_returnable',
        'is_gift',
        'weight',
        'erp_line_data'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'weight' => 'decimal:3',
        'is_returnable' => 'boolean',
        'is_gift' => 'boolean',
        'erp_line_data' => 'json'
    ];

    // Relaciones
    public function order(): BelongsTo
    {
        return $this->belongsTo('App\Models\Return\Order\ReturnOrder', 'order_id', 'id');
    }

    public function returnRequest()
    {
        return $this->belongsTo('App\Models\Return\ReturnRequest', 'request_id', 'id');
    }

    public function returnItems(): HasMany
    {
        return $this->hasMany('App\Models\Return\Order\ReturnOrderProduct', 'product_id', 'id');
    }

    public function requestItems()
    {
        return $this->hasMany(ReturnRequestProduct::class, 'product_id', 'id');
    }


    // Scopes
    public function scopeReturnable($query)
    {
        return $query->where('is_returnable', true)
            ->where('total_price', '>', 0); // Excluir promociones gratuitas
    }

    public function scopeByProduct($query, $productCode)
    {
        return $query->where('product_code', $productCode);
    }

    // Métodos auxiliares
    public function getReturnedQuantityAttribute(): float
    {
        return $this->requestItems()
            ->whereHas('returnRequest', function($q) {
                $q->whereHas('status', function($sq) {
                    $sq->where('active', true)
                        ->where('state_id', '!=', 5);
                });
            })
            ->sum('quantity');
    }


    public function getAvailableForReturnAttribute(): float
    {
        return max(0, $this->quantity - $this->returned_quantity);
    }

    public function canBeReturned(): bool
    {
        return $this->is_returnable &&
            $this->total_price > 0 &&
            $this->available_for_return > 0;
    }

    public function getReturnDeadline(): ?\Carbon\Carbon
    {
        if (!$this->order) {
            return null;
        }

        $returnDays = config('returns.return_days_limit', 30);
        return $this->order->order_date->addDays($returnDays);
    }

    public function isWithinReturnPeriod(): bool
    {
        $deadline = $this->getReturnDeadline();
        return $deadline && now()->lessThanOrEqualTo($deadline);
    }

    /**
     * Crear productos desde líneas ERP
     */
    public static function createFromErp(int $orderId, array $erpLines): void
    {

        foreach ($erpLines as $line) {

            $isReturnable = !empty($line['articulo']['codigo']) &&
                $line['total_con_impuestos'] > 0 &&
                !str_contains(strtoupper($line['articulo']['descripcion'] ?? ''), 'PORTES') &&
                !str_contains(strtoupper($line['articulo']['descripcion'] ?? ''), 'PROMOCION');

                self::create([
                    'order_id' => $orderId,
                    'erp_product_id' => $line['articulo']['idarticulo'] ?? null,
                    'product_code' => $line['articulo']['codigo'] ?? null,
                    'product_name' => $line['articulo']['descripcion'] ?? null,
                    'product_description' => $line['articulo']['descripcion'] ?? null,
                    'quantity' => floatval($line['unidades'] ?? 0),
                    'unit_price' => $line['unidades'] > 0 ?
                        floatval($line['total_con_impuestos']) / floatval($line['unidades']) : 0,
                    'total_price' => floatval($line['total_con_impuestos'] ?? 0),
                    'catalog_id' => is_array($line['idcatalogo']) ? null : $line['idcatalogo'],
                    'is_returnable' => $isReturnable,
                    'is_gift' => floatval($line['total_con_impuestos']) == 0,
                    'erp_line_data' => $line
                ]);
        }
    }

    /**
     * Obtener productos retornables para una orden
     */
    public static function getReturnableByOrder(int $orderId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('order_id', $orderId)
            ->where('is_returnable', true)
            ->whereRaw('quantity > (
            SELECT COALESCE(SUM(rp.quantity), 0)
            FROM return_request_products rp
            INNER JOIN return_requests rr ON rp.return_id = rr.id
            INNER JOIN return_status rs ON rr.status_id = rs.id
            WHERE rp.product_id = return_order_products.id
              AND rs.active = 1
              AND rs.state_id != 5
        )')
            ->get();
    }


    /**
     * Formatear información para mostrar
     */
    public function getDisplayInfo(): array
    {
        return [
            'id' => $this->id,
            'product_code' => $this->product_code,
            'product_name' => $this->product_name,
            'quantity' => $this->quantity,
            'unit_price' => number_format($this->unit_price, 2) . ' €',
            'total_price' => number_format($this->total_price, 2) . ' €',
            'available_for_return' => $this->available_for_return,
            'returned_quantity' => $this->returned_quantity,
            'can_be_returned' => $this->canBeReturned(),
            'is_within_return_period' => $this->isWithinReturnPeriod(),
            'return_deadline' => $this->getReturnDeadline()?->format('d/m/Y'),
            'is_gift' => $this->is_gift
        ];
    }


    // Obtener cantidad total devuelta de un producto en una orden
    public static function getTotalReturnedQuantity($orderId, $productId)
    {
        return DB::table('return_request_products AS rrp')
            ->join('return_requests AS rr', 'rrp.return_id', '=', 'rr.id') // <-- aquí está el cambio
            ->join('return_status AS rs', 'rr.status_id', '=', 'rs.id')
            ->where('rr.order_id', $orderId)
            ->where('rrp.product_id', $productId)
            ->where('rs.active', true)
            ->where('rs.state_id', '!=', 5)
            ->sum('rrp.quantity');
    }


    // Validar cantidad disponible para devolver
    public function validateQuantity()
    {
        $order = $this->returnRequest->order;
        $orderProduct = $order->orderProducts()
            ->where('product_id', $this->product_id)
            ->first();

        if (!$orderProduct) {
            return ['valid' => false, 'message' => 'Producto no encontrado en la orden'];
        }

        $alreadyReturned = self::getTotalReturnedQuantity($order->id, $this->product_id);
        $availableToReturn = $orderProduct->quantity - $alreadyReturned;

        if ($this->quantity > $availableToReturn) {
            return [
                'valid' => false,
                'message' => "Cantidad excede lo disponible. Disponible: {$availableToReturn}"
            ];
        }

        return ['valid' => true, 'available' => $availableToReturn];
    }

}
