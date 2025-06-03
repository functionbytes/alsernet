<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Warranty extends Model
{
    use HasFactory;

    protected $fillable = [
        'warranty_number',
        'order_id',
        'product_id',
        'warranty_type_id',
        'manufacturer_id',
        'user_id',
        'product_serial_number',
        'product_model',
        'product_price',
        'quantity',
        'purchase_date',
        'warranty_start_date',
        'warranty_end_date',
        'warranty_duration_months',
        'status',
        'is_registered_with_manufacturer',
        'manufacturer_warranty_id',
        'manufacturer_registration_date',
        'activation_date',
        'activated_by',
        'activation_details',
        'original_owner_id',
        'transferred_at',
        'transfer_history',
        'warranty_cost',
        'is_paid',
        'payment_date',
        'documents',
        'proof_of_purchase',
        'terms_and_conditions',
        'terms_accepted',
        'terms_accepted_at',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_start_date' => 'date',
        'warranty_end_date' => 'date',
        'activation_date' => 'date',
        'manufacturer_registration_date' => 'datetime',
        'transferred_at' => 'datetime',
        'payment_date' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'product_price' => 'decimal:2',
        'warranty_cost' => 'decimal:2',
        'is_registered_with_manufacturer' => 'boolean',
        'is_paid' => 'boolean',
        'terms_accepted' => 'boolean',
        'activation_details' => 'array',
        'transfer_history' => 'array',
        'documents' => 'array',
        'proof_of_purchase' => 'array',
        'terms_and_conditions' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Estados disponibles
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CLAIMED = 'claimed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_TRANSFERRED = 'transferred';

    /**
     * Relación con orden
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relación con producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con tipo de garantía
     */
    public function warrantyType()
    {
        return $this->belongsTo(WarrantyType::class);
    }

    /**
     * Relación con fabricante
     */
    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /**
     * Relación con usuario propietario
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Usuario que activó la garantía
     */
    public function activatedBy()
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    /**
     * Propietario original (antes de transferencia)
     */
    public function originalOwner()
    {
        return $this->belongsTo(User::class, 'original_owner_id');
    }

    /**
     * Reclamos de esta garantía
     */
    public function claims()
    {
        return $this->hasMany(WarrantyClaim::class);
    }

    /**
     * Extensiones de esta garantía
     */
    public function extensions()
    {
        return $this->hasMany(WarrantyExtension::class, 'original_warranty_id');
    }

    /**
     * Scope para garantías activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE)
            ->where('warranty_end_date', '>=', now()->toDateString());
    }

    /**
     * Scope para garantías expiradas
     */
    public function scopeExpired($query)
    {
        return $query->where('warranty_end_date', '<', now()->toDateString())
            ->where('status', '!=', self::STATUS_CANCELLED);
    }

    /**
     * Scope para garantías del usuario
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope para garantías próximas a vencer
     */
    public function scopeExpiringSoon($query, $days = 30)
    {
        $futureDate = now()->addDays($days)->toDateString();
        return $query->where('status', self::STATUS_ACTIVE)
            ->whereBetween('warranty_end_date', [now()->toDateString(), $futureDate]);
    }

    /**
     * Verificar si la garantía está activa
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE &&
            $this->warranty_end_date >= now()->toDateString();
    }

    /**
     * Verificar si la garantía ha expirado
     */
    public function isExpired(): bool
    {
        return $this->warranty_end_date < now()->toDateString() ||
            $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Obtener días restantes de garantía
     */
    public function getRemainingDays(): int
    {
        if ($this->isExpired()) {
            return 0;
        }

        return max(0, now()->diffInDays($this->warranty_end_date, false));
    }

    /**
     * Obtener porcentaje de garantía utilizada
     */
    public function getUsagePercentage(): float
    {
        $totalDays = $this->warranty_start_date->diffInDays($this->warranty_end_date);
        $usedDays = $this->warranty_start_date->diffInDays(now());

        if ($totalDays === 0) {
            return 100;
        }

        return min(100, max(0, ($usedDays / $totalDays) * 100));
    }

    /**
     * Activar garantía
     */
    public function activate(User $user = null, array $details = []): bool
    {
        if ($this->activation_date) {
            return false; // Ya está activada
        }

        $this->update([
            'activation_date' => now()->toDateString(),
            'activated_by' => $user?->id,
            'activation_details' => $details,
            'status' => self::STATUS_ACTIVE,
        ]);

        // Registrar con fabricante si es posible
        if ($this->manufacturer && $this->manufacturer->auto_warranty_registration) {
            $this->registerWithManufacturer();
        }

        return true;
    }

    /**
     * Registrar garantía con fabricante
     */
    public function registerWithManufacturer(): array
    {
        if (!$this->manufacturer) {
            return [
                'success' => false,
                'message' => 'No hay fabricante asociado',
            ];
        }

        if ($this->is_registered_with_manufacturer) {
            return [
                'success' => false,
                'message' => 'Ya está registrada con el fabricante',
            ];
        }

        $result = $this->manufacturer->registerWarranty($this);

        if ($result['success']) {
            $this->update([
                'is_registered_with_manufacturer' => true,
                'manufacturer_warranty_id' => $result['manufacturer_warranty_id'] ?? null,
                'manufacturer_registration_date' => now(),
            ]);
        }

        return $result;
    }

    /**
     * Transferir garantía a otro usuario
     */
    public function transferTo(User $newOwner, array $transferDetails = []): bool
    {
        if (!$this->warrantyType->transferable) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        $transferHistory = $this->transfer_history ?? [];
        $transferHistory[] = [
            'from_user_id' => $this->user_id,
            'to_user_id' => $newOwner->id,
            'transferred_at' => now(),
            'details' => $transferDetails,
        ];

        $this->update([
            'original_owner_id' => $this->original_owner_id ?? $this->user_id,
            'user_id' => $newOwner->id,
            'transferred_at' => now(),
            'transfer_history' => $transferHistory,
            'status' => self::STATUS_TRANSFERRED,
        ]);

        return true;
    }

    /**
     * Extender garantía
     */
    public function extend(int $additionalMonths, WarrantyType $extensionType, float $cost = 0): WarrantyExtension
    {
        $extension = WarrantyExtension::create([
            'original_warranty_id' => $this->id,
            'user_id' => $this->user_id,
            'warranty_type_id' => $extensionType->id,
            'extension_number' => 'EXT-' . strtoupper(uniqid()),
            'additional_months' => $additionalMonths,
            'extension_start_date' => $this->warranty_end_date,
            'extension_end_date' => $this->warranty_end_date->copy()->addMonths($additionalMonths),
            'extension_cost' => $cost,
            'status' => 'active',
            'is_active' => true,
            'activated_at' => now(),
        ]);

        // Actualizar fecha de fin de garantía original
        $this->update([
            'warranty_end_date' => $extension->extension_end_date,
            'warranty_duration_months' => $this->warranty_duration_months + $additionalMonths,
        ]);

        return $extension;
    }

    /**
     * Cancelar garantía
     */
    public function cancel(string $reason = ''): bool
    {
        if ($this->status === self::STATUS_CANCELLED) {
            return false;
        }

        $this->update([
            'status' => self::STATUS_CANCELLED,
            'notes' => ($this->notes ? $this->notes . "\n" : '') . "Cancelada: {$reason}",
        ]);

        return true;
    }

    /**
     * Generar número de garantía único
     */
    public static function generateWarrantyNumber(): string
    {
        do {
            $number = 'WAR-' . now()->format('Y') . '-' . strtoupper(uniqid());
        } while (self::where('warranty_number', $number)->exists());

        return $number;
    }

    /**
     * Crear garantía desde orden
     */
    public static function createFromOrder(Order $order, Product $product, array $data = []): self
    {
        $warrantyType = WarrantyType::find($data['warranty_type_id'] ?? null)
            ?? WarrantyType::where('code', 'MANUFACTURER')->first();

        $warrantyData = array_merge([
            'warranty_number' => self::generateWarrantyNumber(),
            'order_id' => $order->id,
            'product_id' => $product->id,
            'warranty_type_id' => $warrantyType->id,
            'manufacturer_id' => $product->manufacturer_id,
            'user_id' => $order->user_id,
            'product_price' => $product->price,
            'quantity' => 1,
            'purchase_date' => $order->created_at->toDateString(),
            'warranty_start_date' => $order->created_at->toDateString(),
            'warranty_duration_months' => $product->default_warranty_months,
            'warranty_cost' => 0,
            'is_paid' => true,
            'payment_date' => $order->created_at,
            'status' => self::STATUS_ACTIVE,
        ], $data);

        // Calcular fecha de fin
        $startDate = Carbon::parse($warrantyData['warranty_start_date']);
        $warrantyData['warranty_end_date'] = $startDate
            ->copy()
            ->addMonths($warrantyData['warranty_duration_months'])
            ->toDateString();

        return self::create($warrantyData);
    }
}
